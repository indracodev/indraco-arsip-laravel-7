<?php

namespace Tests\Feature\Warehouse;

use App\Models\Archive;
use App\Services\NumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Verification hub (Laravel 7 ArchiveVerificationController@verify) and box numbering
 * (NumberingService): transitions, source status, atomic counter, preview without side effect.
 */
class VerificationTest extends TestCase
{
    use RefreshDatabase;
    use WarehouseFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareWarehouse();
    }

    public function test_approve_books_the_box_with_a_generated_number(): void
    {
        $box = $this->box('Box Kontrak');
        DB::table('archives')->where('id', $box)->update(['rejection_note' => 'lama']);

        $this->actingAs($this->gudang)->post("/archives/{$box}/verify", ['action' => 'approve'])
            ->assertRedirect("/archives/{$box}")
            ->assertSessionHas('success', 'Pengajuan arsip disetujui! Nomor Box Generated: IND/HCS/2026/0001');

        $row = $this->archive($box);
        $this->assertSame(['approved_booked', 'IND/HCS/2026/0001', null], [$row->status, $row->box_number, $row->rejection_note]);
        $this->assertSame(1, $this->counter());
        $log = DB::table('activity_logs')->where('action', 'ARCHIVE_VERIFY')->first();
        $this->assertSame(['DOKUMEN_ARSIP', "Verifikasi persetujuan arsip 'Box Kontrak' & alokasi nomor box generated 'IND/HCS/2026/0001'.", 'IND/HCS/2026/0001'],
            [$log->module, $log->description, $log->reference_id]);
        $this->assertSame('approved_booked', json_decode((string) DB::table('system_events')->where('entity_id', $box)->value('payload'), true)['status']);
    }

    public function test_reject_returns_the_draft_with_a_revision_note(): void
    {
        $box = $this->box('Box Kontrak');

        $this->actingAs($this->gudang)->post("/archives/{$box}/verify", ['action' => 'reject'])->assertSessionHasErrors('rejection_note');
        $this->actingAs($this->gudang)->post("/archives/{$box}/verify", ['action' => 'reject', 'rejection_note' => 'Scan form buram'])
            ->assertRedirect("/archives/{$box}")->assertSessionHas('warning', 'Pengajuan arsip ditolak dan dikembalikan ke PIC Departemen.');

        $row = $this->archive($box);
        $this->assertSame(['draft', 'Scan form buram', null], [$row->status, $row->rejection_note, $row->box_number]);
        $this->assertSame(0, $this->counter(), 'reject never burns a number');
        $this->assertSame(1, DB::table('activity_logs')->where('action', 'ARCHIVE_REJECT')->count());
        // The PIC sees "Revisi & Ajukan" in the catalog.
        $this->actingAs($this->picHcs)->get('/archives')->assertSee('Scan form buram');
    }

    public function test_only_a_pending_submission_is_verified(): void
    {
        $box = $this->box('Box Kontrak');
        $this->actingAs($this->gudang)->post("/archives/{$box}/verify", ['action' => 'approve']);
        $this->actingAs($this->gudang)->from("/archives/{$box}")->post("/archives/{$box}/verify", ['action' => 'approve'])
            ->assertRedirect("/archives/{$box}")->assertSessionHas('error', 'Pengajuan arsip ini sudah diproses (status: Booking Disetujui). Muat ulang halaman.');
        $this->actingAs($this->gudang)->post("/archives/{$box}/verify", ['action' => 'reject', 'rejection_note' => 'x'])->assertSessionHas('error');

        $draft = $this->box('Draft', 'draft');
        $this->actingAs($this->admin)->post("/archives/{$draft}/verify", ['action' => 'approve'])->assertSessionHas('error');

        $this->assertSame(['approved_booked', 'IND/HCS/2026/0001'], [$this->archive($box)->status, $this->archive($box)->box_number]);
        $this->assertSame(['draft', null], [$this->archive($draft)->status, $this->archive($draft)->box_number]);
        $this->assertSame(1, $this->counter(), 'a refused click does not burn a number');
    }

    public function test_numbers_are_sequential_unique_and_skip_active_holders(): void
    {
        $this->box('Lama Aktif', 'in_warehouse', 'IND/HCS/2026/0002');
        $this->box('Lama Keluar', 'taken', 'IND/HCS/2026/0003'); // inactive: its number may be issued again
        $boxes = [$this->box('A'), $this->box('B'), $this->box('C')];
        foreach ($boxes as $box) {
            $this->actingAs($this->gudang)->post("/archives/{$box}/verify", ['action' => 'approve'])->assertSessionHas('success');
        }

        $this->assertSame(['IND/HCS/2026/0001', 'IND/HCS/2026/0003', 'IND/HCS/2026/0004'], array_map(fn (int $id) => $this->archive($id)->box_number, $boxes));
        $this->assertSame(4, $this->counter());
    }

    public function test_preview_never_moves_the_counter(): void
    {
        $box = $this->box('Box Kontrak');
        $archive = Archive::query()->with('department')->findOrFail($box);
        $service = app(NumberingService::class);

        $this->assertSame('IND/HCS/2026/0001', $service->preview($archive));
        $this->assertSame('IND/HCS/2026/0001', $service->preview($archive));
        $this->actingAs($this->gudang)->get("/archives/{$box}")->assertOk()
            ->assertSee('Setujui & Generate Box', false)->assertSee('IND/HCS/2026/0001');
        $this->assertSame(0, $this->counter());
        $this->assertSame('IND/HCS/2026/0001', $service->generate($archive));
        $this->assertSame('IND/HCS/2026/0002', $service->preview($archive));
    }

    public function test_a_failed_save_rolls_the_counter_back(): void
    {
        $archive = Archive::query()->with('department')->findOrFail($this->box('Box Kontrak'));
        try {
            DB::transaction(function () use ($archive): void {
                app(NumberingService::class)->generate($archive);
                throw new \RuntimeException('save failed');
            });
        } catch (\RuntimeException) {
        }

        $this->assertSame(0, $this->counter());
    }
}
