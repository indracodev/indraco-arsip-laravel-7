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
                'canvas_x' => 220,
                'canvas_y' => 30,
                'canvas_width' => 280,
                'canvas_height' => 220,
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
                'box_capacity' => 900,
                'current_box_count' => 0,
                'canvas_x' => 515,
                'canvas_y' => 30,
                'canvas_width' => 265,
                'canvas_height' => 220,
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
                'canvas_x' => 795,
                'canvas_y' => 30,
                'canvas_width' => 375,
                'canvas_height' => 510,
                'orientation' => 'vertical',
                'custom_color' => '#1e3a8a', // Dark blue (Locked FAT)
                'is_locked' => true,
                'is_fat_locked' => true, // Locked FAT Room 2
                'assigned_department_id' => $deptFin ? $deptFin->id : null,
            ],
            [
                'location_type' => 'room',
                'room_sector' => 'R7',
                'rack_code' => 'GUDANG R7',
                'shelf_code' => 'SEKTOR-R7',
                'box_capacity' => 2000,
                'current_box_count' => 0,
                'canvas_x' => 190,
                'canvas_y' => 265,
                'canvas_width' => 590,
                'canvas_height' => 275,
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
                'canvas_y' => 560,
                'canvas_width' => 230,
                'canvas_height' => 125,
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
                'box_capacity' => 400,
                'current_box_count' => 0,
                'canvas_x' => 30,
                'canvas_y' => 700,
                'canvas_width' => 230,
                'canvas_height' => 165,
                'orientation' => 'horizontal',
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
                'canvas_x' => 275,
                'canvas_y' => 560,
                'canvas_width' => 505,
                'canvas_height' => 305,
                'orientation' => 'horizontal',
                'custom_color' => '#1e293b',
                'is_locked' => true,
                'is_fat_locked' => false,
            ],
            [
                'location_type' => 'room',
                'room_sector' => 'R4',
                'rack_code' => 'GUDANG R4',
                'shelf_code' => 'SEKTOR-R4',
                'box_capacity' => 400,
                'current_box_count' => 0,
                'canvas_x' => 795,
                'canvas_y' => 560,
                'canvas_width' => 175,
                'canvas_height' => 305,
                'orientation' => 'vertical',
                'custom_color' => '#1e293b',
                'is_locked' => true,
                'is_fat_locked' => false,
            ],
            [
                'location_type' => 'room',
                'room_sector' => 'R3',
                'rack_code' => 'GUDANG R3',
                'shelf_code' => 'SEKTOR-R3',
                'box_capacity' => 500,
                'current_box_count' => 0,
                'canvas_x' => 985,
                'canvas_y' => 560,
                'canvas_width' => 185,
                'canvas_height' => 305,
                'orientation' => 'vertical',
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

        // R1: 9 Raks (A s/d I) sesuai denah ruangan R1
        $r1Racks = [
            'B' => ['x' => 525, 'y' => 38,  'w' => 16,  'h' => 85,  'orientation' => 'vertical'],
            'A' => ['x' => 525, 'y' => 130, 'w' => 16,  'h' => 85,  'orientation' => 'vertical'],
            'I' => ['x' => 635, 'y' => 38,  'w' => 130, 'h' => 16,  'orientation' => 'horizontal'],
            'H' => ['x' => 635, 'y' => 70,  'w' => 130, 'h' => 16,  'orientation' => 'horizontal'],
            'G' => ['x' => 635, 'y' => 86,  'w' => 130, 'h' => 16,  'orientation' => 'horizontal'],
            'F' => ['x' => 635, 'y' => 120, 'w' => 130, 'h' => 16,  'orientation' => 'horizontal'],
            'E' => ['x' => 635, 'y' => 136, 'w' => 130, 'h' => 16,  'orientation' => 'horizontal'],
            'D' => ['x' => 635, 'y' => 170, 'w' => 130, 'h' => 16,  'orientation' => 'horizontal'],
            'C' => ['x' => 635, 'y' => 186, 'w' => 130, 'h' => 16,  'orientation' => 'horizontal'],
        ];

        foreach ($r1Racks as $char => $pos) {
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
                'canvas_x' => $pos['x'],
                'canvas_y' => $pos['y'],
                'canvas_width' => $pos['w'],
                'canvas_height' => $pos['h'],
                'orientation' => $pos['orientation'],
                'is_locked' => true,
                'is_fat_locked' => true,
                'assigned_department_id' => $deptFin ? $deptFin->id : null,
            ];
        }

        // R2: 26 Raks (J s/d AI) sesuai denah ruangan R2
        $r2Racks = [
            // Horizontal racks di bawah (K & J)
            'K'  => ['x' => 940,  'y' => 480, 'w' => 210, 'h' => 18,  'orientation' => 'horizontal'],
            'J'  => ['x' => 940,  'y' => 498, 'w' => 210, 'h' => 18,  'orientation' => 'horizontal'],

            // Kolom 1 (Kiri - Single): N (atas), M (tengah), L (bawah)
            'N'  => ['x' => 805,  'y' => 40,  'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
            'M'  => ['x' => 805,  'y' => 175, 'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
            'L'  => ['x' => 805,  'y' => 310, 'w' => 18,  'h' => 130, 'orientation' => 'vertical'],

            // Kolom 2 (Pasangan Kiri & Kanan): Q/P/O & T/S/R
            'Q'  => ['x' => 865,  'y' => 40,  'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
            'P'  => ['x' => 865,  'y' => 175, 'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
            'O'  => ['x' => 865,  'y' => 310, 'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
            'T'  => ['x' => 883,  'y' => 40,  'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
            'S'  => ['x' => 883,  'y' => 175, 'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
            'R'  => ['x' => 883,  'y' => 310, 'w' => 18,  'h' => 130, 'orientation' => 'vertical'],

            // Kolom 3 (Pasangan Kiri & Kanan): W/V/U & Z/Y/X
            'W'  => ['x' => 943,  'y' => 40,  'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
            'V'  => ['x' => 943,  'y' => 175, 'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
            'U'  => ['x' => 943,  'y' => 310, 'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
            'Z'  => ['x' => 961,  'y' => 40,  'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
            'Y'  => ['x' => 961,  'y' => 175, 'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
            'X'  => ['x' => 961,  'y' => 310, 'w' => 18,  'h' => 130, 'orientation' => 'vertical'],

            // Kolom 4 (Pasangan Kiri & Kanan): AC/AB/AA & AF/AE/AD
            'AC' => ['x' => 1021, 'y' => 40,  'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
            'AB' => ['x' => 1021, 'y' => 175, 'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
            'AA' => ['x' => 1021, 'y' => 310, 'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
            'AF' => ['x' => 1039, 'y' => 40,  'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
            'AE' => ['x' => 1039, 'y' => 175, 'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
            'AD' => ['x' => 1039, 'y' => 310, 'w' => 18,  'h' => 130, 'orientation' => 'vertical'],

            // Kolom 5 (Kanan - Single): AI (atas), AH (tengah), AG (bawah)
            'AI' => ['x' => 1115, 'y' => 40,  'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
            'AH' => ['x' => 1115, 'y' => 175, 'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
            'AG' => ['x' => 1115, 'y' => 310, 'w' => 18,  'h' => 130, 'orientation' => 'vertical'],
        ];

        foreach ($r2Racks as $char => $pos) {
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
                'canvas_x' => $pos['x'],
                'canvas_y' => $pos['y'],
                'canvas_width' => $pos['w'],
                'canvas_height' => $pos['h'],
                'orientation' => $pos['orientation'],
                'is_locked' => true,
                'is_fat_locked' => true,
                'assigned_department_id' => $deptFin ? $deptFin->id : null,
            ];
        }

        // R7: 20 Raks (BI s/d CB) sesuai denah ruangan R7
        $r7Racks = [
            // Baris 1 Atas (BI, BJ, BK atas; BL, BM, BN bawah)
            'BI' => ['x' => 270, 'y' => 290, 'w' => 145, 'h' => 18, 'orientation' => 'horizontal'],
            'BL' => ['x' => 270, 'y' => 308, 'w' => 145, 'h' => 18, 'orientation' => 'horizontal'],
            'BJ' => ['x' => 440, 'y' => 290, 'w' => 145, 'h' => 18, 'orientation' => 'horizontal'],
            'BM' => ['x' => 440, 'y' => 308, 'w' => 145, 'h' => 18, 'orientation' => 'horizontal'],
            'BK' => ['x' => 610, 'y' => 290, 'w' => 145, 'h' => 18, 'orientation' => 'horizontal'],
            'BN' => ['x' => 610, 'y' => 308, 'w' => 145, 'h' => 18, 'orientation' => 'horizontal'],

            // Baris 2 Tengah (BO, BP, BQ atas; BR, BS, BT bawah)
            'BO' => ['x' => 270, 'y' => 350, 'w' => 145, 'h' => 18, 'orientation' => 'horizontal'],
            'BR' => ['x' => 270, 'y' => 368, 'w' => 145, 'h' => 18, 'orientation' => 'horizontal'],
            'BP' => ['x' => 440, 'y' => 350, 'w' => 145, 'h' => 18, 'orientation' => 'horizontal'],
            'BS' => ['x' => 440, 'y' => 368, 'w' => 145, 'h' => 18, 'orientation' => 'horizontal'],
            'BQ' => ['x' => 610, 'y' => 350, 'w' => 145, 'h' => 18, 'orientation' => 'horizontal'],
            'BT' => ['x' => 610, 'y' => 368, 'w' => 145, 'h' => 18, 'orientation' => 'horizontal'],

            // Baris 3 Bawah (BU, BV, BW atas; BX, BY, BZ bawah)
            'BU' => ['x' => 270, 'y' => 410, 'w' => 145, 'h' => 18, 'orientation' => 'horizontal'],
            'BX' => ['x' => 270, 'y' => 428, 'w' => 145, 'h' => 18, 'orientation' => 'horizontal'],
            'BV' => ['x' => 440, 'y' => 410, 'w' => 145, 'h' => 18, 'orientation' => 'horizontal'],
            'BY' => ['x' => 440, 'y' => 428, 'w' => 145, 'h' => 18, 'orientation' => 'horizontal'],
            'BW' => ['x' => 610, 'y' => 410, 'w' => 145, 'h' => 18, 'orientation' => 'horizontal'],
            'BZ' => ['x' => 610, 'y' => 428, 'w' => 145, 'h' => 18, 'orientation' => 'horizontal'],

            // Baris Bawah Dinding (CA kiri, CB kanan)
            'CA' => ['x' => 430, 'y' => 505, 'w' => 160, 'h' => 18, 'orientation' => 'horizontal'],
            'CB' => ['x' => 595, 'y' => 505, 'w' => 160, 'h' => 18, 'orientation' => 'horizontal'],
        ];

        foreach ($r7Racks as $char => $pos) {
            $racksData[] = [
                'location_type' => 'rack',
                'room_sector' => 'R7',
                'warehouse_id' => $whMap['GUDANG R7'] ?? null,
                'rack_code' => "RAK-R7-{$char}",
                'shelf_code' => 'BARIS-01',
                'box_capacity' => 100,
                'total_sap' => 5,
                'boxes_per_sap' => 20,
                'box_type' => 'TB 30g',
                'current_box_count' => 15,
                'canvas_x' => $pos['x'],
                'canvas_y' => $pos['y'],
                'canvas_width' => $pos['w'],
                'canvas_height' => $pos['h'],
                'orientation' => $pos['orientation'],
                'is_locked' => true,
                'is_fat_locked' => false,
            ];
        }

        // R6: 4 Raks (BE s/d BH) sesuai denah ruangan R6
        $r6Racks = [
            'BH' => ['x' => 45, 'y' => 712, 'w' => 160, 'h' => 18, 'orientation' => 'horizontal'],
            'BG' => ['x' => 45, 'y' => 760, 'w' => 160, 'h' => 18, 'orientation' => 'horizontal'],
            'BF' => ['x' => 45, 'y' => 778, 'w' => 160, 'h' => 18, 'orientation' => 'horizontal'],
            'BE' => ['x' => 45, 'y' => 825, 'w' => 160, 'h' => 18, 'orientation' => 'horizontal'],
        ];

        foreach ($r6Racks as $char => $pos) {
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
                'canvas_x' => $pos['x'],
                'canvas_y' => $pos['y'],
                'canvas_width' => $pos['w'],
                'canvas_height' => $pos['h'],
                'orientation' => $pos['orientation'],
                'is_locked' => true,
                'is_fat_locked' => false,
            ];
        }

        // R5: 12 Raks (AS s/d BD) sesuai denah ruangan R5
        $r5Racks = [
            // Horizontal racks di bagian atas dinding (BD & BC)
            'BD' => ['x' => 360, 'y' => 575, 'w' => 175, 'h' => 18,  'orientation' => 'horizontal'],
            'BC' => ['x' => 540, 'y' => 575, 'w' => 175, 'h' => 18,  'orientation' => 'horizontal'],

            // Kolom 6 (Kiri - Single): BB
            'BB' => ['x' => 290, 'y' => 640, 'w' => 18,  'h' => 205, 'orientation' => 'vertical'],

            // Kolom 5 (Pasangan): BA & AZ
            'BA' => ['x' => 365, 'y' => 640, 'w' => 18,  'h' => 205, 'orientation' => 'vertical'],
            'AZ' => ['x' => 383, 'y' => 640, 'w' => 18,  'h' => 205, 'orientation' => 'vertical'],

            // Kolom 4 (Pasangan): AY & AX
            'AY' => ['x' => 458, 'y' => 640, 'w' => 18,  'h' => 205, 'orientation' => 'vertical'],
            'AX' => ['x' => 476, 'y' => 640, 'w' => 18,  'h' => 205, 'orientation' => 'vertical'],

            // Kolom 3 (Pasangan): AW & AV
            'AW' => ['x' => 551, 'y' => 640, 'w' => 18,  'h' => 205, 'orientation' => 'vertical'],
            'AV' => ['x' => 569, 'y' => 640, 'w' => 18,  'h' => 205, 'orientation' => 'vertical'],

            // Kolom 2 (Pasangan): AU & AT
            'AU' => ['x' => 644, 'y' => 640, 'w' => 18,  'h' => 205, 'orientation' => 'vertical'],
            'AT' => ['x' => 662, 'y' => 640, 'w' => 18,  'h' => 205, 'orientation' => 'vertical'],

            // Kolom 1 (Kanan - Single): AS
            'AS' => ['x' => 737, 'y' => 640, 'w' => 18,  'h' => 205, 'orientation' => 'vertical'],
        ];

        foreach ($r5Racks as $char => $pos) {
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
                'canvas_x' => $pos['x'],
                'canvas_y' => $pos['y'],
                'canvas_width' => $pos['w'],
                'canvas_height' => $pos['h'],
                'orientation' => $pos['orientation'],
                'is_locked' => true,
                'is_fat_locked' => false,
            ];
        }

        // R4: 4 Raks (AO s/d AR) sesuai denah ruangan R4
        $r4Racks = [
            'AR' => ['x' => 808, 'y' => 640, 'w' => 18, 'h' => 205, 'orientation' => 'vertical'],
            'AQ' => ['x' => 852, 'y' => 640, 'w' => 18, 'h' => 205, 'orientation' => 'vertical'],
            'AP' => ['x' => 870, 'y' => 640, 'w' => 18, 'h' => 205, 'orientation' => 'vertical'],
            'AO' => ['x' => 918, 'y' => 640, 'w' => 18, 'h' => 205, 'orientation' => 'vertical'],
        ];

        foreach ($r4Racks as $char => $pos) {
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
                'canvas_x' => $pos['x'],
                'canvas_y' => $pos['y'],
                'canvas_width' => $pos['w'],
                'canvas_height' => $pos['h'],
                'orientation' => $pos['orientation'],
                'is_locked' => true,
                'is_fat_locked' => false,
            ];
        }

        // R3: 5 Raks (AJ s/d AN) sesuai denah ruangan R3
        $r3Racks = [
            'AN' => ['x' => 998,  'y' => 640, 'w' => 18, 'h' => 205, 'orientation' => 'vertical'],
            'AM' => ['x' => 1042, 'y' => 640, 'w' => 18, 'h' => 205, 'orientation' => 'vertical'],
            'AL' => ['x' => 1060, 'y' => 640, 'w' => 18, 'h' => 205, 'orientation' => 'vertical'],
            'AJ' => ['x' => 1135, 'y' => 575, 'w' => 18, 'h' => 130, 'orientation' => 'vertical'],
            'AK' => ['x' => 1135, 'y' => 715, 'w' => 18, 'h' => 130, 'orientation' => 'vertical'],
        ];

        foreach ($r3Racks as $char => $pos) {
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
                'canvas_x' => $pos['x'],
                'canvas_y' => $pos['y'],
                'canvas_width' => $pos['w'],
                'canvas_height' => $pos['h'],
                'orientation' => $pos['orientation'],
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
