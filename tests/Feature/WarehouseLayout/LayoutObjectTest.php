<?php

namespace Tests\Feature\WarehouseLayout;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Warehouse\WarehouseFixtures;
use Tests\TestCase;

/** Layout Gudang 2D objects: access, canvas data, add / edit / move, booking, delete. */
class LayoutObjectTest extends TestCase
{
    use RefreshDatabase;
    use WarehouseFixtures;

    private int $gaRack;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareWarehouse();
        $ga = DB::table('warehouses')->insertGetId(['code' => 'GUDANG GA', 'name' => 'GUDANG GA']);
        $this->gaRack = DB::table('warehouse_locations')->insertGetId(['warehouse_id' => $ga, 'rack_code' => 'GA-A', 'room_sector' => 'GA', 'shelf_code' => 'S1']);
    }

    public function test_pic_departemen_gets_403_everywhere(): void
    {
        $this->actingAs($this->picHcs)->get('/master/warehouses/layout')->assertForbidden();
        $this->getJson('/api/warehouse/layout-data')->assertForbidden();
        $this->getJson("/api/warehouse/locations/{$this->rack}/slots")->assertForbidden();
        $this->postJson('/api/warehouse/locations/positions', ['items' => [$this->geometry($this->rack)]])->assertForbidden();
        $this->postJson("/api/warehouse/locations/{$this->rack}/book", ['department_id' => $this->hcs, 'booking_notes' => 'x'])->assertForbidden();
        $this->postJson("/api/warehouse/locations/{$this->rack}/delete")->assertForbidden();
        $this->assertDatabaseHas('warehouse_locations', ['id' => $this->rack, 'canvas_x' => 0, 'is_booked' => 0]);
    }

    public function test_canvas_data_has_no_slots_and_hides_gudang_ga_it_from_pic_gudang(): void
    {
        $this->actingAs($this->gudang)->get('/master/warehouses/layout')->assertOk()->assertSee('id="wl-canvas"', false);
        $data = $this->getJson('/api/warehouse/layout-data')->assertOk()->json();
        $this->assertSame([$this->rack, $this->fatRack], array_column($data['locations'], 'id'));
        $this->assertArrayNotHasKey('slots', $data['locations'][0]);
        $this->assertSame(['filled' => 0, 'expired' => 0, 'near_exp' => 0, 'empty' => 100], $data['locations'][0]['stats']);
        $this->assertSame([$this->fat], $data['locations'][1]['assigned_department_ids']);
        $this->getJson("/api/warehouse/locations/{$this->gaRack}/slots")->assertForbidden();
        $this->postJson("/api/warehouse/locations/{$this->gaRack}/update", ['rack_code' => 'GA-X'])->assertForbidden();

        $this->actingAs($this->admin)->getJson('/api/warehouse/layout-data')->assertOk()->assertJsonCount(3, 'locations');
    }

    public function test_add_rack_creates_its_100_slots_and_add_room_its_gudang(): void
    {
        $this->actingAs($this->gudang)->postJson('/api/warehouse/locations/store', $this->newObject('rack', 'RAK-R7-K'))
            ->assertOk()->assertJson(['success' => true, 'message' => "Object 'RAK-R7-K' berhasil ditambahkan ke canvas."]);
        $rack = DB::table('warehouse_locations')->where('rack_code', 'RAK-R7-K')->first();
        $this->assertSame(100, DB::table('warehouse_rack_slots')->where('warehouse_location_id', $rack->id)->count());
        $this->assertSame(['K1', 'K11', 'K100'], DB::table('warehouse_rack_slots')->where('warehouse_location_id', $rack->id)->whereIn('slot_number', [1, 11, 100])->orderBy('slot_number')->pluck('slot_code')->all());
        $this->assertSame(['TB 30g', 100, 1], [$rack->box_type, (int) $rack->box_capacity, (int) $rack->is_locked]);

        $this->postJson('/api/warehouse/locations/store', $this->newObject('room', 'GUDANG R9'))->assertOk();
        $warehouse = DB::table('warehouses')->where('code', 'GUDANG R9')->first();
        $this->assertSame('Gudang GUDANG R9', $warehouse->name);
        $this->assertDatabaseHas('warehouse_locations', ['rack_code' => 'GUDANG R9', 'location_type' => 'room', 'warehouse_id' => $warehouse->id]);
        $this->assertSame(2, DB::table('activity_logs')->where('action', 'LAYOUT_OBJECT_CREATE')->count());
    }

    public function test_object_fields_are_validated(): void
    {
        $this->actingAs($this->gudang);
        $this->postJson('/api/warehouse/locations/store', ['rack_code' => '', 'location_type' => 'shelf', 'canvas_x' => -5, 'canvas_y' => 0, 'canvas_width' => 10, 'canvas_height' => 50, 'custom_color' => 'red;x'])
            ->assertUnprocessable()->assertJsonValidationErrors(['rack_code', 'location_type', 'canvas_x', 'canvas_width', 'custom_color']);
        $this->postJson('/api/warehouse/locations/positions', ['items' => [['id' => $this->rack] + $this->geometry($this->rack, ['canvas_width' => 3])]])
            ->assertUnprocessable()->assertJsonValidationErrors(['items.0.canvas_width']);
        $this->postJson('/api/warehouse/locations/positions', ['items' => [$this->geometry(999999)]])->assertNotFound();
        $this->assertSame(1, DB::table('warehouse_locations')->where('canvas_width', 60)->where('id', $this->rack)->count());
    }

    public function test_positions_save_many_objects_in_one_request_last_write_wins(): void
    {
        $this->actingAs($this->gudang);
        $first = $this->postJson('/api/warehouse/locations/positions', ['items' => [$this->geometry($this->rack, ['canvas_x' => 100, 'canvas_width' => 16]), $this->geometry($this->fatRack, ['canvas_x' => 300])]])
            ->assertOk()->json('updated_at');
        $this->assertEqualsCanonicalizing([$this->rack, $this->fatRack], array_keys($first));
        $this->actingAs($this->admin)->postJson('/api/warehouse/locations/positions', ['items' => [$this->geometry($this->rack, ['canvas_x' => 150, 'rotation_angle' => 90])]])->assertOk();

        $this->assertDatabaseHas('warehouse_locations', ['id' => $this->rack, 'canvas_x' => 150, 'canvas_width' => 40, 'canvas_height' => 120, 'rotation_angle' => 90]);
        $this->assertDatabaseHas('warehouse_locations', ['id' => $this->fatRack, 'canvas_x' => 300]);
        $this->assertSame(2, DB::table('activity_logs')->where('action', 'LAYOUT_OBJECT_UPDATE')->count());
    }

    public function test_edit_refuses_a_stale_form_and_only_the_admin_sets_departments(): void
    {
        $opened = '2026-11-24 10:00:00';
        DB::table('warehouse_locations')->where('id', $this->rack)->update(['updated_at' => '2026-11-24 10:05:00']);

        $this->actingAs($this->gudang)->postJson("/api/warehouse/locations/{$this->rack}/update", ['rack_code' => 'R1-Z', 'expected_updated_at' => $opened])
            ->assertUnprocessable()->assertJsonPath('message', "Data object 'R1-B' sudah diubah user lain setelah Anda membukanya. Muat ulang halaman lalu ulangi.");
        $this->assertDatabaseHas('warehouse_locations', ['id' => $this->rack, 'rack_code' => 'R1-B']);

        $this->postJson("/api/warehouse/locations/{$this->rack}/update", ['rack_code' => 'R1-Z', 'box_capacity' => 80, 'assigned_department_ids' => [$this->hcs]])->assertOk();
        $this->assertDatabaseHas('warehouse_locations', ['id' => $this->rack, 'rack_code' => 'R1-Z', 'box_capacity' => 80, 'assigned_department_ids' => null]);

        $this->actingAs($this->admin)->postJson("/api/warehouse/locations/{$this->rack}/update", ['rack_code' => 'R1-Z', 'assigned_department_ids' => [$this->fat, $this->hcs], 'custom_color' => '#ef4444'])->assertOk();
        $row = DB::table('warehouse_locations')->find($this->rack);
        $this->assertSame([[$this->fat, $this->hcs], $this->fat, '#ef4444'], [json_decode($row->assigned_department_ids), (int) $row->assigned_department_id, $row->custom_color]);
    }

    public function test_booking_rules(): void
    {
        $this->actingAs($this->gudang);
        $this->postJson("/api/warehouse/locations/{$this->rack}/book", ['department_id' => $this->hcs])->assertUnprocessable()->assertJsonValidationErrors('booking_notes');
        $this->postJson("/api/warehouse/locations/{$this->fatRack}/book", ['department_id' => $this->hcs, 'booking_notes' => '15 box'])
            ->assertUnprocessable()->assertJsonPath('message', fn (string $m) => str_starts_with($m, 'Akses Ditolak: Lokasi ini dikhususkan untuk [FAT].'));

        $this->postJson("/api/warehouse/locations/{$this->rack}/book", ['department_id' => $this->hcs, 'booking_notes' => '15 box Faktur'])
            ->assertOk()->assertJsonPath('message', "Slot rak 'R1-B' berhasil di-booking untuk departemen HCS.");
        $this->assertDatabaseHas('warehouse_locations', ['id' => $this->rack, 'is_booked' => 1, 'assigned_department_id' => $this->hcs, 'booked_by_user_id' => $this->gudang->id, 'booking_notes' => '15 box Faktur']);

        $this->actingAs($this->admin)->postJson("/api/warehouse/locations/{$this->rack}/book", ['department_id' => $this->hcs, 'booking_notes' => 'timpa'])
            ->assertUnprocessable()->assertJsonPath('message', "Rak 'R1-B' sudah di-booking user lain. Muat ulang halaman.");
        $this->assertDatabaseHas('warehouse_locations', ['id' => $this->rack, 'booking_notes' => '15 box Faktur']);

        $this->postJson("/api/warehouse/locations/{$this->rack}/unbook")->assertOk();
        $this->assertDatabaseHas('warehouse_locations', ['id' => $this->rack, 'is_booked' => 0, 'booked_by_user_id' => null, 'booking_notes' => null]);
        $this->assertSame(['LAYOUT_SLOT_BOOK', 'LAYOUT_SLOT_UNBOOK'], DB::table('activity_logs')->whereIn('action', ['LAYOUT_SLOT_BOOK', 'LAYOUT_SLOT_UNBOOK'])->orderBy('id')->pluck('action')->all());
    }

    public function test_delete_refuses_a_rack_with_boxes_and_a_gudang_for_pic_gudang(): void
    {
        $box = $this->box('Box', 'in_warehouse', 'IND/HCS/2026/0001');
        DB::table('archives')->where('id', $box)->update(['warehouse_location_id' => $this->rack]);
        $this->actingAs($this->gudang)->postJson("/api/warehouse/locations/{$this->rack}/delete")
            ->assertUnprocessable()->assertJsonPath('message', "Rak 'R1-B' tidak dapat dihapus karena masih menampung box arsip aktif.");

        $this->postJson("/api/warehouse/locations/{$this->fatRack}/delete")->assertOk()->assertJsonPath('message', "Object 'R2-F' berhasil dihapus dari canvas.");
        $this->assertDatabaseMissing('warehouse_locations', ['id' => $this->fatRack]);

        $this->postJson('/api/warehouse/locations/store', $this->newObject('room', 'GUDANG R9'))->assertOk();
        $room = (int) DB::table('warehouse_locations')->where('rack_code', 'GUDANG R9')->value('id');
        $this->postJson("/api/warehouse/locations/{$room}/delete")->assertForbidden();
        $this->actingAs($this->admin)->postJson("/api/warehouse/locations/{$room}/delete")->assertOk();
        $this->assertDatabaseMissing('warehouses', ['code' => 'GUDANG R9']);
        $this->assertDatabaseMissing('warehouse_locations', ['id' => $room]);
    }

    /** @return array<string, mixed> */
    private function newObject(string $type, string $code): array
    {
        return ['location_type' => $type, 'rack_code' => $code, 'room_sector' => 'R7', 'canvas_x' => 40, 'canvas_y' => 60,
            'canvas_width' => $type === 'room' ? 160 : 40, 'canvas_height' => $type === 'room' ? 140 : 120, 'custom_color' => '#10b981'];
    }

    /**
     * @param  array<string, int>  $override
     * @return array<string, int>
     */
    private function geometry(int $id, array $override = []): array
    {
        return $override + ['id' => $id, 'canvas_x' => 10, 'canvas_y' => 20, 'canvas_width' => 40, 'canvas_height' => 120];
    }
}
