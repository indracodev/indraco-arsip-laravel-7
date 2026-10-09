<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Archive;
use App\Models\Department;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RevisiPerubahanFiturTest extends TestCase
{
    use DatabaseTransactions;

    protected $adminUser;
    protected $picDeptUser;
    protected $dept;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->dept = Department::firstOrCreate(
            ['code' => 'TEST_FIN'],
            ['name' => 'Finance & Tax Testing', 'is_active' => true]
        );

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_test@indraco.com'],
            [
                'name' => 'Admin Test',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'department_id' => $this->dept->id,
            ]
        );

        $this->picDeptUser = User::firstOrCreate(
            ['email' => 'pic_dept_test@indraco.com'],
            [
                'name' => 'PIC Dept Test',
                'password' => bcrypt('password'),
                'role' => 'pic_dept',
                'department_id' => $this->dept->id,
            ]
        );
    }

    /** @test */
    public function test_document_type_is_required_on_archive_create()
    {
        $this->actingAs($this->adminUser);
        Storage::fake('public');

        $response = $this->post(route('archives.store'), [
            'department_id' => $this->dept->id,
            'title' => 'Berkas Testing',
            'document_type' => '', // Empty, should fail
            'tgl_penyerahan' => date('Y-m-d'),
            'physical_condition' => 'Baik',
        ]);

        $response->assertSessionHasErrors(['document_type']);
    }

    /** @test */
    public function test_periode_more_than_60_months_is_rejected_on_archive_create()
    {
        $this->actingAs($this->adminUser);
        Storage::fake('public');

        $response = $this->post(route('archives.store'), [
            'department_id' => $this->dept->id,
            'title' => 'Berkas Testing',
            'document_type' => 'Pajak',
            'periode' => '120 Bulan', // Lebih dari 60 bulan
            'tgl_penyerahan' => date('Y-m-d'),
            'physical_condition' => 'Baik',
        ]);

        $response->assertSessionHasErrors(['periode']);
    }

    /** @test */
    public function test_extension_more_than_60_months_is_rejected()
    {
        $this->actingAs($this->picDeptUser);
        Storage::fake('public');

        $archive = Archive::create([
            'archive_code' => 'TEST-001',
            'title' => 'Berkas Perpanjangan',
            'document_type' => 'Pajak',
            'department_id' => $this->dept->id,
            'created_by_user_id' => $this->picDeptUser->id,
            'tgl_penyerahan' => date('Y-m-d'),
            'period_start_date' => date('Y-m-d'),
            'period_end_date' => date('Y-m-d'),
            'content_description' => 'Uraian berkas testing perpanjangan',
            'physical_condition' => 'Baik',
            'status' => 'in_warehouse',
            'retention_years' => 5,
            'retention_expiry_date' => now()->addYears(5),
        ]);

        $response = $this->post(route('destructions.extend_store', $archive->id), [
            'additional_months' => 120, // Lebih dari 60
            'extension_reason' => 'Perpanjangan dokumen untuk audit perpajakan',
        ]);

        $response->assertSessionHasErrors(['additional_months']);
    }

    /** @test */
    public function test_file_upload_more_than_500kb_is_rejected()
    {
        $this->actingAs($this->adminUser);
        Storage::fake('public');

        $largeFile = UploadedFile::fake()->create('dokumen_besar.pdf', 600); // 600 KB > 500 KB

        $response = $this->post(route('archives.store'), [
            'department_id' => $this->dept->id,
            'title' => 'Berkas Testing',
            'document_type' => 'Pajak',
            'periode' => '12 Bulan',
            'tgl_penyerahan' => date('Y-m-d'),
            'physical_condition' => 'Baik',
            'scan_input_form' => $largeFile,
        ]);

        $response->assertSessionHasErrors(['scan_input_form']);
    }

    /** @test */
    public function test_file_upload_invalid_extension_is_rejected()
    {
        $this->actingAs($this->adminUser);
        Storage::fake('public');

        $zipFile = UploadedFile::fake()->create('dokumen.zip', 200); // format ZIP ditolak

        $response = $this->post(route('archives.store'), [
            'department_id' => $this->dept->id,
            'title' => 'Berkas Testing',
            'document_type' => 'Pajak',
            'periode' => '12 Bulan',
            'tgl_penyerahan' => date('Y-m-d'),
            'physical_condition' => 'Baik',
            'scan_input_form' => $zipFile,
        ]);

        $response->assertSessionHasErrors(['scan_input_form']);
    }

    /** @test */
    public function test_pic_dept_propose_destruction_creates_pending_status_and_does_not_destroy_archive()
    {
        $this->actingAs($this->picDeptUser);
        Storage::fake('public');

        $archive = Archive::create([
            'archive_code' => 'TEST-DEST-001',
            'title' => 'Berkas Pemusnahan',
            'document_type' => 'Pajak',
            'department_id' => $this->dept->id,
            'created_by_user_id' => $this->picDeptUser->id,
            'tgl_penyerahan' => date('Y-m-d'),
            'period_start_date' => date('Y-m-d'),
            'period_end_date' => date('Y-m-d'),
            'content_description' => 'Uraian berkas testing pemusnahan',
            'physical_condition' => 'Baik',
            'status' => 'in_warehouse',
            'retention_years' => 5,
            'retention_expiry_date' => now()->subDay(),
        ]);

        $approvalFile = UploadedFile::fake()->create('approval.pdf', 100);

        $response = $this->post(route('destructions.store', $archive->id), [
            'destruction_date' => date('Y-m-d'),
            'method' => 'Pencacahan Mesin Industrial Paper Shredder',
            'approval_file' => $approvalFile,
        ]);

        $response->assertRedirect();
        
        // Cek arsip berstatus 'pending_destruction' (bukan 'destroyed')
        $archive->refresh();
        $this->assertEquals('pending_destruction', $archive->status);

        // Cek log pemusnahan berstatus 'pending' dan nomor BAP pending
        $log = \App\Models\DestructionLog::where('archive_id', $archive->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('pending', $log->approval_status);
        $this->assertEquals('(Menunggu Penerbitan BAP)', $log->bap_number);

        // Cek cetak BAP ditolak / diblokir karena belum diterbitkan oleh Gudang
        $printResponse = $this->get(route('destructions.bap', $log->id));
        $printResponse->assertRedirect();
        $printResponse->assertSessionHas('error');
    }

    /** @test */
    public function test_gudang_can_approve_and_then_complete_destruction()
    {
        Storage::fake('public');

        $gudangUser = User::firstOrCreate(
            ['email' => 'gudang_test@indraco.com'],
            [
                'name' => 'PIC Gudang Test',
                'password' => bcrypt('password'),
                'role' => 'pic_gudang',
                'department_id' => $this->dept->id,
            ]
        );

        $archive = Archive::create([
            'archive_code' => 'TEST-DEST-002',
            'title' => 'Berkas Tahap 2 dan 3',
            'document_type' => 'Pajak',
            'department_id' => $this->dept->id,
            'created_by_user_id' => $this->picDeptUser->id,
            'tgl_penyerahan' => date('Y-m-d'),
            'period_start_date' => date('Y-m-d'),
            'period_end_date' => date('Y-m-d'),
            'content_description' => 'Uraian berkas testing',
            'physical_condition' => 'Baik',
            'status' => 'pending_destruction',
            'retention_years' => 5,
            'retention_expiry_date' => now()->subDay(),
        ]);

        $log = \App\Models\DestructionLog::create([
            'archive_id' => $archive->id,
            'proposed_by_user_id' => $this->picDeptUser->id,
            'bap_number' => '(Menunggu Penerbitan BAP)',
            'destruction_date' => now(),
            'method' => 'Pencacahan Mesin Industrial Paper Shredder',
            'approval_status' => 'pending',
        ]);

        // 1. TAHAP 2: Gudang menyetujui pengajuan masuk antrean
        $this->actingAs($gudangUser);
        $approveResponse = $this->post(route('destructions.approve', $log->id));
        $approveResponse->assertRedirect();

        $log->refresh();
        $this->assertEquals('approved', $log->approval_status);

        // Cetak BAP masih diblokir
        $printResponse = $this->get(route('destructions.bap', $log->id));
        $printResponse->assertRedirect();

        // 2. TAHAP 3: Gudang mengeksekusi & menerbitkan nomor BAP resmi
        $certFile = UploadedFile::fake()->create('sertifikat_bap.pdf', 150);
        $completeResponse = $this->post(route('destructions.complete', $log->id), [
            'bap_number' => 'BAP/IND/2026/99999',
            'destruction_date' => date('Y-m-d'),
            'certificate_file' => $certFile,
            'notes' => 'Telah dimusnahkan dengan disaksikan tim audit',
        ]);
        $completeResponse->assertRedirect();

        $log->refresh();
        $archive->refresh();

        $this->assertEquals('completed', $log->approval_status);
        $this->assertEquals('BAP/IND/2026/99999', $log->bap_number);
        $this->assertEquals('destroyed', $archive->status);

        // Cetak BAP sekarang BERHASIL
        $validPrintResponse = $this->get(route('destructions.bap', $log->id));
        $validPrintResponse->assertOk();
    }

    /** @test */
    public function test_superadmin_status_update_route_works_via_ajax()
    {
        $this->actingAs($this->adminUser);
        Storage::fake('public');

        $archive = Archive::create([
            'archive_code' => 'TEST-DEST-003',
            'title' => 'Berkas Testing SuperAdmin Modal',
            'document_type' => 'Pajak',
            'department_id' => $this->dept->id,
            'created_by_user_id' => $this->adminUser->id,
            'tgl_penyerahan' => date('Y-m-d'),
            'period_start_date' => date('Y-m-d'),
            'period_end_date' => date('Y-m-d'),
            'content_description' => 'Uraian berkas testing modal',
            'physical_condition' => 'Baik',
            'status' => 'in_warehouse',
            'retention_years' => 5,
            'retention_expiry_date' => now()->subDay(),
        ]);

        $file = UploadedFile::fake()->create('bap_scan.pdf', 200);

        $response = $this->postJson(route('archives.superadmin_status', $archive->id), [
            'target_status' => 'destroyed',
            'bap_number' => 'BAP/IND/2026/00087',
            'destruction_date' => date('Y-m-d'),
            'method' => 'Shredding (Pencacahan Fisik)',
            'approval_file' => $file,
            'notes' => 'Testing pemusnahan via modal SuperAdmin',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $archive->refresh();
        $this->assertEquals('destroyed', $archive->status);

        $log = \App\Models\DestructionLog::where('archive_id', $archive->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('BAP/IND/2026/00087', $log->bap_number);
        $this->assertEquals('completed', $log->approval_status);
    }
}
