<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Telemetry of "Live Monitor LAN & User" (Laravel 7 HealthController@metrics +
 * UserPresenceService::getConnectedUsers): PHP memory, OPcache/JIT, SQLite file sizes, latest
 * client IPs and who is online (presence in the file cache, no DB writes).
 */
final class LiveMonitor
{
    private const ONLINE_SEC = 180;

    private const IDLE_SEC = 600;

    public function __construct(private UserPresenceService $presence) {}

    /** @return array<string, mixed> */
    public function metrics(?int $currentUserId, ?string $clientIp, ?string $serverIp): array
    {
        $memMb = round(memory_get_usage(true) / 1048576, 2);
        $opcache = $this->opcache();
        $db = (string) config('database.connections.sqlite.database');
        $size = fn (string $file): float => is_file($file) ? round(filesize($file) / 1024, 1) : 0;
        $walKb = $size($db.'-wal');

        [$score, $bottlenecks, $advice] = [100, [], []];
        if ($memMb > 180) {
            [$score, $bottlenecks[]] = [$score - 20, "Penggunaan RAM PHP mendekati batas ({$memMb} MB)"];
        }
        if (! $opcache['enabled']) {
            [$score, $bottlenecks[], $advice[]] = [$score - 30, 'OPcache belum aktif di php.ini', 'Aktifkan opcache di evironment/php-dms.ini'];
        }
        if ($walKb > 50000) {
            [$score, $bottlenecks[], $advice[]] = [$score - 15, "File WAL SQLite membesar ({$walKb} KB)", 'Jalankan checkpoint WAL: PRAGMA wal_checkpoint(TRUNCATE)'];
        }
        $status = $score >= 90 ? 'OPTIMAL' : ($score >= 70 ? 'WARNING' : 'CRITICAL');

        return [
            'diagnosis' => ['health_score' => $score, 'overall_status' => $status, 'bottlenecks' => $bottlenecks, 'recommendations' => $advice,
                'summary' => sprintf('Status %s (%d/100) • RAM: %.1f MB • OPcache: %s • JIT: %s • SQLite WAL: %.1f KB', $status, $score, $memMb,
                    $opcache['enabled'] ? 'AKTIF ('.$opcache['hit_rate_pct'].'%)' : 'NON-AKTIF', $opcache['jit_enabled'] ? 'ON' : 'OFF', $walKb)],
            'server' => ['name' => gethostname(), 'ip' => $serverIp, 'php_version' => PHP_VERSION, 'os' => PHP_OS, 'architecture' => (PHP_INT_SIZE * 8).'-bit'],
            'client' => ['ip' => $clientIp],
            'memory' => ['current_mb' => $memMb, 'peak_mb' => round(memory_get_peak_usage(true) / 1048576, 2), 'limit' => ini_get('memory_limit')],
            'opcache' => $opcache,
            'database' => ['db_size_kb' => $size($db), 'wal_size_kb' => $walKb, 'shm_size_kb' => $size($db.'-shm')],
            'recent_clients' => Cache::remember('telemetry_recent_clients', 5, fn (): array => ActivityLog::query()->whereNotNull('ip_address')->latest('id')->limit(8)
                ->get(['ip_address', 'user_name', 'action', 'created_at'])
                ->map(fn (ActivityLog $log): array => ['ip' => $log->ip_address, 'user' => $log->user_name, 'action' => $log->action, 'time' => $log->created_at?->format('H:i:s d/m/Y') ?? '-'])->all()),
            'connected_users' => $this->connectedUsers($currentUserId),
        ];
    }

    /** @return array{enabled: bool, jit_enabled: bool, hit_rate_pct: float|int, cached_scripts: int, used_memory_mb: float|int} */
    private function opcache(): array
    {
        $status = function_exists('opcache_get_status') ? @opcache_get_status(false) : false;
        if (! is_array($status) || empty($status['opcache_enabled'])) {
            return ['enabled' => false, 'jit_enabled' => false, 'hit_rate_pct' => 0, 'cached_scripts' => 0, 'used_memory_mb' => 0];
        }

        return [
            'enabled' => true,
            'jit_enabled' => ! empty($status['jit']['enabled']) && ! empty($status['jit']['on']),
            'hit_rate_pct' => round($status['opcache_statistics']['opcache_hit_rate'] ?? 0, 1),
            'cached_scripts' => (int) ($status['opcache_statistics']['num_cached_scripts'] ?? 0),
            'used_memory_mb' => round(($status['memory_usage']['used_memory'] ?? 0) / 1048576, 2),
        ];
    }

    /**
     * Every user with online (<= 3 min) / idle (<= 10 min) / offline status; offline users show
     * their last activity log. Sorted online -> idle -> offline, then by name (Laravel 7).
     *
     * @return array{summary: array<string, int>, users: list<array<string, mixed>>}
     */
    private function connectedUsers(?int $currentUserId): array
    {
        $users = User::query()->with('department:id,code,name')->orderBy('name')->get(['id', 'name', 'email', 'role', 'department_id']);
        $lastLogs = ActivityLog::query()->whereIn('id', ActivityLog::query()->whereNotNull('user_id')->groupBy('user_id')->selectRaw('MAX(id)'))
            ->get(['user_id', 'ip_address', 'user_agent', 'action', 'created_at'])->keyBy('user_id');
        $now = now()->timestamp;
        $list = [];
        $count = ['online' => 0, 'idle' => 0, 'offline' => 0];
        $ips = [];
        foreach ($users as $u) {
            $p = Cache::get(UserPresenceService::CACHE_KEY_PREFIX.$u->id);
            $age = isset($p['last_activity_at']) ? max(0, $now - (int) $p['last_activity_at']) : null;
            $status = $age === null ? 'offline' : ($age <= self::ONLINE_SEC ? 'online' : ($age <= self::IDLE_SEC ? 'idle' : 'offline'));
            $count[$status]++;
            $log = $lastLogs->get($u->id);
            $row = $age !== null
                ? ['ip' => $p['ip'] ?? null, 'device' => $p['device'] ?? '-', 'os' => $p['os'] ?? '-', 'browser' => $p['browser'] ?? '-', 'last_action' => $p['last_page_name'] ?? '-',
                    'last_seen_relative' => $age < 30 ? 'Baru saja' : round($age / 60).' mnt lalu', 'last_seen_time' => $p['last_activity_formatted'] ?? null]
                : ($log ? ['ip' => $log->ip_address, 'last_action' => $log->action, 'last_seen_relative' => $log->created_at?->diffForHumans() ?? 'Offline', 'last_seen_time' => $log->created_at?->format('H:i:s d/m/Y')]
                    + array_intersect_key($this->presence->parseUserAgent((string) $log->user_agent), ['device' => 1, 'os' => 1, 'browser' => 1])
                    : ['ip' => null, 'device' => '-', 'os' => '-', 'browser' => '-', 'last_action' => 'Tidak ada aktivitas', 'last_seen_relative' => 'Offline', 'last_seen_time' => null]);
            if ($status !== 'offline' && ! empty($row['ip'])) {
                $ips[$row['ip']] = true;
            }
            $list[] = ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role, 'role_label' => $u->roleLabel(),
                'department_name' => $u->department->name ?? '-', 'department_code' => $u->department->code ?? '-', 'status' => $status,
                'is_current_user' => $u->id === $currentUserId] + $row;
        }
        $order = ['online' => 0, 'idle' => 1, 'offline' => 2];
        usort($list, fn (array $a, array $b): int => [$order[$a['status']], strcasecmp($a['name'], $b['name']), $a['id']] <=> [$order[$b['status']], 0, $b['id']]);

        return ['summary' => $count + ['total' => count($list), 'active_clients' => count($ips)], 'users' => $list];
    }
}
