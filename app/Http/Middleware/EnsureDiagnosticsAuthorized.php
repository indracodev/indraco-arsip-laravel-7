<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureDiagnosticsAuthorized
{
    /**
     * Handle incoming request for diagnostics endpoints.
     * Only Super Admin (role: admin) or requests carrying a valid diagnostic key are permitted.
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Authenticated as Super Admin (role: admin)
        if (auth()->check() && (auth()->user()->isAdmin() || auth()->user()->role === 'admin')) {
            return $next($request);
        }

        // 2. Secret diagnostic key inspection (for automated telemetry / remote AI diagnostics)
        $expectedToken = config('app.diagnostic_key') ?? substr(hash('sha256', config('app.key', 'indraco-secret')), 0, 16);
        $providedToken = $request->bearerToken() ?? $request->query('token') ?? $request->header('X-Diagnostic-Key');

        if (!empty($providedToken) && hash_equals($expectedToken, (string) $providedToken)) {
            return $next($request);
        }

        // 3. Unauthorized access handling
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'Akses ditolak. Endpoint diagnostik server hanya dapat diakses oleh Super Admin.',
            ], 403);
        }

        if (auth()->check()) {
            abort(403, 'Akses ditolak: Hanya Super Admin yang diizinkan mengakses panel diagnostik performa server.');
        }

        return redirect()->route('login')->with('error', 'Silakan login sebagai Super Admin untuk mengakses panel diagnostik.');
    }
}
