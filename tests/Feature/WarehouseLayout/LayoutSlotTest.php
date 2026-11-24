<?php

namespace Tests\Feature\WarehouseLayout;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Warehouse\WarehouseFixtures;
use Tests\TestCase;

/** Layout Gudang 2D rack grid: one rack's slots, queue, placing / moving / releasing a box, slot switch. */
class LayoutSlotTest extends TestCase
{
    use RefreshDatabase;
    use WarehouseFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareWarehouse();
    }

    private function slot(string $code): object
    {
        return DB::table('warehouse_rack_slots')->where('slot_code', $code)->first();
    }

    public function test_slots_of_one_rack_with_box_status_and_the_queue(): void
    {
        $stored = $this->box('Box Lama', 'in_warehouse', 'IND/HCS/2025/0001');
        DB::table('warehouse_rack_slots')->where('slot_code', 'B12')->update(['archive_id' => $stored, 'status' => 'filled']);
        DB::table('archives')->where('id', $stored)->update(['warehouse_location_id' => $this->rack, 'warehouse_rack_slot_id' => $this->slot('B12')->id]);
        $queued = $this->box('Box Antre', 'approved_booked', 'IND/HCS/2026/0002');
        $this->box('Draft', 'draft');

        $data = $this->actingAs($this->gudang)->getJson("/api/warehouse/locations/{$this->rack}/slots")->assertOk()->json();
        $this->assertSame(['B11', 'B12', 'B13'], array_column($data['slots'], 'slot_code'));
        $this->assertSame(['empty', 'expired'], [$data['slots'][0]['status'], $data['slots'][1]['status']]);
        $this->assertSame(['IND/HCS/2025/0001', 'HCS', true], [$data['slots'][1]['archive']['box_number'], $data['slots'][1]['archive']['department'], $data['slots'][1]['archive']['can_label']]);
        $this->assertSame([$queued], array_column($data['queue'], 'id'), 'stored with a slot and drafts are not queued');

        $this->getJson("/api/warehouse/locations/{$this->fatRack}/slots")->assertOk()->assertJsonCount(100, 'slots');
        $this->assertSame('F100', DB::table('warehouse_rack_slots')->where('warehouse_location_id', $this->fatRack)->where('slot_number', 100)->value('slot_code'));
    }

    public function test_placing_an_approved_box_checks_it_in_to_the_chosen_slot(): void
    {
        $box = $this->box('Box Kontrak', 'approved_booked', 'IND/HCS/2026/0001');

        $this->actingAs($this->gudang)->postJson("/api/warehouse/locations/{$this->rack}/slots/assign", ['slot_id' => $this->slot('B13')->id, 'archive_id' => $box])
            ->assertOk()->assertJsonPath('message', "Box arsip 'IND/HCS/2026/0001' berhasil ditempatkan di slot B13.");

        $row = $this->archive($box);
        $this->assertSame(['in_warehouse', $this->rack, $this->slot('B13')->id], [$row->status, (int) $row->warehouse_location_id, (int) $row->warehouse_rack_slot_id]);
        $this->assertSame(['filled', $box], [$this->slot('B13')->status, (int) $this->slot('B13')->archive_id]);
        $this->assertSame('empty', $this->slot('B11')->status, 'not the first empty slot');
        $this->assertSame(1, (int) DB::table('warehouse_locations')->where('id', $this->rack)->value('current_box_count'));
        $this->assertSame(['ARCHIVE_CHECKIN', 'SLOT_ASSIGN'], DB::table('activity_logs')->orderBy('id')->pluck('action')->all());
        $this->assertSame(1, DB::table('warehouse_entry_logs')->where('archive_id', $box)->count());
    }

    public function test_placing_a_box_waiting_for_verification_approves_it_first(): void
    {
        $box = $this->box('Box Baru');

        $this->actingAs($this->admin)->postJson("/api/warehouse/locations/{$this->rack}/slots/assign", ['slot_id' => $this->slot('B11')->id, 'archive_id' => $box])
            ->assertOk()->assertJsonPath('message', fn (string $m) => str_ends_with($m, 'berhasil disetujui (ACC) dan ditempatkan di slot B11.'));

        $row = $this->archive($box);
        $this->assertSame(['in_warehouse', 1], [$row->status, $this->counter()]);
        $this->assertNotNull($row->box_number);
        $this->assertSame(['ARCHIVE_VERIFY', 'ARCHIVE_CHECKIN', 'SLOT_ASSIGN'], DB::table('activity_logs')->orderBy('id')->pluck('action')->all());
    }

    public function test_a_filled_inactive_or_other_department_slot_is_refused_without_changes(): void
    {
        $first = $this->box('A', 'approved_booked', 'IND/HCS/2026/0001');
        $second = $this->box('B', 'approved_booked', 'IND/HCS/2026/0002');
        $this->actingAs($this->gudang)->postJson("/api/warehouse/locations/{$this->rack}/slots/assign", ['slot_id' => $this->slot('B11')->id, 'archive_id' => $first])->assertOk();

        $this->postJson("/api/warehouse/locations/{$this->rack}/slots/assign", ['slot_id' => $this->slot('B11')->id, 'archive_id' => $second])
            ->assertUnprocessable()->assertJsonPath('message', 'Slot sudah terisi. Muat ulang denah rak.');
        DB::table('warehouse_rack_slots')->where('slot_code', 'B12')->update(['is_active' => 0, 'status' => 'inactive']);
        $this->postJson("/api/warehouse/locations/{$this->rack}/slots/assign", ['slot_id' => $this->slot('B12')->id, 'archive_id' => $second])
            ->assertUnprocessable()->assertJsonPath('message', fn (string $m) => str_contains($m, 'NON-AKTIF'));
        $this->getJson("/api/warehouse/locations/{$this->fatRack}/slots")->assertOk();
        $fatSlot = DB::table('warehouse_rack_slots')->where('warehouse_location_id', $this->fatRack)->value('id');
        $this->postJson("/api/warehouse/locations/{$this->fatRack}/slots/assign", ['slot_id' => $fatSlot, 'archive_id' => $second])
            ->assertUnprocessable()->assertJsonPath('message', fn (string $m) => str_starts_with($m, "Akses Ditolak: Lokasi 'R2-F' dikhususkan untuk [FAT]."));
        $this->postJson("/api/warehouse/locations/{$this->fatRack}/slots/assign", ['slot_id' => $this->slot('B13')->id, 'archive_id' => $second])->assertNotFound();

        $this->assertSame(['approved_booked', null], [$this->archive($second)->status, $this->archive($second)->warehouse_rack_slot_id]);
        $this->assertSame([$first], DB::table('warehouse_rack_slots')->whereNotNull('archive_id')->pluck('archive_id')->map(fn ($id) => (int) $id)->all(), 'only B11 filled');
    }

    public function test_moving_a_stored_box_frees_its_old_slot(): void
    {
        $box = $this->box('A', 'in_warehouse', 'IND/HCS/2026/0001');
        DB::table('archives')->where('id', $box)->update(['warehouse_location_id' => $this->rack, 'warehouse_rack_slot_id' => $this->slot('B11')->id]);
        DB::table('warehouse_rack_slots')->where('slot_code', 'B11')->update(['archive_id' => $box, 'status' => 'filled']);

        $this->actingAs($this->gudang)->postJson("/api/warehouse/locations/{$this->rack}/slots/assign", ['slot_id' => $this->slot('B13')->id, 'archive_id' => $box])->assertOk();

        $this->assertSame([null, 'empty'], [$this->slot('B11')->archive_id, $this->slot('B11')->status]);
        $this->assertSame([$box, 'filled'], [(int) $this->slot('B13')->archive_id, $this->slot('B13')->status]);
        $this->assertSame($this->slot('B13')->id, (int) $this->archive($box)->warehouse_rack_slot_id);
    }

    public function test_release_empties_the_slot_and_the_box_keeps_its_status(): void
    {
        $box = $this->box('A', 'in_warehouse', 'IND/HCS/2026/0001');
        DB::table('archives')->where('id', $box)->update(['warehouse_location_id' => $this->rack, 'warehouse_rack_slot_id' => $this->slot('B11')->id]);
        DB::table('warehouse_rack_slots')->where('slot_code', 'B11')->update(['archive_id' => $box, 'status' => 'filled']);

        $this->actingAs($this->gudang)->postJson("/api/warehouse/locations/{$this->rack}/slots/unassign", ['slot_id' => $this->slot('B11')->id])
            ->assertOk()->assertJsonPath('message', 'Slot berhasil dikosongkan.');

        $this->assertSame([null, 'empty'], [$this->slot('B11')->archive_id, $this->slot('B11')->status]);
        $this->assertSame(['in_warehouse', null], [$this->archive($box)->status, $this->archive($box)->warehouse_rack_slot_id]);
        $this->assertStringContainsString('(Box IND/HCS/2026/0001 dilepas)', (string) DB::table('activity_logs')->where('action', 'SLOT_UNASSIGN')->value('description'));
    }

    public function test_slot_switch_is_admin_only_and_pic_departemen_cannot_place(): void
    {
        $slot = $this->slot('B11')->id;
        $this->actingAs($this->picHcs)->postJson("/api/warehouse/locations/{$this->rack}/slots/assign", ['slot_id' => $slot, 'archive_id' => $this->box('A', 'approved_booked')])->assertForbidden();
        $this->actingAs($this->gudang)->postJson("/api/warehouse/locations/{$this->rack}/slots/toggle-active", ['slot_id' => $slot])->assertForbidden();

        $this->actingAs($this->admin)->postJson("/api/warehouse/locations/{$this->rack}/slots/toggle-active", ['slot_id' => $slot])
            ->assertOk()->assertJsonPath('message', 'Status slot B11 berhasil diubah menjadi NON-AKTIF (Inactive).');
        $this->assertSame([0, 'inactive'], [(int) $this->slot('B11')->is_active, $this->slot('B11')->status]);
        $this->postJson("/api/warehouse/locations/{$this->rack}/slots/toggle-active", ['slot_id' => $slot])->assertOk();
        $this->assertSame([1, 'empty'], [(int) $this->slot('B11')->is_active, $this->slot('B11')->status]);
    }
}
