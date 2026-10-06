<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UserPresenceService
{
    const CACHE_KEY_PREFIX = 'dms_presence_user_';
    const THROTTLE_KEY_PREFIX = 'dms_presence_throttle_';
    const ACTIVE_IDS_KEY = 'dms_presence_active_user_ids';

    // Status thresholds (in seconds)
    const ONLINE_THRESHOLD_SEC = 180;  // 3 minutes
    const IDLE_THRESHOLD_SEC = 600;    // 10 minutes

    /**
     * Touch & record user presence from incoming HTTP request.
     * Uses 15-second throttle to eliminate database & cache write pressure.
     */
    public function recordPresence(User $user, Request $request): void
    {
        $userId = $user->id;
        $throttleKey = self::THROTTLE_KEY_PREFIX . $userId;

        // Fast-path: If touched within last 15 seconds, exit immediately
        if (Cache::has($throttleKey)) {
            return;
        }

        $userAgent = (string) ($request->userAgent() ?? '');
        $clientInfo = $this->parseUserAgent($userAgent);
        $clientIp = $request->ip() ?? '127.0.0.1';
        $currentPath = '/' . ltrim($request->path(), '/');
        $pageName = $this->resolvePageName($request);

        $data = [
            'user_id' => $userId,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'role_label' => $user->role_label,
            'department_id' => $user->department_id,
            'department_name' => $user->department ? $user->department->name : '-',
            'ip' => $clientIp,
            'user_agent' => substr($userAgent, 0, 255),
            'device' => $clientInfo['device'],
            'os' => $clientInfo['os'],
            'browser' => $clientInfo['browser'],
            'browser_icon' => $clientInfo['browser_icon'],
            'device_icon' => $clientInfo['device_icon'],
            'last_path' => $currentPath,
            'last_page_name' => $pageName,
            'last_activity_at' => now()->timestamp,
            'last_activity_formatted' => now()->format('H:i:s'),
        ];

        // Store presence in cache (TTL 15 minutes)
        Cache::put(self::CACHE_KEY_PREFIX . $userId, $data, now()->addMinutes(15));

        // Throttle future touches for 15 seconds
        Cache::put($throttleKey, true, now()->addSeconds(15));

        // Maintain active user IDs set in cache
        $activeIds = Cache::get(self::ACTIVE_IDS_KEY, []);
        if (!in_array($userId, $activeIds, true)) {
            $activeIds[] = $userId;
            Cache::put(self::ACTIVE_IDS_KEY, $activeIds, now()->addMinutes(30));
        }
    }

    /**
     * Ultra-fast native User-Agent parser (<0.05ms)
     */
    public function parseUserAgent(?string $ua): array
    {
        if (empty($ua)) {
            return [
                'device' => 'Desktop PC',
                'os' => 'Windows / LAN Client',
                'browser' => 'Web Browser',
                'device_icon' => 'monitor',
                'browser_icon' => 'globe',
            ];
        }

        // 1. Device Type
        $device = 'Desktop PC';
        $deviceIcon = 'monitor';
        if (preg_match('/(ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
            $device = 'Tablet';
            $deviceIcon = 'tablet';
        } elseif (preg_match('/(iPhone|iPod)/i', $ua)) {
            $device = 'iPhone';
            $deviceIcon = 'smartphone';
        } elseif (preg_match('/(Mobile|Android|BlackBerry|IEMobile|Opera Mini)/i', $ua)) {
            $device = 'Smartphone Android';
            $deviceIcon = 'smartphone';
        } elseif (preg_match('/macintosh|mac os x/i', $ua)) {
            $device = 'Apple Mac';
            $deviceIcon = 'laptop';
        }

        // 2. Operating System
        $os = 'Windows';
        if (preg_match('/windows nt 10/i', $ua)) {
            $os = 'Windows 10 / 11';
        } elseif (preg_match('/windows nt 6\.3/i', $ua)) {
            $os = 'Windows 8.1';
        } elseif (preg_match('/windows nt 6\.2/i', $ua)) {
            $os = 'Windows 8';
        } elseif (preg_match('/windows nt 6\.1/i', $ua)) {
            $os = 'Windows 7';
        } elseif (preg_match('/windows nt 6\.0/i', $ua)) {
            $os = 'Windows Vista';
        } elseif (preg_match('/android\s*([0-9\.]+)?/i', $ua, $m)) {
            $os = 'Android ' . ($m[1] ?? '');
        } elseif (preg_match('/iphone os\s*([0-9_]+)/i', $ua, $m)) {
            $os = 'iOS ' . str_replace('_', '.', $m[1] ?? '');
        } elseif (preg_match('/mac os x\s*([0-9_]+)/i', $ua, $m)) {
            $os = 'macOS ' . str_replace('_', '.', $m[1] ?? '');
        } elseif (preg_match('/linux/i', $ua)) {
            $os = 'Linux';
        }

        // 3. Browser
        $browser = 'Web Browser';
        $browserIcon = 'globe';
        if (preg_match('/edg[ea]?\/([0-9]+)/i', $ua, $m)) {
            $browser = 'Microsoft Edge ' . $m[1];
            $browserIcon = 'compass';
        } elseif (preg_match('/opr\/([0-9]+)|opera\/([0-9]+)/i', $ua, $m)) {
            $browser = 'Opera ' . ($m[1] ?: ($m[2] ?? ''));
            $browserIcon = 'compass';
        } elseif (preg_match('/chrome\/([0-9]+)/i', $ua, $m)) {
            $browser = 'Chrome ' . $m[1];
            $browserIcon = 'chrome';
        } elseif (preg_match('/firefox\/([0-9]+)/i', $ua, $m)) {
            $browser = 'Firefox ' . $m[1];
            $browserIcon = 'flame';
        } elseif (preg_match('/version\/([0-9]+).*safari/i', $ua, $m)) {
            $browser = 'Safari ' . $m[1];
            $browserIcon = 'compass';
        }

        return [
            'device' => trim($device),
            'os' => trim($os),
            'browser' => trim($browser),
            'device_icon' => $deviceIcon,
            'browser_icon' => $browserIcon,
        ];
    }

    /**
     * Resolve human-readable page name for activity
     */
    public function resolvePageName(Request $request): string
    {
        $path = trim($request->path(), '/');
        if ($path === '' || $path === 'dashboard') return 'Dashboard Utama';
        if (str_starts_with($path, 'archives/create')) return 'Input Dokumen Baru';
        if (str_starts_with($path, 'archives')) return 'Katalog Arsip';
        if (str_starts_with($path, 'master/departments')) return 'Master Departemen';
        if (str_starts_with($path, 'master/warehouses/layout')) return 'Layout Gudang 2D';
        if (str_starts_with($path, 'master/warehouses')) return 'Master Gudang & Rak';
        if (str_starts_with($path, 'master/users')) return 'Kelola User & Akses';
        if (str_starts_with($path, 'master/numbering')) return 'Format Penomoran';
        if (str_starts_with($path, 'logs')) return 'Log History Aktivitas';
        if (str_starts_with($path, 'reports')) return 'Laporan Arsip';
        if (str_starts_with($path, 'diagnostics')) return 'Monitor Server & LAN';

        return 'Halaman /' . $path;
    }

    /**
     * Get real-time status of all users on LAN.
     */
    public function getConnectedUsers(?int $currentUserId = null): array
    {
        $users = User::select('id', 'name', 'email', 'role', 'department_id')
            ->with('department:id,name,code')
            ->orderBy('name')
            ->get();

        // Single query for fallback last known activity
        $fallbackLogs = [];
        try {
            $latestLogIds = ActivityLog::selectRaw('max(id) as id')
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->pluck('id');

            if ($latestLogIds->isNotEmpty()) {
                $fallbackLogs = ActivityLog::select('id', 'user_id', 'ip_address', 'user_agent', 'action', 'created_at')
                    ->whereIn('id', $latestLogIds)
                    ->get()
                    ->keyBy('user_id');
            }
        } catch (\Throwable $e) {
            // Suppress if any error
        }

        $now = now()->timestamp;
        $userList = [];
        $onlineCount = 0;
        $idleCount = 0;
        $offlineCount = 0;
        $activeIps = [];

        foreach ($users as $u) {
            $presence = Cache::get(self::CACHE_KEY_PREFIX . $u->id);
            $status = 'offline';
            $statusLabel = 'Offline';
            $statusColor = 'slate';
            $ip = null;
            $device = '-';
            $os = '-';
            $browser = '-';
            $deviceIcon = 'monitor';
            $browserIcon = 'globe';
            $lastAction = 'Tidak ada aktivitas';
            $lastSeenRelative = 'Offline';
            $lastSeenTime = null;
            $lastSeenTimestamp = 0;

            if ($presence && isset($presence['last_activity_at'])) {
                $lastSeenTimestamp = (int) $presence['last_activity_at'];
                $diffSec = max(0, $now - $lastSeenTimestamp);
                $ip = $presence['ip'] ?? null;
                $device = $presence['device'] ?? 'Desktop PC';
                $os = $presence['os'] ?? 'Windows';
                $browser = $presence['browser'] ?? 'Web Browser';
                $deviceIcon = $presence['device_icon'] ?? 'monitor';
                $browserIcon = $presence['browser_icon'] ?? 'globe';
                $lastAction = $presence['last_page_name'] ?? ($presence['last_path'] ?? '-');
                $lastSeenTime = $presence['last_activity_formatted'] ?? date('H:i:s', $lastSeenTimestamp);

                if ($diffSec <= self::ONLINE_THRESHOLD_SEC) {
                    $status = 'online';
                    $statusLabel = 'Online Aktif';
                    $statusColor = 'emerald';
                    $onlineCount++;
                    $lastSeenRelative = $diffSec < 30 ? 'Baru saja' : round($diffSec / 60) . ' mnt lalu';
                    if ($ip) $activeIps[$ip] = true;
                } elseif ($diffSec <= self::IDLE_THRESHOLD_SEC) {
                    $status = 'idle';
                    $statusLabel = 'Idle (Standby)';
                    $statusColor = 'amber';
                    $idleCount++;
                    $lastSeenRelative = round($diffSec / 60) . ' mnt lalu';
                    if ($ip) $activeIps[$ip] = true;
                } else {
                    $status = 'offline';
                    $statusLabel = 'Offline';
                    $statusColor = 'slate';
                    $offlineCount++;
                    $lastSeenRelative = round($diffSec / 60) . ' mnt lalu';
                }
            } else {
                $offlineCount++;
                // Check fallback log
                if (isset($fallbackLogs[$u->id])) {
                    $log = $fallbackLogs[$u->id];
                    $ip = $log->ip_address;
                    $parsed = $this->parseUserAgent($log->user_agent);
                    $device = $parsed['device'];
                    $os = $parsed['os'];
                    $browser = $parsed['browser'];
                    $deviceIcon = $parsed['device_icon'];
                    $browserIcon = $parsed['browser_icon'];
                    $lastAction = $log->action;
                    if ($log->created_at) {
                        $lastSeenRelative = $log->created_at->diffForHumans();
                        $lastSeenTime = $log->created_at->format('H:i:s d/m/Y');
                        $lastSeenTimestamp = $log->created_at->timestamp;
                    }
                }
            }

            $userList[] = [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'role_label' => $u->role_label,
                'department_name' => $u->department ? $u->department->name : '-',
                'department_code' => $u->department ? $u->department->code : '-',
                'status' => $status,
                'status_label' => $statusLabel,
                'status_color' => $statusColor,
                'ip' => $ip,
                'device' => $device,
                'os' => $os,
                'browser' => $browser,
                'device_icon' => $deviceIcon,
                'browser_icon' => $browserIcon,
                'last_action' => $lastAction,
                'last_seen_relative' => $lastSeenRelative,
                'last_seen_time' => $lastSeenTime,
                'last_seen_timestamp' => $lastSeenTimestamp,
                'is_current_user' => ($currentUserId !== null && $currentUserId === $u->id),
            ];
        }

        // Sort: online first, then idle, then offline; within same status, most recently active first
        $statusOrder = ['online' => 0, 'idle' => 1, 'offline' => 2];
        usort($userList, function ($a, $b) use ($statusOrder) {
            $orderA = $statusOrder[$a['status']] ?? 3;
            $orderB = $statusOrder[$b['status']] ?? 3;
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }
            return $b['last_seen_timestamp'] <=> $a['last_seen_timestamp'];
        });

        return [
            'summary' => [
                'total_registered' => count($users),
                'online_count' => $onlineCount,
                'idle_count' => $idleCount,
                'offline_count' => $offlineCount,
                'active_clients_count' => count($activeIps),
            ],
            'users' => $userList,
        ];
    }
}
