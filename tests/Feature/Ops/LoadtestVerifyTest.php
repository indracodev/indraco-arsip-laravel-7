<?php

namespace Tests\Feature\Ops;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Borrowing\BorrowingFixtures;
use Tests\TestCase;

/** dms:loadtest-verify: box counters of LOADTEST boxes unique and without gaps, per run or all runs. */
class LoadtestVerifyTest extends TestCase
{
    use BorrowingFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBorrowingFixtures();
    }

    public function test_contiguous_counters_pass_even_when_a_number_is_held_by_a_normal_box(): void
    {
        $this->box('LOADTEST-r1-a', 'IND/HCS/2026/0001');
        $this->box('Arsip biasa', 'IND/FAT/2026/0002');
        $this->box('LOADTEST-r2-b', 'IND/FAT/2026/0003');

        $this->artisan('dms:loadtest-verify')->expectsOutputToContain('OK')->assertSuccessful();
        $this->artisan('dms:loadtest-verify', ['run' => 'r1'])->assertSuccessful();
    }

    public function test_gaps_and_duplicate_counters_fail(): void
    {
        $this->box('LOADTEST-r1-a', 'IND/HCS/2026/0001');
        $this->box('LOADTEST-r1-b', 'IND/FAT/2026/0004');
        $this->box('LOADTEST-r2-c', 'IND/FAT/2025/0004');

        $this->artisan('dms:loadtest-verify')
            ->expectsOutputToContain('lompat): 2, 3')
            ->expectsOutputToContain('Counter box 4 dipakai 2')
            ->assertFailed();
    }

    private function box(string $title, string $boxNumber): void
    {
        DB::table('archives')->where('id', $this->storedBox($this->hcs, $title))->update(['box_number' => $boxNumber]);
    }
}
