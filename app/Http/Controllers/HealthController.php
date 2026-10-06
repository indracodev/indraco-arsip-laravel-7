<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    /**
     * Ultra-lightweight LAN Ping Endpoint (<2ms response)
     * Does not trigger database queries or session locking.
     */
    public function ping(Request $request): JsonResponse
    {
        $serverTime = microtime(true);
        $serverIp = $request->server('SERVER_ADDR', gethostbyname(gethostname()));
        $serverName = gethostname();
        $clientIp = $request->ip();

        return response()->json([
            'status' => 'ok',
            'server_time' => $serverTime,
            'server_ip' => $serverIp,
            'server_name' => $serverName,
            'client_ip' => $clientIp,
            'app' => 'DMS PT INDRACO',
            'version' => '3.0.0-offline',
        ])->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
