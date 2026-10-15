<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Archive;
use App\Models\Department;
use App\Models\DestructionLog;
use App\Models\ArchiveExtensionLog;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExtensionWorkflowAndPostBapTest extends TestCase
{
    use DatabaseTransactions;

    protected $adminUser;
    protected $picGudangUser;
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

        $this->picGudangUser = User::firstOrCreate(
            ['email' => 'gudang_test@indraco.com'],
            [
                'name' => 'Gudang Specialist Test',
                'password' => bcrypt('password'),
                'role' => 'pic_gudang',
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

    protected function createArchive(array $attributes = [])
    {
        return Archive::create(array_merge([
            'archive_code' => 'ARC-TEST-' . uniqid(),
            'title' => 'Berkas Arsip Testing',
            'department_id' => $this->dept->id,
            'created_by_user_id' => $this->picDeptUser->id,
            'tgl_penyerahan' => date('Y-m-d'),
            'period_start_date' => date('Y-m-d'),
            'period_end_date' => date('Y-m-d'),
            'content_description' => 'Uraian berkas testing',
            'physical_condition' => 'Baik',
            'status' => 'in_warehouse',
            'retention_expiry_date' => Carbon::now()->addMonths(6)->endOfMonth()->format('Y-m-d'),
        ], $attributes));
    }

    /** @test */
    public function test_extension_propose_creates_pending_log_and_sets_archive_status_pending_extension()
    {
        $this->actingAs($this->picDeptUser);
        Storage::fake('public');

        $initialExpiry = Carbon::now()->addMonths(1)->endOfMonth()->format('Y-m-d');

        $archive = $this->createArchive([
            'archive_code' => 'ARC-EXT-001',
            'title' => 'Berkas Target Perpanjangan 6 Bulan',
            'status' => 'in_warehouse',
            'retention_expiry_date' => $initialExpiry,
        ]);

        $response = $this->post(route('destructions.extend_store'), [
            'archive_ids' => [$archive->id],
            'additional_months' => 6,
            'extension_reason' => 'Perpanjangan audit pajak 6 bulan',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // 1. Status arsip harus berubah jadi 'pending_extension'
        $archive->refresh();
        $this->assertEquals('pending_extension', $archive->status);
        // Tanggal expiry belum berubah sebelum di-ACC oleh Gudang
        $this->assertEquals($initialExpiry, $archive->retention_expiry_date->format('Y-m-d'));

        // 2. Log tercatat di archive_extension_logs dengan status 'pending'
        $log = ArchiveExtensionLog::where('archive_id', $archive->id)->latest()->first();
        $this->assertNotNull($log);
        $this->assertEquals('pending', $log->approval_status);
        $this->assertEquals(6, $log->additional_months);
        $this->assertEquals('+6 Bulan', $log->duration_label);
    }

    /** @test */
    public function test_gudang_approves_extension_and_returns_status_to_in_warehouse()
    {
        $initialExpiry = Carbon::parse('2026-10-31');

        $archive = $this->createArchive([
            'archive_code' => 'ARC-EXT-002',
            'title' => 'Berkas Target Approve Gudang',
            'status' => 'pending_extension',
            'retention_expiry_date' => $initialExpiry->format('Y-m-d'),
        ]);

        $log = ArchiveExtensionLog::create([
            'archive_id' => $archive->id,
            'proposed_by_user_id' => $this->picDeptUser->id,
            'additional_months' => 6,
            'additional_years' => 0.5,
            'previous_expiry_date' => $initialExpiry->format('Y-m-d'),
            'new_expiry_date' => $initialExpiry->copy()->addMonths(6)->endOfMonth()->format('Y-m-d'),
            'extension_reason' => 'Perpanjangan dokumen 6 bulan',
            'approval_status' => 'pending',
        ]);

        $this->actingAs($this->picGudangUser);

        $response = $this->post(route('destructions.extensions.approve', $log->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $archive->refresh();
        $log->refresh();

        // 1. Status arsip kembali ke 'in_warehouse'
        $this->assertEquals('in_warehouse', $archive->status);
        // 2. Expiry bertambah tepat 6 bulan (April 2027)
        $expectedNewExpiry = $initialExpiry->copy()->addMonths(6)->endOfMonth()->format('Y-m-d');
        $this->assertEquals($expectedNewExpiry, $archive->retention_expiry_date->format('Y-m-d'));
        // 3. Log status approved
        $this->assertEquals('approved', $log->approval_status);
        $this->assertEquals($this->picGudangUser->id, $log->approved_by_user_id);
    }

    /** @test */
    public function test_gudang_rejects_extension_and_returns_status_to_in_warehouse()
    {
        $initialExpiry = Carbon::parse('2026-10-31');

        $archive = $this->createArchive([
            'archive_code' => 'ARC-EXT-003',
            'title' => 'Berkas Target Reject Gudang',
            'status' => 'pending_extension',
            'retention_expiry_date' => $initialExpiry->format('Y-m-d'),
        ]);

        $log = ArchiveExtensionLog::create([
            'archive_id' => $archive->id,
            'proposed_by_user_id' => $this->picDeptUser->id,
            'additional_months' => 6,
            'additional_years' => 0.5,
            'previous_expiry_date' => $initialExpiry->format('Y-m-d'),
            'new_expiry_date' => $initialExpiry->copy()->addMonths(6)->endOfMonth()->format('Y-m-d'),
            'extension_reason' => 'Perpanjangan dokumen 6 bulan',
            'approval_status' => 'pending',
        ]);

        $this->actingAs($this->picGudangUser);

        $response = $this->post(route('destructions.extensions.reject', $log->id), [
            'rejection_note' => 'Lampiran surat fisik tidak lengkap',
        ]);
        $response->assertRedirect();

        $archive->refresh();
        $log->refresh();

        // 1. Status arsip kembali ke 'in_warehouse' tanpa perubahan tanggal expiry
        $this->assertEquals('in_warehouse', $archive->status);
        $this->assertEquals($initialExpiry->format('Y-m-d'), $archive->retention_expiry_date->format('Y-m-d'));
        // 2. Log status rejected
        $this->assertEquals('rejected', $log->approval_status);
        $this->assertEquals('Lampiran surat fisik tidak lengkap', $log->rejection_note);
    }

    /** @test */
    public function test_gudang_can_upload_bap_post_destruction_completion()
    {
        $this->actingAs($this->picGudangUser);
        Storage::fake('public');

        $archive = $this->createArchive([
            'archive_code' => 'ARC-DES-001',
            'title' => 'Berkas Telah Dimusnahkan Tanpa BAP',
            'status' => 'destroyed',
        ]);

        $destructionLog = DestructionLog::create([
            'archive_id' => $archive->id,
            'bap_number' => 'BAP/IND/2026/00099',
            'proposed_by_user_id' => $this->picDeptUser->id,
            'approved_by_user_id' => $this->picGudangUser->id,
            'destruction_date' => Carbon::now(),
            'method' => 'incineration',
            'approval_status' => 'completed',
            'certificate_file' => null, // Awalnya lupa diupload
        ]);

        $file = UploadedFile::fake()->create('bap_signed.pdf', 300, 'application/pdf');

        $response = $this->post(route('destructions.upload_bap', $destructionLog->id), [
            'certificate_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $destructionLog->refresh();
        $this->assertNotNull($destructionLog->certificate_file);
        Storage::disk('public')->assertExists($destructionLog->certificate_file);
    }
}
