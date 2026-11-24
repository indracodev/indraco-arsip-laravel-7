<?php

namespace Tests\Feature\Master;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Archive\ArchiveFixtures;
use Tests\TestCase;

/**
 * Import Excel Data Master (Laravel 7 MasterDataImportController / MasterDataImportService) with
 * the official template template-import-master/template_import_data_master.xlsx: preview writes
 * nothing, commit adds what is new (matched by code), a second commit adds nothing.
 */
class MasterDataImportTest extends TestCase
{
    use ArchiveFixtures;
    use RefreshDatabase;

    // ArchiveFixtures is set up by Laravel (setUpArchiveFixtures runs automatically for the trait):
    // HCS "Human Capital" (sub PAY), FAT "Finance".

    public function test_official_template_preview_commit_and_second_commit(): void
    {
        $counts = fn (): array => [DB::table('departments')->count(), DB::table('sub_departments')->count(), DB::table('master_archives')->count()];
        $before = $counts();
        $summary = ['departemen_baru' => 4, 'sub_departemen_baru' => 7, 'dokumen_baru' => 13, 'dokumen_sudah_ada' => 0, 'total_baris' => 15];

        $preview = $this->actingAs($this->admin)->post('/master/departments/import/preview', ['file' => $this->template()])
            ->assertOk()->assertJson(['success' => true, 'errors' => [], 'summary' => $summary]);
        $this->assertSame($before, $counts(), 'preview writes nothing');
        $this->assertCount(2, $preview->json('warnings'), 'FAT and HCS names differ from the application');
        $this->assertSame(['row' => 2, 'dept_code' => 'FAT', 'status' => 'Baru', 'message' => 'Sub departemen baru, Dokumen baru'],
            array_intersect_key($preview->json('rows.0'), array_flip(['row', 'dept_code', 'status', 'message'])));

        $this->actingAs($this->admin)->post('/master/departments/import/commit', ['file' => $this->template()])->assertOk()
            ->assertJson(['success' => true, 'message' => 'Import berhasil: 4 departemen, 7 sub departemen, dan 13 type berkas baru ditambahkan.']);
        $this->assertSame([$before[0] + 4, $before[1] + 7, $before[2] + 13], $counts());
        $this->assertSame('Finance', DB::table('departments')->where('code', 'FAT')->value('name'), 'existing names are kept');
        $gaDocs = DB::table('master_archives')->join('sub_departments', 'sub_departments.id', '=', 'master_archives.sub_department_id')
            ->where('sub_departments.code', 'GA')->pluck('master_archives.retention_years')->unique()->values()->all();
        $this->assertSame([5], array_map('intval', $gaDocs));
        $this->assertSame(1, DB::table('activity_logs')->where('action', 'MASTER_DATA_IMPORT')->count());

        $this->actingAs($this->admin)->post('/master/departments/import/commit', ['file' => $this->template()])->assertOk()
            ->assertJson(['message' => 'Tidak ada data baru untuk diimpor; semua data di file sudah ada.', 'summary' => ['dokumen_baru' => 0, 'dokumen_sudah_ada' => 13]]);
        $this->assertSame([$before[0] + 4, $before[1] + 7, $before[2] + 13], $counts());
        $this->assertSame(1, DB::table('activity_logs')->where('action', 'MASTER_DATA_IMPORT')->count());
    }

    public function test_row_errors_are_numbered_and_block_the_commit(): void
    {
        $csv = "Kode Departemen;Departemen;Kode Sub Departemen;Sub Departemen;Nama Dokumen;Retensi (Tahun)\n"
            ."HCS;Human Capital;PAY;;Slip;abc\n"
            .";Tanpa Kode;;;Dokumen;\n"
            ."QA;Quality;;;SOP;5\n"
            ."qa;Quality Assurance;;;SOP 2;5\n";
        $file = $this->upload('master.csv', $csv, 'text/csv');

        $this->actingAs($this->admin)->post('/master/departments/import/preview', ['file' => $file])->assertOk()
            ->assertJson(['success' => false, 'errors' => [
                'Baris 2: Sub Departemen wajib diisi jika Kode Sub Departemen diisi.',
                'Baris 2: Retensi (Tahun) harus angka bulat 1-100 (isi: abc).',
                'Baris 3: Kode Departemen wajib diisi.',
                "Baris 5: Kode Departemen QA memakai nama berbeda dengan baris 4 ('Quality').",
            ]]);

        $this->actingAs($this->admin)->post('/master/departments/import/commit', ['file' => $file])->assertStatus(422)
            ->assertJson(['message' => 'Import dibatalkan: perbaiki 4 error pada file lalu unggah ulang.']);
        $this->assertSame(0, DB::table('departments')->where('code', 'QA')->count());
    }

    public function test_file_type_template_sheet_and_access(): void
    {
        $this->actingAs($this->admin)->post('/master/departments/import/preview', ['file' => $this->upload('master.txt', 'x', 'text/plain')])
            ->assertStatus(422)->assertJson(['errors' => ['Format file tidak didukung. Harap unggah berkas berekstensi .xlsx atau .csv.']]);
        $this->actingAs($this->admin)->postJson('/master/departments/import/preview', [])->assertJsonValidationErrors('file');
        $this->actingAs($this->admin)->get('/master/departments/import/template')->assertOk()->assertDownload('template_import_data_master.xlsx');

        foreach ([$this->picHcs, $this->gudang] as $user) {
            $this->actingAs($user)->post('/master/departments/import/commit', ['file' => $this->template()])->assertForbidden();
            $this->actingAs($user)->get('/master/departments/import/template')->assertForbidden();
        }
    }

    private function template(): UploadedFile
    {
        return $this->upload('template_import_data_master.xlsx', (string) file_get_contents(base_path('template-import-master/template_import_data_master.xlsx')),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
