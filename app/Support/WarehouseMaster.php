<?php

namespace App\Support;

use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Builder;

/**
 * Data of the Master Gudang & Rak page (Laravel 7 WarehouseController@index): gudang with their
 * racks (rooms of the 2D layout are not racks), box count per rack. Gudang GA / IT and their
 * racks are only shown to the admin ("Khusus Super Admin").
 */
final class WarehouseMaster
{
    public const ADMIN_ONLY = ['GUDANG GA', 'GUDANG IT', 'GA', 'IT'];

    /**
     * @return list<array<string, mixed>>
     */
    public static function list(bool $isAdmin): array
    {
        $racks = self::withBoxCounts(WarehouseLocation::query()->where(fn ($q) => $q->whereNull('location_type')->orWhere('location_type', 'rack')))
            ->when(! $isAdmin, fn ($q) => $q->where(fn ($q) => $q->whereNull('room_sector')->orWhereNotIn('room_sector', self::ADMIN_ONLY)))
            ->orderBy('rack_code')->get();

        return Warehouse::query()->when(! $isAdmin, fn ($q) => $q->whereNotIn('code', self::ADMIN_ONLY))->orderBy('code')->get()
            ->map(function (Warehouse $warehouse) use ($racks): array {
                $sectors = array_map('strtolower', [$warehouse->code, self::strip($warehouse->code), self::strip($warehouse->name)]);
                $locations = $racks->filter(fn (WarehouseLocation $rack): bool => $rack->warehouse_id === $warehouse->id
                    || in_array(strtolower((string) $rack->room_sector), $sectors, true))
                    ->map(fn (WarehouseLocation $rack): array => [
                        'id' => $rack->id,
                        'warehouse_id' => $rack->warehouse_id,
                        'room_sector' => $rack->room_sector,
                        'rack_code' => $rack->rack_code,
                        'shelf_code' => $rack->shelf_code ?? 'BARIS-01',
                        'full_location' => $rack->rack_code.' ('.($rack->room_sector ?? 'Umum').')',
                        'box_capacity' => $rack->box_capacity ?? 100,
                        'current_box_count' => self::boxCount($rack),
                    ])->values()->all();

                return [
                    'id' => $warehouse->id,
                    'code' => $warehouse->code,
                    'name' => self::displayName($warehouse->name),
                    'raw_name' => $warehouse->name,
                    'address' => $warehouse->address,
                    'is_active' => (bool) ($warehouse->is_active ?? true),
                    'admin_only' => in_array($warehouse->code, self::ADMIN_ONLY, true),
                    'locations' => $locations,
                ];
            })->all();
    }

    /**
     * Filled slots and boxes on the rack (Laravel 7 WarehouseLocation::current_box_count).
     *
     * @param  Builder<WarehouseLocation>  $query
     * @return Builder<WarehouseLocation>
     */
    public static function withBoxCounts(Builder $query): Builder
    {
        return $query->withCount([
            'slots as filled_slots_count' => fn ($q) => $q->where(fn ($q) => $q->whereNotNull('archive_id')->orWhere('status', 'filled')),
            'archives as active_archives_count' => fn ($q) => $q->whereNotIn('status', ['destroyed', 'draft']),
        ]);
    }

    public static function boxCount(WarehouseLocation $rack): int
    {
        return max((int) $rack->getAttribute('filled_slots_count'), (int) $rack->getAttribute('active_archives_count'));
    }

    /** Laravel 7 Warehouse::getNameAttribute: always "Gudang <name>". */
    public static function displayName(string $name): string
    {
        return 'Gudang '.trim(self::strip($name));
    }

    private static function strip(string $value): string
    {
        return (string) preg_replace('/^(gudang\s*)+/i', '', $value);
    }
}
