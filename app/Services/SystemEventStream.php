<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\BorrowingLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * `system_events` ledger read by the realtime notifications (same rows as Laravel 7, where the
 * Archive model events wrote them). Called explicitly by the Actions, inside their transaction.
 * A failed write never breaks the business transaction.
 */
class SystemEventStream
{
    /** Laravel 7 keeps about the latest 2000 events (pruned on every 200th id). */
    private const KEEP = 2000;

    /**
     * One event per archive, one INSERT. Payload = Laravel 7 Archive model event payload
     * (needs `department` loaded, or the department passed via $departments).
     *
     * @param  iterable<Archive>  $archives
     * @param  array<string, mixed>  $extra  merged into every payload (e.g. `changes`)
     */
    public static function archives(string $eventType, iterable $archives, array $extra = []): void
    {
        $rows = [];
        foreach ($archives as $archive) {
            $payload = $eventType === 'archive_deleted' ? null : [
                'box_number' => $archive->box_number,
                'status' => $archive->status,
                'title' => $archive->title,
                'created_by_user_id' => $archive->created_by_user_id,
                'dept_code' => $archive->department->code ?? 'GEN',
                'dept_name' => $archive->department->name ?? 'Departemen',
            ] + $extra;
            $rows[] = [
                'event_type' => $eventType,
                'entity_type' => 'Archive',
                'entity_id' => $archive->id,
                'department_id' => $archive->department_id,
                'payload' => $payload === null ? null : json_encode($payload),
                'created_at' => now(),
            ];
        }
        self::insert($eventType, $rows);
    }

    /**
     * Penarikan events `borrowing_created` / `borrowing_updated` (Laravel 7 BorrowingLog model
     * events, same payload). The single call point for realtime notifications of this module.
     * Needs `archive.department` and `borrower` loaded.
     *
     * @param  iterable<BorrowingLog>  $logs
     */
    public static function borrowings(string $eventType, iterable $logs, int $actionByUserId): void
    {
        $rows = [];
        foreach ($logs as $log) {
            $archive = $log->archive;
            $rows[] = [
                'event_type' => $eventType,
                'entity_type' => 'BorrowingLog',
                'entity_id' => $log->id,
                'department_id' => $archive?->department_id,
                'payload' => json_encode([
                    'status' => $log->status,
                    'box_number' => $archive->box_number ?? '',
                    'title' => $archive->title ?? '',
                    'dept_code' => $archive->department->code ?? '',
                    'borrower' => $log->borrower->name ?? '',
                    'borrower_user_id' => (int) $log->borrower_user_id,
                    'action_by_user_id' => $actionByUserId,
                ]),
                'created_at' => now(),
            ];
        }
        self::insert($eventType, $rows);
    }

    /** @param list<array<string, mixed>> $rows */
    private static function insert(string $eventType, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        try {
            DB::table('system_events')->insert($rows);
            $latest = (int) DB::table('system_events')->max('id');
            if (intdiv($latest, 200) !== intdiv($latest - count($rows), 200) && $latest > self::KEEP) {
                DB::table('system_events')->where('id', '<', $latest - self::KEEP)->delete();
            }
        } catch (Throwable $e) {
            Log::error('Failed to write system event: '.$e->getMessage(), ['event' => $eventType]);
        }
    }
}
