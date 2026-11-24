<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Laravel 7 WarehouseLayoutSeeder: one warehouse + one room object per sector, 80 racks on the
 * 2D layout, 100 slots per rack (5 levels: bottom 1..10, top 11..20, ...). The fake
 * current_box_count values of Laravel 7 are not copied: a fresh install has no boxes.
 */
class WarehouseLayoutSeeder extends Seeder
{
    /** FAT rooms and their racks belong to department FIN. */
    private const FAT_SECTORS = ['R1', 'R2'];

    /** @var list<array{0: string, 1: int, 2: int, 3: int, 4: int, 5: int, 6: string, 7: bool}> [sector, capacity, x, y, w, h, orientation, fat] */
    private const ROOMS = [
        ['GA', 1000, 220, 30, 280, 220, 'horizontal', false],
        ['R1', 900, 515, 30, 265, 220, 'horizontal', true],
        ['R2', 2600, 795, 30, 375, 510, 'vertical', true],
        ['R7', 2000, 190, 265, 590, 275, 'horizontal', false],
        ['IT', 500, 30, 560, 230, 125, 'horizontal', false],
        ['R6', 400, 30, 700, 230, 165, 'horizontal', false],
        ['R5', 1200, 275, 560, 505, 305, 'horizontal', false],
        ['R4', 400, 795, 560, 175, 305, 'vertical', false],
        ['R3', 500, 985, 560, 185, 305, 'vertical', false],
    ];

    /** @var list<array{0: string, 1: string, 2: int, 3: int, 4: int, 5: int, 6: string}> [sector, rack letter, x, y, w, h, orientation] */
    private const RACKS = [
        ['R1', 'B', 525, 38, 16, 85, 'vertical'],
        ['R1', 'A', 525, 130, 16, 85, 'vertical'],
        ['R1', 'I', 635, 38, 130, 16, 'horizontal'],
        ['R1', 'H', 635, 70, 130, 16, 'horizontal'],
        ['R1', 'G', 635, 86, 130, 16, 'horizontal'],
        ['R1', 'F', 635, 120, 130, 16, 'horizontal'],
        ['R1', 'E', 635, 136, 130, 16, 'horizontal'],
        ['R1', 'D', 635, 170, 130, 16, 'horizontal'],
        ['R1', 'C', 635, 186, 130, 16, 'horizontal'],
        ['R2', 'K', 940, 480, 210, 18, 'horizontal'],
        ['R2', 'J', 940, 498, 210, 18, 'horizontal'],
        ['R2', 'N', 805, 40, 18, 130, 'vertical'],
        ['R2', 'M', 805, 175, 18, 130, 'vertical'],
        ['R2', 'L', 805, 310, 18, 130, 'vertical'],
        ['R2', 'Q', 865, 40, 18, 130, 'vertical'],
        ['R2', 'P', 865, 175, 18, 130, 'vertical'],
        ['R2', 'O', 865, 310, 18, 130, 'vertical'],
        ['R2', 'T', 883, 40, 18, 130, 'vertical'],
        ['R2', 'S', 883, 175, 18, 130, 'vertical'],
        ['R2', 'R', 883, 310, 18, 130, 'vertical'],
        ['R2', 'W', 943, 40, 18, 130, 'vertical'],
        ['R2', 'V', 943, 175, 18, 130, 'vertical'],
        ['R2', 'U', 943, 310, 18, 130, 'vertical'],
        ['R2', 'Z', 961, 40, 18, 130, 'vertical'],
        ['R2', 'Y', 961, 175, 18, 130, 'vertical'],
        ['R2', 'X', 961, 310, 18, 130, 'vertical'],
        ['R2', 'AC', 1021, 40, 18, 130, 'vertical'],
        ['R2', 'AB', 1021, 175, 18, 130, 'vertical'],
        ['R2', 'AA', 1021, 310, 18, 130, 'vertical'],
        ['R2', 'AF', 1039, 40, 18, 130, 'vertical'],
        ['R2', 'AE', 1039, 175, 18, 130, 'vertical'],
        ['R2', 'AD', 1039, 310, 18, 130, 'vertical'],
        ['R2', 'AI', 1115, 40, 18, 130, 'vertical'],
        ['R2', 'AH', 1115, 175, 18, 130, 'vertical'],
        ['R2', 'AG', 1115, 310, 18, 130, 'vertical'],
        ['R7', 'BI', 270, 290, 145, 18, 'horizontal'],
        ['R7', 'BL', 270, 308, 145, 18, 'horizontal'],
        ['R7', 'BJ', 440, 290, 145, 18, 'horizontal'],
        ['R7', 'BM', 440, 308, 145, 18, 'horizontal'],
        ['R7', 'BK', 610, 290, 145, 18, 'horizontal'],
        ['R7', 'BN', 610, 308, 145, 18, 'horizontal'],
        ['R7', 'BO', 270, 350, 145, 18, 'horizontal'],
        ['R7', 'BR', 270, 368, 145, 18, 'horizontal'],
        ['R7', 'BP', 440, 350, 145, 18, 'horizontal'],
        ['R7', 'BS', 440, 368, 145, 18, 'horizontal'],
        ['R7', 'BQ', 610, 350, 145, 18, 'horizontal'],
        ['R7', 'BT', 610, 368, 145, 18, 'horizontal'],
        ['R7', 'BU', 270, 410, 145, 18, 'horizontal'],
        ['R7', 'BX', 270, 428, 145, 18, 'horizontal'],
        ['R7', 'BV', 440, 410, 145, 18, 'horizontal'],
        ['R7', 'BY', 440, 428, 145, 18, 'horizontal'],
        ['R7', 'BW', 610, 410, 145, 18, 'horizontal'],
        ['R7', 'BZ', 610, 428, 145, 18, 'horizontal'],
        ['R7', 'CA', 430, 505, 160, 18, 'horizontal'],
        ['R7', 'CB', 595, 505, 160, 18, 'horizontal'],
        ['R6', 'BH', 45, 712, 160, 18, 'horizontal'],
        ['R6', 'BG', 45, 760, 160, 18, 'horizontal'],
        ['R6', 'BF', 45, 778, 160, 18, 'horizontal'],
        ['R6', 'BE', 45, 825, 160, 18, 'horizontal'],
        ['R5', 'BD', 360, 575, 175, 18, 'horizontal'],
        ['R5', 'BC', 540, 575, 175, 18, 'horizontal'],
        ['R5', 'BB', 290, 640, 18, 205, 'vertical'],
        ['R5', 'BA', 365, 640, 18, 205, 'vertical'],
        ['R5', 'AZ', 383, 640, 18, 205, 'vertical'],
        ['R5', 'AY', 458, 640, 18, 205, 'vertical'],
        ['R5', 'AX', 476, 640, 18, 205, 'vertical'],
        ['R5', 'AW', 551, 640, 18, 205, 'vertical'],
        ['R5', 'AV', 569, 640, 18, 205, 'vertical'],
        ['R5', 'AU', 644, 640, 18, 205, 'vertical'],
        ['R5', 'AT', 662, 640, 18, 205, 'vertical'],
        ['R5', 'AS', 737, 640, 18, 205, 'vertical'],
        ['R4', 'AR', 808, 640, 18, 205, 'vertical'],
        ['R4', 'AQ', 852, 640, 18, 205, 'vertical'],
        ['R4', 'AP', 870, 640, 18, 205, 'vertical'],
        ['R4', 'AO', 918, 640, 18, 205, 'vertical'],
        ['R3', 'AN', 998, 640, 18, 205, 'vertical'],
        ['R3', 'AM', 1042, 640, 18, 205, 'vertical'],
        ['R3', 'AL', 1060, 640, 18, 205, 'vertical'],
        ['R3', 'AJ', 1135, 575, 18, 130, 'vertical'],
        ['R3', 'AK', 1135, 715, 18, 130, 'vertical'],
    ];

    public function run(): void
    {
        $finId = DB::table('departments')->where('code', 'FIN')->value('id');
        $now = now();
        $stamps = ['created_at' => $now, 'updated_at' => $now];

        $warehouseIds = [];
        foreach (self::ROOMS as [$sector, $capacity, $x, $y, $w, $h, $orientation, $fat]) {
            $warehouseIds[$sector] = DB::table('warehouses')->insertGetId([
                'code' => "GUDANG {$sector}",
                'name' => "GUDANG {$sector}",
                'address' => "Kawasan Industri Indraco - Sektor {$sector}",
                'is_fat_locked' => $fat,
            ] + $stamps);

            DB::table('warehouse_locations')->insert([
                'warehouse_id' => $warehouseIds[$sector],
                'location_type' => 'room',
                'room_sector' => $sector,
                'rack_code' => "GUDANG {$sector}",
                'shelf_code' => "SEKTOR-{$sector}",
                'box_capacity' => $capacity,
                'canvas_x' => $x,
                'canvas_y' => $y,
                'canvas_width' => $w,
                'canvas_height' => $h,
                'orientation' => $orientation,
                'custom_color' => $fat ? '#1e3a8a' : '#1e293b',
                'is_locked' => true,
                'is_fat_locked' => $fat,
                'assigned_department_id' => $fat ? $finId : null,
            ] + $stamps);
        }

        foreach (self::RACKS as [$sector, $letter, $x, $y, $w, $h, $orientation]) {
            $fat = in_array($sector, self::FAT_SECTORS, true);
            $rackId = DB::table('warehouse_locations')->insertGetId([
                'warehouse_id' => $warehouseIds[$sector],
                'location_type' => 'rack',
                'room_sector' => $sector,
                'rack_code' => "RAK-{$sector}-{$letter}",
                'shelf_code' => 'BARIS-01',
                'box_capacity' => 100,
                'total_sap' => 5,
                'boxes_per_sap' => 20,
                'box_type' => 'TB 30g',
                'canvas_x' => $x,
                'canvas_y' => $y,
                'canvas_width' => $w,
                'canvas_height' => $h,
                'orientation' => $orientation,
                'is_locked' => true,
                'is_fat_locked' => $fat,
                'assigned_department_id' => $fat ? $finId : null,
            ] + $stamps);

            DB::table('warehouse_rack_slots')->insert($this->slots($rackId, $letter, $stamps));
        }
    }

    /**
     * Same numbering as Laravel 7 WarehouseLocation::generateStandardSlots(): code = letter + box number.
     *
     * @param  array<string, mixed>  $stamps
     * @return list<array<string, mixed>>
     */
    private function slots(int $rackId, string $letter, array $stamps): array
    {
        $slots = [];
        for ($level = 1; $level <= 5; $level++) {
            foreach (['bottom' => 0, 'top' => 10] as $layer => $offset) {
                for ($i = 1; $i <= 10; $i++) {
                    $number = ($level - 1) * 20 + $offset + $i;
                    $slots[] = [
                        'warehouse_location_id' => $rackId,
                        'sap_level' => $level,
                        'layer' => $layer,
                        'slot_number' => $number,
                        'slot_code' => $letter.$number,
                        'status' => 'empty',
                    ] + $stamps;
                }
            }
        }

        return $slots;
    }
}
