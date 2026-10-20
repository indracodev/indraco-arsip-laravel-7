<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Department;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Models\Archive;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class WarehouseLockAndCustomDeptTest extends TestCase
{
    use DatabaseTransactions;

    protected $adminGudang;
    protected $deptA;
    protected $deptB;
    protected $warehouse;
    protected $location;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->adminGudang = User::where('role', 'pic_gudang')->first() ?: User::create([
            'name' => 'PIC Gudang Test',
            'email' => 'pic_wh_test@test.com',
            'password' => bcrypt('password'),
            'role' => 'pic_gudang',
        ]);
        $this->deptA = Department::first() ?: Department::create(['code' => 'DPTA', 'name' => 'Dept A']);
        $this->deptB = Department::where('id', '!=', $this->deptA->id)->first() ?: Department::create(['code' => 'DPTB', 'name' => 'Dept B']);
        $this->warehouse = Warehouse::first() ?: Warehouse::create(['code' => 'WH1', 'name' => 'Gudang Utama', 'address' => 'Jl. Test']);

        $this->location = WarehouseLocation::create([
            'warehouse_id' => $this->warehouse->id,
            'rack_code' => 'TEST-RACK-01',
            'location_type' => 'rack',
            'room_sector' => 'SEKTOR-TEST',
            'shelf_code' => 'S1',
            'box_capacity' => 100,
            'total_sap' => 5,
            'boxes_per_sap' => 20,
            'canvas_x' => 10,
            'canvas_y' => 10,
            'canvas_width' => 100,
            'canvas_height' => 30,
            'is_locked' => false,
            'assigned_department_id' => null,
        ]);
        $this->location->generateStandardSlots();
    }

    public function test_assign_slot_allowed_even_when_canvas_is_locked()
    {
        // Posisi canvas terkunci (is_locked = true), penempatan slot box tetap diperbolehkan
        $this->location->update(['is_locked' => true, 'assigned_department_id' => null]);

        $response = $this->actingAs($this->adminGudang)->postJson("/api/warehouse/locations/{$this->location->id}/slots/assign", [
            'sap_level' => 1,
            'layer' => 'bottom',
            'slot_number' => 1,
            'mode' => 'create_new',
            'department_id' => $this->deptA->id,
            'title' => 'Dokumen Test Kunci Canvas',
            'document_type' => 'UMUM',
            'periode_doc' => '2026/10',
            'retention_years' => 5,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_assign_slot_rejected_when_custom_department_mismatches()
    {
        // Set location custom for deptA
        $this->location->update(['is_locked' => false, 'assigned_department_id' => $this->deptA->id]);

        // Try assigning document from deptB
        $response = $this->actingAs($this->adminGudang)->postJson("/api/warehouse/locations/{$this->location->id}/slots/assign", [
            'sap_level' => 1,
            'layer' => 'bottom',
            'slot_number' => 2,
            'mode' => 'create_new',
            'department_id' => $this->deptB->id,
            'title' => 'Dokumen Mismatch Dept',
            'document_type' => 'UMUM',
            'periode_doc' => '2026/10',
            'retention_years' => 5,
        ]);

        $response->assertStatus(403);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('dikhususkan', strtolower($response->json('message')));
    }

    public function test_assign_slot_allowed_when_custom_department_matches()
    {
        // Set location custom for deptA
        $this->location->update(['is_locked' => false, 'assigned_department_id' => $this->deptA->id]);

        // Assign document from deptA
        $response = $this->actingAs($this->adminGudang)->postJson("/api/warehouse/locations/{$this->location->id}/slots/assign", [
            'sap_level' => 1,
            'layer' => 'bottom',
            'slot_number' => 3,
            'mode' => 'create_new',
            'department_id' => $this->deptA->id,
            'title' => 'Dokumen Matching Dept',
            'document_type' => 'UMUM',
            'periode_doc' => '2026/10',
            'retention_years' => 5,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_assign_slot_general_location_accepts_any_department()
    {
        // General location (assigned_department_id = null)
        $this->location->update(['is_locked' => false, 'assigned_department_id' => null]);

        // Dept A
        $resA = $this->actingAs($this->adminGudang)->postJson("/api/warehouse/locations/{$this->location->id}/slots/assign", [
            'sap_level' => 1,
            'layer' => 'bottom',
            'slot_number' => 4,
            'mode' => 'create_new',
            'department_id' => $this->deptA->id,
            'title' => 'Dokumen Umum Dept A',
            'document_type' => 'UMUM',
            'periode_doc' => '2026/10',
            'retention_years' => 5,
        ]);
        $resA->assertStatus(200);

        // Dept B
        $resB = $this->actingAs($this->adminGudang)->postJson("/api/warehouse/locations/{$this->location->id}/slots/assign", [
            'sap_level' => 1,
            'layer' => 'bottom',
            'slot_number' => 5,
            'mode' => 'create_new',
            'department_id' => $this->deptB->id,
            'title' => 'Dokumen Umum Dept B',
            'document_type' => 'UMUM',
            'periode_doc' => '2026/10',
            'retention_years' => 5,
        ]);
        $resB->assertStatus(200);
    }
}
