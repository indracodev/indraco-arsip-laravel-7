<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * "Who is online" for the LAN monitor, kept in the cache (file driver), never in SQLite.
 * Same cache keys and payload as Laravel 7, so the diagnostics module can read them.
 */
class UserPresenceService
{
    public const CACHE_KEY_PREFIX = 'dms_presence_user_';

    public const ACTIVE_IDS_KEY = 'dms_presence_active_user_ids';

    public function recordPresence(User $user, Request $request): void
    {
        $userAgent = (string) $request->userAgent();
        $now = now();
        $user->loadMissing('department');

        Cache::put(self::CACHE_KEY_PREFIX.$user->id, [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'role_label' => $user->roleLabel(),
            'department_id' => $user->department_id,
            'department_name' => $user->department->name ?? '-',
            'ip' => $request->ip() ?? '127.0.0.1',
            'user_agent' => substr($userAgent, 0, 255),
            ...$this->parseUserAgent($userAgent),
            'last_path' => '/'.ltrim($request->path(), '/'),
            'last_page_name' => $this->resolvePageName($request),
            'last_activity_at' => $now->timestamp,
            'last_activity_formatted' => $now->format('H:i:s'),
        ], $now->copy()->addMinutes(15));

        /** @var list<int> $activeIds */
        $activeIds = Cache::get(self::ACTIVE_IDS_KEY, []);
        if (! in_array($user->id, $activeIds, true)) {
            $activeIds[] = $user->id;
            Cache::put(self::ACTIVE_IDS_KEY, $activeIds, $now->copy()->addMinutes(30));
        }
    }

    /**
     * @return array{device: string, os: string, browser: string, device_icon: string, browser_icon: string}
     */
    public function parseUserAgent(string $ua): array
    {
        if ($ua === '') {
            return ['device' => 'Desktop PC', 'os' => 'Windows / LAN Client', 'browser' => 'Web Browser', 'device_icon' => 'monitor', 'browser_icon' => 'globe'];
        }

        [$device, $deviceIcon] = match (true) {
            (bool) preg_match('/(ipad|playbook|silk)|(android(?!.*mobi))/i', $ua) => ['Tablet', 'tablet'],
            (bool) preg_match('/(iPhone|iPod)/i', $ua) => ['iPhone', 'smartphone'],
            (bool) preg_match('/(Mobile|Android|BlackBerry|IEMobile|Opera Mini)/i', $ua) => ['Smartphone Android', 'smartphone'],
            (bool) preg_match('/macintosh|mac os x/i', $ua) => ['Apple Mac', 'laptop'],
            default => ['Desktop PC', 'monitor'],
        };

        $os = match (true) {
            (bool) preg_match('/windows nt 10/i', $ua) => 'Windows 10 / 11',
            (bool) preg_match('/windows nt 6\.3/i', $ua) => 'Windows 8.1',
            (bool) preg_match('/windows nt 6\.2/i', $ua) => 'Windows 8',
            (bool) preg_match('/windows nt 6\.1/i', $ua) => 'Windows 7',
            (bool) preg_match('/windows nt 6\.0/i', $ua) => 'Windows Vista',
            (bool) preg_match('/android\s*([0-9.]+)?/i', $ua, $m) => 'Android '.($m[1] ?? ''),
            (bool) preg_match('/iphone os\s*([0-9_]+)/i', $ua, $m) => 'iOS '.str_replace('_', '.', $m[1]),
            (bool) preg_match('/mac os x\s*([0-9_]+)/i', $ua, $m) => 'macOS '.str_replace('_', '.', $m[1]),
            (bool) preg_match('/linux/i', $ua) => 'Linux',
            default => 'Windows',
        };

        [$browser, $browserIcon] = match (true) {
            (bool) preg_match('/edg[ea]?\/([0-9]+)/i', $ua, $m) => ['Microsoft Edge '.$m[1], 'compass'],
            (bool) preg_match('/opr\/([0-9]+)|opera\/([0-9]+)/i', $ua, $m) => ['Opera '.($m[1] ?: ($m[2] ?? '')), 'compass'],
            (bool) preg_match('/chrome\/([0-9]+)/i', $ua, $m) => ['Chrome '.$m[1], 'chrome'],
            (bool) preg_match('/firefox\/([0-9]+)/i', $ua, $m) => ['Firefox '.$m[1], 'flame'],
            (bool) preg_match('/version\/([0-9]+).*safari/i', $ua, $m) => ['Safari '.$m[1], 'compass'],
            default => ['Web Browser', 'globe'],
        };

        return ['device' => $device, 'os' => trim($os), 'browser' => $browser, 'device_icon' => $deviceIcon, 'browser_icon' => $browserIcon];
    }

    public function resolvePageName(Request $request): string
    {
        $path = trim($request->path(), '/');

        return match (true) {
            $path === '' || $path === 'dashboard' => 'Dashboard Utama',
            str_starts_with($path, 'archives/create') => 'Input Dokumen Baru',
            str_starts_with($path, 'archives') => 'Katalog Arsip',
            str_starts_with($path, 'master/departments') => 'Master Departemen',
            str_starts_with($path, 'master/warehouses/layout') => 'Layout Gudang 2D',
            str_starts_with($path, 'master/warehouses') => 'Master Gudang & Rak',
            str_starts_with($path, 'master/users') => 'Kelola User & Akses',
            str_starts_with($path, 'master/numbering') => 'Format Penomoran',
            str_starts_with($path, 'logs') => 'Log History Aktivitas',
            str_starts_with($path, 'reports') => 'Laporan Arsip',
            str_starts_with($path, 'diagnostics') => 'Monitor Server & LAN',
            default => 'Halaman /'.$path,
        };
    }
}
