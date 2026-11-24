<?php

namespace Tests\Feature\File;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Archive\ArchiveFixtures;
use Tests\TestCase;

/**
 * File preview/download (findings #2, #3, B11): login + ArchivePolicy, upload-folder allow-list,
 * disk search order private -> public -> legacy_public, 404 without fallback, X-Accel-Redirect.
 */
class ArchiveFileTest extends TestCase
{
    use ArchiveFixtures;
    use RefreshDatabase;

    private const PDF = "%PDF-1.4\n%%EOF\n";

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpArchiveFixtures();
        Storage::fake('public');
        Storage::fake('legacy_public');
        config(['filesystems.x_accel' => true]);
    }

    private function archive(int $deptId, ?string $scan = 'archive_scans/scan1.pdf'): int
    {
        return DB::table('archives')->insertGetId([
            'department_id' => $deptId, 'created_by_user_id' => $this->picHcs->id, 'title' => 'Box '.$deptId,
            'period_start_date' => '2026-01-01', 'period_end_date' => '2026-06-30', 'content_description' => 'isi',
            'status' => 'in_warehouse', 'scan_input_form' => $scan, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_owner_previews_and_downloads_through_x_accel(): void
    {
        Storage::disk('local')->put('archive_scans/scan1.pdf', self::PDF);
        $id = $this->archive($this->hcs);

        $this->actingAs($this->picHcs)->get("/files/archive/{$id}/scan_input_form")->assertOk()
            ->assertHeader('X-Accel-Redirect', '/private/archive_scans/scan1.pdf')
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', "inline; filename=scan-input-form-archive-{$id}.pdf")
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertContent('');
        $this->actingAs($this->picHcs)->get("/files/archive/{$id}/scan_input_form/download")->assertOk()
            ->assertHeader('Content-Disposition', "attachment; filename=scan-input-form-archive-{$id}.pdf");
    }

    public function test_without_x_accel_php_streams_the_file(): void
    {
        config(['filesystems.x_accel' => false]);
        Storage::disk('local')->put('archive_scans/scan1.pdf', self::PDF);
        $id = $this->archive($this->hcs);

        $response = $this->actingAs($this->picHcs)->get("/files/archive/{$id}/scan_input_form")->assertOk()->assertHeaderMissing('X-Accel-Redirect');
        $this->assertSame(self::PDF, $response->streamedContent());
    }

    public function test_access_follows_the_archive_policy(): void
    {
        Storage::disk('local')->put('archive_scans/scan1.pdf', self::PDF);
        $id = $this->archive($this->hcs);
        $url = "/files/archive/{$id}/scan_input_form";

        $this->get($url)->assertRedirect('/login');
        $this->get("{$url}/download")->assertRedirect('/login');
        $this->actingAs($this->picFat)->get($url)->assertForbidden();
        $this->actingAs($this->picFat)->get("{$url}/download")->assertForbidden();
        $this->actingAs($this->gudang)->get($url)->assertOk();
        $this->actingAs($this->admin)->get($url)->assertOk();
    }

    public function test_log_record_files_use_the_parent_archive(): void
    {
        Storage::disk('public')->put('borrowing_approvals/ok.pdf', self::PDF);
        $logId = DB::table('borrowing_logs')->insertGetId([
            'archive_id' => $this->archive($this->hcs), 'borrower_user_id' => $this->picHcs->id, 'request_date' => now(),
            'purpose' => 'Audit', 'approval_file' => 'borrowing_approvals/ok.pdf', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->picHcs)->get("/files/borrowing/{$logId}/approval_file")->assertOk()
            ->assertHeader('X-Accel-Redirect', '/public/borrowing_approvals/ok.pdf');
        $this->actingAs($this->picFat)->get("/files/borrowing/{$logId}/approval_file")->assertForbidden();
        $this->actingAs($this->picHcs)->get("/files/borrowing/{$logId}/purpose")->assertNotFound();
    }

    public function test_legacy_disks_are_searched_in_order(): void
    {
        Storage::disk('public')->put('archive_scans/old.jpg', 'jpg');
        Storage::disk('legacy_public')->put('archive_scans/older.pdf', self::PDF);
        Storage::disk('local')->put('archive_scans/both.pdf', self::PDF);
        Storage::disk('public')->put('archive_scans/both.pdf', self::PDF);

        $this->actingAs($this->admin)->get('/files/archive/'.$this->archive($this->hcs, 'archive_scans/old.jpg').'/scan_input_form')
            ->assertHeader('X-Accel-Redirect', '/public/archive_scans/old.jpg')->assertHeader('Content-Type', 'image/jpeg');
        $this->actingAs($this->admin)->get('/files/archive/'.$this->archive($this->hcs, 'archive_scans/older.pdf').'/scan_input_form')
            ->assertHeader('X-Accel-Redirect', '/legacy-public/archive_scans/older.pdf');
        $this->actingAs($this->admin)->get('/files/archive/'.$this->archive($this->hcs, 'archive_scans/both.pdf').'/scan_input_form')
            ->assertHeader('X-Accel-Redirect', '/private/archive_scans/both.pdf');
    }

    public function test_missing_file_is_404_without_any_fallback_file(): void
    {
        Storage::disk('local')->put('archive_scans/other-real.pdf', self::PDF);

        $this->actingAs($this->admin)->get('/files/archive/'.$this->archive($this->hcs, 'archive_scans/gone.pdf').'/scan_input_form')
            ->assertNotFound()->assertHeaderMissing('X-Accel-Redirect')->assertSee('Berkas tidak ditemukan di server penyimpanan');
        $this->actingAs($this->admin)->get('/files/archive/'.$this->archive($this->hcs, null).'/scan_input_form')
            ->assertNotFound()->assertHeaderMissing('X-Accel-Redirect');
        $this->actingAs($this->admin)->get('/files/archive/999999/scan_input_form')->assertNotFound();
    }

    public function test_stored_paths_outside_the_upload_folders_are_never_served(): void
    {
        // Real files next to the upload folders (like the Laravel 7 backups in storage/app).
        Storage::disk('local')->put('backup_indraco_1.sqlite', 'db');
        Storage::disk('local')->put('bench/sample.pdf', self::PDF);
        Storage::disk('local')->put('archive_scans/note.txt', 'text');
        Storage::disk('local')->put('archive_scans/scan1.pdf', self::PDF);

        $paths = ['backup_indraco_1.sqlite', 'bench/sample.pdf', 'archive_scans/note.txt', '../.env', 'archive_scans/../backup_indraco_1.sqlite',
            'archive_scans/..\\backup_indraco_1.sqlite', 'archive_scans/%2e%2e/backup_indraco_1.sqlite', '/archive_scans/scan1.pdf',
            'C:/Windows/win.ini', '\\\\server\\share\\x.pdf', 'archive_scans/sub/scan1.pdf', 'archive_scans/scan1.pdf.', 'archive_scans/scan1.pdf::$DATA'];
        foreach ($paths as $path) {
            $this->actingAs($this->admin)->get('/files/archive/'.$this->archive($this->hcs, $path).'/scan_input_form')
                ->assertNotFound()->assertHeaderMissing('X-Accel-Redirect');
        }
    }

    public function test_tampered_urls_are_404(): void
    {
        $id = $this->archive($this->hcs);
        foreach (["/files/archive/{$id}/password", "/files/users/{$this->admin->id}/password", "/files/archive/{$id}/..%2F..%2F.env",
            "/files/archive/{$id}/%2e%2e", '/files/archive/abc/scan_input_form', '/files/stream/archive_scans/scan1.pdf',
            '/files/preview-stream?token='.base64_encode('archive_scans/scan1.pdf')] as $url) {
            $this->actingAs($this->admin)->get($url)->assertNotFound();
        }
    }
}
