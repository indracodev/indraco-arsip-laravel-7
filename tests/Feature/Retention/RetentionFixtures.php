<?php

namespace Tests\Feature\Retention;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Borrowing\BorrowingFixtures;

/**
 * RETENSI setup on top of the penarikan fixtures (HCS / FAT, users per role, boxes in a rack
 * slot, time frozen at 2026-11-24): boxes with a retention expiry and valid payloads.
 */
trait RetentionFixtures
{
    use BorrowingFixtures;

    protected function setUpRetentionFixtures(): void
    {
        $this->setUpBorrowingFixtures();
    }

    /** A stored box whose retention ends on $expiry (Y-m-d). */
    protected function expiringBox(int $departmentId, string $title, string $expiry = '2026-11-30', string $status = 'in_warehouse'): int
    {
        $id = $this->storedBox($departmentId, $title, $status);
        DB::table('archives')->where('id', $id)->update(['retention_expiry_date' => $expiry.' 00:00:00', 'retention_years' => 1, 'periode' => '12 Bulan']);

        return $id;
    }

    /**
     * @param  list<int>  $archiveIds
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function proposal(array $archiveIds, array $overrides = []): array
    {
        return array_replace(['archive_ids' => $archiveIds, 'destruction_date' => '2026-11-24', 'method' => 'Pencacahan Mesin Industrial Paper Shredder',
            'notes' => 'Masa simpan habis', 'approval_file' => $this->pdf('approval.pdf')], $overrides);
    }

    /**
     * @param  list<int>  $archiveIds
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function extension(array $archiveIds, array $overrides = []): array
    {
        return array_replace(['archive_ids' => $archiveIds, 'additional_months' => 12, 'additional_years' => 1, 'extension_reason' => 'Audit pajak'], $overrides);
    }

    /** Propose as $user and return the created destruction log id. @param list<int> $archiveIds */
    protected function proposed(array $archiveIds, ?User $user = null): int
    {
        $this->actingAs($user ?? $this->picHcs)->post('/destructions/propose', $this->proposal($archiveIds))->assertSessionHasNoErrors();

        return (int) DB::table('destruction_logs')->max('id');
    }

    /** Request an extension as $user and return the log id. @param list<int> $archiveIds */
    protected function extended(array $archiveIds, ?User $user = null, int $months = 12): int
    {
        $this->actingAs($user ?? $this->picHcs)->post('/destructions/extend', $this->extension($archiveIds, ['additional_months' => $months]))->assertSessionHasNoErrors();

        return (int) DB::table('archive_extension_logs')->max('id');
    }
}
