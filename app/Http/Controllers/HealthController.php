<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    /**
     * Comprehensive Server Performance & Telemetry API
     * Returns RAM, OPcache, JIT, SQLite WAL size, and client traffic details.
     */
    public function metrics(Request $request): JsonResponse
    {
        $serverIp = $request->server('SERVER_ADDR', gethostbyname(gethostname()));
        $clientIp = $request->ip();

        // 1. PHP Memory Telemetry
        $memUsage = memory_get_usage(true);
        $memPeak = memory_get_peak_usage(true);
        $memLimit = ini_get('memory_limit');

        // 2. OPcache & JIT Telemetry
        $opcacheData = [
            'enabled' => false,
            'jit_enabled' => false,
            'used_memory_mb' => 0,
            'free_memory_mb' => 0,
            'hit_rate_pct' => 0,
            'cached_scripts' => 0,
        ];

        if (function_exists('opcache_get_status')) {
            $status = @opcache_get_status(false);
            if (is_array($status) && !empty($status['opcache_enabled'])) {
                $mem = $status['memory_usage'] ?? [];
                $stats = $status['opcache_statistics'] ?? [];
                $jit = $status['jit'] ?? [];

                $opcacheData = [
                    'enabled' => true,
                    'jit_enabled' => !empty($jit['enabled']) && !empty($jit['on']),
                    'used_memory_mb' => round(($mem['used_memory'] ?? 0) / (1024 * 1024), 2),
                    'free_memory_mb' => round(($mem['free_memory'] ?? 0) / (1024 * 1024), 2),
                    'hit_rate_pct' => round($stats['opcache_hit_rate'] ?? 0, 1),
                    'cached_scripts' => $stats['num_cached_scripts'] ?? 0,
                    'hits' => $stats['hits'] ?? 0,
                    'misses' => $stats['misses'] ?? 0,
                ];
            }
        }

        // 3. SQLite Database & WAL Size
        $dbPath = database_path('database.sqlite');
        $walPath = database_path('database.sqlite-wal');
        $shmPath = database_path('database.sqlite-shm');

        $dbSizeKb = file_exists($dbPath) ? round(filesize($dbPath) / 1024, 1) : 0;
        $walSizeKb = file_exists($walPath) ? round(filesize($walPath) / 1024, 1) : 0;
        $shmSizeKb = file_exists($shmPath) ? round(filesize($shmPath) / 1024, 1) : 0;

        // 4. Traffic & Connected Client Logs
        $recentClients = [];
        try {
            $recentClients = ActivityLog::select('ip_address', 'user_name', 'action', 'created_at')
                ->whereNotNull('ip_address')
                ->latest()
                ->take(8)
                ->get()
                ->map(function ($item) {
                    return [
                        'ip' => $item->ip_address,
                        'user' => $item->user_name,
                        'action' => $item->action,
                        'time' => $item->created_at ? $item->created_at->format('H:i:s d/m/Y') : '-',
                    ];
                });
        } catch (\Throwable $e) {
            // In case table not reachable
        }

        // 5. Automated AI & Engineer Diagnostic Verdict

        $bottlenecks = [];
        $recommendations = [];
        $healthScore = 100;

        $memMb = round($memUsage / (1024 * 1024), 2);
        if ($memMb > 180) {
            $bottlenecks[] = "Penggunaan RAM PHP mendekati batas ($memMb MB)";
            $healthScore -= 20;
        }

        if (!$opcacheData['enabled']) {
            $bottlenecks[] = "OPcache belum aktif di php.ini";
            $recommendations[] = "Aktifkan opcache.enable=1 di evironment/php-8.2.29-Win32-vs16-x64/php.ini";
            $healthScore -= 30;
        }

        if ($walSizeKb > 50000) {
            $bottlenecks[] = "File WAL SQLite membesar ($walSizeKb KB)";
            $recommendations[] = "Jalankan checkpoint WAL: PRAGMA wal_checkpoint(TRUNCATE)";
            $healthScore -= 15;
        }

        $overallStatus = $healthScore >= 90 ? 'OPTIMAL' : ($healthScore >= 70 ? 'WARNING' : 'CRITICAL');
        $summary = sprintf(
            'Status %s (%d/100) • RAM: %.1f MB • OPcache: %s • JIT: %s • SQLite WAL: %.1f KB',
            $overallStatus,
            $healthScore,
            $memMb,
            $opcacheData['enabled'] ? 'AKTIF (' . $opcacheData['hit_rate_pct'] . '%)' : 'NON-AKTIF',
            $opcacheData['jit_enabled'] ? 'ON' : 'OFF',
            $walSizeKb
        );

        $diagnosticKey = config('app.diagnostic_key') ?? substr(hash('sha256', config('app.key', 'indraco-secret')), 0, 16);

        return response()->json([
            'status' => 'ok',
            'timestamp' => microtime(true),
            'diagnosis' => [
                'health_score' => $healthScore,
                'overall_status' => $overallStatus,
                'summary_for_ai' => $summary,
                'bottlenecks' => $bottlenecks,
                'recommendations' => $recommendations,
            ],
            'server' => [
                'name' => gethostname(),
                'ip' => $serverIp,
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'architecture' => (PHP_INT_SIZE * 8) . '-bit',
            ],
            'client' => [
                'ip' => $clientIp,
            ],
            'memory' => [
                'current_mb' => $memMb,
                'peak_mb' => round($memPeak / (1024 * 1024), 2),
                'limit' => $memLimit,
            ],
            'opcache' => $opcacheData,
            'database' => [
                'driver' => config('database.default'),
                'db_size_kb' => $dbSizeKb,
                'wal_size_kb' => $walSizeKb,
                'shm_size_kb' => $shmSizeKb,
                'wal_mode_active' => true,
            ],
            'recent_clients' => $recentClients,
            'diagnostic_key' => $diagnosticKey,
        ])->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }


    /**
     * Probe / Ping a specific IP on the LAN from the server
     * Usage: GET /api/health/probe-ip?target=192.168.6.232
     */
    public function probeIp(Request $request): JsonResponse
    {
        $targetIp = $request->query('target', $request->ip());

        // Validate IP format
        if (!filter_var($targetIp, FILTER_VALIDATE_IP)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format IP Address tidak valid: ' . $targetIp,
            ], 422);
        }

        $start = microtime(true);
        $isReachable = false;
        $responseTimeMs = null;

        // Fast TCP socket connect probe (port 80, 8000, 445, 135, or icmp fallback)
        $portsToTry = [8000, 80, 445, 139];
        foreach ($portsToTry as $port) {
            $socket = @fsockopen($targetIp, $port, $errno, $errstr, 0.4);
            if ($socket) {
                fclose($socket);
                $isReachable = true;
                $responseTimeMs = round((microtime(true) - $start) * 1000, 2);
                break;
            }
        }

        // If socket ports closed, try Windows fast ping (1 packet, 600ms timeout)
        if (!$isReachable) {
            $cmd = sprintf('ping -n 1 -w 600 %s', escapeshellarg($targetIp));
            $output = [];
            $retVal = 0;
            @exec($cmd, $output, $retVal);

            if ($retVal === 0) {
                $isReachable = true;
                $responseTimeMs = round((microtime(true) - $start) * 1000, 2);
                // Try parse exact time from ping output
                foreach ($output as $line) {
                    if (preg_match('/time[<=]([0-9]+)ms/i', $line, $matches)) {
                        $responseTimeMs = (float) $matches[1];
                        break;
                    }
                }
            }
        }

        return response()->json([
            'status' => 'ok',
            'target_ip' => $targetIp,
            'is_reachable' => $isReachable,
            'rtt_ms' => $responseTimeMs,
            'server_ip' => $request->server('SERVER_ADDR', gethostbyname(gethostname())),
        ]);
    }

    /**
     * Tail and inspect storage/logs/laravel.log
     * Usage: GET /api/health/logs?lines=50
     */
    public function logs(Request $request): JsonResponse
    {
        $logPath = storage_path('logs/laravel.log');
        $linesToRead = min(max((int) $request->query('lines', 50), 10), 300);

        if (!file_exists($logPath)) {
            return response()->json([
                'status' => 'ok',
                'file_exists' => false,
                'file_size_kb' => 0,
                'lines' => [],
                'raw' => 'File laravel.log belum dibuat.',
            ]);
        }

        $fileSize = filesize($logPath);
        $readBytes = min($fileSize, 131072); // Read up to 128 KB from tail
        $fp = fopen($logPath, 'r');
        if ($fileSize > $readBytes) {
            fseek($fp, -$readBytes, SEEK_END);
        }
        $data = fread($fp, $readBytes);
        fclose($fp);

        $rawLines = explode("\n", trim($data));
        $tail = array_slice($rawLines, -$linesToRead);

        return response()->json([
            'status' => 'ok',
            'file_exists' => true,
            'file_size_kb' => round($fileSize / 1024, 1),
            'log_path' => 'storage/logs/laravel.log',
            'count' => count($tail),
            'lines' => array_values($tail),
        ]);
    }

    /**
     * Visual Live Diagnostics Dashboard View
     */
    public function diagnosticsView()
    {
        return view('diagnostics.index');
    }
}

