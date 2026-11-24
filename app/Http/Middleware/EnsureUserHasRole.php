<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `role:pic_gudang,...` route middleware, same rule as Laravel 7 CheckRole:
 * the listed roles pass, and **admin always passes** (explicit, by owner-approved behaviour).
 * Department ownership is not checked here; that belongs in Policies.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        if ($user->isAdmin() || in_array($user->role, $roles, true)) {
            return $next($request);
        }

        abort(403, 'Akses tidak diizinkan untuk peran pengguna Anda.');
    }
}
