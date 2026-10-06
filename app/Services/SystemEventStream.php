<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

class SystemEventStream
{
    /**
     * Emit a lightweight system event to the sequence ledger.
     *
     * @param string $eventType e.g. archive_created, archive_updated, borrowing_created
     * @param string|null $entityType e.g. Archive, BorrowingLog
     * @param int|null $entityId
     * @param int|null $departmentId
     * @param array $payload
     * @return int|null Inserted event sequence ID
     */
    public static function emit(string $eventType, ?string $entityType = null, ?int $entityId = null, ?int $departmentId = null, array $payload = []): ?int
    {
        try {
            $id = DB::table('system_events')->insertGetId([
                'event_type' => $eventType,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'department_id' => $departmentId,
                'payload' => !empty($payload) ? json_encode($payload) : null,
                'created_at' => now(),
            ]);

            // Lightweight auto-prune: Keep maximum 3000 events to keep SQLite lightning fast
            if ($id % 200 === 0) {
                self::pruneOldEvents();
            }

            return $id;
        } catch (Throwable $e) {
            // Never break main business transaction if event logging fails
            report($e);
            return null;
        }
    }

    /**
     * Get the latest sequence ID currently recorded.
     */
    public static function getLatestSeq(): int
    {
        try {
            return (int) DB::table('system_events')->max('id');
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Prune events older than the latest 2000 events.
     */
    public static function pruneOldEvents(int $keepCount = 2000): void
    {
        try {
            $latestId = (int) DB::table('system_events')->max('id');
            if ($latestId > $keepCount) {
                $cutoffId = $latestId - $keepCount;
                DB::table('system_events')->where('id', '<', $cutoffId)->delete();
            }
        } catch (Throwable $e) {
            // Ignore prune errors
        }
    }
}
