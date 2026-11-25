<?php

namespace Tests\Feature\Retention;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * PIC / admin "Pengajuan Pemusnahan" (Laravel 7 DestructionController@proposeForm / @propose):
 * validation parity, candidate check (B2), department / role access, admin immediate completion.
 */
class DestructionProposalTest extends TestCase
{
    use RefreshDatabase;
    use RetentionFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRetentionFixtures();
    }

    public function test_pic_proposal_creates_one_pending_log_per_box(): void
    {
        $a = $this->expiringBox($this->hcs, 'HCS-01');
        $b = $this->expiringBox($this->hcs, 'HCS-02');

        $this->actingAs($this->picHcs)->post('/destructions/propose', $this->proposal([$a, $b]))
            ->assertRedirect(route('destructions.index', ['tab' => 'destructions']))
            ->assertSessionHas('success', 'Pengajuan pemusnahan 2 box berkas (IND/HCS-01, IND/HCS-02) berhasil dikirim. Menunggu verifikasi dan persetujuan dari PIC Gudang.');

        $logs = DB::table('destruction_logs')->orderBy('id')->get();
        $this->assertCount(2, $logs);
        $log = $logs[0];
        $this->assertSame(['(Menunggu Penerbitan BAP)', 'pending', 'Pencacahan Mesin Industrial Paper Shredder', 'Masa simpan habis', $this->picHcs->id, $this->picHcs->id, 1],
            [$log->bap_number, $log->approval_status, $log->method, $log->notes, (int) $log->proposed_by_user_id, (int) $log->department_approval_by, (int) $log->is_approval_uploaded]);
        $this->assertNull($log->approved_by_dept_pic_id);
        $this->assertSame($log->approval_file, $log->scan_approval_destruction);
        $this->assertSame($log->approval_file, $logs[1]->approval_file, 'one scan for all boxes');
        $this->assertStringStartsWith('destruction_approvals/', $log->approval_file);
        Storage::disk('local')->assertExists($log->approval_file);

        $this->assertSame(['pending_destruction', 'pending_destruction'], DB::table('archives')->whereIn('id', [$a, $b])->pluck('status')->all());
        $this->assertNotNull(DB::table('archives')->where('id', $a)->value('warehouse_rack_slot_id'), 'the box stays in its rack');

        $activity = DB::table('activity_logs')->where('action', 'DESTRUCTION_PROPOSE')->orderBy('id')->first();
        $this->assertSame(['PEMUSNAHAN_RETENSI', "Pengajuan usulan pemusnahan berkas 'HCS-01' (Box: IND/HCS-01) oleh PIC_HCS", 'IND/HCS-01'],
            [$activity->module, $activity->description, $activity->reference_id]);
        $this->assertSame(['archive_id' => $a, 'bap_number' => '(Menunggu Penerbitan BAP)', 'method' => 'Pencacahan Mesin Industrial Paper Shredder', 'destruction_date' => '2026-11-24', 'status' => 'pending'],
            json_decode($activity->properties, true));
        $event = DB::table('system_events')->where('event_type', 'destruction_created')->first();
        $this->assertSame(['bap_number' => '(Menunggu Penerbitan BAP)', 'archive_id' => $a, 'method' => 'Pencacahan Mesin Industrial Paper Shredder'], json_decode($event->payload, true));
        $this->assertSame($this->hcs, (int) $event->department_id);
    }

    public function test_single_box_from_the_route_fallback_field(): void
    {
        $box = $this->expiringBox($this->hcs, 'HCS-01');
        $this->actingAs($this->picHcs)->post('/destructions/propose', $this->proposal([], ['archive_ids' => null, 'archive_id' => $box, 'approval_file' => null, 'scan_approval_destruction' => $this->pdf()]))
            ->assertSessionHas('success', 'Pengajuan pemusnahan 1 box berkas (IND/HCS-01) berhasil dikirim. Menunggu verifikasi dan persetujuan dari PIC Gudang.');
    }

    public function test_pic_bap_number_is_ignored(): void
    {
        $box = $this->expiringBox($this->hcs, 'HCS-01');
        $this->actingAs($this->picHcs)->post('/destructions/propose', $this->proposal([$box], ['bap_number' => 'BAP/IND/2026/1']))->assertSessionHasNoErrors();
        $this->assertSame(['(Menunggu Penerbitan BAP)', 'pending'], array_values((array) DB::table('destruction_logs')->first(['bap_number', 'approval_status'])));
    }

    /** @return array<string, array{array<string, mixed>, string, string}> */
    public static function invalidInput(): array
    {
        return [
            'no box' => [['archive_ids' => []], 'archive_ids', 'Pilih minimal satu berkas arsip yang akan dimusnahkan terlebih dahulu.'],
            'no date' => [['destruction_date' => ''], 'destruction_date', 'Tanggal pelaksanaan pemusnahan wajib diisi.'],
            'no method' => [['method' => ''], 'method', 'Metode fisik pemusnahan wajib dipilih.'],
            'no approval' => [['approval_file' => null], 'approval_file', 'Wajib mengunggah berkas surat persetujuan (approval) pemusnahan.'],
        ];
    }

    /** @param array<string, mixed> $overrides */
    #[DataProvider('invalidInput')]
    public function test_validation_messages_match_laravel_7(array $overrides, string $field, string $message): void
    {
        $box = $this->expiringBox($this->hcs, 'HCS-01');
        $this->actingAs($this->picHcs)->post('/destructions/propose', $this->proposal([$box], $overrides))->assertSessionHasErrors([$field => $message]);
        $this->assertSame(0, DB::table('destruction_logs')->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    /** @return array<string, array{string, string, string}> */
    public static function rejectedUploads(): array
    {
        return [
            'php script' => ['approval.php', '<?php echo 1;', 'application/x-php'],
            'double extension' => ['approval.pdf.php', "%PDF-1.4\n%%EOF\n", 'application/pdf'],
            'text disguised as pdf' => ['approval.pdf', 'plain text', 'application/pdf'],
            'html disguised as png' => ['approval.png', '<html></html>', 'image/png'],
        ];
    }

    #[DataProvider('rejectedUploads')]
    public function test_dangerous_uploads_are_rejected(string $name, string $content, string $clientMime): void
    {
        $box = $this->expiringBox($this->hcs, 'HCS-01');
        $this->actingAs($this->picHcs)->post('/destructions/propose', $this->proposal([$box], ['approval_file' => $this->upload($name, $content, $clientMime)]))
            ->assertSessionHasErrors('approval_file');
        $this->actingAs($this->picHcs)->post('/destructions/propose', $this->proposal([$box], ['approval_file' => $this->pdf('big.pdf', 501)]))
            ->assertSessionHasErrors(['approval_file' => 'Ukuran file surat persetujuan maksimal 500 KB.']);
        $this->assertSame(0, DB::table('destruction_logs')->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    /** @return array<string, array{string, string}> */
    public static function notCandidates(): array
    {
        $busy = "Berkas 'HCS-01' sedang dalam proses pengajuan pemusnahan/perpanjangan atau telah dimusnahkan.";

        return [
            'waiting for verification (B2)' => ['pending_verification', "Berkas 'HCS-01' tidak tersimpan di gudang (status: Menunggu Verifikasi), sehingga belum dapat diajukan pemusnahan/perpanjangan."],
            'draft (B2)' => ['draft', "Berkas 'HCS-01' tidak tersimpan di gudang (status: Draft Usulan), sehingga belum dapat diajukan pemusnahan/perpanjangan."],
            'withdrawn' => ['taken', "Berkas 'HCS-01' tidak tersimpan di gudang (status: Telah Diambil (Permanen)), sehingga belum dapat diajukan pemusnahan/perpanjangan."],
            'already proposed' => ['pending_destruction', $busy],
            'extension pending' => ['pending_extension', $busy],
            'destroyed' => ['destroyed', $busy],
        ];
    }

    #[DataProvider('notCandidates')]
    public function test_only_boxes_in_the_warehouse_can_be_proposed(string $status, string $message): void
    {
        $box = $this->expiringBox($this->hcs, 'HCS-01', status: $status);
        $this->actingAs($this->picHcs)->post('/destructions/propose', $this->proposal([$box]))->assertSessionHasErrors(['archive_ids' => $message]);
        $this->assertSame(0, DB::table('destruction_logs')->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_double_submit_creates_one_log(): void
    {
        $box = $this->expiringBox($this->hcs, 'HCS-01');
        $this->proposed([$box]);
        $this->actingAs($this->picHcs)->post('/destructions/propose', $this->proposal([$box]))
            ->assertSessionHasErrors(['archive_ids' => "Berkas 'HCS-01' sedang dalam proses pengajuan pemusnahan/perpanjangan atau telah dimusnahkan."]);
        $this->assertSame(1, DB::table('destruction_logs')->count());
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_rejected_box_can_be_proposed_again(): void
    {
        $box = $this->expiringBox($this->hcs, 'HCS-01');
        $log = $this->proposed([$box]);
        $this->actingAs($this->gudang)->post("/destructions/{$log}/reject", ['rejection_note' => 'Form belum ditandatangani'])->assertSessionHas('info');
        $this->proposed([$box]);
        $this->assertSame(['rejected', 'pending'], DB::table('destruction_logs')->orderBy('id')->pluck('approval_status')->all());
    }

    public function test_other_department_and_pic_gudang_are_forbidden(): void
    {
        $fatBox = $this->expiringBox($this->fat, 'FAT-01');
        $this->actingAs($this->picHcs)->post('/destructions/propose', $this->proposal([$fatBox]))->assertForbidden();
        $this->actingAs($this->picHcs)->get("/destructions/propose/{$fatBox}")->assertOk()->assertDontSee('FAT-01');
        $this->actingAs($this->gudang)->get('/destructions/propose')->assertForbidden();
        $this->actingAs($this->gudang)->post('/destructions/propose', $this->proposal([$fatBox]))->assertForbidden();
        $this->assertSame(0, DB::table('destruction_logs')->count());
    }

    public function test_admin_with_a_bap_number_completes_at_once(): void
    {
        $box = $this->expiringBox($this->fat, 'FAT-01');
        $this->actingAs($this->admin)->post('/destructions/propose', $this->proposal([$box], ['bap_number' => 'BAP/IND/2026/00099', 'certificate_file' => $this->pdf('bap.pdf')]))
            ->assertSessionHas('success', 'Proses pemusnahan 1 box berkas (IND/FAT-01) berhasil disahkan dengan No. BAP BAP/IND/2026/00099.');

        $log = DB::table('destruction_logs')->first();
        $this->assertSame(['BAP/IND/2026/00099', 'completed', $this->admin->id], [$log->bap_number, $log->approval_status, (int) $log->approved_by_dept_pic_id]);
        $this->assertStringStartsWith('bap_certificates/', $log->certificate_file);
        $archive = DB::table('archives')->find($box);
        $this->assertSame(['destroyed', null, null], [$archive->status, $archive->warehouse_location_id, $archive->warehouse_rack_slot_id]);
        $this->assertSame('empty', DB::table('warehouse_rack_slots')->where('slot_number', $box)->value('status'));
        $this->assertSame(1, (int) DB::table('warehouse_locations')->where('id', $this->location)->value('current_box_count'));
        $this->assertSame("Pemusnahan berkas 'FAT-01' (Box: IND/FAT-01) disahkan dengan No. BAP BAP/IND/2026/00099",
            DB::table('activity_logs')->where('action', 'DESTRUCTION_COMPLETE')->value('description'));
    }

    public function test_propose_form_offers_only_own_candidates_with_laravel_7_fields(): void
    {
        $this->expiringBox($this->hcs, 'HCS-01');
        $this->expiringBox($this->hcs, 'HCS-VERIF', status: 'pending_verification');
        $this->expiringBox($this->fat, 'FAT-01');

        $this->actingAs($this->picHcs)->get('/destructions/propose')->assertOk()
            ->assertSee('Formulir Pengajuan Usulan Pemusnahan Box Arsip')
            ->assertSee('HCS-01')->assertDontSee('HCS-VERIF')->assertDontSee('FAT-01')
            ->assertSee('TANGGAL PELAKSANAAN PEMUSNAHAN')->assertSee('METODE FISIK PEMUSNAHAN')
            ->assertSee('Pembakaran Standard Suhu Tinggi (Incinerator)')->assertSee('CATATAN / ALASAN PEMUSNAHAN BERKAS')
            ->assertSee('WAJIB UNTUK PEMUSNAHAN')->assertDontSee('NOMOR BERITA ACARA (BAP)')->assertDontSee('name="certificate_file"', false);

        $this->actingAs($this->admin)->get('/destructions/propose')->assertOk()
            ->assertSee('Formulir Pengesahan / Pemusnahan Dokumen Arsip (BAP)')->assertSee('NOMOR BERITA ACARA (BAP)')->assertSee('name="certificate_file"', false);
    }

    public function test_form_pemusnahan_print_lists_only_visible_boxes(): void
    {
        $box = $this->expiringBox($this->hcs, 'HCS-01');
        $fatBox = $this->expiringBox($this->fat, 'FAT-01');
        $response = $this->actingAs($this->picHcs)->get('/destructions/form-pemusnahan?'.http_build_query(['archive_ids' => [$box, $fatBox], 'notes' => 'Rusak']))->assertOk()
            ->assertSee('HCS-01')->assertDontSee('FAT-01')->assertSee('Habis')->assertSee('Nov 2026');
        $this->assertSame(['Pemusnahan', 'Usulan pemusnahan fisik berkas arsip. Catatan: Rusak'], [$response->viewData('fields')['tipe'], $response->viewData('fields')['catatan']]);
        $this->actingAs($this->gudang)->get('/destructions/form-pemusnahan')->assertForbidden();
    }

    public function test_not_yet_due_boxes_carry_a_warning_badge(): void
    {
        $early = $this->expiringBox($this->hcs, 'HCS-EARLY', '2027-01-15');
        $this->expiringBox($this->hcs, 'HCS-DUE', '2026-10-31');

        $archives = collect($this->actingAs($this->picHcs)->get('/destructions/propose')->assertOk()->assertSee('arc.early', false)->viewData('archives'))->keyBy('title');
        $this->assertSame(['Belum jatuh tempo (sisa 68 hari)', null], [$archives['HCS-EARLY']['early'], $archives['HCS-DUE']['early']]);

        $this->proposed([$early]);
        $this->actingAs($this->gudang)->get('/destructions?tab=destructions')->assertOk()
            ->assertSee('Belum jatuh tempo (sisa 68 hari)')->assertSee('Perhatian: box ini belum jatuh tempo (sisa 68 hari).');
        $this->actingAs($this->gudang)->post('/destructions/'.DB::table('destruction_logs')->max('id').'/approve');
        $this->actingAs($this->gudang)->get('/destructions?tab=destructions')->assertSee('Belum jatuh tempo (sisa 68 hari)');
    }

    public function test_expired_box_has_no_warning_badge_in_the_gudang_list(): void
    {
        $this->proposed([$this->expiringBox($this->hcs, 'HCS-DUE', '2026-10-31')]);
        $this->actingAs($this->gudang)->get('/destructions?tab=destructions')->assertOk()->assertSee('HCS-DUE')->assertDontSee('Belum jatuh tempo');
    }
}
