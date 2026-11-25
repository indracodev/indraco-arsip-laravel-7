<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\UpdateUserPresence;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => EnsureUserHasRole::class]);
        // R50: no-store so Back after logout (shared PC) or after a deploy never shows a stored page.
        $middleware->web(append: [UpdateUserPresence::class, SetCacheHeaders::using('no_store;private')]);
        // Laravel 7 sent logged-in users hitting /login to a missing /home page.
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
