<?php

namespace Tests\Feature\ArchiveImport;

use App\Models\User;
use App\Services\SpreadsheetReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

/**
 * Template download (Laravel 7 MultiBoxTemplateService) and the catalog / form entry points.
 */
class ImportTemplateTest extends TestCase
{
    use ImportFixtures;
    use RefreshDatabase;

    public function test_pic_template_is_scoped_to_their_department_and_imports_cleanly(): void
    {
        $path = $this->download($this->picHcs, 'xlsx');
        $data = array_values((array) SpreadsheetReader::xlsx($path, 'DATA_ARSIP'));
        $this->assertSame(self::HEADER, $data[0]);
        $this->assertSame(['Berkas Operasional Payroll 2024', 'HCS', 'PAY'], array_slice($data[1], 0, 3));
        $this->assertSame('Kontrak Kerja', $data[1][3]);

        $reference = implode("\n", array_map(fn (array $r): string => implode('|', $r), (array) SpreadsheetReader::xlsx($path, 'REFERENSI_KODE')));
        $this->assertStringContainsString('BUKU PANDUAN REFERENSI KODE RESMI - DEPARTEMEN Human Capital (HCS)', $reference);
        $this->assertStringContainsString('PAY|Payroll|HCS', $reference);
        $this->assertStringContainsString('[4. PETUNJUK PENGISIAN PENGAJUAN ARSIP]', $reference);
        $this->assertStringNotContainsString('FAT|Finance', $reference);
        $this->assertStringNotContainsString('LOKASI_RUANG', $reference);

        $upload = new UploadedFile($path, 'template_arsip_multi_box.xlsx', null, null, true);
        $this->actingAs($this->picHcs)->post('/archives/import/preview', ['file' => $upload])->assertOk()->assertJson(['success' => true, 'total_boxes' => 2, 'total_items' => 4]);
    }

    public function test_admin_template_has_location_columns_and_csv_is_escaped(): void
    {
        DB::table('master_archives')->insert(['department_id' => $this->fat, 'code' => 'F01', 'name' => '=HYPERLINK("x")', 'document_type' => 'Pajak', 'is_active' => 1]);
        $data = array_values((array) SpreadsheetReader::xlsx($this->download($this->admin, 'xlsx'), 'DATA_ARSIP'));
        $this->assertSame([...self::HEADER, 'LOKASI_RUANG', 'LOKASI_RAK', 'NO_SLOT'], $data[0]);

        $csv = $this->actingAs($this->admin)->get('/archives/import/template?format=csv')->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="template_arsip_multi_box.csv"')->getContent();
        $this->assertStringStartsWith("\xEF\xBB\xBFJUDUL_BOX,KODE_DEPARTEMEN", (string) $csv);
        $this->assertStringContainsString("\"'=HYPERLINK(\"\"x\"\")\"", (string) $csv);
    }

    public function test_catalog_and_form_open_the_import(): void
    {
        $this->actingAs($this->picHcs)->get('/archives')->assertOk()
            ->assertSee('data-modal-open="archive-import-modal"', false)->assertSee('Impor Multi-Box &amp; Berkas Dokumen (Excel / CSV)', false)
            ->assertSee('js/pages/archive-import.js', false)->assertDontSee('Tersimpan di Gudang (Data Existing)');
        $this->actingAs($this->admin)->get('/archives?open_import=1')->assertOk()
            ->assertSee('Tersimpan di Gudang (Data Existing)')->assertSee('"openOnLoad":true', false);
        $this->actingAs($this->picHcs)->get('/archives/create')->assertOk()
            ->assertSee(route('archives.index', ['open_import' => 1]), false)->assertDontSee('Data Pengajuan Berhasil Dimuat dari File Excel!');
    }

    private function download(User $user, string $format): string
    {
        $response = $this->actingAs($user)->get("/archives/import/template?format={$format}")->assertOk()->baseResponse;
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $copy = (string) tempnam(sys_get_temp_dir(), 'dmstpl');
        copy($response->getFile()->getPathname(), $copy);
        @unlink($response->getFile()->getPathname());
        $this->beforeApplicationDestroyed(fn () => @unlink($copy));

        return $copy;
    }
}
