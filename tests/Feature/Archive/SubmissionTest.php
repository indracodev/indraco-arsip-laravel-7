<?php

namespace Tests\Feature\Archive;

use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Box submission (Laravel 7 HandlesMultiBoxCreation/Update): draft as one row, submit as one row
 * per box, stored values compared with what Laravel 7 writes, validation parity, FAT rule.
 */
class SubmissionTest extends TestCase
{
    use ArchiveFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpArchiveFixtures();
    }

    public function test_form_has_the_laravel7_composition(): void
    {
        $this->actingAs($this->picHcs)->get('/archives/create')->assertOk()
            ->assertSeeInOrder([
                'Form Pengajuan Multi-Box Arsip (TB 30g)',
                '1. Identitas Unit Kerja &amp; Tanggal Serah Terima Pengajuan', 'PERUSAHAAN ENTITAS', 'DEPARTEMEN PEMILIK ARSIP', 'SUB-DEPARTEMEN', '(Opsional)', 'TGL. SERAH TERIMA FISIK KE GUDANG',
                '2. Pengelolaan Multi-Box Arsip', 'Tambah Box', 'Duplikat Box...',
                'NAMA / JUDUL BOX', 'SUB-DEPARTEMEN', 'KATEGORI DOKUMEN (Terkunci)', 'KONDISI FISIK WADAH BOX', 'PERIODE DOKUMEN (BULAN)', 'Ikuti Periode Box Sebelumnya',
                'Tambah Berkas', 'Tambah 5 Baris', 'NAMA DOKUMEN / BERKAS ARSIP', 'PERIODE MULAI', 'PERIODE SELESAI', 'KETERANGAN',
                '1. Scan / Berkas Formulir Pengajuan Fisik', 'WAJIB UNTUK PENGAJUAN', '2. Foto Fisik Box / Lampiran Digital', 'OPSIONAL',
                'Batal', 'Simpan Sementara (Draft)', 'Simpan &amp; Ajukan 1 Box Arsip',
            ], false)
            ->assertSee('HCS - Human Capital')->assertSee('PT INDRACO GLOBAL INDONESIA')
            ->assertDontSee('name="department_id"', false)
            ->assertDontSee('MOCKUP');
    }

    public function test_draft_is_one_row_with_every_box_as_json_and_own_department(): void
    {
        $response = $this->actingAs($this->picHcs)->post('/archives', $this->submission([
            'submit_action' => 'draft', 'scan_input_form' => null, 'department_id' => $this->fat,
        ]));

        $response->assertRedirect('/archives')->assertSessionHas('success', 'Berhasil menyimpan 2 box arsip sebagai 1 data draft sementara.');
        $draft = DB::table('archives')->sole();
        $this->assertSame('draft', $draft->status);
        $this->assertSame($this->hcs, (int) $draft->department_id, 'department_id from the form is ignored for PIC');
        $this->assertSame('Box Kontrak 2026 (2 Box)', $draft->title);
        $this->assertNull($draft->box_number);
        $this->assertSame(['6 Bulan', '2026/11', '6 Bulan (s/d May 2027)', '2026-11-01 00:00:00', '2027-05-31 00:00:00', 0, '2027-05-31 00:00:00'],
            [$draft->periode, $draft->periode_doc, $draft->period_text, $draft->period_start_date, $draft->period_end_date, (int) $draft->retention_years, $draft->retention_expiry_date]);
        $json = json_decode($draft->content_description, true);
        $this->assertTrue($json['is_multi_box_draft']);
        $this->assertCount(2, $json['boxes']);
        $this->assertSame(['1', '2', '3'], DB::table('archive_items')->where('archive_id', $draft->id)->orderBy('item_number')->pluck('item_number')->map(fn ($n) => (string) $n)->all());

        $log = ActivityLog::query()->sole();
        $this->assertSame(['ARCHIVE_DRAFT_SAVE', 'DOKUMEN_ARSIP', 'PIC_HCS'], [$log->action, $log->module, $log->reference_id]);
        $this->assertSame(['archive_id' => $draft->id, 'boxes_count' => 2, 'items_count' => 3, 'department_id' => $this->hcs], $log->properties);
        $this->assertSame('archive_draft_created', DB::table('system_events')->value('event_type'));
    }

    public function test_draft_requires_nothing(): void
    {
        $this->actingAs($this->picHcs)->post('/archives', ['submit_action' => 'draft'])->assertRedirect('/archives')->assertSessionHasNoErrors();

        $this->assertSame('Draft Box 1', DB::table('archives')->value('title'));
        $this->assertSame('Draft Dokumen', DB::table('archive_items')->value('document_name'));
    }

    public function test_submit_creates_one_row_per_box_with_the_values_laravel7_stores(): void
    {
        $this->actingAs($this->picHcs)->post('/archives', $this->submission())
            ->assertRedirect('/archives')->assertSessionHas('success', 'Pengajuan 2 box arsip (3 butir dokumen) berhasil disubmit untuk diverifikasi PIC Gudang.');

        [$first, $second] = DB::table('archives')->orderBy('id')->get()->all();
        foreach ([$first, $second] as $row) {
            $this->assertSame(['pending_verification', null, $this->hcs, $this->picHcs->id, 'PT INDRACO GLOBAL INDONESIA', '2026-11-20 00:00:00'],
                [$row->status, $row->box_number, (int) $row->department_id, (int) $row->created_by_user_id, $row->company_name, $row->tgl_penyerahan]);
        }
        $this->assertSame(['Box Kontrak 2026', null, '6 Bulan', '2026-01 - 2026-04', '2026-01 - 2026-04', '6 Bulan (s/d May 2027)', '2026-11-01 00:00:00', '2027-05-31 00:00:00', 0, '2027-05-31 00:00:00'],
            [$first->title, $first->sub_department_id, $first->periode, $first->periode_doc, $first->period_yy_mm, $first->period_text, $first->period_start_date, $first->period_end_date, (int) $first->retention_years, $first->retention_expiry_date]);
        $this->assertSame("1. Kontrak Kerja (2026-01 s/d 2026-03) - No 1-50\n2. Kontrak Kerja (2026-04) - No 51-60", $first->content_description);
        $this->assertSame(['12 Bulan', '2025-12 - 2026-11', '12 Bulan (s/d November 2027)', 1, $this->hcsSub], [$second->periode, $second->periode_doc, $second->period_text, (int) $second->retention_years, (int) $second->sub_department_id]);
        $this->assertSame(['2026-11-24 10:00:00', '2026-11-24 10:00:01'], [$first->created_at, $second->created_at]);
        $this->assertSame('2026-11', DB::table('archive_items')->where('archive_id', $second->id)->value('period_end'), 'periods are clamped to the current month');

        $this->assertSame($first->scan_input_form, $second->scan_input_form, 'one scan for every box');
        $this->assertMatchesRegularExpression('#^archive_scans/[A-Za-z0-9]{40}\.pdf$#', $first->scan_input_form);
        Storage::disk('local')->assertExists($first->scan_input_form);

        $log = ActivityLog::query()->sole();
        $this->assertSame(['ARCHIVE_CREATE', 'Pengajuan batch 2 box arsip (3 butir berkas) oleh PIC_HCS.'], [$log->action, $log->description]);
        $this->assertSame(['boxes_count' => 2, 'items_count' => 3, 'status' => 'pending_verification', 'department_id' => $this->hcs], $log->properties);
        $this->assertSame(['archive_submitted', 'archive_submitted'], DB::table('system_events')->pluck('event_type')->all());
    }

    public function test_submit_validation_matches_laravel7_required_fields(): void
    {
        $this->actingAs($this->picHcs)->post('/archives', ['submit_action' => 'submit'])
            ->assertSessionHasErrors(['tgl_penyerahan', 'boxes']);

        $box = ['title' => '', 'physical_condition' => '', 'periode' => '1 Bulan', 'items' => [['document_name' => '', 'period_start' => '', 'period_end' => '', 'notes' => '']]];
        $this->actingAs($this->picHcs)->post('/archives', $this->submission(['boxes' => [$box]]))
            ->assertSessionHasErrors(['boxes.0.title', 'boxes.0.physical_condition', 'boxes.0.items.0.document_name', 'boxes.0.items.0.period_start', 'boxes.0.items.0.period_end', 'boxes.0.items.0.notes'])
            ->assertSessionDoesntHaveErrors(['boxes.0.sub_department_id', 'boxes.0.document_type', 'company_name', 'file']);

        $this->actingAs($this->picHcs)->post('/archives', $this->submission(['scan_input_form' => null]))
            ->assertSessionHasErrors(['scan_input_form' => 'Scan Formulir Input wajib diunggah sebelum mengajukan verifikasi box arsip.']);
        $this->actingAs($this->picHcs)->post('/archives', $this->submission(['tgl_penyerahan' => '2026-11-25']))->assertSessionHasErrors('tgl_penyerahan');
        $this->assertSame(0, DB::table('archives')->count());
    }

    public function test_identical_document_lines_in_one_box_are_refused(): void
    {
        $payload = $this->submission();
        $payload['boxes'][0]['items'][1] = $payload['boxes'][0]['items'][0];

        $this->actingAs($this->picHcs)->post('/archives', $payload)->assertSessionHasErrors([
            'duplicate_item' => 'Box #1 memiliki data butir berkas kembar identik: Baris #2 sama persis dengan Baris #1 (Nama Berkas, Periode, dan Keterangan sama persis). Data duplikat tidak diperbolehkan.',
        ]);
    }

    public function test_fat_departments_may_use_120_months_others_are_capped_at_60(): void
    {
        $box = fn (string $title) => ['title' => $title, 'physical_condition' => 'Baik', 'periode' => '120 Bulan', 'items' => [['document_name' => 'Voucher', 'period_start' => '2026-01', 'period_end' => '2026-01', 'notes' => 'x']]];

        $this->actingAs($this->picFat)->post('/archives', $this->submission(['boxes' => [$box('Box FAT')]]))->assertSessionHasNoErrors();
        $this->actingAs($this->picHcs)->post('/archives', $this->submission(['boxes' => [$box('Box HCS')]]))->assertSessionHasNoErrors();

        $this->assertSame(['120 Bulan', 10, '2036-11-30 00:00:00'], array_values((array) DB::table('archives')->where('title', 'Box FAT')->first(['periode', 'retention_years', 'retention_expiry_date'])));
        $this->assertSame(['60 Bulan', 5, '2031-11-30 00:00:00'], array_values((array) DB::table('archives')->where('title', 'Box HCS')->first(['periode', 'retention_years', 'retention_expiry_date'])));
        $this->actingAs($this->picFat)->get('/archives/create')->assertSee('"code":"FAT"', false);
    }

    public function test_draft_can_be_edited_then_submitted_reusing_its_row(): void
    {
        $id = $this->draftId($this->picHcs);
        $this->actingAs($this->picHcs)->get("/archives/{$id}/edit")->assertOk()
            ->assertSee('Edit Draft Pengajuan Box Arsip (TB 30g)')->assertSee('Simpan &amp; Ajukan Permanen ke Gudang', false)->assertSee('Box Kontrak Payroll');

        $this->travel(5)->seconds();
        $this->actingAs($this->picHcs)->put("/archives/{$id}", $this->submission(['submit_action' => 'draft', 'scan_input_form' => null, 'original_updated_at' => $this->updatedAt($id), 'boxes' => [$this->submission()['boxes'][0]]]))
            ->assertRedirect('/archives')->assertSessionHas('success', 'Berhasil memperbarui draft pengajuan (1 Box) sebagai 1 data draft.');
        $this->assertSame('Box Kontrak 2026', DB::table('archives')->where('id', $id)->value('title'));

        $this->travel(5)->seconds();
        $this->actingAs($this->picHcs)->put("/archives/{$id}", $this->submission(['original_updated_at' => $this->updatedAt($id)]))
            ->assertRedirect('/archives')->assertSessionHas('success', 'Pengajuan 2 box arsip (3 butir dokumen) berhasil disubmit untuk diverifikasi PIC Gudang.');

        $rows = DB::table('archives')->orderBy('id')->get();
        $this->assertSame([$id, 'pending_verification', 'Box Kontrak 2026'], [(int) $rows[0]->id, $rows[0]->status, $rows[0]->title]);
        $this->assertSame(['pending_verification', 'Box Kontrak Payroll'], [$rows[1]->status, $rows[1]->title]);
        $this->assertSame(2, DB::table('archive_items')->where('archive_id', $id)->count());
        $this->assertSame(['ARCHIVE_DRAFT_SAVE', 'ARCHIVE_DRAFT_UPDATE', 'ARCHIVE_CREATE'], ActivityLog::query()->orderBy('id')->pluck('action')->all());
        $this->assertStringContainsString('dari draft oleh PIC_HCS', (string) ActivityLog::query()->latest('id')->value('description'));
        $this->assertSame(['archive_draft_created', 'archive_updated', 'archive_submitted', 'archive_submitted'], DB::table('system_events')->orderBy('id')->pluck('event_type')->all());
    }
}
