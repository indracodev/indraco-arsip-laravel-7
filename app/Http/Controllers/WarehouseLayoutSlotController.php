<?php

namespace App\Http\Controllers;

use App\Actions\Warehouse\PlaceArchiveInSlot;
use App\Actions\Warehouse\ReleaseRackSlot;
use App\Models\Archive;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use App\Services\ActivityLogger;
use App\Support\WarehouseLayout;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Layout Gudang 2D, rack grid (Laravel 7 slots/assign, slots/unassign, slots/toggle-active).
 * Placing / releasing a box: ArchivePolicy@processWarehouse (PIC Gudang + admin); the slot active
 * switch is admin-only (Laravel 7). Slots are addressed by id within the rack.
 */
class WarehouseLayoutSlotController extends Controller
{
    public function assign(Request $request, WarehouseLocation $location, PlaceArchiveInSlot $place): JsonResponse
    {
        $this->guard($request, $location);
        $data = $request->validate([
            'slot_id' => ['required', 'integer'],
            'archive_id' => ['required', 'integer', 'exists:archives,id'],
        ]);

        try {
            return response()->json(['success' => true, 'message' => $place->handle($request->user(), $location, (int) $data['slot_id'], (int) $data['archive_id'])]);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function unassign(Request $request, WarehouseLocation $location, ReleaseRackSlot $release): JsonResponse
    {
        $this->guard($request, $location);
        $data = $request->validate(['slot_id' => ['required', 'integer']]);

        return response()->json(['success' => true, 'message' => $release->handle($request->user(), $location, (int) $data['slot_id'])]);
    }

    public function toggleActive(Request $request, WarehouseLocation $location): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Akses Ditolak: Hanya Super Admin yang diizinkan mengubah status aktif/non-aktif slot rak.');
        $data = $request->validate(['slot_id' => ['required', 'integer']]);
        $slot = WarehouseRackSlot::query()->whereKey($data['slot_id'])->where('warehouse_location_id', $location->id)->firstOrFail();

        $active = ! $slot->getAttribute('is_active');
        $slot->forceFill(['is_active' => $active, 'status' => $slot->getAttribute('archive_id') ? 'filled' : ($active ? 'empty' : 'inactive')])->save();
        $label = $active ? 'AKTIF (Active)' : 'NON-AKTIF (Inactive)';
        $layer = $slot->getAttribute('layer') === 'top' ? 'Baris Atas' : 'Baris Bawah';
        ActivityLogger::log('SLOT_STATUS_TOGGLE', "SuperAdmin mengubah status slot rak {$location->rack_code} [{$slot->slot_code}] (Sap {$slot->getAttribute('sap_level')}, {$layer}, Slot {$slot->slot_number}) menjadi {$label}.", 'LAYOUT_GUDANG',
            ['location_id' => $location->id, 'rack_code' => $location->rack_code, 'slot_id' => $slot->id, 'slot_code' => $slot->slot_code, 'is_active' => $active], $location->rack_code, $request->user());

        return response()->json(['success' => true, 'message' => "Status slot {$slot->slot_code} berhasil diubah menjadi {$label}."]);
    }

    private function guard(Request $request, WarehouseLocation $location): void
    {
        Gate::authorize('processWarehouse', Archive::class);
        abort_if(! $request->user()->isAdmin() && WarehouseLayout::isAdminOnly($location), 403, 'Akses Ditolak: object ini khusus Super Admin.');
        abort_if($location->location_type === 'room', 422, 'Slot hanya ada pada object rak.');
    }
}
