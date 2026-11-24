<?php

namespace Tests\Feature\Retention;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Perpanjangan masa simpan (Laravel 7 HandlesArchiveExtensions): request, FAT rule by department
 * code only (ASK 12 / B12), ACC / Tolak with source-status checks, extension print (findings #5).
 */
class ExtensionTest extends TestCase
{
    use RefreshDatabase;
    use RetentionFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRetentionFixtures();
    }

    public function test_request_creates_a_pending_log_and_marks_the_box(): void
    {
        $box = $this->expiringBox($this->hcs, 'HCS-01', '2026-10-31');
        $this->actingAs($this->picHcs)->post('/destructions/extend', $this->extension([$box], ['additional_months' => 1, 'scan_extension_form' => $this->pdf('form.pdf')]))
            ->assertRedirect(route('destructions.index', ['tab' => 'extensions']))
            ->assertSessionHas('success', 'Pengajuan perpanjangan masa simpan 1 box berkas (IND/HCS-01) sebanyak +1 Bulan berhasil dikirim. Menunggu pengecekan dokumen & persetujuan (ACC) dari Admin Gudang.');

        $log = DB::table('archive_extension_logs')->first();
        $this->assertSame([1, 0.1, '2026-10-31', '2026-11-30', 'Audit pajak', 'pending', $this->picHcs->id],
            [(int) $log->additional_months, (float) $log->additional_years, substr($log->previous_expiry_date, 0, 10), substr($log->new_expiry_date, 0, 10), $log->extension_reason, $log->approval_status, (int) $log->proposed_by_user_id]);
        Storage::disk('local')->assertExists($log->scan_extension_form);
        $archive = DB::table('archives')->find($box);
        $this->assertSame(['pending_extension', 'Audit pajak', $log->scan_extension_form], [$archive->status, $archive->extension_reason, $archive->scan_extension_form]);
        $this->assertSame("Pengajuan perpanjangan masa retensi berkas 'HCS-01' (Box: IND/HCS-01) sebanyak +1 Bulan diajukan oleh PIC_HCS dan menunggu verifikasi/ACC Gudang.",
            DB::table('activity_logs')->where('action', 'RETENTION_EXTEND_PROPOSED')->value('description'));
    }

    public function test_fat_rule_by_department_code(): void
    {
        $hcsBox = $this->expiringBox($this->hcs, 'HCS-01');
        $fatBox = $this->expiringBox($this->fat, 'FAT-01');

        $this->actingAs($this->picHcs)->post('/destructions/extend', $this->extension([$hcsBox], ['additional_months' => 61]))
            ->assertSessionHasErrors(['additional_months' => 'Tambahan masa simpan maksimal 60 bulan (5 tahun).']);
        $this->actingAs($this->picFat)->post('/destructions/extend', $this->extension([$fatBox], ['additional_months' => 121]))
            ->assertSessionHasErrors(['additional_months' => 'Tambahan masa simpan maksimal 120 bulan (10 tahun).']);
        $this->actingAs($this->picFat)->post('/destructions/extend', $this->extension([$fatBox], ['additional_months' => 120, 'additional_years' => 10]))->assertSessionHasNoErrors();
        $this->assertSame('2036-11-30', substr((string) DB::table('archive_extension_logs')->value('new_expiry_date'), 0, 10));

        // The user name no longer matters (B12): a "FIN" name in HCS keeps the 60-month limit.
        $this->picHcs->forceFill(['name' => 'PIC FIN HCS'])->save();
        $this->actingAs($this->picHcs)->post('/destructions/extend', $this->extension([$hcsBox], ['additional_months' => 72]))->assertSessionHasErrors('additional_months');
        // Admin: the box's department decides.
        $other = $this->expiringBox($this->fat, 'FAT-02');
        $this->actingAs($this->admin)->post('/destructions/extend', $this->extension([$other], ['additional_months' => 96]))->assertSessionHasNoErrors();
    }

    public function test_validation_messages_match_laravel_7(): void
    {
        $box = $this->expiringBox($this->hcs, 'HCS-01');
        $this->actingAs($this->picHcs)->post('/destructions/extend', $this->extension([], ['archive_ids' => []]))
            ->assertSessionHasErrors(['archive_ids' => 'Minimal satu berkas arsip wajib dipilih untuk perpanjangan masa simpan.']);
        $this->actingAs($this->picHcs)->post('/destructions/extend', $this->extension([$box], ['extension_reason' => '']))
            ->assertSessionHasErrors(['extension_reason' => 'Alasan perpanjangan masa simpan wajib diisi.']);
        $this->actingAs($this->picHcs)->post('/destructions/extend', $this->extension([$box], ['additional_months' => null, 'additional_years' => null]))
            ->assertSessionHasErrors(['additional_months' => 'Tambahan masa simpan harus antara 1 sampai 60 bulan.']);
        $this->actingAs($this->picHcs)->post('/destructions/extend', $this->extension([$box], ['scan_extension_form' => $this->pdf('big.pdf', 501)]))
            ->assertSessionHasErrors(['scan_extension_form' => 'Ukuran file formulir perpanjangan maksimal 500 KB.']);
        $this->actingAs($this->picHcs)->post('/destructions/extend', $this->extension([$box], ['scan_extension_form' => $this->upload('form.php', '<?php echo 1;', 'application/x-php')]))
            ->assertSessionHasErrors('scan_extension_form');
        $this->assertSame(0, DB::table('archive_extension_logs')->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
        // Years only (Laravel 7 fallback): 2 years = 24 months.
        $this->actingAs($this->picHcs)->post('/destructions/extend', $this->extension([$box], ['additional_months' => null, 'additional_years' => 2]))->assertSessionHasNoErrors();
        $this->assertSame(24, (int) DB::table('archive_extension_logs')->value('additional_months'));
    }

    public function test_candidates_access_and_double_submit(): void
    {
        $verif = $this->expiringBox($this->hcs, 'HCS-VERIF', status: 'pending_verification');
        $this->actingAs($this->picHcs)->post('/destructions/extend', $this->extension([$verif]))
            ->assertSessionHasErrors(['additional_months' => "Berkas 'HCS-VERIF' tidak tersimpan di gudang (status: Menunggu Verifikasi), sehingga belum dapat diajukan pemusnahan/perpanjangan."]);
        $box = $this->expiringBox($this->hcs, 'HCS-01');
        $this->extended([$box]);
        $this->actingAs($this->picHcs)->post('/destructions/extend', $this->extension([$box]))->assertSessionHasErrors('additional_months');
        $this->actingAs($this->picHcs)->post('/destructions/propose', $this->proposal([$box]))->assertSessionHasErrors('archive_ids');
        $this->assertSame(1, DB::table('archive_extension_logs')->count());

        $fatBox = $this->expiringBox($this->fat, 'FAT-01');
        $this->actingAs($this->picHcs)->post('/destructions/extend', $this->extension([$fatBox]))->assertForbidden();
        $this->actingAs($this->gudang)->get('/destructions/extend')->assertForbidden();
        $this->actingAs($this->gudang)->post('/destructions/extend', $this->extension([$fatBox]))->assertForbidden();
    }

    public function test_approve_applies_the_new_retention_once(): void
    {
        // Laravel 7 row #94 (FAT): periode "12 Bulan", expiry 2034-12-31, +120 months.
        $box = $this->expiringBox($this->fat, 'FAT-01', '2034-12-31');
        $log = $this->extended([$box], $this->picFat, 120);
        $this->actingAs($this->picFat)->post("/destructions/extensions/{$log}/approve")->assertForbidden();

        $this->actingAs($this->gudang)->post("/destructions/extensions/{$log}/approve")
            ->assertSessionHas('success', "Pengajuan perpanjangan berkas 'FAT-01' berhasil di-ACC oleh Gudang. Masa simpan resmi diperpanjang hingga Dec 2044 dan status berkas kembali aktif di gudang.");
        $archive = DB::table('archives')->find($box);
        $this->assertSame(['in_warehouse', '2044-12-31', 11, '11 Tahun'], [$archive->status, substr($archive->retention_expiry_date, 0, 10), (int) $archive->retention_years, $archive->periode]);
        $row = DB::table('archive_extension_logs')->find($log);
        $this->assertSame(['approved', $this->gudang->id], [$row->approval_status, (int) $row->approved_by_user_id]);
        $this->assertNotNull($row->approved_at);
        $this->assertSame("Perpanjangan masa retensi berkas 'FAT-01' (Box: IND/FAT-01) sebanyak +10 Tahun disetujui (ACC) oleh {$this->gudang->name}. Masa simpan baru: Dec 2044.",
            DB::table('activity_logs')->where('action', 'RETENTION_EXTEND_APPROVED')->value('description'));

        $this->actingAs($this->gudang)->post("/destructions/extensions/{$log}/approve")
            ->assertSessionHas('error', 'Pengajuan perpanjangan ini sudah diproses (status: Disetujui Gudang). Muat ulang halaman.');
        $this->assertSame('2044-12-31', substr((string) DB::table('archives')->where('id', $box)->value('retention_expiry_date'), 0, 10));
    }

    public function test_reject_returns_the_box_with_the_default_reason(): void
    {
        $box = $this->expiringBox($this->hcs, 'HCS-01');
        $log = $this->extended([$box]);
        $this->actingAs($this->admin)->post("/destructions/extensions/{$log}/reject", ['rejection_note' => ''])
            ->assertSessionHas('info', 'Pengajuan perpanjangan berkas ditolak. Status berkas kembali normal di gudang.');
        $this->assertSame(['rejected', 'Dokumen / surat permohonan perpanjangan tidak memenuhi syarat.'],
            array_values((array) DB::table('archive_extension_logs')->where('id', $log)->first(['approval_status', 'rejection_note'])));
        $this->assertSame(['in_warehouse', '2026-11-30'], [DB::table('archives')->where('id', $box)->value('status'), substr((string) DB::table('archives')->where('id', $box)->value('retention_expiry_date'), 0, 10)]);
        $this->actingAs($this->admin)->post("/destructions/extensions/{$log}/approve")->assertSessionHas('error');
    }

    public function test_extension_print_follows_the_archive_department(): void
    {
        $box = $this->expiringBox($this->hcs, 'HCS-01');
        $this->extended([$box]);
        $this->actingAs($this->picHcs)->get("/destructions/extend-print/{$box}")->assertOk()
            ->assertSee('FORMULIR PERMOHONAN PERPANJANGAN MASA SIMPAN DOKUMEN')->assertSee('No. Box Container: IND/HCS-01')
            ->assertSee('Audit pajak')->assertSee('Human Capital (HCS)')->assertSee('Gudang Utama - R01 / S1');
        $this->actingAs($this->gudang)->get("/destructions/extend-print/{$box}")->assertOk();
        $this->actingAs($this->picFat)->get("/destructions/extend-print/{$box}")->assertForbidden();
    }

    public function test_extend_form_and_print_form(): void
    {
        $box = $this->expiringBox($this->fat, 'FAT-01', '2034-12-31');
        $this->actingAs($this->picFat)->get("/destructions/extend/{$box}")->assertOk()
            ->assertSee('Formulir Pengajuan Perpanjangan Masa Simpan Box Arsip [Form Expiry]')->assertSee('TAMBAHAN MASA SIMPAN (BULAN)')
            ->assertSee('ALASAN PERPANJANGAN MASA SIMPAN DOKUMEN')->assertSee('Scan Formulir Perpanjangan Masa Simpan')->assertSee('"userMax":120', false);
        $response = $this->actingAs($this->picFat)->get('/destructions/form-perpanjangan?'.http_build_query(['archive_ids' => [$box], 'months' => 24, 'reason' => 'Audit']))->assertOk()
            ->assertSee('FAT-01')->assertSee('24 Bln')->assertSee('12-2036');
        $this->assertSame(['Perpanjangan', 'Perpanjangan masa simpan (24 Bulan). Alasan: Audit'], [$response->viewData('fields')['tipe'], $response->viewData('fields')['catatan']]);
    }
}
