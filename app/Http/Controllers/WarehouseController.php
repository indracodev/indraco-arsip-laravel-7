<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        // 1. Sync Gudang rooms from 2D Layout (warehouse_locations) into warehouses table
        $roomLocations = WarehouseLocation::where('location_type', 'room')->get();

        $validCodes = $roomLocations->pluck('rack_code')->filter()->values()->toArray();
        $existingCodes = Warehouse::pluck('code')->toArray();

        // Only synchronize if there are differences
        if (count(array_diff($validCodes, $existingCodes)) > 0 || count(array_diff($existingCodes, $validCodes)) > 0) {
            foreach ($roomLocations as $room) {
                $code = $room->rack_code;
                $sector = $room->room_sector ?? $code;

                Warehouse::firstOrCreate(
                    ['code' => $code],
                    [
                        'name' => \Illuminate\Support\Str::startsWith(strtoupper($code), 'GUDANG') ? $code : "Gudang " . $code,
                        'address' => "Kawasan Industri Indraco - Sektor " . $sector,
                    ]
                );
            }

            if (count($validCodes) > 0) {
                Warehouse::whereNotIn('code', $validCodes)->delete();
            }
        }

        // 2. Fetch all registered racks in ONE single query with eager loading
        $allRacks = WarehouseLocation::where(function ($q) {
            $q->whereNull('location_type')->orWhere('location_type', 'rack');
        })
        ->with(['slots', 'archives'])
        ->get();

        // 3. Map warehouses and assign racks in memory without extra queries
        $warehouses = Warehouse::all()->map(function ($wh) use ($allRacks) {
            $sectorCode = preg_replace('/^gudang\s*/i', '', $wh->code);
            $sectorName = preg_replace('/^gudang\s*/i', '', $wh->name);

            $racks = $allRacks->filter(function ($loc) use ($wh, $sectorCode, $sectorName) {
                return $loc->warehouse_id === $wh->id
                    || strcasecmp($loc->room_sector, $wh->code) === 0
                    || strcasecmp($loc->room_sector, $sectorCode) === 0
                    || strcasecmp($loc->room_sector, $sectorName) === 0;
            })->map(function ($loc) {
                return [
                    'id' => $loc->id,
                    'warehouse_id' => $loc->warehouse_id,
                    'room_sector' => $loc->room_sector,
                    'rack_code' => $loc->rack_code,
                    'shelf_code' => $loc->shelf_code ?? 'BARIS-01',
                    'full_location' => $loc->rack_code . ' (' . ($loc->room_sector ?? 'Umum') . ')',
                    'box_capacity' => $loc->box_capacity ?? 100,
                    'current_box_count' => $loc->current_box_count,
                ];
            })->values();

            return [
                'id' => $wh->id,
                'code' => $wh->code,
                'name' => $wh->name,
                'address' => $wh->address,
                'is_active' => (bool) ($wh->is_active ?? true),
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

        ActivityLogger::log('MASTER_WAREHOUSE_CREATE', "Menambahkan data master gudang {$warehouse->name} ({$warehouse->code})", 'warehouse', [
            'id' => $warehouse->id,
            'code' => $warehouse->code,
            'name' => $warehouse->name,
        ], $warehouse->id);

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

        $oldData = $warehouse->only(['code', 'name', 'address']);
        $warehouse->update($validated);

        // Sync room object on 2D layout canvas
        WarehouseLocation::where('location_type', 'room')
            ->where('rack_code', $oldCode)
            ->update([
                'rack_code' => $validated['code'],
                'room_sector' => $validated['code'],
            ]);

        ActivityLogger::log('MASTER_WAREHOUSE_UPDATE', "Memperbarui master gudang {$warehouse->name} ({$warehouse->code})", 'warehouse', [
            'id' => $warehouse->id,
            'old' => $oldData,
            'new' => $validated,
        ], $warehouse->id);

        return redirect()->route('master.warehouses')
            ->with('success', "Data gudang {$warehouse->name} berhasil diperbarui.");
    }

    public function destroyWarehouse(Warehouse $warehouse)
    {
        $name = $warehouse->name;
        $code = $warehouse->code;
        $id = $warehouse->id;

        // Delete corresponding room object on 2D layout canvas
        WarehouseLocation::where('location_type', 'room')
            ->where('rack_code', $warehouse->code)
            ->delete();

        $warehouse->delete();

        ActivityLogger::log('MASTER_WAREHOUSE_DELETE', "Menghapus master gudang {$name} ({$code})", 'warehouse', [
            'id' => $id,
            'code' => $code,
            'name' => $name,
        ], $id);

        return redirect()->route('master.warehouses')
            ->with('success', "Gudang {$name} berhasil dihapus.");
    }

    public function toggleWarehouseActive(Request $request, Warehouse $warehouse)
    {
        $user = auth()->user();
        if (!$user || !$user->isSuperAdmin()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Hanya SuperAdmin yang berwenang mengubah status aktif gudang.'], 403);
            }
            return back()->with('error', 'Hanya SuperAdmin yang berwenang mengubah status aktif gudang.');
        }

        $newActive = $request->has('is_active') ? (bool) $request->input('is_active') : !$warehouse->is_active;
        $oldActive = (bool) ($warehouse->is_active ?? true);

        $warehouse->update(['is_active' => $newActive]);

        // Sync room object in 2D layout canvas
        WarehouseLocation::where('location_type', 'room')
            ->where(function ($q) use ($warehouse) {
                $q->where('rack_code', $warehouse->code)
                  ->orWhere('room_sector', $warehouse->code);
            })
            ->update(['is_active' => $newActive]);

        $statusStr = $newActive ? 'AKTIF' : 'NON-AKTIF';
        $desc = "Mengubah status master gudang {$warehouse->name} ({$warehouse->code}) menjadi {$statusStr}";

        ActivityLogger::log('MASTER_WAREHOUSE_STATUS_TOGGLE', $desc, 'warehouse', [
            'id' => $warehouse->id,
            'code' => $warehouse->code,
            'name' => $warehouse->name,
            'old_is_active' => $oldActive,
            'new_is_active' => $newActive,
        ], $warehouse->id);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Status gudang {$warehouse->name} berhasil diubah menjadi {$statusStr}.",
                'is_active' => $newActive,
                'warehouse' => [
                    'id' => $warehouse->id,
                    'code' => $warehouse->code,
                    'name' => $warehouse->name,
                    'is_active' => $newActive,
                ]
            ]);
        }

        return redirect()->route('master.warehouses')
            ->with('success', "Status gudang {$warehouse->name} berhasil diubah menjadi {$statusStr}.");
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

        $loc = WarehouseLocation::create($validated);

        ActivityLogger::log('MASTER_RACK_CREATE', "Menambahkan rak penyimpanan baru {$loc->rack_code} di {$validated['room_sector']}", 'warehouse', [
            'id' => $loc->id,
            'rack_code' => $loc->rack_code,
            'shelf_code' => $loc->shelf_code,
            'box_capacity' => $loc->box_capacity,
        ], $loc->id);

        return redirect()->route('master.warehouses')
            ->with('success', 'Lokasi penyimpanan rak/baris berhasil ditambahkan.');
    }

    public function updateLocation(Request $request, WarehouseLocation $location)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'rack_code' => 'required|string|max:50',
            'shelf_code' => 'required|string|max:50',
            'box_capacity' => 'required|integer|min:1|max:5000',
        ]);

        $wh = Warehouse::find($validated['warehouse_id']);
        $validated['room_sector'] = $wh ? $wh->code : ($location->room_sector ?? 'Umum');

        $oldData = $location->only(['warehouse_id', 'rack_code', 'shelf_code', 'box_capacity', 'room_sector']);
        $location->update($validated);

        ActivityLogger::log('MASTER_RACK_UPDATE', "Memperbarui data rak penyimpanan {$location->rack_code}", 'warehouse', [
            'id' => $location->id,
            'old' => $oldData,
            'new' => $validated,
        ], $location->id);

        return redirect()->route('master.warehouses')
            ->with('success', "Data rak {$location->rack_code} berhasil diperbarui.");
    }

    public function destroyLocation(WarehouseLocation $location)
    {
        if ($location->current_box_count > 0) {
            return back()->with('error', 'Lokasi rak ini tidak dapat dihapus karena masih menampung box arsip aktif.');
        }

        $code = $location->rack_code;
        $id = $location->id;
        $location->delete();

        ActivityLogger::log('MASTER_RACK_DELETE', "Menghapus rak penyimpanan {$code}", 'warehouse', [
            'id' => $id,
            'rack_code' => $code,
        ], $id);

        return redirect()->route('master.warehouses')
            ->with('success', 'Lokasi rak berhasil dihapus.');
    }
}
