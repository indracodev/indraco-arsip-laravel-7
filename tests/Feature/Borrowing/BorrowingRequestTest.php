<?php

namespace Tests\Feature\Borrowing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * PIC "[Form 2] Penarikan Berkas": form composition, request rules and messages (Laravel 7
 * BorrowingController@create/store), double submit, department isolation, uploads.
 */
class BorrowingRequestTest extends TestCase
{
    use BorrowingFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBorrowingFixtures();
    }

    public function test_form_offers_only_own_department_boxes_in_the_warehouse(): void
    {
        $this->storedBox($this->hcs, 'HCS-01');
        $this->storedBox($this->hcs, 'HCS-OUT', 'taken');
        $this->storedBox($this->fat, 'FAT-01');

        $response = $this->actingAs($this->picHcs)->get('/borrowings/create?archive_ids=1&from=archives')->assertOk()
            ->assertSee('Formulir Permintaan Penarikan Berkas Arsip [Form 2]')
            ->assertSee('PENCARIAN BERKAS GUDANG UNTUK DITAMBAHKAN')
            ->assertSee('ALASAN LENGKAP PENARIKAN')
            ->assertSee('Berkas Dokumen Persetujuan (Approval)')
            ->assertSee('WAJIB UNTUK PENARIKAN')
            ->assertSee('name="is_permanent" value="1"', false)
            ->assertSee('Filter Keamanan Departemen Terkunci');
        $response->assertSee('HCS-01')->assertDontSee('HCS-OUT')->assertDontSee('FAT-01');
    }

    public function test_pic_gudang_cannot_open_or_submit_the_form(): void
    {
        $box = $this->storedBox($this->hcs, 'HCS-01');
        $this->actingAs($this->gudang)->get('/borrowings/create')->assertForbidden();
        $this->actingAs($this->gudang)->post('/borrowings', $this->request([$box]))->assertForbidden();
    }

    public function test_request_creates_one_permanent_log_per_box_with_logs_and_events(): void
    {
        $a = $this->storedBox($this->hcs, 'HCS-01');
        $b = $this->storedBox($this->hcs, 'HCS-02');

        $this->actingAs($this->picHcs)->post('/borrowings', $this->request([$a, $b], ['from' => 'archives', 'expected_return_date' => '2027-01-01']))
            ->assertRedirect('/archives')
            ->assertSessionHas('success', 'Permintaan penarikan 2 box berkas berhasil diajukan dengan lampiran form persetujuan. Menunggu verifikasi Departemen & pengeluaran fisik Gudang.');

        $logs = DB::table('borrowing_logs')->orderBy('id')->get();
        $this->assertCount(2, $logs);
        foreach ($logs as $log) {
            $this->assertSame(['requested', 'pending', null, $this->picHcs->id, 'Keperluan audit', 1],
                [$log->status, $log->approval_status, $log->expected_return_date, (int) $log->borrower_user_id, $log->purpose, (int) $log->is_approval_uploaded]);
            $this->assertSame($logs[0]->approval_file, $log->scan_approval_borrow);
            $this->assertMatchesRegularExpression('#^borrowing_approvals/[A-Za-z0-9]{40}\.pdf$#', $log->approval_file);
        }
        Storage::disk('local')->assertExists($logs[0]->approval_file);
        $this->assertSame(['in_warehouse', 'in_warehouse'], DB::table('archives')->whereIn('id', [$a, $b])->pluck('status')->all());

        $activity = DB::table('activity_logs')->where('action', 'BORROW_CREATE')->first();
        $this->assertSame('PENARIKAN_BERKAS', $activity->module);
        $this->assertSame("Pengajuan penarikan 2 box berkas ('IND/HCS-01, IND/HCS-02') oleh PIC_HCS untuk keperluan: Keperluan audit", $activity->description);
        $this->assertSame('IND/HCS-01, IND/HCS-02', $activity->reference_id);
        $this->assertSame(2, DB::table('system_events')->where('event_type', 'borrowing_created')->count());
        $payload = json_decode((string) DB::table('system_events')->where('event_type', 'borrowing_created')->value('payload'), true);
        $this->assertSame(['status' => 'requested', 'box_number' => 'IND/HCS-01', 'title' => 'HCS-01', 'dept_code' => 'HCS', 'borrower' => 'PIC_HCS', 'borrower_user_id' => $this->picHcs->id, 'action_by_user_id' => $this->picHcs->id], $payload);
    }

    public function test_single_box_message_and_redirect_to_the_list(): void
    {
        $box = $this->storedBox($this->hcs, 'HCS-01');
        $this->actingAs($this->picHcs)->post('/borrowings', $this->request([], ['archive_ids' => null, 'archive_id' => $box]))
            ->assertRedirect('/borrowings')
            ->assertSessionHas('success', 'Permintaan penarikan berkas berhasil diajukan dengan lampiran form persetujuan. Menunggu verifikasi Departemen & pengeluaran fisik Gudang.');
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidInput(): array
    {
        return [
            'no box' => [['archive_ids' => []], 'archive_ids'],
            'no purpose' => [['purpose' => ''], 'purpose'],
            'purpose over 500' => [['purpose' => str_repeat('a', 501)], 'purpose'],
            'no approval file' => [['approval_file' => null], 'approval_file'],
        ];
    }

    /** @param array<string, mixed> $overrides */
    #[DataProvider('invalidInput')]
    public function test_required_fields_are_enforced(array $overrides, string $field): void
    {
        $box = $this->storedBox($this->hcs, 'HCS-01');
        $this->actingAs($this->picHcs)->post('/borrowings', $this->request([$box], $overrides))->assertSessionHasErrors($field);
        $this->assertSame(0, DB::table('borrowing_logs')->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_missing_box_uses_the_laravel_7_message(): void
    {
        $this->actingAs($this->picHcs)->post('/borrowings', $this->request([]))
            ->assertSessionHasErrors(['archive_ids' => 'Minimal satu berkas arsip wajib dipilih untuk pengajuan penarikan.']);
    }

    public function test_box_not_in_the_warehouse_is_refused(): void
    {
        $box = $this->storedBox($this->hcs, 'HCS-01', 'pending_destruction');
        $this->actingAs($this->picHcs)->post('/borrowings', $this->request([$box]))
            ->assertSessionHas('error', 'Berkas arsip berikut tidak tersedia di gudang untuk ditarik: [IND/HCS-01] HCS-01');
        $this->assertSame(0, DB::table('borrowing_logs')->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_duplicate_active_request_is_blocked(): void
    {
        $box = $this->storedBox($this->hcs, 'HCS-01');
        $this->requested([$box]);

        $this->actingAs($this->picHcs)->post('/borrowings', $this->request([$box]))
            ->assertSessionHas('error', 'Gagal mengajukan penarikan: Box arsip berikut sedang dalam proses penarikan aktif: [IND/HCS-01] HCS-01');
        $this->assertSame(1, DB::table('borrowing_logs')->count());
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_other_department_box_is_forbidden(): void
    {
        $fatBox = $this->storedBox($this->fat, 'FAT-01');
        $this->actingAs($this->picHcs)->post('/borrowings', $this->request([$fatBox]))->assertForbidden();
        $this->assertSame(0, DB::table('borrowing_logs')->count());

        $this->actingAs($this->admin)->post('/borrowings', $this->request([$fatBox]))->assertSessionMissing('error');
        $this->assertSame(1, DB::table('borrowing_logs')->count());
    }

    /** @return array<string, array{string, string, string}> */
    public static function rejectedUploads(): array
    {
        return [
            'php script' => ['test.php', '<?php echo 1;', 'application/x-php'],
            'double extension' => ['approval.pdf.php', "%PDF-1.4\n%%EOF\n", 'application/pdf'],
            'php disguised as pdf' => ['approval.pdf', '<?php echo 1;', 'application/pdf'],
            'html disguised as png' => ['approval.png', '<html></html>', 'image/png'],
        ];
    }

    #[DataProvider('rejectedUploads')]
    public function test_dangerous_uploads_are_rejected(string $name, string $content, string $clientMime): void
    {
        $box = $this->storedBox($this->hcs, 'HCS-01');
        $this->actingAs($this->picHcs)->post('/borrowings', $this->request([$box], ['approval_file' => $this->upload($name, $content, $clientMime)]))
            ->assertSessionHasErrors('approval_file');
        $this->assertSame(0, DB::table('borrowing_logs')->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_upload_over_500_kb_is_rejected(): void
    {
        $box = $this->storedBox($this->hcs, 'HCS-01');
        $this->actingAs($this->picHcs)->post('/borrowings', $this->request([$box], ['approval_file' => $this->pdf('approval.pdf', 501)]))
            ->assertSessionHasErrors('approval_file');
        $this->assertSame(0, DB::table('borrowing_logs')->count());
    }

    public function test_print_form_lists_only_visible_boxes_as_penarikan(): void
    {
        $box = $this->storedBox($this->hcs, 'HCS-01');
        $fatBox = $this->storedBox($this->fat, 'FAT-01');

        $response = $this->actingAs($this->picHcs)->get('/borrowings/form-penarikan?'.http_build_query(['archive_ids' => [$box, $fatBox], 'purpose' => 'Audit pajak']))
            ->assertOk()->assertSee('HCS-01')->assertDontSee('FAT-01')->assertSee('Audit pajak');
        $this->assertSame('Penarikan', $response->viewData('fields')['tipe']);
        $this->actingAs($this->gudang)->get('/borrowings/form-penarikan')->assertForbidden();
    }
}
