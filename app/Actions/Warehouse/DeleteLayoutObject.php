<?php

namespace App\Actions\Warehouse;

use App\Actions\Master\DeleteWarehouse;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\ActivityLogger;
use App\Support\WarehouseMaster;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * "Hapus Object Dari Canvas" (Laravel 7 destroyLocation). A rack goes with its slots; a room that
 * owns a gudang (same code) deletes that gudang through the Master Gudang action, as Laravel 7 did.
 *
 * Fixes vs Laravel 7: a rack holding boxes is refused (Laravel 7 deleted it and the boxes lost their
 * place); deleting a gudang (its racks go by FK cascade) is admin-only, like Master Gudang & Rak.
 */
final class DeleteLayoutObject
{
    public function __construct(private DeleteWarehouse $deleteWarehouse) {}

    public function handle(User $user, WarehouseLocation $location): string
    {
        $name = $location->rack_code;
        $warehouse = $location->location_type === 'room' ? Warehouse::query()->where('code', $name)->first() : null;

        if ($warehouse !== null) {
            if (! $user->isAdmin()) {
                throw new AuthorizationException("Akses Ditolak: object '{$name}' adalah master gudang. Hanya Super Admin yang dapat menghapusnya.");
            }
            $this->deleteWarehouse->handle($warehouse);
        } else {
            DB::transaction(function () use ($location, $name): void {
                $counted = WarehouseMaster::withBoxCounts(WarehouseLocation::query()->whereKey($location->id))->first();
                if ($counted !== null && WarehouseMaster::boxCount($counted) > 0) {
                    throw new DomainException("Rak '{$name}' tidak dapat dihapus karena masih menampung box arsip aktif.");
                }
                WarehouseLocation::query()->whereKey($location->id)->delete();
            });
        }

        ActivityLogger::log('LAYOUT_OBJECT_DELETE', "Menghapus objek '{$name}' dari canvas layout gudang.", 'LAYOUT_GUDANG', ['rack_code' => $name], $name);

        return "Object '{$name}' berhasil dihapus dari canvas.";
    }
}
