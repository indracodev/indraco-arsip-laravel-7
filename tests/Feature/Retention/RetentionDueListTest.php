<?php

namespace Tests\Feature\Retention;

use App\Models\Archive;
use App\Services\RetentionCalculator;
use App\Services\RetentionList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * P1: the H-30 filter runs in SQL (stored expiry) and must give exactly the boxes of the PHP rule
 * (RetentionCalculator::isDue, Laravel 7), in the same order; each tab is one page with ?q= search.
 */
class RetentionDueListTest extends TestCase
{
    use RefreshDatabase;
    use RetentionFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRetentionFixtures();
    }

    public function test_sql_due_filter_matches_the_php_rule_on_edge_dates(): void
    {
        foreach (['2023-12-31', '2024-01-31', '2024-02-01', '2024-02-29', '2024-03-31', '2024-12-31', '2025-01-15', '2025-02-28', '2025-03-01'] as $i => $expiry) {
            $id = $this->expiringBox($this->hcs, "EXP-{$i}", $expiry);
        }
        DB::table('archives')->where('id', $id)->update(['retention_expiry_date' => '2025-03-01']); // stored without time
        // No stored expiry (legacy rows; period_end_date is NOT NULL): period end + years (2019-02-28 + 5 -> Feb 2024),
        // "N bulan" period (no years), retention 0 -> 5 years, free period text.
        $legacy = [
            ['period_end_date' => '2019-02-28', 'periode_doc' => '2019/02', 'retention_years' => 5],
            ['period_end_date' => '2024-01-15', 'periode_doc' => '1 Bulan', 'retention_years' => 5],
            ['period_end_date' => '2023-02-28', 'periode_doc' => '2023/02', 'retention_years' => 0],
            ['period_end_date' => '2019-12-31', 'periode_doc' => 'abc', 'retention_years' => 5],
        ];
        foreach ($legacy as $i => $attributes) {
            DB::table('archives')->where('id', $this->storedBox($this->fat, "OLD-{$i}"))->update($attributes + ['retention_expiry_date' => null]);
        }

        $calc = new RetentionCalculator;
        $list = app(RetentionList::class);
        // H-30 boundary of Feb 2024 (29 Feb - 30 = 30 Jan) and Feb 2025 (28 Feb - 30 = 29 Jan), month ends, expiry today, after.
        foreach (['2023-12-01', '2024-01-01', '2024-01-29', '2024-01-30', '2024-02-29', '2024-03-01', '2024-11-30', '2024-12-01', '2025-01-28', '2025-01-29', '2025-03-01'] as $today) {
            Carbon::setTestNow($today.' 10:00:00');
            $expected = $list->candidates($this->gudang)->get()
                ->map(fn (Archive $a): array => ['id' => $a->id, 'end' => $calc->expiryEnd($a)])
                ->filter(fn (array $row): bool => $row['end'] !== null && $calc->isDue($row['end']))
                ->sortBy(fn (array $row): string => $row['end']->format('Y-m-d').sprintf('%08d', $row['id']))->pluck('id')->values()->all();
            $sql = $list->due($this->gudang)->orderBy('expiry_end')->orderBy('archives.id')->pluck('archives.id')->all();

            $this->assertSame($expected, $sql, "today {$today}");
            $this->assertSame(count($expected), $list->counts($this->gudang)['h30'], "count {$today}");
        }
        $this->assertNotSame([], $expected);
    }

    public function test_tabs_are_paginated_keep_the_tab_and_search_beyond_page_one(): void
    {
        for ($i = 1; $i <= RetentionList::PER_PAGE + 1; $i++) {
            $this->expiringBox($this->hcs, sprintf('DUE-%03d', $i), $i <= RetentionList::PER_PAGE ? '2026-10-31' : '2026-11-30');
        }

        $this->actingAs($this->picHcs)->get('/destructions?tab=h30')->assertOk()
            ->assertSee('DUE-050')->assertDontSee('DUE-051')->assertSee('tab=h30&amp;page=2', false)->assertSee('>51</span>', false);
        $this->actingAs($this->picHcs)->get('/destructions?tab=h30&page=2')->assertOk()->assertSee('DUE-051')->assertDontSee('DUE-050');
        $this->actingAs($this->picHcs)->get('/destructions?tab=h30&q=DUE-051')->assertOk()->assertSee('DUE-051')->assertDontSee('DUE-001')
            ->assertSee('value="DUE-051"', false);

        // Tab chosen by the session (after an action): page links still carry it.
        $this->actingAs($this->gudang)->withSession(['active_tab' => 'h30'])->get('/destructions')->assertOk()->assertSee('tab=h30&amp;page=2', false);
    }

    public function test_history_search_runs_on_the_server(): void
    {
        $this->proposed([$this->expiringBox($this->hcs, 'BOX-ALPHA')]);
        $this->proposed([$this->expiringBox($this->hcs, 'BOX-BETA')]);
        $this->extended([$this->expiringBox($this->hcs, 'BOX-GAMMA')]);
        $this->extended([$this->expiringBox($this->hcs, 'BOX-DELTA')]);

        $this->actingAs($this->picHcs)->get('/destructions?tab=destructions&q=alpha')->assertOk()->assertSee('BOX-ALPHA')->assertDontSee('BOX-BETA');
        $this->actingAs($this->picHcs)->get('/destructions?tab=destructions&q=Pengajuan Pemusnahan')->assertOk()->assertSee('BOX-ALPHA')->assertSee('BOX-BETA');
        $this->actingAs($this->picHcs)->get('/destructions?tab=extensions&q=gamma')->assertOk()->assertSee('BOX-GAMMA')->assertDontSee('BOX-DELTA');
        $this->actingAs($this->picFat)->get('/destructions?tab=extensions&q=gamma')->assertOk()->assertDontSee('BOX-GAMMA');
    }
}
