<?php

namespace App\Actions\Warehouse;

use App\Models\Archive;
use App\Models\User;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use App\Services\ActivityLogger;
use App\Support\StatusLabel;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * "Alokasikan Box ke Slot" from the rack grid (Laravel 7 assignSlotArchive, mode existing_archive):
 * - Menunggu Verifikasi: approve (VerifyArchive) then check in to the slot (CheckinArchive);
 * - Booking Disetujui: check in to the slot (CheckinArchive);
 * - Di Gudang without / with another slot: move the box to this slot.
 * Same department / FAT rule as the check-in. Mode "Input Dokumen Baru" is not built (see module notes).
 *
 * Fix vs Laravel 7: moving a stored box frees its old slot and keeps both racks' counters right
 * (Laravel 7 left the old slot filled); every slot claim is a conditional UPDATE (no double fill).
 */
final class PlaceArchiveInSlot
{
    public function __construct(private VerifyArchive $verify, private CheckinArchive $checkin) {}

    public function handle(User $user, WarehouseLocation $rack, int $slotId, int $archiveId): string
    {
        $slot = WarehouseRackSlot::query()->whereKey($slotId)->where('warehouse_location_id', $rack->id)->firstOrFail();
        if ($slot->getAttribute('is_active') === false) {
            throw new DomainException("Akses Ditolak: Slot '{$slot->slot_code}' saat ini dalam status NON-AKTIF (Inactive). Silakan aktifkan slot terlebih dahulu untuk menempatkan arsip.");
        }
        $archive = Archive::query()->with('department')->findOrFail($archiveId);
        CheckinArchive::ensureAllowed($rack, $archive);

        $wasPending = $archive->status === 'pending_verification';
        match ($archive->status) {
            'pending_verification', 'approved_booked' => $this->checkIn($user, $archive, $rack, $slot->id, $wasPending),
            'in_warehouse' => $this->move($archive, $rack, $slot),
            default => throw new DomainException('Akses Ditolak: Status arsip tidak valid untuk penempatan ke rak (Status: '.StatusLabel::archive($archive->status)['label'].').'),
        };
        $archive->refresh();

        ActivityLogger::log('SLOT_ASSIGN', "Penempatan box '{$archive->box_number}' di slot {$slot->slot_code} pada rak '{$rack->rack_code}'.", 'LAYOUT_GUDANG', [
            'location_id' => $rack->id, 'rack_code' => $rack->rack_code, 'slot_code' => $slot->slot_code,
            'archive_id' => $archive->id, 'box_number' => $archive->box_number, 'title' => $archive->title,
        ], $archive->box_number, $user);

        return $wasPending
            ? "Box arsip '{$archive->box_number}' berhasil disetujui (ACC) dan ditempatkan di slot {$slot->slot_code}."
            : "Box arsip '{$archive->box_number}' berhasil ditempatkan di slot {$slot->slot_code}.";
    }

    private function checkIn(User $user, Archive $archive, WarehouseLocation $rack, int $slotId, bool $approveFirst): void
    {
        if (WarehouseRackSlot::query()->whereKey($slotId)->where('status', 'empty')->whereNull('archive_id')->doesntExist()) {
            throw new DomainException('Slot sudah terisi. Muat ulang denah rak.');
        }
        if ($approveFirst) {
            $this->verify->handle($user, $archive, true, null);
        }
        $this->checkin->handle($user, $archive, $rack->id, 'Penempatan langsung ke slot dari Layout Gudang 2D.', $slotId);
    }

    private function move(Archive $archive, WarehouseLocation $rack, WarehouseRackSlot $slot): void
    {
        DB::transaction(function () use ($archive, $rack, $slot): void {
            $fresh = Archive::query()->findOrFail($archive->id);
            if ($fresh->status !== 'in_warehouse') {
                throw new DomainException('Status box sudah berubah. Muat ulang halaman.');
            }
            $claimed = WarehouseRackSlot::query()->whereKey($slot->id)->where('status', 'empty')->whereNull('archive_id')->where('is_active', true)
                ->update(['status' => 'filled', 'archive_id' => $fresh->id, 'updated_at' => now()]);
            if ($claimed !== 1) {
                throw new DomainException("Slot '{$slot->slot_code}' sudah terisi. Muat ulang denah rak.");
            }
            if ($fresh->warehouse_rack_slot_id) {
                WarehouseRackSlot::query()->whereKey($fresh->warehouse_rack_slot_id)->where('archive_id', $fresh->id)
                    ->update(['status' => 'empty', 'archive_id' => null, 'updated_at' => now()]);
            }
            $oldLocation = $fresh->warehouse_location_id;
            if ($oldLocation !== $rack->id) {
                WarehouseLocation::query()->whereKey($oldLocation)->where('current_box_count', '>', 0)->decrement('current_box_count');
                WarehouseLocation::query()->whereKey($rack->id)->increment('current_box_count');
            }
            $fresh->update(['warehouse_location_id' => $rack->id, 'warehouse_rack_slot_id' => $slot->id, 'rejection_note' => null]);
        });
    }
}
