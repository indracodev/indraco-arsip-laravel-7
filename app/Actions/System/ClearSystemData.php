<?php

namespace App\Actions\System;

use App\Models\ActivityLog;
use App\Models\Archive;
use App\Models\ArchiveItem;
use App\Models\BorrowingLog;
use App\Models\DestructionLog;
use App\Models\WarehouseEntryLog;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use App\Services\ActivityLogger;
use App\Services\DatabaseBackup;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * "Pemeliharaan & Reset Database" (Laravel 7 SettingController clearLogs / clearBoxAllocations /
 * clearArchives, same deletes, log actions and messages). ASK 7: a kept VACUUM INTO backup is
 * written first; if it fails nothing is deleted. Archive extension logs, slots and entry logs of
 * deleted archives go with them (foreign keys ON DELETE CASCADE / SET NULL).
 */
final class ClearSystemData
{
    public const TARGETS = ['logs', 'box_allocations', 'archives'];

    public function __construct(private DatabaseBackup $backup) {}

    /** @return string success message (the backup file name included) */
    public function handle(string $target): string
    {
        try {
            $file = basename($this->backup->keep('sebelum_hapus_'.$target));
        } catch (Throwable $e) {
            report($e);
            throw new DomainException('Backup otomatis gagal dibuat, data tidak dihapus. Coba lagi atau cek ruang disk server.');
        }

        [$action, $description, $message, $properties] = DB::transaction(fn (): array => match ($target) {
            'logs' => $this->logs(),
            'box_allocations' => $this->boxAllocations(),
            'archives' => $this->archives(),
            default => throw new InvalidArgumentException("Target hapus massal tidak dikenal: {$target}"),
        });
        ActivityLogger::log($action, $description, 'SETTINGS', $properties + ['backup_file' => $file]);

        return "{$message} Backup otomatis: storage/app/backups/{$file}";
    }

    /** @return array{string, string, string, array<string, int>} */
    private function logs(): array
    {
        ActivityLog::query()->delete();
        WarehouseEntryLog::query()->delete();

        return ['SYSTEM_LOGS_CLEARED', 'Mengosongkan seluruh riwayat log aktivitas dan log gudang.', 'Seluruh riwayat log aktivitas & riwayat gudang berhasil dikosongkan.', []];
    }

    /** @return array{string, string, string, array<string, int>} */
    private function boxAllocations(): array
    {
        $slots = WarehouseRackSlot::query()->whereNotNull('archive_id')->orWhere('status', '!=', 'empty')->update(['archive_id' => null, 'status' => 'empty']);
        WarehouseLocation::query()->update(['current_box_count' => 0]);
        $archives = Archive::query()->whereNotNull('warehouse_rack_slot_id')->orWhereNotNull('warehouse_location_id')
            ->update(['warehouse_location_id' => null, 'warehouse_rack_slot_id' => null, 'status' => 'approved_booked']);

        return ['SYSTEM_BOX_ALLOCATIONS_CLEARED', "Mengosongkan penempatan box di rak gudang ({$slots} slot rak direset, {$archives} box arsip dikosongkan dari rak).",
            "Semua box di rak gudang berhasil dikosongkan ({$slots} slot rak direset, {$archives} box arsip dikembalikan ke status belum ditempatkan).",
            ['affected_slots' => $slots, 'affected_archives' => $archives]];
    }

    /** @return array{string, string, string, array<string, int>} */
    private function archives(): array
    {
        $total = Archive::query()->count();
        BorrowingLog::query()->delete();
        DestructionLog::query()->delete();
        WarehouseEntryLog::query()->delete();
        ArchiveItem::query()->delete();
        WarehouseRackSlot::query()->update(['archive_id' => null, 'status' => 'empty']);
        WarehouseLocation::query()->update(['current_box_count' => 0]);
        Archive::query()->delete();

        return ['SYSTEM_ARCHIVES_CLEARED', "Mengosongkan seluruh data pengajuan box arsip ({$total} data arsip beserta item & log transaksi dihapus).",
            "Seluruh data pengajuan box arsip ({$total} arsip) berhasil dikosongkan.", ['deleted_archives' => $total]];
    }
}
