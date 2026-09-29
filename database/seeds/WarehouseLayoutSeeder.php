<?php

use App\Models\Department;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use Illuminate\Database\Seeder;

class WarehouseLayoutSeeder extends Seeder
{
    public function run()
    {
        WarehouseRackSlot::query()->delete();
        WarehouseLocation::query()->delete();
        Warehouse::query()->delete();

        $deptFin = Department::where('code', 'FIN')->first();
        $deptHrd = Department::where('code', 'HRD')->first();
        $deptMkt = Department::where('code', 'MKT')->first();

        // 2 Ruangan Khusus FAT: GUDANG R1 dan GUDANG R2
        $rooms = [
            [
                'location_type' => 'room',
                'room_sector' => 'GA',
                'rack_code' => 'GUDANG GA',
                'shelf_code' => 'SEKTOR-GA',
                'box_capacity' => 1000,
                'current_box_count' => 0,
                'canvas_x' => 30,
                'canvas_y' => 40,
                'canvas_width' => 180,
                'canvas_height' => 140,
                'orientation' => 'horizontal',
                'custom_color' => '#1e293b',
                'is_locked' => true,
                'is_fat_locked' => false,
            ],
            [
                'location_type' => 'room',
                'room_sector' => 'IT',
                'rack_code' => 'GUDANG IT',
                'shelf_code' => 'SEKTOR-IT',
                'box_capacity' => 500,
                'current_box_count' => 0,
                'canvas_x' => 30,
                'canvas_y' => 195,
                'canvas_width' => 180,
                'canvas_height' => 140,
                'orientation' => 'horizontal',
                'custom_color' => '#1e293b',
                'is_locked' => true,
                'is_fat_locked' => false,
            ],
            [
                'location_type' => 'room',
                'room_sector' => 'R1',
                'rack_code' => 'GUDANG R1',
                'shelf_code' => 'SEKTOR-R1',
                'box_capacity' => 1000,
                'current_box_count' => 0,
                'canvas_x' => 230,
                'canvas_y' => 40,
                'canvas_width' => 240,
                'canvas_height' => 215,
                'orientation' => 'horizontal',
                'custom_color' => '#1e3a8a', // Dark blue (Locked FAT)
                'is_locked' => true,
                'is_fat_locked' => true, // Locked FAT Room 1
                'assigned_department_id' => $deptFin ? $deptFin->id : null,
            ],
            [
                'location_type' => 'room',
                'room_sector' => 'R2',
                'rack_code' => 'GUDANG R2',
                'shelf_code' => 'SEKTOR-R2',
                'box_capacity' => 2600,
                'current_box_count' => 0,
                'canvas_x' => 485,
                'canvas_y' => 40,
                'canvas_width' => 445,
                'canvas_height' => 480,
                'orientation' => 'vertical',
                'custom_color' => '#1e3a8a', // Dark blue (Locked FAT)
                'is_locked' => true,
                'is_fat_locked' => true, // Locked FAT Room 2
                'assigned_department_id' => $deptFin ? $deptFin->id : null,
            ],
            [
                'location_type' => 'room',
                'room_sector' => 'R3',
                'rack_code' => 'GUDANG R3',
                'shelf_code' => 'SEKTOR-R3',
                'box_capacity' => 500,
                'current_box_count' => 0,
                'canvas_x' => 30,
                'canvas_y' => 535,
                'canvas_width' => 180,
                'canvas_height' => 175,
                'orientation' => 'vertical',
                'custom_color' => '#1e293b',
                'is_locked' => true,
                'is_fat_locked' => false,
            ],
            [
                'location_type' => 'room',
                'room_sector' => 'R4',
                'rack_code' => 'GUDANG R4',
                'shelf_code' => 'SEKTOR-R4',
                'box_capacity' => 500,
                'current_box_count' => 0,
                'canvas_x' => 750,
                'canvas_y' => 535,
                'canvas_width' => 180,
                'canvas_height' => 175,
                'orientation' => 'vertical',
                'custom_color' => '#1e293b',
                'is_locked' => true,
                'is_fat_locked' => false,
            ],
            [
                'location_type' => 'room',
                'room_sector' => 'R5',
                'rack_code' => 'GUDANG R5',
                'shelf_code' => 'SEKTOR-R5',
                'box_capacity' => 1200,
                'current_box_count' => 0,
                'canvas_x' => 230,
                'canvas_y' => 270,
                'canvas_width' => 240,
                'canvas_height' => 440,
                'orientation' => 'horizontal',
                'custom_color' => '#1e293b',
                'is_locked' => true,
                'is_fat_locked' => false,
            ],
            [
                'location_type' => 'room',
                'room_sector' => 'R6',
                'rack_code' => 'GUDANG R6',
                'shelf_code' => 'SEKTOR-R6',
                'box_capacity' => 500,
                'current_box_count' => 0,
                'canvas_x' => 30,
                'canvas_y' => 350,
                'canvas_width' => 180,
                'canvas_height' => 170,
                'orientation' => 'horizontal',
                'custom_color' => '#1e293b',
                'is_locked' => true,
                'is_fat_locked' => false,
            ],
        ];

        foreach ($rooms as $roomData) {
            $wh = Warehouse::create([
                'code' => $roomData['rack_code'],
                'name' => $roomData['rack_code'],
                'address' => 'Kawasan Industri Indraco - Sektor ' . $roomData['room_sector'],
                'is_fat_locked' => $roomData['is_fat_locked'] ?? false,
            ]);

            WarehouseLocation::create(array_merge($roomData, ['warehouse_id' => $wh->id]));
        }

        $whMap = Warehouse::pluck('id', 'code')->toArray();
        $racksData = [];

        // R1: 6 Raks (FAT Locked)
        $r1Letters = range('A', 'F');
        foreach ($r1Letters as $idx => $char) {
            $racksData[] = [
                'location_type' => 'rack',
                'room_sector' => 'R1',
                'warehouse_id' => $whMap['GUDANG R1'] ?? null,
                'rack_code' => "RAK-R1-{$char}",
                'shelf_code' => 'BARIS-01',
                'box_capacity' => 100,
                'total_sap' => 5,
                'boxes_per_sap' => 20,
                'box_type' => 'TB 30g',
                'current_box_count' => 15,
                'canvas_x' => 245 + ($idx * 35),
                'canvas_y' => 75,
                'canvas_width' => 25,
                'canvas_height' => 140,
                'orientation' => 'vertical',
                'is_locked' => true,
                'is_fat_locked' => true,
                'assigned_department_id' => $deptFin ? $deptFin->id : null,
            ];
        }

        // R2: 26 Raks (FAT Locked)
        $r2Letters = range('A', 'Z');
        foreach ($r2Letters as $idx => $char) {
            $row = $idx < 13 ? 0 : 1;
            $col = $idx % 13;
            $racksData[] = [
                'location_type' => 'rack',
                'room_sector' => 'R2',
                'warehouse_id' => $whMap['GUDANG R2'] ?? null,
                'rack_code' => "RAK-R2-{$char}",
                'shelf_code' => 'BARIS-01',
                'box_capacity' => 100,
                'total_sap' => 5,
                'boxes_per_sap' => 20,
                'box_type' => 'TB 30g',
                'current_box_count' => 20,
                'canvas_x' => 500 + ($col * 32),
                'canvas_y' => 75 + ($row * 220),
                'canvas_width' => 25,
                'canvas_height' => 180,
                'orientation' => 'vertical',
                'is_locked' => true,
                'is_fat_locked' => true,
                'assigned_department_id' => $deptFin ? $deptFin->id : null,
            ];
        }

        // R3: 4 Raks (Umum)
        $r3Letters = range('A', 'D');
        foreach ($r3Letters as $idx => $char) {
            $racksData[] = [
                'location_type' => 'rack',
                'room_sector' => 'R3',
                'warehouse_id' => $whMap['GUDANG R3'] ?? null,
                'rack_code' => "RAK-R3-{$char}",
                'shelf_code' => 'BARIS-01',
                'box_capacity' => 100,
                'total_sap' => 5,
                'boxes_per_sap' => 20,
                'box_type' => 'TB 30g',
                'current_box_count' => 10,
                'canvas_x' => 45 + ($idx * 36),
                'canvas_y' => 565,
                'canvas_width' => 25,
                'canvas_height' => 125,
                'orientation' => 'vertical',
                'is_locked' => true,
                'is_fat_locked' => false,
            ];
        }

        // R4: 4 Raks (Umum)
        $r4Letters = range('A', 'D');
        foreach ($r4Letters as $idx => $char) {
            $racksData[] = [
                'location_type' => 'rack',
                'room_sector' => 'R4',
                'warehouse_id' => $whMap['GUDANG R4'] ?? null,
                'rack_code' => "RAK-R4-{$char}",
                'shelf_code' => 'BARIS-01',
                'box_capacity' => 100,
                'total_sap' => 5,
                'boxes_per_sap' => 20,
                'box_type' => 'TB 30g',
                'current_box_count' => 12,
                'canvas_x' => 765 + ($idx * 36),
                'canvas_y' => 565,
                'canvas_width' => 25,
                'canvas_height' => 125,
                'orientation' => 'vertical',
                'is_locked' => true,
                'is_fat_locked' => false,
            ];
        }

        // R5: 10 Raks (Umum)
        $r5Letters = range('A', 'J');
        foreach ($r5Letters as $idx => $char) {
            $row = $idx < 5 ? 0 : 1;
            $col = $idx % 5;
            $racksData[] = [
                'location_type' => 'rack',
                'room_sector' => 'R5',
                'warehouse_id' => $whMap['GUDANG R5'] ?? null,
                'rack_code' => "RAK-R5-{$char}",
                'shelf_code' => 'BARIS-01',
                'box_capacity' => 100,
                'total_sap' => 5,
                'boxes_per_sap' => 20,
                'box_type' => 'TB 30g',
                'current_box_count' => 18,
                'canvas_x' => 245 + ($col * 42),
                'canvas_y' => 300 + ($row * 200),
                'canvas_width' => 25,
                'canvas_height' => 160,
                'orientation' => 'vertical',
                'is_locked' => true,
                'is_fat_locked' => false,
            ];
        }

        // R6: 4 Raks (Umum)
        $r6Letters = range('A', 'D');
        foreach ($r6Letters as $idx => $char) {
            $racksData[] = [
                'location_type' => 'rack',
                'room_sector' => 'R6',
                'warehouse_id' => $whMap['GUDANG R6'] ?? null,
                'rack_code' => "RAK-R6-{$char}",
                'shelf_code' => 'BARIS-01',
                'box_capacity' => 100,
                'total_sap' => 5,
                'boxes_per_sap' => 20,
                'box_type' => 'TB 30g',
                'current_box_count' => 8,
                'canvas_x' => 45,
                'canvas_y' => 370 + ($idx * 32),
                'canvas_width' => 140,
                'canvas_height' => 25,
                'orientation' => 'horizontal',
                'is_locked' => true,
                'is_fat_locked' => false,
            ];
        }

        foreach ($racksData as $rack) {
            $loc = WarehouseLocation::create($rack);
            $loc->generateStandardSlots();
        }
    }
}
