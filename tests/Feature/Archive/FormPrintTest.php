<?php

namespace Tests\Feature\Archive;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Printed "Form Pengajuan" (Laravel 7 components/form-pengajuan-modal): filled on the server from
 * the saved archive (Laravel 7 UAT note "print form pengajuan simpan data tidak muncul"), A4, light,
 * readable by whoever may read the archive (ArchivePolicy@view).
 */
class FormPrintTest extends TestCase
{
    use ArchiveFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpArchiveFixtures();
    }

    public function test_draft_prints_every_saved_box_with_the_laravel7_columns(): void
    {
        $id = $this->draftId($this->picHcs);

        $response = $this->actingAs($this->picHcs)->get("/archives/{$id}/form-pengajuan")->assertOk();

        $response->assertSeeInOrder([
            'Form Pengajuan', 'DMS (Document Management System)',
            'No. Form', 'Tanggal Pengajuan', '20-11-2026', 'Departemen/ Sub Dept.', 'Human Capital', 'Nama PIC/ Pemohon', 'PIC_HCS',
            'Tipe Pengajuan :', '✓', 'Simpan', 'Penarikan', 'Pemusnahan', 'Perpanjangan',
            'Nama / Judul Box', 'Sub-Dept', 'Masa Simpan', 'Periode Akhir', 'Label Box',
            'Box Kontrak 2026', '-', '6', '05-2027',
            'Box Kontrak Payroll', 'PAY', '12', '11-2027',
            'Catatan (misal kondisi fisik Box dan dokumen/ *optional) :', 'Kondisi fisik box standar TB 30g dalam keadaan baik.',
            'Diajukan oleh,', 'Disetujui oleh,', 'Diketahui oleh,', 'Petunjuk Pengisian :', 'Dalam satu pengajuan dibatasi maksimal 10 box.',
        ], false);
        $this->assertSame(10, substr_count((string) $response->getContent(), '<td class="fp__left" contenteditable="true">'), 'at least 10 rows like Laravel 7');
        $response->assertSee('data-theme="light"', false)->assertDontSee('js/prefs.js', false)->assertSee(route('archives.edit', $id), false);
    }

    public function test_single_sub_department_is_shown_next_to_the_department(): void
    {
        $boxes = $this->submission()['boxes'];
        $boxes[0]['sub_department_id'] = (string) $this->hcsSub;
        $id = $this->draftId($this->picHcs, ['boxes' => $boxes]);

        $this->actingAs($this->picHcs)->get("/archives/{$id}/form-pengajuan")->assertOk()->assertSee('Human Capital / Payroll');
    }

    public function test_access_follows_reading_the_archive(): void
    {
        $id = $this->draftId($this->picHcs);

        $this->actingAs($this->picFat)->get("/archives/{$id}/form-pengajuan")->assertForbidden();
        $this->actingAs($this->admin)->get("/archives/{$id}/form-pengajuan")->assertOk();
        $this->actingAs($this->gudang)->get("/archives/{$id}/form-pengajuan")->assertOk()->assertSee(route('archives.index'), false);
        auth()->logout();
        $this->get("/archives/{$id}/form-pengajuan")->assertRedirect('/login');
    }

    public function test_submitted_box_prints_its_own_row_and_edit_page_links_to_the_print(): void
    {
        $this->actingAs($this->picHcs)->post('/archives', $this->submission())->assertRedirect('/archives');
        $id = (int) DB::table('archives')->where('title', 'Box Kontrak Payroll')->value('id');

        $this->actingAs($this->picHcs)->get("/archives/{$id}/form-pengajuan")->assertOk()
            ->assertSeeInOrder(['Human Capital / Payroll', 'Box Kontrak Payroll', 'PAY', '12', '11-2027'])
            ->assertDontSee('Box Kontrak 2026');

        $draft = $this->draftId($this->picHcs);
        $this->actingAs($this->picHcs)->get("/archives/{$draft}/edit")->assertSee(route('archives.form_print', $draft), false);
    }
}
