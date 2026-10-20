<?php

namespace Tests\Feature;

use App\Models\Archive;
use App\Models\BorrowingLog;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\WarehouseRackSlot;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Traits\Revisi1UatSetupTrait;
use Tests\TestCase;

class Revisi1UatPart2Test extends TestCase
{
    use DatabaseTransactions, Revisi1UatSetupTrait;

    /**
     * Test Archive Box Creation with Multi-Month Period Range (e.g. 2026/07 - 2026/09)
     */
    public function test_archive_creation_with_multi_month_period_range()
    {
        $this->actingAs($this->picDeptFatUser);

        $archiveData = [
            'department_id' => $this->fatDepartment->id,
            'title' => 'Faktur Pajak & Bukti Kas Q3 2026',
            'periode_doc' => '2026/07 - 2026/09',
            'tgl_penyerahan' => '2026-09-22',
            'period_text' => 'Juli - September 2026',
            'content_description' => "1. Faktur Pajak Juli 2026\n2. Faktur Pajak Agustus 2026\n3. Faktur Pajak September 2026",
            'masa_simpan_custom' => 5,
            'physical_condition' => 'Baik / Box TB 30g',
        ];

        $response = $this->post(route('archives.store'), $archiveData);
        $response->assertRedirect(route('archives.index'));

        $archive = Archive::where('title', 'Faktur Pajak & Bukti Kas Q3 2026')->first();
        $this->assertNotNull($archive);
        $this->assertEquals('2026/07 - 2026/09', $archive->periode_doc);
        $this->assertEquals('2026-07-01', $archive->period_start_date->format('Y-m-d'));
        $this->assertEquals('2026-09-30', $archive->period_end_date->format('Y-m-d'));
        // Expiry date must be calculated from end of period (2026-09-30 + 5 years = 2031-09-30)
        $this->assertEquals('2031-09-30', $archive->retention_expiry_date->format('Y-m-d'));
    }

    /**
     * Test PIC Department Search API with Auto-Suggestion, Keywords, and Physical Location
     */
    public function test_pic_department_search_api_with_auto_suggestion_and_location()
    {
        // 1. Create FAT Archive
        $fatArchive = Archive::create([
            'department_id' => $this->fatDepartment->id,
            'box_number' => 'IND/FIN/2026/IX/0001',
            'title' => 'Laporan Keuangan & Faktur Pajak Q3',
            'periode_doc' => '2026/07 - 2026/09',
            'tgl_penyerahan' => '2026-09-22',
            'period_start_date' => '2026-07-01',
            'period_end_date' => '2026-09-30',
            'retention_years' => 10,
            'retention_expiry_date' => Carbon::parse('2036-09-30'),
            'content_description' => 'Bukti Kas Masuk No. 001 - 150 dan Faktur Keluaran',
            'physical_condition' => 'Baik / Box TB 30g',
            'status' => 'in_warehouse',
            'warehouse_location_id' => $this->generalLocation->id,
            'created_by_user_id' => $this->picDeptFatUser->id,
        ]);

        // 2. Create HR Archive (different department)
        $hrArchive = Archive::create([
            'department_id' => $this->hrDepartment->id,
            'box_number' => 'IND/HR/2026/IX/0002',
            'title' => 'Berkas Absensi Karyawan & Faktur Pajak Pribadi',
            'periode_doc' => '2026/09',
            'tgl_penyerahan' => '2026-09-22',
            'period_start_date' => '2026-09-01',
            'period_end_date' => '2026-09-30',
            'retention_years' => 5,
            'retention_expiry_date' => Carbon::parse('2031-09-30'),
            'content_description' => 'Rekapitulasi Gaji dan Slip Pajak HR',
            'physical_condition' => 'Baik',
            'status' => 'in_warehouse',
            'warehouse_location_id' => $this->generalLocation->id,
            'created_by_user_id' => $this->picDeptHrUser->id,
        ]);

        // 3. Act as PIC Dept FAT
        $this->actingAs($this->picDeptFatUser);

        // Search by content keyword "Bukti Kas"
        $response1 = $this->getJson(route('archives.search_api', ['q' => 'Bukti Kas']));
        $response1->assertStatus(200);
        $response1->assertJsonFragment(['title' => 'Laporan Keuangan & Faktur Pajak Q3']);
        $response1->assertJsonFragment(['box_number' => 'IND/FIN/2026/IX/0001']);
        $this->assertStringContainsString($this->generalLocation->rack_code, $response1->json('items.0.location'));

        // Search by generic keyword "Faktur Pajak" -> FAT user must only get FAT documents, and NEVER HR documents
        $response2 = $this->getJson(route('archives.search_api', ['q' => 'Faktur Pajak']));
        $response2->assertStatus(200);
        $items = $response2->json('items');
        $this->assertNotEmpty($items);
        foreach ($items as $item) {
            $this->assertNotEquals('IND/HR/2026/IX/0002', $item['box_number']);
            $this->assertEquals($this->fatDepartment->code, $item['dept_code']);
        }

        // Search for HR exclusive keyword "Absensi Karyawan" as FAT user -> Returns 0 results (forbidden)
        $responseHr = $this->getJson(route('archives.search_api', ['q' => 'Absensi Karyawan']));
        $responseHr->assertStatus(200);
        $this->assertEmpty($responseHr->json('items'));

        // Empty query gives suggestions and recent FAT documents
        $response3 = $this->getJson(route('archives.search_api'));
        $response3->assertStatus(200);
        $this->assertEquals('suggestions', $response3->json('type'));
        $this->assertNotEmpty($response3->json('keywords'));
        $this->assertNotEmpty($response3->json('recent'));
    }

    /**
     * Test Super Admin global quick search API across all departments.
     */
    public function test_superadmin_search_api_with_global_multi_department_documents()
    {
        // 1. Create FAT Archive
        $fatArchive = Archive::create([
            'department_id' => $this->fatDepartment->id,
            'box_number' => 'IND/FIN/2026/IX/0001',
            'title' => 'Laporan Keuangan & Faktur Pajak Q3',
            'periode_doc' => '2026/07 - 2026/09',
            'tgl_penyerahan' => '2026-09-22',
            'period_start_date' => '2026-07-01',
            'period_end_date' => '2026-09-30',
            'retention_years' => 10,
            'retention_expiry_date' => Carbon::parse('2036-09-30'),
            'content_description' => 'Bukti Kas Masuk No. 001 - 150 dan Faktur Keluaran',
            'physical_condition' => 'Baik / Box TB 30g',
            'status' => 'in_warehouse',
            'warehouse_location_id' => $this->generalLocation->id,
            'created_by_user_id' => $this->picDeptFatUser->id,
        ]);

        // 2. Create HR Archive
        $hrArchive = Archive::create([
            'department_id' => $this->hrDepartment->id,
            'box_number' => 'IND/HR/2026/IX/0002',
            'title' => 'Berkas Absensi Karyawan & Faktur Pajak Pribadi',
            'periode_doc' => '2026/09',
            'tgl_penyerahan' => '2026-09-22',
            'period_start_date' => '2026-09-01',
            'period_end_date' => '2026-09-30',
            'retention_years' => 5,
            'retention_expiry_date' => Carbon::parse('2031-09-30'),
            'content_description' => 'Rekapitulasi Gaji dan Slip Pajak HR',
            'physical_condition' => 'Baik',
            'status' => 'in_warehouse',
            'warehouse_location_id' => $this->generalLocation->id,
            'created_by_user_id' => $this->picDeptHrUser->id,
        ]);

        // 3. Act as Super Admin
        $this->actingAs($this->adminUser);

        // Super Admin searches "Faktur Pajak" -> MUST get BOTH FAT and HR archives
        $response = $this->getJson(route('archives.search_api', ['q' => 'Faktur Pajak']));
        $response->assertStatus(200);
        $boxNumbers = collect($response->json('items'))->pluck('box_number')->toArray();
        $this->assertContains('IND/FIN/2026/IX/0001', $boxNumbers);
        $this->assertContains('IND/HR/2026/IX/0002', $boxNumbers);

        // Super Admin searches "Absensi Karyawan" -> Gets HR document
        $responseHr = $this->getJson(route('archives.search_api', ['q' => 'Absensi Karyawan']));
        $responseHr->assertStatus(200);
        $this->assertNotEmpty($responseHr->json('items'));
        $this->assertEquals('IND/HR/2026/IX/0002', $responseHr->json('items.0.box_number'));

        // Super Admin empty query suggestions
        $responseSuggestions = $this->getJson(route('archives.search_api'));
        $responseSuggestions->assertStatus(200);
        $this->assertEquals('suggestions', $responseSuggestions->json('type'));
        $this->assertNotEmpty($responseSuggestions->json('keywords'));
    }

    public function testDispatchVacatesRackSlotAndSupportsTakenStatus()
    {
        $slot = $this->generalLocation->slots()->first();
        $archive = Archive::create([
            'box_number' => 'BOX-DISPATCH-TEST',
            'department_id' => $this->hrDepartment->id,
            'title' => 'Berkas Uji Dispatch Rack Slot',
            'content_description' => 'Rincian Berkas Dokumen Test',
            'period_start_date' => '2026-09-01',
            'period_end_date' => '2026-09-30',
            'periode_doc' => '2026/09',
            'status' => 'in_warehouse',
            'warehouse_location_id' => $this->generalLocation->id,
            'warehouse_rack_slot_id' => $slot->id,
            'created_by_user_id' => $this->picDeptHrUser->id,
        ]);

        $slot->update([
            'status' => 'filled',
            'archive_id' => $archive->id,
        ]);
        $this->generalLocation->update(['current_box_count' => 1]);

        // 1. Permanent Take-out (is_permanent = true, no expected_return_date)
        $borrowingLog = BorrowingLog::create([
            'archive_id' => $archive->id,
            'borrower_user_id' => $this->picDeptHrUser->id,
            'request_date' => now(),
            'expected_return_date' => null,
            'purpose' => 'Pengambilan berkas permanen',
            'status' => 'requested',
            'approval_file' => 'test_approval.pdf',
        ]);

        $this->actingAs($this->picGudangUser);
        $response = $this->post(route('borrowings.dispatch', $borrowingLog));
        $response->assertRedirect();

        // Assert archive status is taken
        $archive->refresh();
        $this->assertEquals('taken', $archive->status);

        // Assert slot is now empty
        $slot->refresh();
        $this->assertEquals('empty', $slot->status);
        $this->assertNull($slot->archive_id);

        // Assert BorrowingLog status label displays "Sudah Ditarik"
        $borrowingLog->refresh();
        $this->assertEquals('Sudah Ditarik', $borrowingLog->status_label);
    }

    /**
     * Test Filter Tipe Dokumen per Subdepartemen (Point 5)
     */
    public function test_filter_document_type_per_sub_department()
    {
        $archiveA = Archive::create([
            'department_id' => $this->hrDepartment->id,
            'sub_department_id' => $this->hrSubDepartment->id,
            'box_number' => 'BOX-DOC-TYPE-A',
            'title' => 'Berkas PKWT Batch 1',
            'document_type' => 'PKWT',
            'periode_doc' => '2026/01',
            'tgl_penyerahan' => '2026-01-10',
            'period_start_date' => '2026-01-01',
            'period_end_date' => '2026-01-31',
            'retention_years' => 5,
            'retention_expiry_date' => Carbon::parse('2031-01-31'),
            'content_description' => 'Isi PKWT',
            'physical_condition' => 'Baik',
            'status' => 'in_warehouse',
            'created_by_user_id' => $this->picDeptHrUser->id,
        ]);

        $archiveB = Archive::create([
            'department_id' => $this->hrDepartment->id,
            'sub_department_id' => $this->hrSubDepartment->id,
            'box_number' => 'BOX-DOC-TYPE-B',
            'title' => 'Berkas SP Karyawan',
            'document_type' => 'SURAT_PERINGATAN',
            'periode_doc' => '2026/01',
            'tgl_penyerahan' => '2026-01-10',
            'period_start_date' => '2026-01-01',
            'period_end_date' => '2026-01-31',
            'retention_years' => 3,
            'retention_expiry_date' => Carbon::parse('2029-01-31'),
            'content_description' => 'Isi SP',
            'physical_condition' => 'Baik',
            'status' => 'in_warehouse',
            'created_by_user_id' => $this->picDeptHrUser->id,
        ]);

        $this->actingAs($this->picDeptHrUser);

        // Filter by PKWT
        $responseA = $this->get(route('archives.index', [
            'sub_department_id' => $this->hrSubDepartment->id,
            'document_type' => 'PKWT',
        ]));
        $archivesInView = $responseA->original->getData()['archives'];
        $first = $archivesInView->first();
        $this->assertNotNull($first);
        $this->assertEquals('BOX-DOC-TYPE-A', $first->box_number);
        $this->assertEquals('PKWT', $first->document_type);

        // Filter by SURAT_PERINGATAN
        $responseB = $this->get(route('archives.index', [
            'sub_department_id' => $this->hrSubDepartment->id,
            'document_type' => 'SURAT_PERINGATAN',
        ]));
        $responseB->assertOk();
        $archivesInViewB = $responseB->original->getData()['archives'];
        $firstB = $archivesInViewB->first();
        $this->assertNotNull($firstB);
        $this->assertEquals('BOX-DOC-TYPE-B', $firstB->box_number);
        $this->assertEquals('SURAT_PERINGATAN', $firstB->document_type);
        $this->assertCount(1, $archivesInViewB);
    }

    /**
     * Test Point 1, 2, 3: Realtime poller returns role-aware badges & enriched new_archives
     */
    public function test_realtime_check_returns_role_badges_and_new_archives_payload()
    {
        // 1. Create an archive in pending_verification for HR
        $archive = Archive::create([
            'department_id' => $this->hrDepartment->id,
            'sub_department_id' => $this->hrSubDepartment->id,
            'box_number' => 'BOX-REALTIME-TEST',
            'title' => 'Berkas Realtime UAT Test',
            'document_type' => 'KONTRAK',
            'periode_doc' => '2026/02',
            'tgl_penyerahan' => '2026-02-10',
            'period_start_date' => '2026-02-01',
            'period_end_date' => '2026-02-28',
            'retention_years' => 5,
            'retention_expiry_date' => Carbon::parse('2031-02-28'),
            'content_description' => 'Realtime Payload Verification',
            'physical_condition' => 'Baik',
            'status' => 'pending_verification',
            'created_by_user_id' => $this->picDeptHrUser->id,
        ]);

        // 2. Query as PIC Gudang
        $this->actingAs($this->picGudangUser);
        $response = $this->getJson(route('api.realtime.check', [
            'last_id' => $archive->id - 1,
            'initial' => 0,
        ]));

        $response->assertOk();
        $data = $response->json();

        // Assert badges exist
        $this->assertArrayHasKey('badges', $data);
        $this->assertArrayHasKey('borrowings', $data['badges']);
        $this->assertArrayHasKey('submissions', $data['badges']);
        $this->assertArrayHasKey('destructions', $data['badges']);
        $this->assertGreaterThanOrEqual(1, $data['badges']['submissions']);

        // Assert new_archives payload
        $this->assertArrayHasKey('new_archives', $data);
        $newArchiveItem = collect($data['new_archives'])->firstWhere('id', $archive->id);
        $this->assertNotNull($newArchiveItem);
        $this->assertEquals('BOX-REALTIME-TEST', $newArchiveItem['box_number']);
        $this->assertEquals('KONTRAK', $newArchiveItem['document_type']);
        $this->assertEquals($this->hrDepartment->id, $newArchiveItem['department_id']);
        $this->assertEquals($this->hrSubDepartment->id, $newArchiveItem['sub_department_id']);
        $this->assertEquals('pending_verification', $newArchiveItem['status']);
    }

    /**
     * Test Point 1: BorrowingLog emits system event stream with enriched payload
     */
    public function test_borrowing_log_realtime_stream_event()
    {
        $archive = Archive::create([
            'department_id' => $this->hrDepartment->id,
            'box_number' => 'BOX-BORROW-STREAM',
            'title' => 'Berkas Borrow Stream Test',
            'periode_doc' => '2026/02',
            'tgl_penyerahan' => '2026-02-10',
            'period_start_date' => '2026-02-01',
            'period_end_date' => '2026-02-28',
            'retention_years' => 5,
            'retention_expiry_date' => Carbon::parse('2031-02-28'),
            'content_description' => 'Stream Verification',
            'physical_condition' => 'Baik',
            'status' => 'in_warehouse',
            'created_by_user_id' => $this->picDeptHrUser->id,
        ]);

        $borrowLog = BorrowingLog::create([
            'archive_id' => $archive->id,
            'borrower_user_id' => $this->picDeptHrUser->id,
            'request_date' => now(),
            'purpose' => 'Audit Test UAT',
            'status' => 'requested',
            'borrow_date' => now()->toDateString(),
        ]);

        $this->assertNotNull($borrowLog->id);

        // Check system_events table
        $event = \Illuminate\Support\Facades\DB::table('system_events')
            ->where('event_type', 'borrowing_created')
            ->where('entity_id', $borrowLog->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($event);
        $payload = json_decode($event->payload, true);
        $this->assertEquals('requested', $payload['status']);
        $this->assertEquals('BOX-BORROW-STREAM', $payload['box_number']);
        $this->assertEquals($this->hrDepartment->code, $payload['dept_code']);
    }
}
