<?php

namespace Tests\Feature\ArchiveImport;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Commit (draft / pending_verification) through the form's own Actions, parity with what the
 * form saves for the same boxes, PIC department enforcement, all-or-nothing, chunked inserts,
 * and "Lanjutkan ke Form Pengajuan" (old input of the create form).
 */
class ImportCommitTest extends TestCase
{
    use ImportFixtures;
    use RefreshDatabase;

    public function test_commit_as_draft_is_one_form_draft_with_every_box(): void
    {
        $this->actingAs($this->picHcs)->post('/archives/import/commit', ['file' => $this->xlsxFile($this->sampleRows()), 'status' => 'draft'])
            ->assertOk()->assertJson(['success' => true, 'message' => 'Berhasil mengimpor 2 box arsip dengan 3 butir dokumen.', 'redirect_url' => route('archives.index')])
            ->assertSessionHas('success', 'Berhasil mengimpor 2 box arsip dengan 3 butir dokumen.');

        $draft = DB::table('archives')->sole();
        $this->assertSame(['draft', $this->hcs, $this->picHcs->id, 'Box Kontrak 2026 (2 Box)', '24 Bulan'], [$draft->status, $draft->department_id, $draft->created_by_user_id, $draft->title, $draft->periode]);
        $json = json_decode($draft->content_description, true);
        $this->assertTrue($json['is_multi_box_draft']);
        $this->assertSame(['Box Kontrak 2026', 'Box Payroll'], array_column($json['boxes'], 'title'));
        $this->assertSame((string) $this->hcsSub, (string) $json['boxes'][1]['sub_department_id']);
        $this->assertSame(3, DB::table('archive_items')->where('archive_id', $draft->id)->count());
        $this->assertSame(['ARCHIVE_DRAFT_SAVE', 'ARCHIVE_BATCH_IMPORT'], DB::table('activity_logs')->orderBy('id')->pluck('action')->all());
        $this->assertSame('IMPORT_EXCEL', DB::table('activity_logs')->where('action', 'ARCHIVE_BATCH_IMPORT')->value('module'));

        // The draft opens in the multi-box form like any other draft.
        $this->actingAs($this->picHcs)->get("/archives/{$draft->id}/edit")->assertOk()->assertSee('Box Payroll')->assertSee('Slip Gaji');
    }

    /** Imported boxes are stored exactly as the form stores the same boxes (one rule, one implementation). */
    public function test_commit_for_verification_needs_the_scan_and_matches_the_form(): void
    {
        $file = $this->xlsxFile($this->sampleRows());
        $this->actingAs($this->picHcs)->post('/archives/import/commit', ['file' => $file, 'status' => 'pending_verification'])
            ->assertStatus(422)->assertJson(['success' => false, 'errors' => ['Scan Formulir Input wajib diunggah sebelum mengajukan verifikasi box arsip.']]);
        $this->assertSame(0, DB::table('archives')->count());

        $this->actingAs($this->picHcs)->post('/archives/import/commit', ['file' => $file, 'status' => 'pending_verification', 'scan_input_form' => $this->pdf()])->assertOk();
        $imported = $this->storedBoxes();
        $this->assertSame(['pending_verification', 'pending_verification'], array_column($imported, 'status'));
        $this->assertNull($imported[0]['box_number']);
        $this->assertSame("1. Kontrak Kerja (2026-01 s/d 2026-03) - No 1-50\n2. Kontrak Kerja (2026-01 s/d 2026-04) - No 51-60", $imported[0]['content_description']);

        DB::table('archive_items')->delete();
        DB::table('archives')->delete();
        $this->actingAs($this->picHcs)->post('/archives', [
            'submit_action' => 'submit', 'tgl_penyerahan' => '2026-11-24', 'scan_input_form' => $this->pdf(),
            'boxes' => [
                ['title' => 'Box Kontrak 2026', 'document_type' => 'Kepegawaian', 'physical_condition' => 'Baik / Box Karton Standar TB 30g', 'periode' => '24 Bulan', 'items' => [
                    ['document_name' => 'Kontrak Kerja', 'period_start' => '2026-01', 'period_end' => '2026-03', 'notes' => 'No 1-50'],
                    ['document_name' => 'Kontrak Kerja', 'period_start' => '2026-01', 'period_end' => '2026-04', 'notes' => 'No 51-60']]],
                ['title' => 'Box Payroll', 'sub_department_id' => $this->hcsSub, 'physical_condition' => 'Baik / Box Karton Standar TB 30g', 'periode' => '12 Bulan', 'items' => [
                    ['document_name' => 'Slip Gaji', 'period_start' => '2026-03', 'period_end' => '2026-05', 'notes' => 'Payroll Q1']]],
            ],
        ])->assertRedirect('/archives')->assertSessionHasNoErrors();
        $this->assertSame($imported, $this->storedBoxes());
    }

    public function test_pic_department_and_status_are_enforced_server_side(): void
    {
        $rows = $this->sampleRows();
        $rows[4][1] = 'FAT';
        $this->actingAs($this->picHcs)->post('/archives/import/commit', ['file' => $this->xlsxFile($rows), 'status' => 'draft'])
            ->assertStatus(422)->assertJsonFragment(['Baris 5: Sebagai PIC Departemen, Anda hanya diizinkan mengimpor arsip untuk departemen Anda (Human Capital).']);
        $this->actingAs($this->picHcs)->post('/archives/import/commit', ['file' => $this->xlsxFile($this->sampleRows()), 'status' => 'in_warehouse'])
            ->assertStatus(422);
        $this->assertSame(0, DB::table('archives')->count());

        // A file without department column = the PIC's own department.
        $rows = array_map(fn (array $row): array => array_values(array_diff_key($row, [1 => 0])), $this->sampleRows());
        $this->actingAs($this->picHcs)->post('/archives/import/commit', ['file' => $this->xlsxFile($rows), 'status' => 'draft'])->assertOk();
        $this->assertSame([$this->hcs], DB::table('archives')->pluck('department_id')->all());
    }

    public function test_admin_multi_department_import_is_all_or_nothing(): void
    {
        $rows = $this->sampleRows();
        $rows[4] = ['Box Pajak', 'FAT', '', 'Laporan Pajak', '2026-01', '2026-01', 'PPh', '120', ''];
        $this->actingAs($this->admin)->post('/archives/import/commit', ['file' => $this->xlsxFile($rows), 'status' => 'draft'])->assertOk();
        $this->assertEqualsCanonicalizing([$this->hcs, $this->fat], DB::table('archives')->pluck('department_id')->all());

        // HCS boxes are saved first, then FAT fails (title already used in FAT): nothing is kept.
        $hcsDraft = DB::table('archives')->where('department_id', $this->hcs)->value('id');
        DB::table('archive_items')->where('archive_id', $hcsDraft)->delete();
        DB::table('archives')->delete($hcsDraft);
        DB::table('archives')->update(['status' => 'pending_verification']);
        $this->actingAs($this->admin)->post('/archives/import/commit', ['file' => $this->xlsxFile($rows), 'status' => 'pending_verification', 'scan_input_form' => $this->pdf()])
            ->assertStatus(422)->assertJson(['success' => false]);
        $this->assertSame([$this->fat], DB::table('archives')->pluck('department_id')->all());
    }

    public function test_large_sheet_inserts_document_lines_in_chunks_of_250(): void
    {
        $rows = [self::HEADER, ['Box Besar', 'HCS', '', 'Kontrak Kerja', '2026-01', '2026-01', 'No 1', '12', '']];
        for ($i = 2; $i <= 600; $i++) {
            $rows[] = ['', '', '', 'Kontrak Kerja', '2026-01', '2026-01', "No {$i}", '', ''];
        }
        $inserts = [];
        DB::listen(function ($query) use (&$inserts): void {
            if (str_starts_with($query->sql, 'insert into "archive_items"')) {
                $inserts[] = count($query->bindings) / 9;
            }
        });

        $this->actingAs($this->picHcs)->post('/archives/import/commit', ['file' => $this->csvFile($rows), 'status' => 'pending_verification', 'scan_input_form' => $this->pdf()])->assertOk();
        $this->assertSame([250, 250, 100], $inserts);
        $this->assertSame(600, DB::table('archive_items')->count());
    }

    public function test_load_to_form_fills_the_create_form(): void
    {
        $this->actingAs($this->picHcs)->post('/archives/import/load-to-form', ['file' => $this->xlsxFile($this->sampleRows())])
            ->assertOk()->assertJson(['success' => true, 'redirect_url' => route('archives.create', ['from' => 'excel_import'])]);

        $this->actingAs($this->picHcs)->get('/archives/create?from=excel_import')->assertOk()
            ->assertSee('Data Pengajuan Berhasil Dimuat dari File Excel!')->assertSee('2 Box (3 Berkas)')
            ->assertSee('Box Payroll')->assertSee('No 51-60')->assertSee('"periode_bulan":"24"', false);
        $this->assertSame(0, DB::table('archives')->count());

        $rows = $this->sampleRows();
        $rows[1][1] = 'FAT';
        $this->actingAs($this->picHcs)->post('/archives/import/load-to-form', ['file' => $this->xlsxFile($rows)])->assertStatus(422);
    }

    public function test_admin_can_import_directly_into_warehouse_with_rack_and_slot(): void
    {
        $wh = DB::table('warehouses')->insertGetId(['code' => 'G1', 'name' => 'Gudang Utama']);
        $rack = DB::table('warehouse_locations')->insertGetId(['warehouse_id' => $wh, 'location_type' => 'rack', 'room_sector' => 'G1', 'rack_code' => 'R1', 'shelf_code' => 'S1', 'box_capacity' => 10, 'is_active' => 1]);
        $slot = DB::table('warehouse_rack_slots')->insertGetId(['warehouse_location_id' => $rack, 'sap_level' => 1, 'layer' => 'bottom', 'slot_number' => 1, 'slot_code' => 'R1-01', 'status' => 'empty']);
        DB::table('numbering_formats')->insert(['name' => 'Box', 'pattern' => '{COMPANY}/{DEPT}/{YEAR}/{COUNTER}', 'padding' => 4, 'current_counter' => 0, 'is_active' => 1]);

        $rows = [
            ['JUDUL_BOX', 'DEPARTEMEN', 'SUB_DEPARTEMEN', 'NAMA_DOKUMEN', 'PERIODE_DOK_MULAI', 'PERIODE_DOK_SELESAI', 'KETERANGAN_DOKUMEN', 'MASA_SIMPAN_BULAN', 'NO_BOX', 'LOKASI_RUANG', 'LOKASI_RAK', 'NO_SLOT'],
            ['Box Lama Gudang', 'HCS', '', 'Kontrak Kerja', '2025-01', '2025-06', 'Data existing', '36', 'PT/HCS/2025/0099', 'G1', 'R1', 'R1-01'],
        ];

        // Missing rack is refused
        $noRack = $rows;
        $noRack[1][10] = '';
        $this->actingAs($this->admin)->post('/archives/import/commit', ['file' => $this->xlsxFile($noRack), 'status' => 'in_warehouse'])
            ->assertStatus(422);
        $this->assertSame(0, DB::table('archives')->count());

        $this->actingAs($this->admin)->post('/archives/import/commit', ['file' => $this->xlsxFile($rows), 'status' => 'in_warehouse'])
            ->assertOk()->assertJson(['success' => true]);

        $archive = DB::table('archives')->sole();
        $this->assertSame(['in_warehouse', 'PT/HCS/2025/0099', $rack, $slot], [$archive->status, $archive->box_number, $archive->warehouse_location_id, $archive->warehouse_rack_slot_id]);
        $this->assertSame('filled', DB::table('warehouse_rack_slots')->where('id', $slot)->value('status'));
        $this->assertSame(1, DB::table('warehouse_entry_logs')->where('archive_id', $archive->id)->count());
    }

    /** @return list<array<string, mixed>> */
    private function storedBoxes(): array
    {
        return DB::table('archives')->orderBy('id')->get()->map(fn ($a): array => array_diff_key((array) $a, array_flip(['id', 'scan_input_form', 'created_at', 'updated_at'])) + [
            'items' => DB::table('archive_items')->where('archive_id', $a->id)->orderBy('item_number')->get(['item_number', 'document_name', 'period_start', 'period_end', 'period_text', 'notes'])->map(fn ($i) => (array) $i)->all(),
        ])->all();
    }
}
