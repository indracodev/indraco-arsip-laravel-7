<?php

namespace App\Http\Controllers;

use App\Services\LiveMonitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

/**
 * "Live Monitor LAN & User" (Laravel 7 /diagnostics + HealthController metrics / probe-ip / logs /
 * wal-checkpoint). Admin session only (finding #10: no APP_KEY-derived `?token=` any more); only
 * this page polls (public/js/pages/diagnostics.js). /api/health/ping stays answered by Caddy.
 */
class DiagnosticsController extends Controller
{
    private const NO_CACHE = ['Cache-Control' => 'no-cache, no-store, must-revalidate', 'Pragma' => 'no-cache', 'Expires' => '0'];

    public function index(): View
    {
        return view('diagnostics.index');
    }

    public function metrics(Request $request, LiveMonitor $monitor): JsonResponse
    {
        return response()->json(['status' => 'ok', 'timestamp' => microtime(true)]
            + $monitor->metrics($request->user()?->id, $request->ip(), $request->server('SERVER_ADDR')), 200, self::NO_CACHE);
    }

    /**
     * Can the server reach another PC on the LAN? TCP connect to common ports, then one ping.
     * Admin only + 30 requests/minute (finding #18: otherwise a LAN scanner).
     */
    public function probeIp(Request $request): JsonResponse
    {
        $target = (string) $request->query('target', (string) $request->ip());
        if (! filter_var($target, FILTER_VALIDATE_IP)) {
            return response()->json(['status' => 'error', 'message' => 'Format IP Address tidak valid: '.$target], 422);
        }
        $start = microtime(true);
        $rtt = null;
        foreach ([8000, 80, 445, 139] as $port) {
            $socket = @fsockopen($target, $port, $errno, $errstr, 0.4);
            if ($socket) {
                fclose($socket);
                $rtt = round((microtime(true) - $start) * 1000, 2);
                break;
            }
        }
        if ($rtt === null) {
            $output = [];
            @exec('ping -n 1 -w 600 '.escapeshellarg($target), $output, $code);
            if ($code === 0) {
                preg_match('/[=<]\s*([0-9]+)\s*ms/i', implode("\n", $output), $m);
                $rtt = isset($m[1]) ? (float) $m[1] : round((microtime(true) - $start) * 1000, 2);
            }
        }

        return response()->json(['status' => 'ok', 'target_ip' => $target, 'is_reachable' => $rtt !== null, 'rtt_ms' => $rtt, 'server_ip' => $request->server('SERVER_ADDR')]);
    }

    /** Tail of storage/logs/laravel.log (last 10-300 lines, read from the last 128 KB). */
    public function logs(Request $request): JsonResponse
    {
        $path = storage_path('logs/laravel.log');
        if (! is_file($path)) {
            return response()->json(['status' => 'ok', 'file_exists' => false, 'file_size_kb' => 0, 'count' => 0, 'lines' => []], 200, self::NO_CACHE);
        }
        $lines = min(max((int) $request->query('lines', 50), 10), 300);
        $size = (int) filesize($path);
        $handle = fopen($path, 'r');
        fseek($handle, max(0, $size - 131072));
        $tail = array_slice(explode("\n", trim((string) fread($handle, 131072))), -$lines);
        fclose($handle);

        return response()->json(['status' => 'ok', 'file_exists' => true, 'file_size_kb' => round($size / 1024, 1), 'count' => count($tail), 'lines' => $tail], 200, self::NO_CACHE);
    }

    public function walCheckpoint(): JsonResponse
    {
        try {
            $result = DB::select('PRAGMA wal_checkpoint(TRUNCATE)');

            return response()->json(['status' => 'ok', 'message' => 'Checkpoint WAL berhasil dijalankan.', 'result' => $result[0] ?? null]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['status' => 'error', 'message' => 'Gagal menjalankan checkpoint WAL.'], 500);
        }
    }
}
