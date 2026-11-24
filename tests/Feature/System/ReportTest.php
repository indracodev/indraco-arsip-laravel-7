<?php

namespace Tests\Feature\System;

use App\Models\User;
use App\Services\ReportBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Borrowing\BorrowingFixtures;
use Tests\TestCase;

/**
 * Pusat Laporan: CSV export (formula-safe, streamed), filters, rack occupancy and expired boxes.
 */
class ReportTest extends TestCase
{
    use BorrowingFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBorrowingFixtures();
    }

    public function test_csv_cells_starting_with_a_formula_character_are_escaped(): void
    {
        foreach (['=1+1' => "'=1+1", '+62812' => "'+62812", '-5' => "'-5", '@SUM(A1)' => "'@SUM(A1)", "\tx" => "'\tx", 'Aman' => 'Aman', '' => '', '3 Box' => '3 Box'] as $in => $out) {
            $this->assertSame($out, ReportBuilder::csvCell($in));
        }
        $this->assertSame('7', ReportBuilder::csvCell(7));
    }

    public function test_csv_export_streams_escaped_rows(): void
    {
        $evil = User::factory()->create(['name' => '=HYPERLINK("http://x","klik")', 'role' => 'pic_dept', 'department_id' => $this->hcs, 'phone' => '+628123']);

        $response = $this->actingAs($this->admin)->get('/reports/export-csv/users?department_id='.$this->hcs)->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Laporan_Laporan_Pengguna', (string) $response->headers->get('Content-Disposition'));
        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF\"PT INDRACO - DOCUMENT MANAGEMENT SYSTEM\"", $csv);
        $this->assertStringContainsString(',-,', $csv, 'a lone "-" placeholder is not a formula: left as is');
        $this->assertStringContainsString('"\'=HYPERLINK(""http://x"",""klik"")"', $csv);
        $this->assertStringContainsString("'+628123", $csv);
        $this->assertStringNotContainsString(',=HYPERLINK', $csv);
        $this->assertStringContainsString('PIC_HCS', $csv);
        $this->assertStringNotContainsString('PIC_FINANCE', $csv, 'department filter applies to the export');
        $this->assertNotNull($evil->id);
    }

    public function test_rack_usage_counts_boxes_whose_retention_ended(): void
    {
        DB::table('warehouse_locations')->where('id', $this->location)->update(['location_type' => 'rack', 'box_capacity' => 2, 'current_box_count' => 2]);
        $expired = $this->storedBox($this->hcs, 'BOX-OLD');
        $this->storedBox($this->hcs, 'BOX-NEW');
        DB::table('archives')->where('id', $expired)->update(['retention_expiry_date' => '2026-10-01']);

        $this->actingAs($this->admin)->get('/reports?type=rack_usage')->assertOk()
            ->assertSee('1 Box')->assertSee('Penuh (100%)')->assertSee('Box Retensi Habis');
        $csv = $this->actingAs($this->admin)->get('/reports/export-csv/full_racks_2d')->streamedContent();
        $this->assertStringContainsString('R01', $csv);
        $this->assertStringContainsString('100%', $csv);
    }

    public function test_search_filters_rows_on_the_server(): void
    {
        $this->actingAs($this->admin)->get('/reports?type=master_departments&q=Finance')->assertOk()
            ->assertSee('Finance')->assertDontSee('Human Capital');
    }
}
