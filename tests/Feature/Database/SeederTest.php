<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seeded inside the test transaction: the in-memory schema is migrated once per run.
        $this->seed();
    }

    public function test_fresh_install_gets_the_laravel7_master_data_without_test_data(): void
    {
        $this->assertSame(29, DB::table('departments')->count());
        $this->assertSame(14, DB::table('sub_departments')->count());
        $this->assertSame(17, DB::table('master_archives')->count());
        $this->assertSame(31, DB::table('users')->count());
        $this->assertSame(9, DB::table('warehouses')->count());
        $this->assertSame(89, DB::table('warehouse_locations')->count());
        $this->assertSame(8000, DB::table('warehouse_rack_slots')->count());
        $this->assertSame(1, DB::table('numbering_formats')->where('current_counter', 0)->count());
        $this->assertSame(5, DB::table('app_settings')->count());
        $this->assertSame(0, DB::table('archives')->count());

        $this->assertSame(['B1', 'B10', 'B11', 'B20', 'B100'], DB::table('warehouse_rack_slots')
            ->where('warehouse_location_id', DB::table('warehouse_locations')->where('rack_code', 'R1-B')->value('id'))
            ->whereIn('slot_number', [1, 10, 11, 20, 100])->orderBy('slot_number')->pluck('slot_code')->all());
    }

    public function test_fresh_install_gets_the_production_warehouse_layout_without_bookings(): void
    {
        $finId = DB::table('departments')->where('name', 'FIN, ACC & TAX')->value('id');
        $room = DB::table('warehouse_locations')->where('rack_code', 'GUDANG R1')->first();
        $rack = DB::table('warehouse_locations')->where('rack_code', 'R1-B')->first();

        $this->assertNotNull($room);
        $this->assertNotNull($rack);
        $this->assertSame([515, 49, 265, 234], [$room->canvas_x, $room->canvas_y, $room->canvas_width, $room->canvas_height]);
        $this->assertSame($finId, $room->assigned_department_id);
        $this->assertSame("[{$finId}]", $room->assigned_department_ids);
        $this->assertSame('Gudang GUDANG R1', DB::table('warehouses')->where('id', $room->warehouse_id)->value('name'));
        $this->assertSame($finId, $rack->assigned_department_id);
        $this->assertSame('[]', DB::table('warehouse_locations')->where('rack_code', 'GUDANG R3')->value('assigned_department_ids'));
        $this->assertSame(9, DB::table('warehouse_locations')->where('location_type', 'room')->count());
        $this->assertSame(0, DB::table('warehouse_locations')->where(fn ($q) => $q->where('is_booked', 1)->orWhereNotNull('booked_by_user_id')->orWhere('current_box_count', '>', 0))->count());
        $this->assertSame(0, DB::table('warehouse_rack_slots')->where(fn ($q) => $q->whereNotNull('archive_id')->orWhere('status', '<>', 'empty')->orWhere('is_active', 0))->count());
    }

    public function test_default_accounts_use_the_laravel7_default_password(): void
    {
        $admin = DB::table('users')->where('email', 'admin@indraco.com')->first();
        $fin = DB::table('users')->where('email', 'fin@indraco.com')->first();

        $this->assertNotNull($admin);
        $this->assertNotNull($fin);
        $this->assertSame('admin', $admin->role);
        $this->assertSame('pic_dept', $fin->role);
        $this->assertSame(DB::table('departments')->where('code', 'FIN')->value('id'), $fin->department_id);
        $this->assertTrue(Hash::check('password', $admin->password));
    }
}
