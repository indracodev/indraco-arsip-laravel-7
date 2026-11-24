<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseLocationRequest;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\ActivityLogger;
use App\Support\WarehouseMaster;
use Illuminate\Http\RedirectResponse;

/**
 * Slot Rak CRUD of Master Gudang & Rak (Laravel 7 WarehouseController: storeLocation,
 * updateLocation, destroyLocation; same messages and logs). Add: admin + PIC Gudang;
 * edit / delete: admin (routes). A new rack starts at the Laravel 7 default canvas position.
 */
class WarehouseLocationController extends Controller
{
    public function store(WarehouseLocationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $sector = Warehouse::query()->whereKey($data['warehouse_id'])->value('code') ?? 'Umum';
        $location = WarehouseLocation::create($data + [
            'location_type' => 'rack', 'room_sector' => $sector, 'canvas_x' => 400, 'canvas_y' => 300,
            'canvas_width' => 40, 'canvas_height' => 120, 'orientation' => 'horizontal',
        ]);

        ActivityLogger::log('MASTER_RACK_CREATE', "Menambahkan rak penyimpanan baru {$location->rack_code} di {$sector}", 'warehouse',
            $location->only(['id', 'rack_code', 'shelf_code', 'box_capacity']), (string) $location->id);

        return redirect()->route('master.warehouses')->with('success', 'Lokasi penyimpanan rak/baris berhasil ditambahkan.');
    }

    public function update(WarehouseLocationRequest $request, WarehouseLocation $location): RedirectResponse
    {
        $data = $request->validated();
        $data['room_sector'] = Warehouse::query()->whereKey($data['warehouse_id'])->value('code') ?? ($location->room_sector ?? 'Umum');
        $old = $location->only(['warehouse_id', 'rack_code', 'shelf_code', 'box_capacity', 'room_sector']);
        $location->update($data);

        ActivityLogger::log('MASTER_RACK_UPDATE', "Memperbarui data rak penyimpanan {$location->rack_code}", 'warehouse',
            ['id' => $location->id, 'old' => $old, 'new' => $data], (string) $location->id);

        return redirect()->route('master.warehouses')->with('success', "Data rak {$location->rack_code} berhasil diperbarui.");
    }

    public function destroy(WarehouseLocation $location): RedirectResponse
    {
        $counted = WarehouseMaster::withBoxCounts(WarehouseLocation::query()->whereKey($location->id))->firstOrFail();
        if (WarehouseMaster::boxCount($counted) > 0) {
            return back()->with('error', 'Lokasi rak ini tidak dapat dihapus karena masih menampung box arsip aktif.');
        }
        $code = $location->rack_code;
        $id = $location->id;
        $location->delete();

        ActivityLogger::log('MASTER_RACK_DELETE', "Menghapus rak penyimpanan {$code}", 'warehouse', ['id' => $id, 'rack_code' => $code], (string) $id);

        return redirect()->route('master.warehouses')->with('success', 'Lokasi rak berhasil dihapus.');
    }
}
