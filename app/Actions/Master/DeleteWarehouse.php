<?php

namespace App\Actions\Master;

use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\ActivityLogger;
use App\Support\WarehouseMaster;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Hapus Gudang (Laravel 7 WarehouseController@destroyWarehouse): deletes the gudang and its room
 * on the 2D layout. Its racks and slots go with it (FK cascade) and boxes would lose their
 * location, so - new guard - a gudang whose racks still hold boxes is refused (Laravel 7 only
 * guarded a single rack).
 */
class DeleteWarehouse
{
    /** @return string success message */
    public function handle(Warehouse $warehouse): string
    {
        $name = WarehouseMaster::displayName($warehouse->name);
        $logged = ['id' => $warehouse->id, 'code' => $warehouse->code, 'name' => $name];

        DB::transaction(function () use ($warehouse, $name): void {
            $racks = WarehouseMaster::withBoxCounts(WarehouseLocation::query()->where('warehouse_id', $warehouse->id))->get();
            if ($racks->contains(fn (WarehouseLocation $rack): bool => WarehouseMaster::boxCount($rack) > 0)) {
                throw new DomainException("{$name} tidak dapat dihapus karena raknya masih menampung box arsip aktif.");
            }
            WarehouseLocation::query()->where('location_type', 'room')->where('rack_code', $warehouse->code)->delete();
            $warehouse->delete();
        });

        ActivityLogger::log('MASTER_WAREHOUSE_DELETE', "Menghapus master gudang {$name} ({$logged['code']})", 'warehouse', $logged, (string) $logged['id']);

        return "{$name} berhasil dihapus.";
    }
}
