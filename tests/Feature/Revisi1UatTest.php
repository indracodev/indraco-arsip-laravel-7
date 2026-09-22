<?php

namespace Tests\Feature;

use App\Models\Archive;
use App\Models\BorrowingLog;
use App\Models\Department;
use App\Models\DestructionLog;
use App\Models\SubDepartment;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Revisi1UatTest extends TestCase
{
    use DatabaseTransactions;

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

    /**
     * UAT Test 1: Full Lifecycle - Label Creation -> Verification & Allocation -> Form A5 Print
     */
    public function test_full_archive_label_creation_allocation_and_a5_print_lifecycle()
    {
        // Step A: PIC Dept creates an archive box
        $this->actingAs($this->picDeptHrUser);

        $archiveData = [
            'department_id' => $this->hrDepartment->id,
            'sub_department_id' => $this->hrSubDepartment->id,
            'is_custom_doc_name' => 1,
            'custom_doc_name' => 'Berkas Rekrutmen Management Trainee 2026',
            'periode_doc' => '2026/09',
            'tgl_penyerahan' => '2026-09-21',
            'period_text' => 'September 2026',
            'content_description' => "1. Formulir Lamaran MT\n2. Hasil Psikotest\n3. Offering Letter",
            'retention_years' => 5,
            'physical_condition' => 'Baik / Rapi',
        ];

        $response = $this->post(route('archives.store'), $archiveData);
        $response->assertRedirect(route('archives.index'));

        $archive = Archive::where('custom_doc_name', 'Berkas Rekrutmen Management Trainee 2026')->first();
        $this->assertNotNull($archive);
        $this->assertEquals('pending_verification', $archive->status);
        $this->assertEquals('2026/09', $archive->periode_doc);
        $this->assertNull($archive->warehouse_location_id);
        $this->assertNull($archive->warehouse_rack_slot_id);

        // Step B: View Draft Print A5 Label (Draft state: No warehouse/rack)
        $printResponse = $this->get(route('archives.print_sticker', $archive->id));
        $printResponse->assertStatus(200);
        $printResponse->assertSee('LABEL BOX');
        $printResponse->assertSee('UKURAN TB 30g');
        $printResponse->assertSee('Recruitment &amp; Talent', false);
        $printResponse->assertSee('2026/09');
        $printResponse->assertSee('NOMOR GUDANG');
        $printResponse->assertSee('NOMOR RAK');

        // Step C: PIC Gudang verifies and approves the archive
        $this->actingAs($this->picGudangUser);
        $verifyResponse = $this->post(route('archives.verify', $archive->id), [
            'action' => 'approve',
        ]);
        $verifyResponse->assertRedirect(route('archives.show', $archive->id));
        $archive->refresh();
        $this->assertEquals('approved_booked', $archive->status);
        $this->assertNotEmpty($archive->box_number);

        // Step D: PIC Gudang check-in & allocates archive to General Warehouse Rack
        $checkinResponse = $this->post(route('archives.checkin', $archive->id), [
            'warehouse_location_id' => $this->generalLocation->id,
            'notes' => 'Penempatan di Rak General Slot 1',
        ]);
        $checkinResponse->assertRedirect(route('archives.show', $archive->id));
        $archive->refresh();

        $this->assertEquals('in_warehouse', $archive->status);
        $this->assertEquals($this->generalLocation->id, $archive->warehouse_location_id);
        $this->assertNotNull($archive->warehouse_rack_slot_id);

        $slot = WarehouseRackSlot::find($archive->warehouse_rack_slot_id);
        $this->assertEquals('filled', $slot->status);
        $this->assertEquals($archive->id, $slot->archive_id);

        // Step E: PIC Gudang views Final A5 Print Label
        $finalPrintResponse = $this->get(route('archives.print_sticker', $archive->id));
        $finalPrintResponse->assertStatus(200);
        $finalPrintResponse->assertSee($this->generalWarehouse->name);
        $finalPrintResponse->assertSee($this->generalLocation->rack_code);
        $finalPrintResponse->assertSee('FINAL (TERALOKASI GUDANG)');
    }

    /**
     * UAT Test 2: Borrowing Workflow & Approval File Upload Guarding
     */
    public function test_borrowing_workflow_requires_approval_file()
    {
        // Create an active archive in warehouse
        $archive = Archive::create([
            'department_id' => $this->hrDepartment->id,
            'sub_department_id' => $this->hrSubDepartment->id,
            'box_number' => 'BOX-HR-2026-001',
            'title' => 'Berkas HR 2026',
            'periode_doc' => '2026/09',
            'tgl_penyerahan' => '2026-09-20',
            'period_start_date' => '2026-09-01',
            'period_end_date' => '2026-09-30',
            'retention_years' => 5,
            'retention_expiry_date' => Carbon::parse('2031-09-01'),
            'content_description' => 'Dokumen HR penting',
            'physical_condition' => 'Baik',
            'status' => 'in_warehouse',
            'warehouse_location_id' => $this->generalLocation->id,
            'created_by_user_id' => $this->picDeptHrUser->id,
        ]);

        $this->actingAs($this->picDeptHrUser);

        // Attempt A: Submit borrow WITHOUT approval file -> Validation fails
        $failResponse = $this->from(route('borrowings.create'))->post(route('borrowings.store'), [
            'archive_id' => $archive->id,
            'purpose' => 'Audit Internal HR',
            'expected_return_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
        ]);
        $failResponse->assertSessionHasErrors(['approval_file']);

        // Attempt B: Submit borrow WITH approval file -> Success
        $file = UploadedFile::fake()->create('surat_approval_pinjam.pdf', 250, 'application/pdf');
        $successResponse = $this->post(route('borrowings.store'), [
            'archive_id' => $archive->id,
            'purpose' => 'Audit Internal HR',
            'expected_return_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'approval_file' => $file,
        ]);
        $successResponse->assertRedirect(route('borrowings.index'));

        $borrowLog = BorrowingLog::where('archive_id', $archive->id)->first();
        $this->assertNotNull($borrowLog);
        $this->assertEquals('requested', $borrowLog->status);
        $this->assertTrue((bool)$borrowLog->is_approval_uploaded);
        $this->assertNotNull($borrowLog->approval_file);
    }

    /**
     * UAT Test 3: Destruction Workflow & Approval File Upload Guarding
     */
    public function test_destruction_workflow_requires_approval_file_and_frees_slot()
    {
        // Allocate a slot to an expired archive
        $slot = $this->generalLocation->slots()->where('status', 'empty')->first();
        $archive = Archive::create([
            'department_id' => $this->hrDepartment->id,
            'sub_department_id' => $this->hrSubDepartment->id,
            'box_number' => 'BOX-HR-EXP-001',
            'title' => 'Berkas HR Expired 2020',
            'periode_doc' => '2015/01',
            'tgl_penyerahan' => '2015-01-10',
            'period_start_date' => '2015-01-01',
            'period_end_date' => '2015-01-31',
            'retention_years' => 5,
            'retention_expiry_date' => Carbon::parse('2020-01-01'), // Expired
            'content_description' => 'Dokumen HR Lama Siap Musnah',
            'physical_condition' => 'Lengkap',
            'status' => 'in_warehouse',
            'warehouse_location_id' => $this->generalLocation->id,
            'warehouse_rack_slot_id' => $slot->id,
            'created_by_user_id' => $this->picDeptHrUser->id,
        ]);

        $slot->update(['archive_id' => $archive->id, 'status' => 'filled']);
        $this->generalLocation->increment('current_box_count');

        $this->actingAs($this->picGudangUser);

        // Attempt A: Propose destruction WITHOUT approval file -> Validation error
        $failResponse = $this->from(route('destructions.propose', $archive->id))->post(route('destructions.store', $archive->id), [
            'bap_number' => 'BAP/UAT/2026/0001',
            'destruction_date' => '2026-09-22',
            'method' => 'Pencacahan Mesin Shredder',
            'notes' => 'Pemusnahan berkas kadaluarsa',
        ]);
        $failResponse->assertSessionHasErrors(['approval_file']);

        // Attempt B: Propose destruction WITH approval file -> Success, slot freed, status destroyed
        $file = UploadedFile::fake()->create('approval_musnah_bap.pdf', 300, 'application/pdf');
        $successResponse = $this->post(route('destructions.store', $archive->id), [
            'bap_number' => 'BAP/UAT/2026/0001',
            'destruction_date' => '2026-09-22',
            'method' => 'Pencacahan Mesin Shredder',
            'notes' => 'Pemusnahan berkas kadaluarsa dengan approval lengkap',
            'approval_file' => $file,
        ]);
        $successResponse->assertRedirect(route('destructions.index'));

        $archive->refresh();
        $this->assertEquals('destroyed', $archive->status);
        $this->assertNull($archive->warehouse_location_id);
        $this->assertNull($archive->warehouse_rack_slot_id);

        $slot->refresh();
        $this->assertEquals('empty', $slot->status);
        $this->assertNull($slot->archive_id);

        $destructLog = DestructionLog::where('archive_id', $archive->id)->first();
        $this->assertNotNull($destructLog);
        $this->assertEquals('BAP/UAT/2026/0001', $destructLog->bap_number);
    }

    /**
     * UAT Test 4: 100-Slot Rack Structure & FAT Room Locking Protection
     */
    public function test_100_slot_rack_structure_and_fat_room_locking()
    {
        // 1. Validate 100 slots structure
        $totalSlots = $this->generalLocation->slots()->count();
        $this->assertEquals(100, $totalSlots);

        $sap5Slots = $this->generalLocation->slots()->where('sap_level', 5)->count();
        $this->assertEquals(20, $sap5Slots);

        $sap5TopSlots = $this->generalLocation->slots()->where('sap_level', 5)->where('layer', 'T')->count();
        $sap5BottomSlots = $this->generalLocation->slots()->where('sap_level', 5)->where('layer', 'B')->count();
        $this->assertEquals(10, $sap5TopSlots);
        $this->assertEquals(10, $sap5BottomSlots);

        // 2. Validate FAT Room Locking: Non-FAT archive cannot be checked in to FAT-locked location
        $hrArchive = Archive::create([
            'department_id' => $this->hrDepartment->id,
            'sub_department_id' => $this->hrSubDepartment->id,
            'box_number' => 'BOX-HR-LOCK-001',
            'title' => 'Berkas HR',
            'periode_doc' => '2026/09',
            'tgl_penyerahan' => '2026-09-20',
            'period_start_date' => '2026-09-01',
            'period_end_date' => '2026-09-30',
            'retention_years' => 5,
            'content_description' => 'Arsip HR',
            'physical_condition' => 'Baik',
            'status' => 'approved_booked',
            'created_by_user_id' => $this->picDeptHrUser->id,
        ]);

        $this->actingAs($this->picGudangUser);

        // Attempt non-FAT checkin to FAT locked location
        $blockedResponse = $this->from(route('archives.show', $hrArchive->id))->post(route('archives.checkin', $hrArchive->id), [
            'warehouse_location_id' => $this->fatLocation->id,
        ]);
        $blockedResponse->assertSessionHas('error');
        $hrArchive->refresh();
        $this->assertNotEquals('in_warehouse', $hrArchive->status);

        // 3. Validate FAT archive CAN be checked in to FAT-locked location
        $fatArchive = Archive::create([
            'department_id' => $this->fatDepartment->id,
            'box_number' => 'BOX-FAT-001',
            'title' => 'Berkas Pajak & Keuangan FAT',
            'periode_doc' => '2026/09',
            'tgl_penyerahan' => '2026-09-20',
            'period_start_date' => '2026-09-01',
            'period_end_date' => '2026-09-30',
            'retention_years' => 10,
            'content_description' => 'Faktur Pajak & Laporan Keuangan',
            'physical_condition' => 'Baik',
            'status' => 'approved_booked',
            'created_by_user_id' => $this->picDeptFatUser->id,
        ]);

        $allowedResponse = $this->post(route('archives.checkin', $fatArchive->id), [
            'warehouse_location_id' => $this->fatLocation->id,
        ]);
        $allowedResponse->assertRedirect(route('archives.show', $fatArchive->id));
        $fatArchive->refresh();
        $this->assertEquals('in_warehouse', $fatArchive->status);
        $this->assertEquals($this->fatLocation->id, $fatArchive->warehouse_location_id);
    }

    /**
     * Test Slot Direct Document Assignment by Department and Sub-Department on 2D Rack Visualizer
     */
    public function test_slot_assignment_and_unassignment_by_department_and_sub_department()
    {
        $this->actingAs($this->adminUser);

        // 1. Assign new document to an empty slot in general location
        $assignResponse = $this->postJson(route('api.warehouse.locations.slots.assign', $this->generalLocation->id), [
            'sap_level' => 3,
            'layer' => 'top',
            'slot_number' => 5,
            'mode' => 'create_new',
            'department_id' => $this->hrDepartment->id,
            'sub_department_id' => $this->hrSubDepartment->id,
            'title' => 'Berkas Rekrutmen Karyawan 2026',
            'periode_doc' => '2026/01',
            'document_type' => 'SDM',
            'retention_years' => 5,
        ]);

        $assignResponse->assertStatus(200);
        $assignResponse->assertJson([
            'success' => true,
            'slot' => [
                'status' => 'filled',
                'sap_level' => 3,
                'layer' => 'top',
                'slot_number' => 5,
            ]
        ]);

        $slot = WarehouseRackSlot::where('warehouse_location_id', $this->generalLocation->id)
            ->where('sap_level', 3)
            ->where('layer', 'top')
            ->where('slot_number', 5)
            ->first();

        $this->assertNotNull($slot);
        $this->assertEquals('filled', $slot->status);
        $this->assertNotNull($slot->archive_id);
        $this->assertEquals('Berkas Rekrutmen Karyawan 2026', $slot->archive->title);
        $this->assertEquals($this->hrDepartment->id, $slot->archive->department_id);
        $this->assertEquals($this->hrSubDepartment->id, $slot->archive->sub_department_id);

        // 2. Unassign / eject the slot
        $unassignResponse = $this->postJson(route('api.warehouse.locations.slots.unassign', $this->generalLocation->id), [
            'sap_level' => 3,
            'layer' => 'top',
            'slot_number' => 5,
        ]);

        $unassignResponse->assertStatus(200);
        $unassignResponse->assertJson(['success' => true]);

        $slot->refresh();
        $this->assertEquals('empty', $slot->status);
        $this->assertNull($slot->archive_id);
    }
}
