<?php

namespace Tests\Feature\Retention;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Warehouse side of a pemusnahan (Laravel 7 approve / reject / complete / uploadBap) and the BAP
 * print (findings #5): transitions, source-status checks, role / department access.
 */
class DestructionProcessTest extends TestCase
{
    use RefreshDatabase;
    use RetentionFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRetentionFixtures();
    }

    public function test_full_flow_approve_then_complete(): void
    {
        $box = $this->expiringBox($this->hcs, 'HCS-01');
        $log = $this->proposed([$box]);

        $this->actingAs($this->gudang)->from('/destructions')->post("/destructions/{$log}/approve")->assertRedirect('/destructions')
            ->assertSessionHas('success', 'Pengajuan pemusnahan berkas (HCS-01) disetujui dan telah masuk ke Antrean Pemusnahan Gudang.');
        $this->assertSame(['approved', $this->gudang->id], [DB::table('destruction_logs')->where('id', $log)->value('approval_status'), (int) DB::table('destruction_logs')->where('id', $log)->value('approved_by_dept_pic_id')]);
        $this->assertSame('pending_destruction', DB::table('archives')->where('id', $box)->value('status'));
        $this->assertSame("Pengajuan pemusnahan berkas 'HCS-01' disetujui oleh {$this->gudang->name} dan dimasukkan ke Antrean Pemusnahan.", DB::table('activity_logs')->where('action', 'DESTRUCTION_APPROVE')->value('description'));

        $this->actingAs($this->gudang)->post("/destructions/{$log}/complete", ['bap_number' => 'BAP/IND/2026/00001', 'destruction_date' => '2026-11-25', 'notes' => 'Disaksikan HRD', 'certificate_file' => $this->pdf('bap.pdf')])
            ->assertSessionHas('success', 'Pemusnahan berkas (HCS-01) telah selesai dieksekusi dengan No. BAP BAP/IND/2026/00001. Slot rak telah dikosongkan dan BAP siap dicetak.');
        $row = DB::table('destruction_logs')->find($log);
        $this->assertSame(['completed', 'BAP/IND/2026/00001', '2026-11-25 00:00:00', 'Disaksikan HRD'], [$row->approval_status, $row->bap_number, $row->destruction_date, $row->notes]);
        Storage::disk('local')->assertExists($row->certificate_file);
        $archive = DB::table('archives')->find($box);
        $this->assertSame(['destroyed', null, null], [$archive->status, $archive->warehouse_location_id, $archive->warehouse_rack_slot_id]);
        $this->assertSame('empty', DB::table('warehouse_rack_slots')->where('slot_number', $box)->value('status'));
        $this->assertSame(1, (int) DB::table('warehouse_locations')->where('id', $this->location)->value('current_box_count'));
        $activity = DB::table('activity_logs')->where('action', 'DESTRUCTION_EXECUTE')->first();
        $this->assertSame(["Pemusnahan berkas fisik 'HCS-01' (Box: IND/HCS-01) selesai dieksekusi dengan No. BAP BAP/IND/2026/00001.", 'BAP/IND/2026/00001'], [$activity->description, $activity->reference_id]);
    }

    public function test_source_status_is_checked(): void
    {
        $box = $this->expiringBox($this->hcs, 'HCS-01');
        $log = $this->proposed([$box]);
        $this->actingAs($this->admin)->post("/destructions/{$log}/complete", ['bap_number' => 'B1', 'destruction_date' => '2026-11-25'])
            ->assertSessionHas('error', 'Pengajuan pemusnahan ini sudah diproses (status: Pengajuan Pemusnahan). Muat ulang halaman.');
        $this->actingAs($this->admin)->post("/destructions/{$log}/upload-bap", ['certificate_file' => $this->pdf()])->assertSessionHas('error');
        $this->actingAs($this->admin)->post("/destructions/{$log}/approve")->assertSessionHas('success');
        $this->actingAs($this->admin)->post("/destructions/{$log}/approve")
            ->assertSessionHas('error', 'Pengajuan pemusnahan ini sudah diproses (status: Antrean Pemusnahan). Muat ulang halaman.');
        $this->actingAs($this->admin)->post("/destructions/{$log}/complete", ['bap_number' => 'B1', 'destruction_date' => '2026-11-25'])->assertSessionHas('success');
        $this->actingAs($this->admin)->post("/destructions/{$log}/reject", ['rejection_note' => 'x'])
            ->assertSessionHas('error', 'Pengajuan pemusnahan ini sudah diproses (status: Telah Dimusnahkan). Muat ulang halaman.');
        $this->assertSame('destroyed', DB::table('archives')->where('id', $box)->value('status'));
        $this->assertSame([], Storage::disk('local')->files('bap_certificates'), 'refused uploads leave no file');
    }

    public function test_reject_returns_the_box_to_the_warehouse(): void
    {
        $box = $this->expiringBox($this->hcs, 'HCS-01');
        $log = $this->proposed([$box]);
        $this->actingAs($this->gudang)->post("/destructions/{$log}/reject", ['rejection_note' => ''])
            ->assertSessionHasErrors(['rejection_note' => 'Alasan penolakan pemusnahan wajib diisi.']);
        $this->actingAs($this->gudang)->post("/destructions/{$log}/reject", ['rejection_note' => str_repeat('a', 501)])
            ->assertSessionHasErrors(['rejection_note' => 'Alasan penolakan maksimal 500 karakter.']);
        $this->actingAs($this->gudang)->post("/destructions/{$log}/reject", ['rejection_note' => 'Belum ditandatangani'])
            ->assertSessionHas('info', 'Pengajuan pemusnahan berkas berhasil ditolak. Status berkas dikembalikan normal di gudang.');
        $row = DB::table('destruction_logs')->find($log);
        $this->assertSame(['rejected', 'Belum ditandatangani'], [$row->approval_status, $row->approval_notes]);
        $this->assertSame('in_warehouse', DB::table('archives')->where('id', $box)->value('status'));
        $this->assertSame("Pengajuan pemusnahan berkas 'HCS-01' ditolak oleh {$this->gudang->name}. Alasan: Belum ditandatangani", DB::table('activity_logs')->where('action', 'DESTRUCTION_REJECTED')->value('description'));
    }

    public function test_complete_validation_matches_laravel_7(): void
    {
        $log = $this->proposed([$this->expiringBox($this->hcs, 'HCS-01')]);
        $this->actingAs($this->admin)->post("/destructions/{$log}/approve");
        $this->actingAs($this->admin)->post("/destructions/{$log}/complete", ['bap_number' => '', 'destruction_date' => '', 'certificate_file' => $this->pdf('big.pdf', 501)])
            ->assertSessionHasErrors(['bap_number' => 'Nomor BAP resmi wajib diisi oleh Gudang.', 'destruction_date' => 'Tanggal pelaksanaan fisik pemusnahan wajib diisi.', 'certificate_file' => 'Ukuran berkas sertifikat BAP maksimal 500 KB.']);
        $this->actingAs($this->admin)->post("/destructions/{$log}/complete", ['bap_number' => 'B1', 'destruction_date' => '2026-11-25', 'certificate_file' => $this->upload('bap.php', '<?php echo 1;', 'application/x-php')])
            ->assertSessionHasErrors('certificate_file');
        $this->assertSame('approved', DB::table('destruction_logs')->where('id', $log)->value('approval_status'));
    }

    public function test_upload_bap_after_completion(): void
    {
        $log = $this->proposed([$this->expiringBox($this->hcs, 'HCS-01')]);
        $this->actingAs($this->admin)->post("/destructions/{$log}/approve");
        $this->actingAs($this->admin)->post("/destructions/{$log}/complete", ['bap_number' => 'BAP/1', 'destruction_date' => '2026-11-25']);
        $this->actingAs($this->gudang)->post("/destructions/{$log}/upload-bap", [])->assertSessionHasErrors(['certificate_file' => 'Wajib memilih file sertifikat BAP resmi.']);
        $this->actingAs($this->gudang)->post("/destructions/{$log}/upload-bap", ['certificate_file' => $this->pdf('bap.pdf')])
            ->assertSessionHas('success', 'Berkas sertifikat BAP resmi (No: BAP/1) berhasil diunggah. Departemen kini dapat mengunduh dan mencetak dokumen BAP.');
        Storage::disk('local')->assertExists((string) DB::table('destruction_logs')->where('id', $log)->value('certificate_file'));
        $this->assertSame(1, DB::table('activity_logs')->where('action', 'DESTRUCTION_BAP_UPLOADED')->count());
    }

    public function test_pic_cannot_process(): void
    {
        $log = $this->proposed([$this->expiringBox($this->hcs, 'HCS-01')]);
        foreach (['approve', 'reject', 'complete', 'upload-bap'] as $action) {
            $this->actingAs($this->picHcs)->post("/destructions/{$log}/{$action}", ['rejection_note' => 'x', 'bap_number' => 'B', 'destruction_date' => '2026-11-25'])->assertForbidden();
        }
        $this->assertSame('pending', DB::table('destruction_logs')->where('id', $log)->value('approval_status'));
    }

    public function test_bap_print_follows_the_archive_department(): void
    {
        $box = $this->expiringBox($this->fat, 'FAT-01');
        $log = $this->proposed([$box], $this->picFat);
        $this->actingAs($this->picFat)->from('/destructions')->get("/destructions/bap/{$log}")
            ->assertRedirect('/destructions')->assertSessionHas('error', 'File Berita Acara Pemusnahan (BAP) belum diunggah oleh Gudang.');
        $this->actingAs($this->picHcs)->get("/destructions/bap/{$log}")->assertForbidden();

        $this->actingAs($this->gudang)->post("/destructions/{$log}/approve");
        $this->actingAs($this->gudang)->post("/destructions/{$log}/complete", ['bap_number' => 'BAP/IND/2026/00077', 'destruction_date' => '2026-11-24', 'certificate_file' => $this->pdf('bap.pdf')]);

        $this->actingAs($this->picFat)->get("/destructions/bap/{$log}")->assertOk()
            ->assertSee('BERITA ACARA PEMUSNAHAN ARSIP (BAP)')->assertSee('Nomor BAP: BAP/IND/2026/00077')
            ->assertSee('Selasa, 24 November 2026')->assertSee('FAT-01')->assertSee('Finance (FAT)')->assertSee('1 Tahun')
            ->assertSee('Pencacahan Mesin Industrial Paper Shredder')->assertSee('Kurator Gudang Arsip')->assertSee('PIC_FINANCE')
            ->assertSee('css/pages/retention-print.css', false)->assertDontSee('prefs.js', false);
        $this->actingAs($this->admin)->get("/destructions/bap/{$log}")->assertOk();
        $this->actingAs($this->gudang)->get("/destructions/bap/{$log}")->assertOk();
        $this->actingAs($this->picHcs)->get("/destructions/bap/{$log}")->assertForbidden();
        $this->assertSame(3, DB::table('activity_logs')->where('action', 'DESTRUCTION_BAP_PRINT')->count());

        $this->actingAs($this->picHcs)->get("/files/destruction/{$log}/certificate_file")->assertForbidden();
        $this->actingAs($this->picFat)->get("/files/destruction/{$log}/certificate_file/preview-data")->assertOk()->assertJsonPath('mime', 'application/pdf');
    }
}
