<?php

namespace Tests\Feature\Warehouse;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Status change (Laravel 7 superAdminUpdateStatus), label / sticker printing and access:
 * PIC Departemen gets 403 on every warehouse action; checkout is not rebuilt.
 */
class StatusAndLabelTest extends TestCase
{
    use RefreshDatabase;
    use WarehouseFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareWarehouse();
    }

    public function test_manual_box_number_is_unique_among_active_boxes(): void
    {
        $holder = $this->box('Pemegang', 'in_warehouse', 'IND/HCS/2026/0009');
        $this->box('Keluar', 'taken', 'IND/HCS/2026/0010');
        $box = $this->box('Box Kontrak', 'approved_booked', 'IND/HCS/2026/0001');

        $this->actingAs($this->admin)->post("/archives/{$box}/superadmin-status", ['target_status' => 'in_warehouse', 'box_number' => ' IND/HCS/2026/0009 '])
            ->assertSessionHasErrors(['box_number' => "Nomor Box 'IND/HCS/2026/0009' sudah digunakan oleh arsip aktif lain (#{$holder} - Pemegang). Harap gunakan nomor box lain."]);
        $this->assertSame('IND/HCS/2026/0001', $this->archive($box)->box_number);

        $this->actingAs($this->admin)->post("/archives/{$box}/superadmin-status", ['target_status' => 'in_warehouse', 'box_number' => 'IND/HCS/2026/0010', 'reason' => 'Koreksi'])
            ->assertSessionHas('success', "Status arsip box IND/HCS/2026/0010 berhasil diubah menjadi 'Tersimpan di Gudang' oleh Super Admin.");
        $this->assertSame(['in_warehouse', 'IND/HCS/2026/0010'], [$this->archive($box)->status, $this->archive($box)->box_number]);
        $this->assertSame(1, DB::table('activity_logs')->where('action', 'STATUS_UPDATE_WAREHOUSE')->count());
        $this->assertSame(0, $this->counter());
    }

    public function test_reactivating_a_box_whose_number_is_taken_gives_a_message_not_an_error(): void
    {
        $this->box('Pemegang', 'in_warehouse', 'IND/1');
        $box = $this->box('Keluar', 'taken', 'IND/1');

        $this->actingAs($this->admin)->post("/archives/{$box}/superadmin-status", ['target_status' => 'pending_verification'])
            ->assertSessionHas('error');
        $this->assertSame('taken', $this->archive($box)->status);
    }

    public function test_back_to_draft_needs_a_reason_and_frees_the_slot(): void
    {
        $box = $this->box('Box Kontrak', 'approved_booked', 'IND/1');
        $this->actingAs($this->gudang)->post("/archives/{$box}/checkin", ['warehouse_location_id' => $this->rack]);

        $this->actingAs($this->admin)->post("/archives/{$box}/superadmin-status", ['target_status' => 'draft'])->assertSessionHasErrors('reason');
        $this->actingAs($this->admin)->post("/archives/{$box}/superadmin-status", ['target_status' => 'draft', 'reason' => 'Rincian salah'])->assertSessionHas('success');

        $row = $this->archive($box);
        $this->assertSame(['draft', 'Rincian salah', null, null], [$row->status, $row->rejection_note, $row->warehouse_location_id, $row->warehouse_rack_slot_id]);
        $this->assertSame(0, DB::table('warehouse_rack_slots')->whereNotNull('archive_id')->count());
        $this->assertSame(0, (int) DB::table('warehouse_locations')->where('id', $this->rack)->value('current_box_count'));
        $this->assertStringContainsString('SuperAdmin Kembalikan ke DRAFT oleh', (string) DB::table('archives')->where('id', $box)->value('content_description'));
    }

    public function test_exit_and_destruction_targets_are_not_offered_here(): void
    {
        $box = $this->box('Box Kontrak', 'in_warehouse', 'IND/1');
        foreach (['taken', 'out', 'pending_destruction', 'destroyed'] as $target) {
            $this->actingAs($this->admin)->post("/archives/{$box}/superadmin-status", ['target_status' => $target, 'purpose' => 'x'])->assertSessionHasErrors('target_status');
        }
        $this->actingAs($this->gudang)->post("/archives/{$box}/checkout", ['purpose' => 'x'])->assertNotFound();
        $this->assertSame('in_warehouse', $this->archive($box)->status);
    }

    public function test_pic_departemen_gets_403_on_every_warehouse_action(): void
    {
        $pending = $this->box('Pending');
        $booked = $this->box('Booked', 'approved_booked', 'IND/1');
        $stored = $this->box('Stored', 'in_warehouse', 'IND/2');

        foreach ([$this->picHcs, $this->picFat] as $pic) {
            $this->actingAs($pic)->post("/archives/{$pending}/verify", ['action' => 'approve'])->assertForbidden();
            $this->actingAs($pic)->post("/archives/{$booked}/checkin", ['warehouse_location_id' => $this->rack])->assertForbidden();
            $this->actingAs($pic)->post("/archives/{$stored}/superadmin-status", ['target_status' => 'draft', 'reason' => 'abc'])->assertForbidden();
            $this->actingAs($pic)->get("/archives/{$stored}/print-sticker")->assertForbidden();
            $this->actingAs($pic)->get("/archives/print-labels?ids={$stored}")->assertForbidden();
        }
        $this->actingAs($this->picHcs)->get("/archives/{$pending}")->assertOk()->assertDontSee('Hub Verifikasi');
        $this->assertSame(['pending_verification', 'approved_booked', 'in_warehouse'], [$this->archive($pending)->status, $this->archive($booked)->status, $this->archive($stored)->status]);
    }

    public function test_detail_page_shows_the_warehouse_panels(): void
    {
        $pending = $this->box('Pending');
        $booked = $this->box('Booked', 'approved_booked', 'IND/1');

        $this->actingAs($this->gudang)->get("/archives/{$pending}")->assertOk()->assertSee('Tolak & Minta Revisi', false)->assertDontSee('Ubah Status Workflow');
        $this->actingAs($this->gudang)->get("/archives/{$booked}")->assertOk()
            ->assertSee('Konfirmasi Check-in Ke Gudang')->assertSee('Gudang Utama - R1-B / S1 [Umum] (Terisi 0/20 Box)');
        $this->actingAs($this->admin)->get("/archives/{$booked}")->assertOk()->assertSee('Ubah Status Workflow');
    }

    public function test_sticker_prints_the_laravel_7_label(): void
    {
        $box = $this->box('Box Kontrak', 'approved_booked', 'IND/HCS/2026/0001');
        $this->actingAs($this->gudang)->post("/archives/{$box}/checkin", ['warehouse_location_id' => $this->rack]);

        $this->actingAs($this->gudang)->get("/archives/{$box}/print-sticker")->assertOk()
            ->assertSee('Label Box Gudang 10x10cm - IND/HCS/2026/0001')
            ->assertSee('EXP. : 06-2026')
            ->assertSee('data-code="B01"', false)
            ->assertSee('js/vendor/qrcode.min.js', false)
            ->assertSee('Sap 1, Baris Bawah Slot 1')
            ->assertSee('FINAL (TERALOKASI GUDANG)');
        $this->assertSame(1, DB::table('activity_logs')->where('action', 'ARCHIVE_PRINT')->count());

        $draft = $this->box('Draft', 'draft');
        $this->actingAs($this->admin)->get("/archives/{$draft}/print-sticker")->assertRedirect("/archives/{$draft}")->assertSessionHas('error');
    }

    public function test_batch_labels_only_print_eligible_boxes(): void
    {
        $a = $this->box('A', 'in_warehouse', 'IND/HCS/2026/0001');
        $b = $this->box('B', 'pending_extension', 'IND/HCS/2026/0002');
        $draft = $this->box('C', 'draft');

        $this->actingAs($this->admin)->get("/archives/print-labels?ids={$a},{$b},{$draft}")->assertOk()
            ->assertSee('Label Box Gudang 10x10cm - 2 Arsip')->assertSee('IND/HCS/2026/0002')->assertDontSee('DRAFT-BOX');
        $this->actingAs($this->admin)->post('/archives/print-labels', ['ids' => [$a]])->assertOk()->assertSee('IND/HCS/2026/0001');
        $this->actingAs($this->admin)->get("/archives/print-labels?ids={$draft}")->assertRedirect('/archives')
            ->assertSessionHas('warning', 'Tidak ada data arsip yang memenuhi syarat untuk dicetak label.');
    }
}
