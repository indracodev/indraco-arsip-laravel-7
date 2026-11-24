<?php

namespace Tests\Feature\Borrowing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Warehouse side (Laravel 7 BorrowingController@dispatch / @reject) and the list: transitions,
 * source-status checks, role / department access, routes that are not built.
 */
class BorrowingProcessTest extends TestCase
{
    use BorrowingFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBorrowingFixtures();
    }

    public function test_dispatch_takes_the_box_out_of_the_rack(): void
    {
        $box = $this->storedBox($this->hcs, 'HCS-01');
        $log = $this->requested([$box]);
        $approval = DB::table('borrowing_logs')->where('id', $log)->value('approval_file');

        $this->actingAs($this->gudang)->from('/borrowings')
            ->post("/borrowings/{$log}/dispatch", ['confirm_file_checked' => 'on', 'scan_approval_borrow' => $this->pdf('receipt.pdf')])
            ->assertRedirect('/borrowings')
            ->assertSessionHas('success', 'Pengeluaran berkas disahkan! Status penarikan berubah menjadi "Sudah Ditarik" dan slot rak telah dikosongkan.');

        $row = DB::table('borrowing_logs')->find($log);
        $this->assertSame(['dispatched', 'approved', $this->gudang->id, $this->gudang->id, 'IND/HCS-01', 'HCS-01', 'HCS'],
            [$row->status, $row->approval_status, (int) $row->pic_gudang_id, (int) $row->department_approval_by, $row->snapshot_box_number, $row->snapshot_title, $row->snapshot_department]);
        $this->assertNotNull($row->borrow_date);
        $this->assertSame([['number' => 1, 'name' => 'Kontrak Kerja', 'period' => 'Jan 2026', 'notes' => 'No 1-50']], json_decode($row->snapshot_items, true));
        $this->assertSame($approval, $row->approval_file, 'the PIC approval form is kept');
        $this->assertNotSame($approval, $row->scan_approval_borrow);
        Storage::disk('local')->assertExists($row->scan_approval_borrow);

        $archive = DB::table('archives')->find($box);
        $this->assertSame(['taken', null, null], [$archive->status, $archive->warehouse_location_id, $archive->warehouse_rack_slot_id]);
        $this->assertSame(0, DB::table('warehouse_rack_slots')->where('archive_id', $box)->count());
        $this->assertSame('empty', DB::table('warehouse_rack_slots')->where('slot_number', $box)->value('status'));
        $this->assertSame(1, (int) DB::table('warehouse_locations')->where('id', $this->location)->value('current_box_count'));

        $activity = DB::table('activity_logs')->where('action', 'BORROW_DISPATCH')->first();
        $this->assertSame(['PENARIKAN_BERKAS', "Pengeluaran berkas fisik arsip 'HCS-01' (Box: IND/HCS-01) oleh PIC Gudang {$this->gudang->name}", 'IND/HCS-01'],
            [$activity->module, $activity->description, $activity->reference_id]);
        $this->assertSame('dispatched', json_decode((string) DB::table('system_events')->where('event_type', 'borrowing_updated')->value('payload'), true)['status']);
    }

    public function test_dispatch_is_refused_twice_and_after_reject(): void
    {
        $box = $this->storedBox($this->hcs, 'HCS-01');
        $log = $this->requested([$box]);
        $this->actingAs($this->gudang)->post("/borrowings/{$log}/dispatch")->assertSessionHas('success');

        $this->actingAs($this->gudang)->post("/borrowings/{$log}/dispatch")
            ->assertSessionHas('error', 'Pengajuan penarikan ini sudah diproses (status: Sudah Ditarik). Muat ulang halaman.');
        $this->actingAs($this->gudang)->post("/borrowings/{$log}/reject", ['reason' => 'x'])
            ->assertSessionHas('error', 'Pengajuan penarikan ini sudah diproses (status: Sudah Ditarik). Muat ulang halaman.');
        $this->assertSame(1, DB::table('activity_logs')->where('action', 'BORROW_DISPATCH')->count());

        $other = $this->storedBox($this->hcs, 'HCS-02');
        $rejected = $this->requested([$other]);
        $this->actingAs($this->admin)->post("/borrowings/{$rejected}/reject", ['reason' => 'Form kurang'])->assertSessionHas('success');
        $this->actingAs($this->admin)->post("/borrowings/{$rejected}/dispatch")->assertSessionHas('error');
        $this->assertSame('in_warehouse', DB::table('archives')->where('id', $other)->value('status'));
    }

    public function test_dispatch_is_refused_when_the_box_left_the_warehouse(): void
    {
        $box = $this->storedBox($this->hcs, 'HCS-01');
        $log = $this->requested([$box]);
        DB::table('archives')->where('id', $box)->update(['status' => 'pending_destruction']);

        $this->actingAs($this->gudang)->post("/borrowings/{$log}/dispatch")->assertSessionHas('error');
        $this->assertSame('requested', DB::table('borrowing_logs')->where('id', $log)->value('status'));
    }

    public function test_reject_needs_a_reason_and_allows_a_new_request(): void
    {
        $box = $this->storedBox($this->hcs, 'HCS-01');
        $log = $this->requested([$box]);

        $this->actingAs($this->gudang)->post("/borrowings/{$log}/reject", ['reason' => ''])->assertSessionHasErrors('reason');
        $this->actingAs($this->gudang)->post("/borrowings/{$log}/reject", ['reason' => str_repeat('a', 501)])->assertSessionHasErrors('reason');
        $this->actingAs($this->gudang)->post("/borrowings/{$log}/reject", ['reason' => 'form permohonan kurang'])
            ->assertSessionHas('success', 'Pengajuan penarikan berkas arsip (Box: IND/HCS-01) berhasil ditolak.');

        $row = DB::table('borrowing_logs')->find($log);
        $this->assertSame(['rejected', 'rejected', 'form permohonan kurang', $this->gudang->id], [$row->status, $row->approval_status, $row->approval_notes, (int) $row->pic_gudang_id]);
        $activity = DB::table('activity_logs')->where('action', 'BORROW_REJECT')->first();
        $this->assertSame("Penolakan pengajuan penarikan arsip 'HCS-01' (Box: IND/HCS-01) oleh {$this->gudang->name}. Alasan: form permohonan kurang", $activity->description);

        $this->actingAs($this->picHcs)->get('/borrowings')->assertSee('Tarik Ulang')->assertSee('form permohonan kurang');
        $this->requested([$box]);
        $this->actingAs($this->picHcs)->get('/borrowings')->assertSee('PERNAH DITOLAK');
    }

    public function test_only_gudang_and_admin_process_requests(): void
    {
        $box = $this->storedBox($this->hcs, 'HCS-01');
        $log = $this->requested([$box]);

        $this->actingAs($this->picHcs)->post("/borrowings/{$log}/dispatch")->assertForbidden();
        $this->actingAs($this->picHcs)->post("/borrowings/{$log}/reject", ['reason' => 'x'])->assertForbidden();
        $this->actingAs($this->picFat)->post("/borrowings/{$log}/reject", ['reason' => 'x'])->assertForbidden();
        $this->assertSame('requested', DB::table('borrowing_logs')->where('id', $log)->value('status'));

        $this->actingAs($this->picHcs)->get('/borrowings')->assertOk()->assertDontSee('Keluarkan Arsip');
        $this->actingAs($this->gudang)->get('/borrowings')->assertOk()->assertSee('Keluarkan Arsip')->assertSee('Konfirmasi Tolak Pengajuan');
    }

    public function test_list_and_files_are_limited_to_the_department(): void
    {
        $hcsBox = $this->storedBox($this->hcs, 'HCS-01');
        $fatBox = $this->storedBox($this->fat, 'FAT-01');
        $hcsLog = $this->requested([$hcsBox]);
        $fatLog = $this->requested([$fatBox], $this->picFat);

        $this->actingAs($this->picHcs)->get('/borrowings')->assertOk()->assertSee('HCS-01')->assertDontSee('FAT-01');
        $this->actingAs($this->gudang)->get('/borrowings')->assertOk()->assertSee('HCS-01')->assertSee('FAT-01');
        $this->actingAs($this->picHcs)->get("/files/borrowing/{$fatLog}/approval_file")->assertForbidden();
        $this->actingAs($this->picHcs)->get("/files/borrowing/{$hcsLog}/approval_file")->assertOk();
    }

    public function test_one_row_per_box_and_status_filter(): void
    {
        $box = $this->storedBox($this->hcs, 'HCS-01');
        $first = $this->requested([$box]);
        $this->actingAs($this->gudang)->post("/borrowings/{$first}/reject", ['reason' => 'kurang']);
        $this->requested([$box]);

        $page = $this->actingAs($this->admin)->get('/borrowings')->assertOk()->viewData('borrowings');
        $this->assertSame(1, $page->total());
        $this->assertSame(0, $this->actingAs($this->admin)->get('/borrowings?status=rejected')->viewData('borrowings')->total());
        $this->actingAs($this->admin)->get('/borrowings?status=bogus')->assertSessionHasErrors('status');
    }

    public function test_routes_without_laravel_7_ui_are_not_built(): void
    {
        $box = $this->storedBox($this->hcs, 'HCS-01');
        $log = $this->requested([$box]);

        foreach (['dept-approve', 'approve', 'return'] as $action) {
            $this->actingAs($this->admin)->post("/borrowings/{$log}/{$action}")->assertNotFound();
        }
        $this->assertContains($this->actingAs($this->admin)->put("/borrowings/{$log}", ['purpose' => 'x'])->status(), [404, 405]);
        $this->assertSame('requested', DB::table('borrowing_logs')->where('id', $log)->value('status'));
    }
}
