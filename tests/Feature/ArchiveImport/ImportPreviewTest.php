<?php

namespace Tests\Feature\ArchiveImport;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Upload + preview (Laravel 7 ArchiveImportController@previewImport, MultiBoxExcelService::parseFile):
 * grouping, carry-over, fuzzy Master Berkas, period formats, row-numbered errors / warnings,
 * values computed by the form rules, malformed files, access.
 */
class ImportPreviewTest extends TestCase
{
    use ImportFixtures;
    use RefreshDatabase;

    public function test_preview_groups_boxes_like_laravel7_and_shows_the_values_the_form_will_store(): void
    {
        $json = $this->actingAs($this->picHcs)->post('/archives/import/preview', ['file' => $this->xlsxFile($this->sampleRows())])
            ->assertOk()->assertJson(['success' => true, 'errors' => [], 'total_boxes' => 2, 'total_items' => 3])->json();

        [$first, $second] = $json['boxes'];
        $this->assertSame(['BOX-01', 'Box Kontrak 2026', 'HCS', null, 'Kepegawaian'], [$first['temp_box_id'], $first['title'], $first['department_code'], $first['sub_department_code'], $first['document_type']]);
        // MASA_SIMPAN_BULAN 24 = form "PERIODE DOKUMEN (BULAN)" 24: handover month + 24 months, 2 years.
        $this->assertSame(['24 Bulan', '2026-01 - 2026-04', '2026-11-01', '2028-11-30', 2, '2028-11-30'],
            [$first['periode'], $first['periode_doc'], $first['period_start_date'], $first['period_end_date'], $first['retention_years'], $first['retention_expiry_date']]);
        $this->assertSame([[1, 'Kontrak Kerja', '2026-01 s/d 2026-03', 'No 1-50'], [2, 'Kontrak Kerja', '2026-01 s/d 2026-04', 'No 51-60']],
            array_map(fn (array $i): array => [$i['item_number'], $i['document_name'], $i['period_text'], $i['notes']], $first['items']));

        $this->assertSame(['BOX-02', 'Box Payroll', 'PAY', '12 Bulan', '2026-03 s/d 2026-05'], [$second['temp_box_id'], $second['title'], $second['sub_department_code'], $second['periode'], $second['items'][0]['period_text']]);
        $this->assertContains('Baris 3: "kontrak krja" dicocokkan ke Master Berkas "Kontrak Kerja".', $json['warnings']);
        // Row 4 is empty in the sheet: Excel row numbers are kept.
        $this->assertContains('Baris 5: "Slip Gaji" tidak ditemukan di Master Berkas (disimpan sesuai teks file).', $json['warnings']);
        $this->assertSame(0, DB::table('archives')->count());
    }

    public function test_box_columns_carry_over_and_period_formats_are_tolerated(): void
    {
        $rows = [
            ['NO_BOX', 'DEPARTEMEN', 'LIST DOKUMEN', 'PERIODE', 'KETERANGAN', 'MASA SIMPAN'],
            ['A1', 'Human', 'Kontrak Kerja', 'jan 25 - Des 25', 'a', '5 Tahun'],
            ['A1', '', 'Kontrak Kerja', '45658', 'b', ''],
            ['A2', 'HCS', 'Kontrak Kerja', '2024', 'c', '200'],
            ['A1', '', 'Kontrak Kerja', 'bukan periode', 'd', ''],
        ];
        $json = $this->actingAs($this->picHcs)->post('/archives/import/preview', ['file' => $this->csvFile($rows)])->assertOk()->json();

        $this->assertTrue($json['success'], implode(' | ', $json['errors']));
        $this->assertSame(['A1', 'A2'], array_column($json['boxes'], 'temp_box_id'));
        $this->assertSame('Arsip A1 Human Capital - Kontrak Kerja', $json['boxes'][0]['title']);
        $this->assertSame('60 Bulan', $json['boxes'][0]['periode']);
        $this->assertSame(['2025-01 s/d 2025-12', '2025-01', '2026-11'], array_column($json['boxes'][0]['items'], 'period_text'));
        $this->assertSame('2024-01', $json['boxes'][1]['items'][0]['period_text']);
        $this->assertSame('60 Bulan', $json['boxes'][1]['periode']); // 200 months -> limit 60 (HCS)
        $this->assertContains("Box 'A2': Masa simpan 204 bulan. Dibatasi otomatis ke batas maksimal 60 bulan sesuai standar kearsipan.", $json['warnings']);
        $this->assertContains('Baris 5: Periode "bukan periode" tidak dikenali, diisi bulan berjalan.', $json['warnings']);
    }

    public function test_pic_may_only_import_their_own_department_with_row_numbers(): void
    {
        $rows = $this->sampleRows();
        $rows[4][1] = 'FAT';
        $json = $this->actingAs($this->picHcs)->post('/archives/import/preview', ['file' => $this->xlsxFile($rows)])->assertOk()->json();

        $this->assertFalse($json['success']);
        $this->assertSame(['Baris 5: Sebagai PIC Departemen, Anda hanya diizinkan mengimpor arsip untuk departemen Anda (Human Capital).'], $json['errors']);
    }

    public function test_admin_needs_a_department_and_missing_required_fields_are_warnings(): void
    {
        $rows = $this->sampleRows();
        $rows[1][1] = '';
        $rows[1][6] = '';
        $json = $this->actingAs($this->admin)->post('/archives/import/preview', ['file' => $this->xlsxFile($rows)])->assertOk()->json();
        $this->assertSame(["Box 'BOX-01': Tidak memiliki informasi Departemen yang valid. Harap periksa kolom DEPARTEMEN."], $json['errors']);

        $rows[1][1] = 'HCS';
        $json = $this->actingAs($this->admin)->post('/archives/import/preview', ['file' => $this->xlsxFile($rows)])->assertOk()->json();
        $this->assertTrue($json['success']);
        $this->assertContains('Belum lengkap untuk Verifikasi Gudang: Baris 2: The KETERANGAN_DOKUMEN field is required.', $json['warnings']);
    }

    public function test_malformed_files_are_refused(): void
    {
        $user = $this->actingAs($this->picHcs);
        $format = ['Format file tidak didukung. Harap unggah berkas berekstensi .xlsx atau .csv.'];

        $user->post('/archives/import/preview', ['file' => $this->rawFile("a;b\n", 'data.txt')])->assertStatus(422)->assertJson(['success' => false, 'errors' => $format]);
        $user->post('/archives/import/preview', ['file' => $this->rawFile('<?php echo 1;', 'shell.xlsx')])->assertStatus(422)->assertJson(['errors' => $format]);
        $user->post('/archives/import/preview', ['file' => $this->rawFile("PK\x03\x04".str_repeat("\0", 64), 'data.csv')])->assertStatus(422)->assertJson(['errors' => $format]);
        $user->post('/archives/import/preview', ['file' => $this->rawFile(str_repeat('x', 5121 * 1024), 'big.csv')])->assertStatus(422);
        $user->post('/archives/import/preview', [])->assertStatus(422)->assertJson(['success' => false]);

        $user->post('/archives/import/preview', ['file' => $this->csvFile([['foo', 'bar'], ['1', '2']])])->assertOk()
            ->assertJson(['success' => false, 'errors' => ['Kolom header tidak ditemukan. Pastikan menggunakan template resmi atau file Excel yang memiliki kolom Box / List Dokumen / Departemen.']]);
        $user->post('/archives/import/preview', ['file' => $this->xlsxFile([])])->assertOk()
            ->assertJson(['success' => false, 'errors' => ['File kosong atau format spreadsheet tidak terbaca.']]);
    }

    public function test_pic_gudang_is_refused_everywhere(): void
    {
        $file = $this->xlsxFile($this->sampleRows());
        $this->actingAs($this->gudang)->get('/archives/import/template')->assertForbidden();
        foreach (['preview', 'commit', 'load-to-form'] as $action) {
            $this->actingAs($this->gudang)->postJson("/archives/import/{$action}", ['file' => $file, 'status' => 'draft'])->assertForbidden();
        }
        $this->actingAs($this->gudang)->get('/archives')->assertOk()->assertDontSee('archive-import-modal');
    }
}
