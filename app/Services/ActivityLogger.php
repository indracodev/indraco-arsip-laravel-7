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
}
