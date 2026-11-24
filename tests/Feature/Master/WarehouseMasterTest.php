<?php

namespace Tests\Feature\Master;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Archive\ArchiveFixtures;
use Tests\TestCase;

/**
 * Master Gudang & Rak (Laravel 7 WarehouseController): PIC Gudang views and adds, admin edits /
 * deletes / switches; the gudang's room on the 2D layout follows; racks or gudang holding boxes
 * cannot be deleted.
 */
class WarehouseMasterTest extends TestCase
{
    use ArchiveFixtures;
    use RefreshDatabase;

    // ArchiveFixtures is set up by Laravel (setUpArchiveFixtures runs automatically for the trait).

    public function test_pic_gudang_views_and_adds_but_only_admin_edits(): void
    {
        $this->actingAs($this->gudang)->post('/master/warehouses', ['code' => 'GUDANG-C', 'name' => 'Depo Blok C', 'address' => 'Kawasan'])
            ->assertRedirect('/master/warehouses')->assertSessionHas('success', 'Gudang Depo Blok C berhasil ditambahkan.');
        $warehouse = (int) DB::table('warehouses')->where('code', 'GUDANG-C')->value('id');
        $room = DB::table('warehouse_locations')->where('location_type', 'room')->first();
        $this->assertSame([$warehouse, 'GUDANG-C', 'GUDANG-C', 1], [(int) $room->warehouse_id, $room->rack_code, $room->room_sector, (int) $room->is_locked]);

        $this->actingAs($this->gudang)->post('/master/warehouses/locations', ['warehouse_id' => $warehouse, 'rack_code' => 'RAK-A1', 'shelf_code' => 'BARIS-01', 'box_capacity' => 100])
            ->assertSessionHas('success', 'Lokasi penyimpanan rak/baris berhasil ditambahkan.');
        $rack = (int) DB::table('warehouse_locations')->where('rack_code', 'RAK-A1')->value('id');
        $this->actingAs($this->gudang)->get('/master/warehouses')->assertOk()->assertSee('RAK-A1');

        $this->actingAs($this->gudang)->put("/master/warehouses/{$warehouse}", ['code' => 'X', 'name' => 'X'])->assertForbidden();
        $this->actingAs($this->gudang)->delete("/master/warehouses/{$warehouse}")->assertForbidden();
        $this->actingAs($this->gudang)->postJson("/master/warehouses/{$warehouse}/toggle-active")->assertForbidden();
        $this->actingAs($this->gudang)->put("/master/warehouses/locations/{$rack}", ['warehouse_id' => $warehouse, 'rack_code' => 'X', 'shelf_code' => 'X', 'box_capacity' => 1])->assertForbidden();
        $this->actingAs($this->gudang)->delete("/master/warehouses/locations/{$rack}")->assertForbidden();
        $this->actingAs($this->picHcs)->get('/master/warehouses')->assertForbidden();
        $this->actingAs($this->picHcs)->post('/master/warehouses', ['code' => 'Y', 'name' => 'Y'])->assertForbidden();
    }

    public function test_admin_update_renames_the_room_and_toggle_switches_it(): void
    {
        $this->actingAs($this->admin)->post('/master/warehouses', ['code' => 'G1', 'name' => 'Gudang Utama']);
        $warehouse = (int) DB::table('warehouses')->value('id');
        $this->actingAs($this->admin)->post('/master/warehouses', ['code' => 'G1', 'name' => 'Lain'])->assertSessionHasErrors('code');

        $this->actingAs($this->admin)->put("/master/warehouses/{$warehouse}", ['code' => 'G2', 'name' => 'Gudang Utama', 'address' => 'Blok B'])
            ->assertSessionHas('success', 'Data gudang Gudang Utama berhasil diperbarui.');
        $this->assertSame('G2', DB::table('warehouse_locations')->where('location_type', 'room')->value('rack_code'));

        $this->actingAs($this->admin)->postJson("/master/warehouses/{$warehouse}/toggle-active", ['is_active' => false])
            ->assertOk()->assertJson(['success' => true, 'is_active' => false, 'message' => 'Status gudang Gudang Utama berhasil diubah menjadi NON-AKTIF.']);
        $this->assertSame([0, 0], [(int) DB::table('warehouses')->value('is_active'), (int) DB::table('warehouse_locations')->where('location_type', 'room')->value('is_active')]);
    }

    public function test_gudang_and_rack_holding_boxes_cannot_be_deleted(): void
    {
        $warehouse = DB::table('warehouses')->insertGetId(['code' => 'G1', 'name' => 'Utama']);
        $rack = DB::table('warehouse_locations')->insertGetId(['warehouse_id' => $warehouse, 'location_type' => 'rack', 'rack_code' => 'R01', 'shelf_code' => 'S1']);
        DB::table('warehouse_rack_slots')->insert(['warehouse_location_id' => $rack, 'sap_level' => 1, 'layer' => 'A', 'slot_number' => 1, 'slot_code' => 'S1', 'status' => 'filled']);

        $this->actingAs($this->admin)->delete("/master/warehouses/locations/{$rack}")
            ->assertSessionHas('error', 'Lokasi rak ini tidak dapat dihapus karena masih menampung box arsip aktif.');
        $this->actingAs($this->admin)->delete("/master/warehouses/{$warehouse}")
            ->assertSessionHas('error', 'Gudang Utama tidak dapat dihapus karena raknya masih menampung box arsip aktif.');
        $this->assertSame(1, DB::table('warehouse_rack_slots')->count());

        DB::table('warehouse_rack_slots')->update(['status' => 'empty']);
        $this->actingAs($this->admin)->delete("/master/warehouses/locations/{$rack}")->assertSessionHas('success', 'Lokasi rak berhasil dihapus.');
        $this->actingAs($this->admin)->delete("/master/warehouses/{$warehouse}")->assertSessionHas('success', 'Gudang Utama berhasil dihapus.');
        $this->assertSame(0, DB::table('warehouses')->count());
    }

    public function test_gudang_ga_is_only_listed_for_the_admin(): void
    {
        DB::table('warehouses')->insert([['code' => 'GUDANG GA', 'name' => 'GA'], ['code' => 'G1', 'name' => 'Utama']]);

        $this->actingAs($this->admin)->get('/master/warehouses')->assertSee('GUDANG GA')->assertSee('Khusus Super Admin');
        $this->actingAs($this->gudang)->get('/master/warehouses')->assertDontSee('GUDANG GA')->assertSee('G1');
    }
}
