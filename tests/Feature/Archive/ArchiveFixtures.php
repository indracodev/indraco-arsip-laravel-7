<?php

namespace Tests\Feature\Archive;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Shared setup for the box submission tests: departments HCS (retention 3) and FAT (120-month
 * rule), one sub-department + Master Berkas for HCS, users per role, a valid submission.
 * Time is frozen at 2026-11-24 10:00 (Asia/Jakarta) so period values are fixed.
 */
trait ArchiveFixtures
{
    protected int $hcs;

    protected int $fat;

    protected int $hcsSub;

    protected User $picHcs;

    protected User $picFat;

    protected User $admin;

    protected User $gudang;

    protected function setUpArchiveFixtures(): void
    {
        $this->travelTo(Carbon::parse('2026-11-24 10:00:00'));
        Storage::fake('local');
        $this->hcs = DB::table('departments')->insertGetId(['code' => 'HCS', 'name' => 'Human Capital', 'retention_years' => 3, 'is_active' => 1]);
        $this->fat = DB::table('departments')->insertGetId(['code' => 'FAT', 'name' => 'Finance', 'retention_years' => 5, 'is_active' => 1]);
        $this->hcsSub = DB::table('sub_departments')->insertGetId(['department_id' => $this->hcs, 'code' => 'PAY', 'name' => 'Payroll', 'retention_years' => 2, 'is_active' => 1]);
        DB::table('master_archives')->insert(['department_id' => $this->hcs, 'code' => 'K01', 'name' => 'Kontrak Kerja', 'document_type' => 'Kepegawaian', 'is_active' => 1]);
        $this->picHcs = User::factory()->create(['name' => 'PIC_HCS', 'role' => 'pic_dept', 'department_id' => $this->hcs]);
        $this->picFat = User::factory()->create(['name' => 'PIC_FINANCE', 'role' => 'pic_dept', 'department_id' => $this->fat]);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->gudang = User::factory()->create(['role' => 'pic_gudang']);
    }

    /**
     * A valid two-box submission ("Simpan & Ajukan") with a scan form.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function submission(array $overrides = []): array
    {
        return array_replace([
            'company_name' => 'PT INDRACO GLOBAL INDONESIA',
            'sub_department_id' => '',
            'tgl_penyerahan' => '2026-11-20',
            'submit_action' => 'submit',
            'scan_input_form' => $this->pdf(),
            'boxes' => [
                ['title' => 'Box Kontrak 2026', 'sub_department_id' => '', 'document_type' => 'Kepegawaian', 'physical_condition' => 'Baik / Box Karton Standar TB 30g', 'periode' => '6 Bulan',
                    'items' => [
                        ['document_name' => 'Kontrak Kerja', 'period_start' => '2026-01', 'period_end' => '2026-03', 'notes' => 'No 1-50'],
                        ['document_name' => 'Kontrak Kerja', 'period_start' => '2026-04', 'period_end' => '2026-04', 'notes' => 'No 51-60'],
                    ]],
                ['title' => 'Box Kontrak Payroll', 'sub_department_id' => (string) $this->hcsSub, 'document_type' => 'Kepegawaian', 'physical_condition' => 'Baik / Box Karton Standar TB 30g', 'periode' => '12 Bulan',
                    'items' => [['document_name' => 'Kontrak Kerja', 'period_start' => '2025-12', 'period_end' => '2026-12', 'notes' => 'Arsip payroll']]],
            ],
        ], $overrides);
    }

    /** A real (tiny) PDF: the type is detected from the content. */
    protected function pdf(string $name = 'scan.pdf', int $kilobytes = 1): UploadedFile
    {
        return $this->upload($name, "%PDF-1.4\n%".str_repeat('0', $kilobytes * 1024)."\n%%EOF\n", 'application/pdf');
    }

    protected function upload(string $name, string $content, string $clientMime): UploadedFile
    {
        // A real file (the type is detected from the content), removed when the test ends.
        $path = (string) tempnam(sys_get_temp_dir(), 'dms');
        file_put_contents($path, $content);
        $this->beforeApplicationDestroyed(fn () => @unlink($path));

        return new UploadedFile($path, $name, $clientMime, null, true);
    }

    /** Save a draft through the form and return its id. @param array<string, mixed> $overrides */
    protected function draftId(User $user, array $overrides = []): int
    {
        $payload = $this->submission(array_replace(['submit_action' => 'draft', 'scan_input_form' => null], $overrides));
        if ($user->department_id !== $this->hcs) {
            $payload['boxes'][1]['sub_department_id'] = '';
        }
        $this->actingAs($user)->post('/archives', $payload)->assertRedirect('/archives');

        return (int) DB::table('archives')->where('status', 'draft')->max('id');
    }

    protected function updatedAt(int $archiveId): string
    {
        return (string) DB::table('archives')->where('id', $archiveId)->value('updated_at');
    }
}
