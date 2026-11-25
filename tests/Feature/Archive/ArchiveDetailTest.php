<?php

namespace Tests\Feature\Archive;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Detail Arsip (archives.show): access per role (findings #5) and the Laravel 7 sections/actions.
 */
class ArchiveDetailTest extends TestCase
{
    use ArchiveFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpArchiveFixtures();
    }

    private int $boxCounter = 0;

    /** @param array<string, mixed> $extra */
    private function archive(int $deptId, string $status = 'in_warehouse', array $extra = []): int
    {
        $id = DB::table('archives')->insertGetId($extra + [
            'department_id' => $deptId, 'created_by_user_id' => $this->picHcs->id, 'title' => 'Box Kontrak 2026',
            'box_number' => 'IGI/HCS/2026/000'.(7 + $this->boxCounter++), 'period_start_date' => '2026-01-01', 'period_end_date' => '2026-06-30',
            'periode' => '6 Bulan', 'retention_expiry_date' => '2026-06-30', 'content_description' => 'isi', 'status' => $status,
            'physical_condition' => 'Baik / Box Karton Standar TB 30g', 'scan_input_form' => 'archive_scans/abc.pdf',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('archive_items')->insert(['archive_id' => $id, 'item_number' => 1, 'document_name' => 'Kontrak Kerja Rahasia', 'period_text' => 'Jan 2026 - Mar 2026', 'notes' => 'No 1-50', 'created_at' => now(), 'updated_at' => now()]);

        return $id;
    }

    public function test_pic_sees_own_department_detail_and_not_another(): void
    {
        $own = $this->archive($this->hcs);
        $other = $this->archive($this->fat);

        $this->actingAs($this->picHcs)->get("/archives/{$own}")->assertOk()
            ->assertSee('DETAIL BERKAS: IGI/HCS/2026/0007')
            ->assertSeeInOrder(['Informasi Utama Berkas', 'Rincian Berkas Dokumen Dalam Box (1 Berkas)', 'Kontrak Kerja Rahasia', 'Dokumentasi Scan & Lampiran Digital', 'Scan Formulir Input', 'Lokasi Fisik Gudang', 'Masa Simpan & Expiry Retensi', '6 Bulan', 'Jun 2026', 'Audit Trail & Riwayat Berkas'])
            ->assertSee("/files/archive/{$own}/scan_input_form/download", false)
            ->assertSee('files\/archive\/'.$own.'\/scan_input_form\/preview-data', false) // viewer JSON endpoint (R27)
            ->assertDontSee('archive_scans/abc.pdf')
            ->assertDontSee('Cetak Label');
        $this->actingAs($this->picHcs)->get("/archives/{$other}")->assertForbidden();
    }

    public function test_admin_and_gudang_see_any_department_gudang_without_item_details(): void
    {
        $id = $this->archive($this->fat);

        $this->actingAs($this->admin)->get("/archives/{$id}")->assertOk()
            ->assertSee('Kontrak Kerja Rahasia')->assertSee('Edit Data')->assertSee('Cetak Label');
        $this->actingAs($this->gudang)->get("/archives/{$id}")->assertOk()
            ->assertDontSee('Kontrak Kerja Rahasia')->assertSee('Informasi Berkas Dokumen (Privasi Departemen)')
            ->assertDontSee('Edit Data')->assertSee('Cetak Label');
    }

    public function test_actions_and_banners_follow_status_and_role(): void
    {
        $draft = $this->archive($this->hcs, 'draft', ['box_number' => null, 'rejection_note' => 'Scan buram']);
        $this->actingAs($this->picHcs)->get("/archives/{$draft}")->assertOk()
            ->assertSee('DRAFT (NO BOX PENDING)')->assertSee('Edit &amp; Ajukan (F2)', false)
            ->assertSee('DOKUMEN MASIH BERUPA DRAFT SEMENTARA')->assertSee('Scan buram');

        $pending = $this->archive($this->hcs, 'pending_verification');
        $this->actingAs($this->picHcs)->get("/archives/{$pending}")->assertOk()
            ->assertDontSee('Edit Data')->assertDontSee('Hub Verifikasi');
        $this->actingAs($this->gudang)->get("/archives/{$pending}")->assertSee('Hub Verifikasi &amp; Penomoran Box Gudang', false);

        // Expiry 2026-06-30 is within 90 days of today (2026-11-24)? No: 147 days -> no button.
        $this->actingAs($this->gudang)->get("/archives/{$pending}")->assertDontSee('Proses Pemusnahan');
        $soon = $this->archive($this->hcs, 'in_warehouse', ['retention_expiry_date' => '2026-12-31']);
        $this->actingAs($this->gudang)->get("/archives/{$soon}")->assertDontSee('Proses Pemusnahan');
        $this->actingAs($this->admin)->get("/archives/{$soon}")->assertSee('Proses Pemusnahan');
        $this->actingAs($this->picHcs)->get("/archives/{$soon}")->assertDontSee('Proses Pemusnahan');
    }

    public function test_unauthenticated_is_sent_to_login(): void
    {
        $this->get('/archives/'.$this->archive($this->hcs))->assertRedirect('/login');
    }
}
