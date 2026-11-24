<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * R8 (owner decision): a fresh install gets the PRODUCTION warehouse layout, exported from
 * the production database into data/warehouse_layout.json (warehouses, rooms and racks with
 * their 2D canvas fields and assigned departments). No archives, bookings or box counts.
 *
 * Rack slots are regenerated, not stored: the export verified that every production rack
 * uses the standard Laravel 7 numbering (levels x boxes, bottom then top, code = rack letter
 * + number). All slots start empty and active.
 */
class WarehouseLayoutSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array{warehouses: list<array{0: string, 1: string, 2: ?string, 3: int, 4: int}>, locations: list<array<string, mixed>>} $layout */
        $layout = json_decode((string) file_get_contents(__DIR__.'/data/warehouse_layout.json'), true, flags: JSON_THROW_ON_ERROR);
        // Departments are referenced by name: the production code of "FIN, ACC & TAX" is FAT,
        // the seeded one is FIN (see findings R9).
        $departmentIds = DB::table('departments')->pluck('id', 'name')->all();
        $stamps = ['created_at' => now(), 'updated_at' => now()];

        $warehouseIds = [];
        foreach ($layout['warehouses'] as [$code, $name, $address, $fat, $active]) {
            $warehouseIds[$code] = DB::table('warehouses')->insertGetId([
                'code' => $code, 'name' => $name, 'address' => $address,
                'is_fat_locked' => $fat, 'is_active' => $active,
            ] + $stamps);
        }

        foreach ($layout['locations'] as $location) {
            $locationId = DB::table('warehouse_locations')->insertGetId(
                $this->locationRow($location, $warehouseIds, $departmentIds) + $stamps
            );

            if ($location['location_type'] === 'rack') {
                DB::table('warehouse_rack_slots')->insert($this->slots($locationId, $location, $stamps));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $location
     * @param  array<string, int>  $warehouseIds
     * @param  array<string, int>  $departmentIds
     * @return array<string, mixed>
     */
    private function locationRow(array $location, array $warehouseIds, array $departmentIds): array
    {
        [$x, $y, $width, $height] = $location['canvas'];
        $departments = $location['departments'] === null ? null
            : array_values(array_filter(array_map(fn (string $name): ?int => $departmentIds[$name] ?? null, $location['departments'])));

        return [
            'warehouse_id' => $warehouseIds[$location['warehouse']],
            'location_type' => $location['location_type'],
            'room_sector' => $location['room_sector'],
            'rack_code' => $location['rack_code'],
            'shelf_code' => $location['shelf_code'],
            'box_capacity' => $location['box_capacity'],
            'total_sap' => $location['total_sap'],
            'boxes_per_sap' => $location['boxes_per_sap'],
            'box_type' => $location['box_type'],
            'canvas_x' => $x,
            'canvas_y' => $y,
            'canvas_width' => $width,
            'canvas_height' => $height,
            'orientation' => $location['orientation'],
            'rotation_angle' => $location['rotation_angle'],
            'custom_color' => $location['custom_color'],
            'is_locked' => $location['is_locked'],
            'is_fat_locked' => $location['is_fat_locked'],
            'is_active' => $location['is_active'],
            'assigned_department_id' => $location['department'] === null ? null : ($departmentIds[$location['department']] ?? null),
            'assigned_department_ids' => $departments === null ? null : json_encode($departments),
        ];
    }

    /**
     * Same numbering as Laravel 7 WarehouseLocation::generateStandardSlots(): per level the
     * bottom row first, then the top row; code = rack letter + box number.
     *
     * @param  array<string, mixed>  $location
     * @param  array<string, mixed>  $stamps
     * @return list<array<string, mixed>>
     */
    private function slots(int $locationId, array $location, array $stamps): array
    {
        $perLevel = (int) $location['boxes_per_sap'];
        $perRow = intdiv($perLevel, 2);
        $slots = [];
        for ($level = 1; $level <= $location['total_sap']; $level++) {
            foreach (['bottom' => 0, 'top' => $perRow] as $layer => $offset) {
                for ($i = 1; $i <= $perRow; $i++) {
                    $number = ($level - 1) * $perLevel + $offset + $i;
                    $slots[] = [
                        'warehouse_location_id' => $locationId,
                        'sap_level' => $level,
                        'layer' => $layer,
                        'slot_number' => $number,
                        'slot_code' => $location['slot_prefix'].$number,
                        'status' => 'empty',
                        'is_active' => true,
                    ] + $stamps;
                }
            }
        }

        return $slots;
    }
}
