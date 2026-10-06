<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    /**
     * Record an activity log entry safely into the database.
     *
     * @param string $action (e.g. LOGIN, LOGOUT, ARCHIVE_CREATE, ARCHIVE_VERIFY, etc.)
     * @param string $description Human-readable detailed explanation
     * @param string $module (AUTH, DOKUMEN_ARSIP, PEMINJAMAN, PEMUSNAHAN_RETENSI, LAYOUT_GUDANG, MASTER_DATA, USER_MANAGEMENT)
     * @param array $properties Additional context/payload or diff metadata
     * @param string|null $referenceId Key reference e.g. Box Number, Archive ID, BAP Number
     * @param \App\Models\User|null $user Override specific user
     * @return \App\Models\ActivityLog|null
     */
    public static function log(
        string $action,
        string $description,
        string $module = 'GENERAL',
        array $properties = [],
        ?string $referenceId = null,
        $user = null
    ): ?ActivityLog {
        try {
            $currentUser = $user ?: Auth::user();

            $ipAddress = null;
            $userAgent = null;

            if (request()) {
                $ipAddress = request()->ip();
                $userAgent = request()->userAgent();
            }

            return ActivityLog::create([
                'user_id' => $currentUser ? $currentUser->id : null,
                'user_name' => $currentUser ? $currentUser->name : ($properties['attempted_email'] ?? 'System / Anonymous'),
                'user_role' => $currentUser ? $currentUser->role : 'guest',
                'department_id' => $currentUser ? $currentUser->department_id : null,
                'action' => strtoupper($action),
                'module' => strtoupper($module),
                'description' => $description,
                'reference_id' => $referenceId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'properties' => !empty($properties) ? $properties : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to write activity log: ' . $e->getMessage(), [
                'action' => $action,
                'description' => $description,
                'module' => $module,
            ]);
            return null;
        }
    }

    /**
     * Record multiple activity log entries safely in a single batch insert.
     * Prevents multiple sequential database writes and lock contention in SQLite.
     *
     * @param array $entries Array of items, each containing:
     *                       ['action' => string, 'description' => string, 'module' => string,
     *                        'properties' => array, 'reference_id' => string|null, 'user' => User|null]
     * @return int Number of successfully recorded entries
     */
    public static function logBatch(array $entries): int
    {
        if (empty($entries)) {
            return 0;
        }

        try {
            $currentUser = Auth::user();
            $ipAddress = request() ? request()->ip() : null;
            $userAgent = request() ? request()->userAgent() : null;
            $now = now();

            $rows = [];
            foreach ($entries as $e) {
                $user = $e['user'] ?? $currentUser;
                $props = $e['properties'] ?? [];
                $encodedProps = !empty($props) ? (is_string($props) ? $props : json_encode($props)) : null;

                $rows[] = [
                    'user_id' => $user ? $user->id : null,
                    'user_name' => $user ? $user->name : ($props['attempted_email'] ?? 'System / Anonymous'),
                    'user_role' => $user ? $user->role : 'guest',
                    'department_id' => $user ? $user->department_id : null,
                    'action' => strtoupper($e['action'] ?? 'GENERAL'),
                    'module' => strtoupper($e['module'] ?? 'GENERAL'),
                    'description' => $e['description'] ?? '',
                    'reference_id' => $e['reference_id'] ?? null,
                    'ip_address' => $e['ip_address'] ?? $ipAddress,
                    'user_agent' => $e['user_agent'] ?? $userAgent,
                    'properties' => $encodedProps,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                ActivityLog::insert($chunk);
            }

            return count($rows);
        } catch (\Throwable $e) {
            Log::error('Failed to write batch activity log: ' . $e->getMessage());
            return 0;
        }
    }
}

