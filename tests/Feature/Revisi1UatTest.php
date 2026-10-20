<?php

namespace Tests\Feature;

use App\Models\Archive;
use App\Models\BorrowingLog;
use App\Models\Department;
use App\Models\DestructionLog;
use App\Models\SubDepartment;
use App\Models\WarehouseRackSlot;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Traits\Revisi1UatSetupTrait;
use Tests\TestCase;

class Revisi1UatTest extends TestCase
{
    use DatabaseTransactions, Revisi1UatSetupTrait;

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

        // Step B: Unallocated pending archive cannot print label (status restriction)
        $printResponse = $this->get(route('archives.print_sticker', $archive->id));
        $printResponse->assertRedirect(route('archives.show', $archive->id));
        $printResponse->assertSessionHas('error');

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
     * UAT Test 2b: Borrowing Workflow - Permanent Pickup without Expected Return Date
     */
    public function test_borrowing_workflow_supports_permanent_pickup_without_return_date()
    {
        $archive = Archive::create([
            'department_id' => $this->hrDepartment->id,
            'sub_department_id' => $this->hrSubDepartment->id,
            'box_number' => 'BOX-HR-PERM-001',
            'title' => 'Berkas HR Ambil Permanen 2026',
            'periode_doc' => '2026/09',
            'tgl_penyerahan' => '2026-09-20',
            'period_start_date' => '2026-09-01',
            'period_end_date' => '2026-09-30',
            'retention_years' => 5,
            'content_description' => 'Dokumen HR Ambil Permanen',
            'physical_condition' => 'Baik',
            'status' => 'in_warehouse',
            'warehouse_location_id' => $this->generalLocation->id,
            'created_by_user_id' => $this->picDeptHrUser->id,
        ]);

        $this->actingAs($this->picDeptHrUser);

        $file = UploadedFile::fake()->create('approval_permanent_take.pdf', 250, 'application/pdf');
        $response = $this->post(route('borrowings.store'), [
            'archive_id' => $archive->id,
            'purpose' => 'Pengambilan berkas fisik secara permanen untuk keperluan legal audit luar',
            'is_permanent' => 1,
            'expected_return_date' => '',
            'approval_file' => $file,
        ]);

        $response->assertRedirect(route('borrowings.index'));

        $borrowLog = BorrowingLog::where('archive_id', $archive->id)->first();
        $this->assertNotNull($borrowLog);
        $this->assertEquals('requested', $borrowLog->status);
        $this->assertNull($borrowLog->expected_return_date);
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

        $this->actingAs($this->adminUser);

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

    /**
     * Test Master Department and Sub-Department CRUD with retention configuration
     */
    public function test_department_and_sub_department_crud_management()
    {
        $this->actingAs($this->adminUser);

        // 1. Create Department with retention
        $deptResponse = $this->post(route('master.departments.store'), [
            'code' => 'ITD',
            'name' => 'Information Technology',
            'retention_years' => 7,
            'description' => 'Departemen IT & Digital Systems',
        ]);

        $deptResponse->assertRedirect(route('master.departments'));
        $department = Department::where('code', 'ITD')->first();
        $this->assertNotNull($department);
        $this->assertEquals(7, $department->retention_years);

        // 2. Create Sub-Department
        $subResponse = $this->post(route('master.sub_departments.store'), [
            'department_id' => $department->id,
            'code' => 'DEV',
            'name' => 'Software Engineering',
            'retention_years' => 10,
            'description' => 'Sub-dept source code & sistem IT',
        ]);

        $subResponse->assertRedirect(route('master.departments'));
        $subDept = SubDepartment::where('department_id', $department->id)->where('code', 'DEV')->first();
        $this->assertNotNull($subDept);
        $this->assertEquals('Software Engineering', $subDept->name);
        $this->assertEquals(10, $subDept->retention_years);

        // 3. Update Sub-Department
        $updateSubResponse = $this->put(route('master.sub_departments.update', $subDept->id), [
            'department_id' => $department->id,
            'code' => 'DEV',
            'name' => 'Software & Cloud Engineering',
            'retention_years' => 12,
            'description' => 'Sub-dept rekayasa perangkat lunak',
        ]);

        $updateSubResponse->assertRedirect(route('master.departments'));
        $subDept->refresh();
        $this->assertEquals('Software & Cloud Engineering', $subDept->name);
        $this->assertEquals(12, $subDept->retention_years);

        // 4. Update Department
        $updateDeptResponse = $this->put(route('master.departments.update', $department->id), [
            'code' => 'ITD',
            'name' => 'Information Technology & Data',
            'retention_years' => 8,
            'description' => 'Departemen IT, Cloud & Big Data',
        ]);

        $updateDeptResponse->assertRedirect(route('master.departments'));
        $department->refresh();
        $this->assertEquals('Information Technology & Data', $department->name);
        $this->assertEquals(8, $department->retention_years);

        // 5. Delete Sub-Department
        $deleteSubResponse = $this->delete(route('master.sub_departments.destroy', $subDept->id));
        $deleteSubResponse->assertRedirect(route('master.departments'));
        $this->assertNull(SubDepartment::find($subDept->id));

        // 6. Delete Department
        $deleteDeptResponse = $this->delete(route('master.departments.destroy', $department->id));
        $deleteDeptResponse->assertRedirect(route('master.departments'));
        $this->assertNull(Department::find($department->id));
    }
}
