<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\UpdateUserPresence;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => EnsureUserHasRole::class]);
        // S2: AuthenticateSession ends this account's other sessions once its password changes;
        // the session that changed it re-stores the new hash after the request and stays in.
        // R50: no-store so Back after logout (shared PC) or after a deploy never shows a stored page.
        $middleware->web(append: [AuthenticateSession::class, UpdateUserPresence::class, SetCacheHeaders::using('no_store;private')]);
        // S3: only the server's own names in the Host header (config app.allowed_hosts), else 400.
        $middleware->trustHosts(at: fn (): array => array_map(
            fn (string $host): string => '^'.preg_quote($host).'$',
            (array) config('app.allowed_hosts'),
        ), subdomains: false);
        // Laravel 7 sent logged-in users hitting /login to a missing /home page.
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // S5: body above php-dms.ini post_max_size (Caddy refuses above its own, larger cap).
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            $message = 'Ukuran data yang dikirim terlalu besar (maksimal 20 MB per pengiriman). Kurangi jumlah atau ukuran file lalu coba lagi.';

            return $request->is('api/*') || $request->expectsJson()
                ? response()->json(['message' => $message], 413)
                : response()->view('errors.413', ['message' => $message], 413);
        });
        // U1: a page/form reached after the session ended (logout in another tab, password reset via
        // AuthenticateSession, expiry) says so on the login page. JSON/AJAX keeps the plain 401/419.
        $sessionEnded = 'Sesi Anda telah berakhir. Silakan login kembali.';
        $exceptions->render(function (AuthenticationException $e, Request $request) use ($sessionEnded) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }
            $redirect = redirect()->guest($e->redirectTo($request) ?? route('login'));
            // A first visit carries neither a session cookie nor a link from one of our own pages.
            $hadSession = $request->hasCookie((string) config('session.cookie'))
                || str_starts_with((string) $request->headers->get('referer'), $request->getSchemeAndHttpHost().'/');

            return $hadSession ? $redirect->with('warning', $sessionEnded) : $redirect;
        });
        $exceptions->render(function (HttpException $e, Request $request) use ($sessionEnded) {
            if (! $e->getPrevious() instanceof TokenMismatchException || $request->is('api/*') || $request->expectsJson()) {
                return null;
            }
            if ($request->user() !== null) { // token rotated by a login/logout in another tab
                return back()->withInput($request->except(['_token', 'password', 'password_confirmation', 'current_password']))
                    ->with('error', 'Halaman sudah kedaluwarsa. Silakan kirim ulang.');
            }

            return redirect()->route('login')->withInput($request->only('email'))->with('warning', $sessionEnded);
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
