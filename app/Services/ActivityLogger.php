<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Writes `activity_logs` (same columns and values as Laravel 7). A failed log write never
 * breaks the request: it is reported to the Laravel log instead.
 * Never pass passwords or tokens in $properties.
 */
class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public static function log(
        string $action,
        string $description,
        string $module = 'GENERAL',
        array $properties = [],
        ?string $referenceId = null,
        ?User $user = null,
    ): ?ActivityLog {
        try {
            return ActivityLog::create(self::row($action, $description, $module, $properties, $referenceId, $user));
        } catch (Throwable $e) {
            Log::error('Failed to write activity log: '.$e->getMessage(), ['action' => $action, 'module' => $module]);

            return null;
        }
    }

    /**
     * One INSERT per 200 rows instead of one per entry (SQLite write lock).
     *
     * @param  list<array{action: string, description: string, module?: string, properties?: array<string, mixed>, reference_id?: string|null, user?: User|null}>  $entries
     */
    public static function logBatch(array $entries): int
    {
        if ($entries === []) {
            return 0;
        }

        try {
            $now = now();
            $rows = [];
            foreach ($entries as $entry) {
                $row = self::row(
                    $entry['action'],
                    $entry['description'],
                    $entry['module'] ?? 'GENERAL',
                    $entry['properties'] ?? [],
                    $entry['reference_id'] ?? null,
                    $entry['user'] ?? null,
                );
                $row['properties'] = $row['properties'] === null ? null : json_encode($row['properties']);
                $rows[] = $row + ['created_at' => $now, 'updated_at' => $now];
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                ActivityLog::insert($chunk);
            }

            return count($rows);
        } catch (Throwable $e) {
            Log::error('Failed to write batch activity log: '.$e->getMessage());

            return 0;
        }
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private static function row(string $action, string $description, string $module, array $properties, ?string $referenceId, ?User $user): array
    {
        /** @var User|null $user */
        $user ??= Auth::user();
        $request = request();

        return [
            'user_id' => $user?->id,
            'user_name' => $user->name ?? ($properties['attempted_email'] ?? 'System / Anonymous'),
            'user_role' => $user->role ?? 'guest',
            'department_id' => $user?->department_id,
            'action' => strtoupper($action),
            'module' => strtoupper($module),
            'description' => $description,
            'reference_id' => $referenceId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'properties' => $properties === [] ? null : $properties,
        ];
    }
}
