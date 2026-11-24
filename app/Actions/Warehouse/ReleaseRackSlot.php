<?php

namespace App\Actions\Warehouse;

use App\Models\Archive;
use App\Models\User;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

/**
 * "Kosongkan / Lepas Box Dari Slot Ini" (Laravel 7 unassignSlotArchive): the slot becomes empty and
 * the box loses its slot (it keeps its status and rack, as in Laravel 7); the rack counter is
 * recounted from its occupied slots.
 */
final class ReleaseRackSlot
{
    public function handle(User $user, WarehouseLocation $rack, int $slotId): string
    {
        $slot = WarehouseRackSlot::query()->whereKey($slotId)->where('warehouse_location_id', $rack->id)->firstOrFail();

        $box = DB::transaction(function () use ($rack, $slot): ?string {
            $archiveId = WarehouseRackSlot::query()->whereKey($slot->id)->value('archive_id');
            if (! $archiveId) {
                return null;
            }
            WarehouseRackSlot::query()->whereKey($slot->id)->where('archive_id', $archiveId)->update(['archive_id' => null, 'status' => 'empty', 'updated_at' => now()]);
            Archive::query()->whereKey($archiveId)->where('warehouse_rack_slot_id', $slot->id)->update(['warehouse_rack_slot_id' => null, 'updated_at' => now()]);
            $filled = WarehouseRackSlot::query()->where('warehouse_location_id', $rack->id)->where('status', '!=', 'empty')->count();
            WarehouseLocation::query()->whereKey($rack->id)->update(['current_box_count' => $filled]);

            return Archive::query()->whereKey($archiveId)->value('box_number');
        });

        ActivityLogger::log('SLOT_UNASSIGN', "Pengosongan slot {$slot->slot_code} pada rak '{$rack->rack_code}'".($box ? " (Box {$box} dilepas)" : '').'.', 'LAYOUT_GUDANG', [
            'location_id' => $rack->id, 'rack_code' => $rack->rack_code, 'slot_code' => $slot->slot_code, 'released_box' => $box,
        ], $rack->rack_code, $user);

        return 'Slot berhasil dikosongkan.';
    }
}
