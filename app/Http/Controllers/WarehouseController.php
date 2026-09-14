<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        // 1. Sync Gudang rooms from 2D Layout (warehouse_locations) into warehouses table
        $roomLocations = WarehouseLocation::where('location_type', 'room')->get();

        $validCodes = [];
        foreach ($roomLocations as $room) {
            $code = $room->rack_code;
            $sector = $room->room_sector ?? $code;
            $validCodes[] = $code;

            Warehouse::firstOrCreate(
                ['code' => $code],
                [
                    'name' => str_starts_with(strtoupper($code), 'GUDANG') ? $code : "Gudang " . $code,
                    'address' => "Kawasan Industri Indraco - Sektor " . $sector,
                ]
            );
        }

        if (count($validCodes) > 0) {
            Warehouse::whereNotIn('code', $validCodes)->delete();
        }

        // 2. Fetch warehouses with registered racks
        $warehouses = Warehouse::all()->map(function ($wh) {
            $sectorCode = preg_replace('/^gudang\s*/i', '', $wh->code);
            $sectorName = preg_replace('/^gudang\s*/i', '', $wh->name);

            $racks = WarehouseLocation::where(function ($q) {
                $q->whereNull('location_type')->orWhere('location_type', 'rack');
            })
            ->where(function ($q) use ($wh, $sectorCode, $sectorName) {
                $q->where('room_sector', $wh->code)
                  ->orWhere('room_sector', $sectorCode)
                  ->orWhere('room_sector', $sectorName)
                  ->orWhere('warehouse_id', $wh->id);
            })
            ->withCount('archives')
            ->get()
            ->map(function ($loc) {
                return [
                    'id' => $loc->id,
                    'rack_code' => $loc->rack_code,
                    'shelf_code' => $loc->shelf_code ?? 'BARIS-01',
                    'full_location' => $loc->rack_code . ' (' . ($loc->room_sector ?? 'Umum') . ')',
                    'box_capacity' => $loc->box_capacity ?? 100,
                    'current_box_count' => $loc->current_box_count ?? 0,
                ];
            });

            return [
                'id' => $wh->id,
                'code' => $wh->code,
                'name' => $wh->name,
                'address' => $wh->address,
                'locations' => $racks,
                'locations_count' => count($racks),
            ];
        });

        return view('master.warehouses', compact('warehouses'));
    }

    public function storeWarehouse(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:warehouses,code',
            'name' => 'required|string|max:100',
            'address' => 'nullable|string',
        ]);

        $warehouse = Warehouse::create($validated);

        // Auto-create room object on 2D Layout Canvas as well
        WarehouseLocation::create([
            'warehouse_id' => $warehouse->id,
            'location_type' => 'room',
            'rack_code' => $warehouse->code,
            'room_sector' => $warehouse->code,
            'shelf_code' => 'SEKTOR-' . $warehouse->code,
            'box_capacity' => 1000,
            'canvas_x' => 400,
            'canvas_y' => 300,
            'canvas_width' => 160,
            'canvas_height' => 140,
            'orientation' => 'horizontal',
            'custom_color' => '#1e293b',
            'is_locked' => true,
        ]);

        return redirect()->route('master.warehouses')
            ->with('success', "Gudang {$validated['name']} berhasil ditambahkan.");
    }

    public function updateWarehouse(Request $request, Warehouse $warehouse)
    {
        $oldCode = $warehouse->code;

        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:warehouses,code,' . $warehouse->id,
            'name' => 'required|string|max:100',
            'address' => 'nullable|string',
        ]);

        $warehouse->update($validated);

        // Sync room object on 2D layout canvas
        WarehouseLocation::where('location_type', 'room')
            ->where('rack_code', $oldCode)
            ->update([
                'rack_code' => $validated['code'],
                'room_sector' => $validated['code'],
            ]);

        return redirect()->route('master.warehouses')
            ->with('success', "Data gudang {$warehouse->name} berhasil diperbarui.");
    }

    public function destroyWarehouse(Warehouse $warehouse)
    {
        // Delete corresponding room object on 2D layout canvas
        WarehouseLocation::where('location_type', 'room')
            ->where('rack_code', $warehouse->code)
            ->delete();

        $warehouse->delete();

        return redirect()->route('master.warehouses')
            ->with('success', "Gudang {$warehouse->name} berhasil dihapus.");
    }

    public function storeLocation(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'rack_code' => 'required|string|max:50',
            'shelf_code' => 'required|string|max:50',
            'box_capacity' => 'required|integer|min:1|max:5000',
        ]);

        $wh = Warehouse::find($validated['warehouse_id']);
        $validated['location_type'] = 'rack';
        $validated['room_sector'] = $wh ? $wh->code : 'Umum';
        $validated['canvas_x'] = 400;
        $validated['canvas_y'] = 300;
        $validated['canvas_width'] = 40;
        $validated['canvas_height'] = 120;
        $validated['orientation'] = 'horizontal';

        WarehouseLocation::create($validated);

        return redirect()->route('master.warehouses')
            ->with('success', 'Lokasi penyimpanan rak/baris berhasil ditambahkan.');
    }

    public function destroyLocation(WarehouseLocation $location)
    {
        if ($location->current_box_count > 0) {
            return back()->with('error', 'Lokasi rak ini tidak dapat dihapus karena masih menampung box arsip aktif.');
        }

        $location->delete();

        return redirect()->route('master.warehouses')
            ->with('success', 'Lokasi rak berhasil dihapus.');
    }
}
