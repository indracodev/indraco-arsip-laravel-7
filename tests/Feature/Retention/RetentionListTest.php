<?php

namespace Tests\Feature\Retention;

use App\Models\Archive;
use App\Models\Department;
use App\Services\RetentionCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Retention math against Laravel 7 rows (production DB copy, 2026-11-24) and the [Form 3] list:
 * H-30 markers, department scoping, role buttons, legacy extension history.
 */
class RetentionListTest extends TestCase
{
    use RefreshDatabase;
    use RetentionFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRetentionFixtures();
    }

    /** @param array<string, mixed> $attributes */
    private function archive(array $attributes): Archive
    {
        return (new Archive)->forceFill($attributes);
    }

    public function test_expiry_and_days_match_laravel_7_rows(): void
    {
        $calc = new RetentionCalculator;
        $rows = [
            // [Laravel 7 archive, attributes, expected end, days left, listed]
            '#42 HRD' => [['retention_expiry_date' => '2026-11-30 00:00:00'], '2026-11-30', 6, true],
            '#41 HRD' => [['retention_expiry_date' => '2026-10-31 00:00:00'], '2026-10-31', -23, true],
            '#34 HRD' => [['retention_expiry_date' => '2026-08-07 00:00:00'], '2026-08-31', -84, true],
            '#32 HRD' => [['retention_expiry_date' => '2027-10-07 00:00:00'], '2027-10-31', 341, false],
            '#36 TEST_DEPT (no expiry, period + 5 y)' => [['period_end_date' => '2026-09-30', 'periode_doc' => '2026/09', 'retention_years' => 5], '2031-09-30', null, false],
            'monthly period without dates' => [['periode_doc' => '1 Bulan', 'tgl_penyerahan' => '2026-10-08'], '2026-11-30', 6, true],
        ];
        foreach ($rows as $name => [$attributes, $end, $days, $listed]) {
            $expiry = $calc->expiryEnd($this->archive($attributes));
            $this->assertSame($end.' 23:59:59', $expiry?->format('Y-m-d H:i:s'), $name);
            if ($days !== null) {
                $this->assertSame($days, $calc->daysLeft($expiry), $name);
            }
            $this->assertSame($listed, $calc->isDue($expiry), $name);
        }
        $this->assertNull($calc->expiryEnd($this->archive(['periode_doc' => 'abc'])));
    }

    public function test_extension_math_matches_laravel_7_examples(): void
    {
        $calc = new RetentionCalculator;
        // #38 DM (old code log 912): expiry Oct 2026 + 1 month = 30 Nov 2026 (no month overflow).
        $this->assertSame(['previous' => '2026-10-31', 'new' => '2026-11-30'], array_map(fn ($d) => $d->format('Y-m-d'), $calc->extendedExpiry($this->archive(['retention_expiry_date' => '2026-10-31']), 1)));
        // #94 FAT: periode "12 Bulan", retention 10 y, expiry 2034-12-31, +120 months.
        $fat = $this->archive(['retention_expiry_date' => '2034-12-31', 'periode' => '12 Bulan', 'retention_years' => 10]);
        $this->assertSame('2044-12-31', $calc->extendedExpiry($fat, 120)['new']->format('Y-m-d'));
        $this->assertSame(['retention_years' => 11, 'periode' => '11 Tahun'], $calc->approvedRetention($fat, 120));
        // #38 approve: "1 Bulan" + 1 = 2 Bulan, 0 years; no period: retention years x 12; nothing: 60.
        $this->assertSame(['retention_years' => 0, 'periode' => '2 Bulan'], $calc->approvedRetention($this->archive(['periode' => '1 Bulan']), 1));
        $this->assertSame(['retention_years' => 6, 'periode' => '6 Tahun'], $calc->approvedRetention($this->archive(['retention_years' => 5]), 12));
        $this->assertSame(['retention_years' => 5, 'periode' => '66 Bulan'], $calc->approvedRetention($this->archive([]), 6));
        // No expiry: base = period end.
        $this->assertSame('2027-06-30', $calc->extendedExpiry($this->archive(['period_end_date' => '2026-06-30']), 12)['new']->format('Y-m-d'));
        $this->assertSame([120, 60, 120], [RetentionCalculator::maxExtensionMonths(null, Department::query()->find($this->fat)), RetentionCalculator::maxExtensionMonths(null), RetentionCalculator::maxExtensionMonths(Department::query()->find($this->fat))]);
    }

    public function test_h30_tab_shows_own_due_candidates_with_markers(): void
    {
        $this->expiringBox($this->hcs, 'HCS-DUE', '2026-11-30');
        $this->expiringBox($this->hcs, 'HCS-LATE', '2026-10-31');
        $this->expiringBox($this->hcs, 'HCS-LATER', '2027-10-07');
        $this->expiringBox($this->hcs, 'HCS-VERIF', '2026-10-31', 'pending_verification');
        $this->expiringBox($this->fat, 'FAT-DUE', '2026-11-30');

        $this->actingAs($this->picHcs)->get('/destructions')->assertOk()
            ->assertSee('PENGAJUAN PERPANJANGAN & PEMUSNAHAN BERKAS')->assertSee('Berkas Jatuh Tempo (H-30)')
            ->assertSeeInOrder(['HCS-LATE', 'Lewat 23 Hari', 'HCS-DUE', 'Jatuh Tempo Bulan Ini (Sisa 6 Hari)'])
            ->assertDontSee('HCS-LATER')->assertDontSee('HCS-VERIF')->assertDontSee('FAT-DUE')
            ->assertSee(route('destructions.propose'), false)->assertSee('Pemusnahan (');

        $this->actingAs($this->gudang)->get('/destructions')->assertOk()->assertSee('FAT-DUE')->assertSee('Menunggu Dept')
            ->assertDontSee(route('destructions.propose.archive', 1), false);
    }

    public function test_history_tabs_are_scoped_and_show_role_buttons(): void
    {
        $hcsBox = $this->expiringBox($this->hcs, 'HCS-01');
        $fatBox = $this->expiringBox($this->fat, 'FAT-01');
        $this->proposed([$hcsBox]);
        $this->proposed([$fatBox], $this->picFat);
        $this->extended([$this->expiringBox($this->hcs, 'HCS-EXT')]);

        $this->actingAs($this->picHcs)->get('/destructions?tab=destructions')->assertOk()
            ->assertSee('HCS-01')->assertDontSee('FAT-01')->assertSee('(Menunggu Penerbitan BAP)')->assertDontSee('Setujui</button>', false);
        $this->actingAs($this->gudang)->get('/destructions?tab=destructions')->assertOk()
            ->assertSee('HCS-01')->assertSee('FAT-01')->assertSee('Setujui')->assertSee('Tolak Pengajuan Pemusnahan');
        $this->actingAs($this->picHcs)->get('/destructions?tab=extensions')->assertOk()->assertSee('HCS-EXT')->assertSee('Menunggu ACC Gudang')->assertSee('Cetak Form');
        $this->actingAs($this->gudang)->get('/destructions?tab=extensions')->assertOk()->assertSee('ACC Perpanjangan')->assertSee('Tolak Pengajuan Perpanjangan');
    }

    public function test_legacy_extension_from_the_old_code_is_listed(): void
    {
        DB::table('activity_logs')->insert(['action' => 'RETENTION_EXTEND', 'module' => 'PEMUSNAHAN_RETENSI', 'department_id' => $this->hcs, 'user_name' => 'PIC_HCS',
            'description' => 'Perpanjangan lama', 'reference_id' => 'IND/DM/2026/X/0018', 'created_at' => now(),
            'properties' => json_encode(['archive_id' => 999, 'additional_months' => 1, 'new_expiry_date' => '2026-11-30', 'extension_reason' => 'qwerty'])]);
        $this->actingAs($this->picHcs)->get('/destructions?tab=extensions')->assertOk()->assertSee('IND/DM/2026/X/0018')->assertSee('+1 Bulan')->assertSee('Nov 2026')->assertSee('Disetujui Gudang');
        $this->actingAs($this->picFat)->get('/destructions?tab=extensions')->assertOk()->assertDontSee('IND/DM/2026/X/0018');
    }
}
