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
    public function test_pic_dept_auto_generates_bap_on_destruction()
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
            // bap_number tidak dikirim oleh PIC Dept
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('destruction_logs', [
            'archive_id' => $archive->id,
            'method' => 'Pencacahan Mesin Industrial Paper Shredder',
        ]);
    }
}
