<?php

namespace Tests\Feature\Warehouse;

use App\Actions\Warehouse\CheckinArchive;
use App\Models\Archive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Rack check-in (Laravel 7 ArchiveVerificationController@checkin). */
class CheckinTest extends TestCase
{
    use RefreshDatabase;
    use WarehouseFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareWarehouse();
    }

    public function test_checkin_fills_the_first_empty_slot_and_logs_the_entry(): void
    {
        $box = $this->box('Box Kontrak', 'approved_booked', 'IND/HCS/2026/0001');

        $this->actingAs($this->gudang)->post("/archives/{$box}/checkin", ['warehouse_location_id' => $this->rack, 'notes' => 'Segel utuh'])
            ->assertRedirect("/archives/{$box}")
            ->assertSessionHas('success', 'Berkas fisik berhasil di-checkin ke lokasi Gudang Utama - R1-B / S1 & Log Masuk Gudang telah dicatat.');

        $row = $this->archive($box);
        $slot = DB::table('warehouse_rack_slots')->where('slot_code', 'B11')->first();
        $this->assertSame(['in_warehouse', $this->rack, $slot->id], [$row->status, (int) $row->warehouse_location_id, (int) $row->warehouse_rack_slot_id]);
        $this->assertSame(['filled', $box], [$slot->status, (int) $slot->archive_id]);
        $this->assertSame(1, (int) DB::table('warehouse_locations')->where('id', $this->rack)->value('current_box_count'));
        $entry = DB::table('warehouse_entry_logs')->first();
        $this->assertSame([$box, $this->gudang->id, $this->rack, 'Segel utuh'], [(int) $entry->archive_id, (int) $entry->pic_gudang_id, (int) $entry->location_id, $entry->notes]);
        $this->assertSame('B11', json_decode((string) DB::table('activity_logs')->where('action', 'ARCHIVE_CHECKIN')->value('properties'), true)['slot_code']);
        $this->assertSame(0, $this->counter(), 'a numbered box keeps its number');
    }

    public function test_slots_are_never_double_booked(): void
    {
        DB::table('warehouse_rack_slots')->where('slot_code', 'B11')->update(['status' => 'filled', 'archive_id' => $this->box('Sudah di rak', 'in_warehouse', 'X-1')]);
        $boxes = [];
        foreach (['A', 'B', 'C'] as $title) {
            $boxes[] = $id = $this->box($title, 'approved_booked', 'IND/'.$title);
            $this->actingAs($this->gudang)->post("/archives/{$id}/checkin", ['warehouse_location_id' => $this->rack])->assertSessionHas('success');
        }

        $slots = array_map(fn (int $id) => $this->archive($id)->warehouse_rack_slot_id, $boxes);
        $this->assertSame(DB::table('warehouse_rack_slots')->whereIn('slot_code', ['B12', 'B13'])->orderBy('slot_code')->pluck('id')->map(fn ($id) => (int) $id)->push(null)->all(),
            array_map(fn ($id) => $id === null ? null : (int) $id, $slots), 'rack full: the third box gets the rack without a slot (Laravel 7)');
        $this->assertSame(DB::table('warehouse_rack_slots')->whereNotNull('archive_id')->count(), DB::table('warehouse_rack_slots')->whereNotNull('archive_id')->distinct()->count('archive_id'));
        $this->assertSame(3, (int) DB::table('warehouse_locations')->where('id', $this->rack)->value('current_box_count'));
    }

    public function test_checkin_needs_a_booked_box_and_an_allowed_rack(): void
    {
        $pending = $this->box('Pending');
        $this->actingAs($this->gudang)->post("/archives/{$pending}/checkin", ['warehouse_location_id' => $this->rack])
            ->assertSessionHas('error', 'Check-in hanya untuk box berstatus Booking Disetujui (status saat ini: Menunggu Verifikasi). Muat ulang halaman.');

        $booked = $this->box('Booked', 'approved_booked', 'IND/1');
        $this->actingAs($this->gudang)->post("/archives/{$booked}/checkin", ['warehouse_location_id' => $this->fatRack])
            ->assertSessionHas('error', "Akses Ditolak: Lokasi 'R2-F' dikhususkan untuk [FAT]. Box dari HCS (Human Capital) tidak diizinkan dialokasikan ke lokasi ini.");
        $this->actingAs($this->gudang)->post("/archives/{$booked}/checkin", ['warehouse_location_id' => 9999])->assertSessionHasErrors('warehouse_location_id');

        $this->assertSame('pending_verification', $this->archive($pending)->status);
        $this->assertSame('approved_booked', $this->archive($booked)->status);
        $this->assertSame(0, DB::table('warehouse_entry_logs')->count());
        $this->assertSame(0, DB::table('warehouse_rack_slots')->whereNotNull('archive_id')->count());
    }

    public function test_checkin_numbers_an_unnumbered_booked_box(): void
    {
        $box = $this->box('Lama', 'approved_booked');
        $this->actingAs($this->admin)->post("/archives/{$box}/checkin", ['warehouse_location_id' => $this->rack])->assertSessionHas('success');

        $this->assertSame('IND/HCS/2026/0001', $this->archive($box)->box_number);
    }

    public function test_rack_options_mark_full_and_reserved_racks(): void
    {
        $options = collect(CheckinArchive::options(Archive::query()->findOrFail($this->box('Booked', 'approved_booked'))))->keyBy('id');

        $this->assertSame(['Gudang Utama - R1-B / S1 [Umum] (Terisi 0/20 Box)', false], [$options[$this->rack]['text'], $options[$this->rack]['disabled']]);
        $this->assertSame(['Gudang Utama - R2-F / S1 [Khusus: FAT] (Terisi 0/20 Box)', true], [$options[$this->fatRack]['text'], $options[$this->fatRack]['disabled']]);
    }
}
