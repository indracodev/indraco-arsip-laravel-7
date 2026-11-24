<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthController
{
    /**
     * LAN heartbeat: no database, no session, no cookies (routes/api.php has no web middleware).
     */
    public function ping(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'server_time' => (int) (microtime(true) * 1000), // ms, same as the Caddy ping
            'server_ip' => $request->server('SERVER_ADDR'),
            'client_ip' => $request->ip(),
            'app' => 'DMS PT INDRACO',
        ])->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Readiness: proves a php-cgi worker boots Laravel. No DB, no session.
     * The launcher calls it once per worker (pid) before it reports "SIAP".
     */
    public function ready(): JsonResponse
    {
        return response()->json(['status' => 'ready', 'pid' => getmypid()])
            ->header('Cache-Control', 'no-store');
    }
}
