<?php

namespace App\Actions\Warehouse;

use App\Models\Archive;
use App\Models\Department;
use App\Models\User;
use App\Models\WarehouseEntryLog;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use App\Services\ActivityLogger;
use App\Services\NumberingService;
use App\Services\SystemEventStream;
use App\Support\StatusLabel;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Gudang "Check-in Fisik & Penempatan Rak" (Laravel 7 ArchiveVerificationController@checkin):
 * `approved_booked` -> `in_warehouse` in the chosen rack. The first empty slot of the rack is
 * filled (empty -> filled), the rack counter goes +1, a `warehouse_entry_logs` row is written and a
 * box without number gets one. Racks reserved for other departments are refused (Laravel 7 rule:
 * rack list, else its room, else the FAT lock).
 *
 * Fixes vs Laravel 7: the source status is checked (Laravel 7 checked in any status, twice on a
 * double click); the slot is claimed with a conditional UPDATE inside the IMMEDIATE transaction,
 * so two check-ins never get the same slot.
 */
final class CheckinArchive
{
    public function __construct(private NumberingService $numbering) {}

    /** @param  int|null  $slotId  a chosen slot (Layout Gudang 2D); null = first empty slot */
    public function handle(User $user, Archive $archive, int $locationId, ?string $notes, ?int $slotId = null): string
    {
        [$location, $slot] = DB::transaction(function () use ($user, $archive, $locationId, $notes, $slotId): array {
            $fresh = Archive::query()->with('department')->findOrFail($archive->id);
            if ($fresh->status !== 'approved_booked') {
                throw new DomainException('Check-in hanya untuk box berstatus Booking Disetujui (status saat ini: '.StatusLabel::archive($fresh->status)['label'].'). Muat ulang halaman.');
            }
            $location = WarehouseLocation::query()->with('warehouse')->findOrFail($locationId);
            self::ensureAllowed($location, $fresh);

            $slot = $slotId === null ? $this->claimSlot($location, $fresh->id) : $this->claimChosenSlot($location, $slotId, $fresh->id);
            WarehouseLocation::query()->whereKey($location->id)->increment('current_box_count');

            $fresh->box_number = $fresh->box_number ?: $this->numbering->generate($fresh);
            $fresh->warehouse_location_id = $location->id;
            $fresh->warehouse_rack_slot_id = $slot->id ?? null;
            $fresh->status = 'in_warehouse';
            $fresh->save();

            WarehouseEntryLog::query()->create([
                'archive_id' => $fresh->id, 'pic_gudang_id' => $user->id, 'location_id' => $location->id, 'entry_date' => now(),
                'notes' => filled($notes) ? $notes : 'Penerimaan fisik berkas & penempatan di gudang arsip.',
            ]);
            SystemEventStream::archives('archive_updated', [$fresh], ['changes' => array_keys($fresh->getChanges())]);
            $archive->setRawAttributes($fresh->getAttributes(), true);

            return [$location, $slot];
        });

        $place = self::fullLocation($location);
        ActivityLogger::log('ARCHIVE_CHECKIN', "Penerimaan fisik dan check-in berkas box '{$archive->box_number}' ke lokasi rak {$place}.", 'DOKUMEN_ARSIP', [
            'archive_id' => $archive->id, 'box_number' => $archive->box_number, 'location_id' => $location->id,
            'location_name' => $place, 'slot_code' => $slot?->slot_code, 'notes' => $notes,
        ], $archive->box_number, $user);

        return "Berkas fisik berhasil di-checkin ke lokasi {$place} & Log Masuk Gudang telah dicatat.";
    }

    /**
     * Rack options of the check-in panel (Laravel 7 archives/show select): active racks, filled
     * count = max(filled slots, stored boxes) as the Laravel 7 accessor, full / other-department
     * racks disabled.
     *
     * @return list<array{id: int, text: string, disabled: bool}>
     */
    public static function options(Archive $archive): array
    {
        $racks = WarehouseLocation::query()->with('warehouse')
            ->where('location_type', 'rack')->where('is_active', true)
            ->withCount(['slots as filled_slots' => fn ($q) => $q->where(fn ($q) => $q->whereNotNull('archive_id')->orWhere('status', 'filled'))])
            ->withCount(['archives as stored_boxes' => fn ($q) => $q->whereNotIn('status', ['destroyed', 'draft'])])
            ->orderBy('warehouse_id')->orderBy('rack_code')->get();
        $codes = Department::query()->pluck('code', 'id');

        return $racks->map(function (WarehouseLocation $rack) use ($archive, $codes): array {
            $count = max((int) $rack->getAttribute('filled_slots'), (int) $rack->getAttribute('stored_boxes'));
            $capacity = (int) $rack->getAttribute('box_capacity');
            $allowed = self::allowedIds($rack);
            $full = $capacity > 0 && $count >= $capacity;
            $mismatch = $allowed !== [] && ! in_array((int) $archive->department_id, $allowed, true);
            $badge = match (true) {
                $full => ' [PENUH ⚠️]',
                $mismatch => ' [Khusus: '.collect($allowed)->map(fn (int $id) => $codes[$id] ?? null)->filter()->implode(', ').']',
                $allowed !== [] => ' [Diizinkan ✓]',
                default => ' [Umum]',
            };

            return ['id' => $rack->id, 'text' => self::fullLocation($rack).$badge." (Terisi {$count}/{$capacity} Box)", 'disabled' => $full || $mismatch];
        })->all();
    }

    /** Laravel 7 WarehouseLocation::full_location (needs warehouse loaded). */
    public static function fullLocation(WarehouseLocation $location): string
    {
        $warehouse = 'Gudang '.trim((string) preg_replace('/^(Gudang\s+)+/i', '', trim($location->warehouse->name ?? 'Gudang')));
        $sector = $location->room_sector ? "[{$location->room_sector}] " : '';
        $shelf = $location->getAttribute('shelf_code') ? ' / '.$location->getAttribute('shelf_code') : '';

        return "{$warehouse} - {$sector}{$location->rack_code}{$shelf}";
    }

    /**
     * Laravel 7 WarehouseLocation::allowed_department_ids.
     *
     * @return list<int>
     */
    private static function allowedIds(WarehouseLocation $location): array
    {
        $ids = $location->getAttribute('assigned_department_ids');
        if (is_array($ids) && $ids !== []) {
            return array_values(array_filter(array_map('intval', $ids)));
        }

        return $location->getAttribute('assigned_department_id') ? [(int) $location->getAttribute('assigned_department_id')] : [];
    }

    /** Department / FAT rule of a rack; also used by the Layout Gudang 2D slot placement. */
    public static function ensureAllowed(WarehouseLocation $location, Archive $archive): void
    {
        if ($location->getAttribute('location_type') === 'room') {
            throw new DomainException('Akses Ditolak: Lokasi yang dipilih adalah sektor/ruangan. Silakan pilih baris rak fisik yang tersedia.');
        }
        $allowed = self::allowedIds($location);
        if ($allowed === [] && $location->room_sector) {
            $room = WarehouseLocation::query()->where('location_type', 'room')->where('room_sector', $location->room_sector)->first();
            $allowed = $room ? self::allowedIds($room) : [];
        }
        if ($allowed === [] && ($location->getAttribute('is_fat_locked') || $location->warehouse?->getAttribute('is_fat_locked'))) {
            $allowed = Department::query()->whereIn('code', ['FIN', 'FAT', 'FATCLM'])->orWhere('name', 'like', '%FAT%')->pluck('id')->map(fn ($id) => (int) $id)->all();
        }
        if ($allowed !== [] && ! in_array((int) $archive->department_id, $allowed, true)) {
            $codes = Department::query()->whereIn('id', $allowed)->pluck('code')->implode(', ');
            $dept = $archive->department ? "{$archive->department->code} ({$archive->department->name})" : 'Departemen Terkait';
            throw new DomainException("Akses Ditolak: Lokasi '{$location->rack_code}' dikhususkan untuk [{$codes}]. Box dari {$dept} tidak diizinkan dialokasikan ke lokasi ini.");
        }
    }

    /** The chosen slot of the rack, claimed only if still empty and active. */
    private function claimChosenSlot(WarehouseLocation $location, int $slotId, int $archiveId): WarehouseRackSlot
    {
        $slot = WarehouseRackSlot::query()->whereKey($slotId)->where('warehouse_location_id', $location->id)->firstOrFail();
        $claimed = WarehouseRackSlot::query()->whereKey($slot->id)->where('status', 'empty')->whereNull('archive_id')->where('is_active', true)
            ->update(['status' => 'filled', 'archive_id' => $archiveId, 'updated_at' => now()]);
        if ($claimed !== 1) {
            throw new DomainException("Slot '{$slot->slot_code}' sudah terisi atau non-aktif. Muat ulang denah rak.");
        }

        return $slot;
    }

    /** First empty slot of the rack, claimed only if still empty (no double booking). */
    private function claimSlot(WarehouseLocation $location, int $archiveId): ?WarehouseRackSlot
    {
        foreach ($location->slots()->where('status', 'empty')->whereNull('archive_id')->limit(5)->get() as $slot) {
            $claimed = WarehouseRackSlot::query()->whereKey($slot->id)->where('status', 'empty')->whereNull('archive_id')
                ->update(['status' => 'filled', 'archive_id' => $archiveId, 'updated_at' => now()]);
            if ($claimed === 1) {
                return $slot;
            }
        }

        return null;
    }
}
