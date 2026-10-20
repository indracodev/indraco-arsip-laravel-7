<?php

namespace Tests\Feature\Traits;

use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use Illuminate\Support\Facades\Storage;

trait Revisi1UatSetupTrait
{
    protected $adminUser;
    protected $picGudangUser;
    protected $picDeptFatUser;
    protected $picDeptHrUser;
    protected $fatDepartment;
    protected $hrDepartment;
    protected $hrSubDepartment;
    protected $fatWarehouse;
    protected $fatLocation;
    protected $generalWarehouse;
    protected $generalLocation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake('public');

        // 1. Setup Departments
        $this->fatDepartment = Department::firstOrCreate(
            ['code' => 'FIN'],
            ['name' => 'Finance & Accounting', 'default_retention_years' => 10, 'is_active' => true]
        );

        $this->hrDepartment = Department::firstOrCreate(
            ['code' => 'HRD'],
            ['name' => 'Human Resources & General Affairs', 'default_retention_years' => 5, 'is_active' => true]
        );

        $this->hrSubDepartment = SubDepartment::firstOrCreate(
            ['code' => 'HR-REC', 'department_id' => $this->hrDepartment->id],
            ['name' => 'Recruitment & Talent', 'is_active' => true]
        );

        // 2. Setup Users using App\Models\User
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_uat@indraco.com'],
            ['name' => 'Super Admin UAT', 'password' => bcrypt('password'), 'role' => 'admin']
        );

        $this->picGudangUser = User::firstOrCreate(
            ['email' => 'gudang_uat@indraco.com'],
            ['name' => 'PIC Gudang UAT', 'password' => bcrypt('password'), 'role' => 'pic_gudang']
        );

        $this->picDeptFatUser = User::firstOrCreate(
            ['email' => 'fat_uat@indraco.com'],
            ['name' => 'PIC Dept FAT UAT', 'password' => bcrypt('password'), 'role' => 'pic_dept', 'department_id' => $this->fatDepartment->id]
        );

        $this->picDeptHrUser = User::firstOrCreate(
            ['email' => 'hr_uat@indraco.com'],
            ['name' => 'PIC Dept HR UAT', 'password' => bcrypt('password'), 'role' => 'pic_dept', 'department_id' => $this->hrDepartment->id]
        );

        // 3. Setup Warehouses (1 FAT-locked, 1 General)
        $this->fatWarehouse = Warehouse::firstOrCreate(
            ['code' => 'GD-FAT-UAT'],
            ['name' => 'Gudang Arsip Eksklusif FAT', 'is_fat_locked' => true, 'is_active' => true]
        );

        $this->fatLocation = WarehouseLocation::firstOrCreate(
            ['warehouse_id' => $this->fatWarehouse->id, 'rack_code' => 'RAK-FAT-01'],
            ['shelf_code' => 'A', 'box_capacity' => 100, 'total_sap' => 5, 'boxes_per_sap' => 20, 'box_type' => 'TB 30g', 'current_box_count' => 0, 'is_fat_locked' => true]
        );

        $this->generalWarehouse = Warehouse::firstOrCreate(
            ['code' => 'GD-GEN-UAT'],
            ['name' => 'Gudang Arsip Umum', 'is_fat_locked' => false, 'is_active' => true]
        );

        $this->generalLocation = WarehouseLocation::firstOrCreate(
            ['warehouse_id' => $this->generalWarehouse->id, 'rack_code' => 'RAK-GEN-01'],
            ['shelf_code' => 'A', 'box_capacity' => 100, 'total_sap' => 5, 'boxes_per_sap' => 20, 'box_type' => 'TB 30g', 'current_box_count' => 0, 'is_fat_locked' => false]
        );

        // Ensure 100 slots exist for generalLocation
        if ($this->generalLocation->slots()->count() < 100) {
            $this->generalLocation->slots()->delete();
            for ($sap = 1; $sap <= 5; $sap++) {
                for ($slot = 1; $slot <= 10; $slot++) {
                    WarehouseRackSlot::create([
                        'warehouse_location_id' => $this->generalLocation->id,
                        'sap_level' => $sap,
                        'layer' => 'T',
                        'slot_number' => $slot,
                        'slot_code' => "S{$sap}-T" . str_pad($slot, 2, '0', STR_PAD_LEFT),
                        'status' => 'empty',
                    ]);
                    WarehouseRackSlot::create([
                        'warehouse_location_id' => $this->generalLocation->id,
                        'sap_level' => $sap,
                        'layer' => 'B',
                        'slot_number' => $slot,
                        'slot_code' => "S{$sap}-B" . str_pad($slot, 2, '0', STR_PAD_LEFT),
                        'status' => 'empty',
                    ]);
                }
            }
        }
    }
}
