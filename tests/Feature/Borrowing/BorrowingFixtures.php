<?php

namespace Tests\Feature\Borrowing;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Archive\ArchiveFixtures;

/**
 * PENARIKAN setup on top of the archive fixtures (HCS / FAT, users per role): boxes stored in a
 * rack slot (`in_warehouse`) and a valid request payload.
 */
trait BorrowingFixtures
{
    use ArchiveFixtures;

    protected int $location;

    protected function setUpBorrowingFixtures(): void
    {
        $this->setUpArchiveFixtures();
        $warehouse = DB::table('warehouses')->insertGetId(['code' => 'G1', 'name' => 'Gudang Utama']);
        $this->location = DB::table('warehouse_locations')->insertGetId(['warehouse_id' => $warehouse, 'rack_code' => 'R01', 'shelf_code' => 'S1', 'current_box_count' => 2]);
    }

    /** A box in the warehouse (rack slot filled) with one document line. */
    protected function storedBox(int $departmentId, string $title, string $status = 'in_warehouse'): int
    {
        $id = DB::table('archives')->insertGetId([
            'department_id' => $departmentId, 'created_by_user_id' => $this->admin->id, 'title' => $title,
            'box_number' => 'IND/'.$title, 'period_start_date' => '2026-01-01', 'period_end_date' => '2026-06-30',
            'content_description' => '1. Kontrak Kerja', 'status' => $status, 'periode' => '6 Bulan',
            'warehouse_location_id' => $this->location, 'created_at' => now()->subDay(), 'updated_at' => now()->subDay(),
        ]);
        $slot = DB::table('warehouse_rack_slots')->insertGetId([
            'warehouse_location_id' => $this->location, 'sap_level' => 1, 'layer' => 'A', 'slot_number' => $id, 'slot_code' => 'S'.$id, 'archive_id' => $id, 'status' => 'filled',
        ]);
        DB::table('archives')->where('id', $id)->update(['warehouse_rack_slot_id' => $slot]);
        DB::table('archive_items')->insert(['archive_id' => $id, 'item_number' => 1, 'document_name' => 'Kontrak Kerja', 'period_text' => 'Jan 2026', 'notes' => 'No 1-50']);

        return $id;
    }

    /**
     * @param  list<int>  $archiveIds
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function request(array $archiveIds, array $overrides = []): array
    {
        return array_replace(['archive_ids' => $archiveIds, 'purpose' => 'Keperluan audit', 'approval_file' => $this->pdf('approval.pdf'), 'is_permanent' => '1'], $overrides);
    }

    /** Submit a request as $user and return the created log id. @param list<int> $archiveIds */
    protected function requested(array $archiveIds, ?User $user = null): int
    {
        $this->actingAs($user ?? $this->picHcs)->post('/borrowings', $this->request($archiveIds))->assertSessionHasNoErrors()->assertSessionMissing('error');

        return (int) DB::table('borrowing_logs')->max('id');
    }
}
