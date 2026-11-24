<?php

namespace App\Http\Controllers;

use App\Actions\Master\DeleteWarehouse;
use App\Http\Requests\WarehouseRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\ActivityLogger;
use App\Support\WarehouseMaster;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Master Gudang & Slot Rak Storage (Laravel 7 WarehouseController, same messages and logs).
 * Each gudang has a `room` object on the 2D layout with the same code, kept in sync here.
 * PIC Gudang: list + add (routes); edit / delete / active switch: admin only.
 * The rack visualisation modal (100 slots) belongs to the Layout Gudang 2D module (Round F).
 */
class WarehouseController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('master.warehouses.index', ['warehouses' => WarehouseMaster::list($user->isAdmin()), 'isAdmin' => $user->isAdmin()]);
    }

    public function store(WarehouseRequest $request): RedirectResponse
    {
        $warehouse = DB::transaction(function () use ($request): Warehouse {
            $warehouse = Warehouse::create($request->validated());
            // Laravel 7: the new gudang also appears as a locked room on the 2D layout canvas.
            WarehouseLocation::create([
                'warehouse_id' => $warehouse->id, 'location_type' => 'room', 'rack_code' => $warehouse->code,
                'room_sector' => $warehouse->code, 'shelf_code' => 'SEKTOR-'.$warehouse->code, 'box_capacity' => 1000,
                'canvas_x' => 400, 'canvas_y' => 300, 'canvas_width' => 160, 'canvas_height' => 140,
                'orientation' => 'horizontal', 'custom_color' => '#1e293b', 'is_locked' => true,
            ]);

            return $warehouse;
        });

        ActivityLogger::log('MASTER_WAREHOUSE_CREATE', 'Menambahkan data master gudang '.WarehouseMaster::displayName($warehouse->name)." ({$warehouse->code})", 'warehouse',
            $warehouse->only(['id', 'code', 'name']), (string) $warehouse->id);

        return redirect()->route('master.warehouses')->with('success', WarehouseMaster::displayName($warehouse->name).' berhasil ditambahkan.');
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $data = $request->validated();
        $old = $warehouse->only(['code', 'name', 'address']);
        DB::transaction(function () use ($warehouse, $data, $old): void {
            $warehouse->update($data);
            WarehouseLocation::query()->where('location_type', 'room')->where('rack_code', $old['code'])
                ->update(['rack_code' => $data['code'], 'room_sector' => $data['code']]);
        });

        ActivityLogger::log('MASTER_WAREHOUSE_UPDATE', "Memperbarui master gudang {$warehouse->name} ({$warehouse->code})", 'warehouse',
            ['id' => $warehouse->id, 'old' => $old, 'new' => $data], (string) $warehouse->id);

        return redirect()->route('master.warehouses')->with('success', 'Data gudang '.WarehouseMaster::displayName($warehouse->name).' berhasil diperbarui.');
    }

    public function destroy(Warehouse $warehouse, DeleteWarehouse $action): RedirectResponse
    {
        try {
            return redirect()->route('master.warehouses')->with('success', $action->handle($warehouse));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function toggleActive(Request $request, Warehouse $warehouse): JsonResponse|RedirectResponse
    {
        $old = (bool) $warehouse->is_active;
        $new = $request->has('is_active') ? $request->boolean('is_active') : ! $old;
        DB::transaction(function () use ($warehouse, $new): void {
            $warehouse->update(['is_active' => $new]);
            WarehouseLocation::query()->where('location_type', 'room')
                ->where(fn ($q) => $q->where('rack_code', $warehouse->code)->orWhere('room_sector', $warehouse->code))
                ->update(['is_active' => $new]);
        });
        $status = $new ? 'AKTIF' : 'NON-AKTIF';
        $name = WarehouseMaster::displayName($warehouse->name);

        ActivityLogger::log('MASTER_WAREHOUSE_STATUS_TOGGLE', "Mengubah status master gudang {$name} ({$warehouse->code}) menjadi {$status}", 'warehouse',
            ['id' => $warehouse->id, 'code' => $warehouse->code, 'name' => $name, 'old_is_active' => $old, 'new_is_active' => $new], (string) $warehouse->id);

        $message = "Status gudang {$name} berhasil diubah menjadi {$status}.";
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'is_active' => $new]);
        }

        return redirect()->route('master.warehouses')->with('success', $message);
    }
}
