<?php

namespace Tests\Feature\Warehouse;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Archive\ArchiveFixtures;

/**
 * GUDANG setup on top of the archive fixtures (HCS / FAT, users per role): the production
 * numbering format, one warehouse with an open rack (3 slots) and a rack reserved for FAT.
 * Laravel already calls setUpArchiveFixtures() (setUp<Trait> of every used trait, recursively),
 * so prepareWarehouse() is called by the test's setUp() after it, and not named setUp*.
 */
trait WarehouseFixtures
{
    use ArchiveFixtures;

    protected int $format;

    protected int $rack;

    protected int $fatRack;

    protected function prepareWarehouse(): void
    {
        $this->format = DB::table('numbering_formats')->insertGetId(['name' => 'Box', 'pattern' => '{COMPANY}/{DEPT}/{YEAR}/{COUNTER}', 'padding' => 4, 'current_counter' => 0, 'is_active' => 1]);
        $warehouse = DB::table('warehouses')->insertGetId(['code' => 'G1', 'name' => 'Gudang Utama']);
        $this->rack = DB::table('warehouse_locations')->insertGetId(['warehouse_id' => $warehouse, 'rack_code' => 'R1-B', 'shelf_code' => 'S1', 'box_capacity' => 20]);
        $this->fatRack = DB::table('warehouse_locations')->insertGetId(['warehouse_id' => $warehouse, 'rack_code' => 'R2-F', 'shelf_code' => 'S1', 'assigned_department_ids' => json_encode([$this->fat])]);
        foreach ([1, 2, 3] as $n) {
            DB::table('warehouse_rack_slots')->insert(['warehouse_location_id' => $this->rack, 'sap_level' => 1, 'layer' => 'bottom', 'slot_number' => $n, 'slot_code' => 'B1'.$n]);
        }
    }

    /** A box with the given status (default: submitted, waiting for verification). */
    protected function box(string $title, string $status = 'pending_verification', ?string $boxNumber = null, ?int $department = null): int
    {
        return DB::table('archives')->insertGetId([
            'department_id' => $department ?? $this->hcs, 'created_by_user_id' => $this->picHcs->id, 'title' => $title, 'status' => $status,
            'box_number' => $boxNumber, 'period_start_date' => '2026-01-01', 'period_end_date' => '2026-06-30', 'periode' => '6 Bulan',
            'retention_expiry_date' => '2026-06-30', 'tgl_penyerahan' => '2026-11-20', 'content_description' => '1. Kontrak Kerja', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function counter(): int
    {
        return (int) DB::table('numbering_formats')->where('id', $this->format)->value('current_counter');
    }

    /** @return object{status: string, box_number: ?string, rejection_note: ?string, warehouse_location_id: ?int, warehouse_rack_slot_id: ?int} */
    protected function archive(int $id): object
    {
        return DB::table('archives')->find($id);
    }
}
