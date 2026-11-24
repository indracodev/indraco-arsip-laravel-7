<?php

namespace Tests\Feature\Archive;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Katalog Arsip: department scoping (IDOR), Laravel 7 filters/labels. Box form: SubmissionTest.
 */
class CatalogTest extends TestCase
{
    use RefreshDatabase;

    private int $hcs;

    private int $fat;

    private User $picHcs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hcs = $this->department('HCS');
        $this->fat = $this->department('FAT');
        $this->picHcs = User::factory()->create(['role' => 'pic_dept', 'department_id' => $this->hcs]);
    }

    private function department(string $code): int
    {
        return DB::table('departments')->insertGetId(['code' => $code, 'name' => "Dept {$code}", 'retention_years' => 5, 'is_active' => 1]);
    }

    /** @param array<string, mixed> $extra */
    private function archive(int $deptId, string $title, string $status = 'in_warehouse', array $extra = []): int
    {
        return DB::table('archives')->insertGetId($extra + [
            'department_id' => $deptId, 'created_by_user_id' => $this->picHcs->id, 'title' => $title,
            'period_start_date' => '2026-01-01', 'period_end_date' => '2026-12-31', 'content_description' => 'isi',
            'status' => $status, 'physical_condition' => 'Baik / Box Karton Standar TB 30g',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_pic_dept_sees_only_own_department_even_when_asking_for_another(): void
    {
        $this->archive($this->hcs, 'Box Milik HCS');
        $this->archive($this->fat, 'Box Rahasia FAT');

        $this->actingAs($this->picHcs)->get('/archives')->assertOk()
            ->assertSee('Box Milik HCS')->assertDontSee('Box Rahasia FAT');
        $this->actingAs($this->picHcs)->get('/archives?department_id='.$this->fat)->assertOk()
            ->assertDontSee('Box Rahasia FAT');
    }

    public function test_pic_dept_cannot_open_another_departments_archive(): void
    {
        $own = $this->archive($this->hcs, 'Box Milik HCS');
        $other = $this->archive($this->fat, 'Box Rahasia FAT');

        $this->actingAs($this->picHcs)->get("/archives/{$other}")->assertForbidden();
        $this->actingAs($this->picHcs)->get("/archives/{$other}/edit")->assertForbidden();
        $this->actingAs($this->picHcs)->get("/archives/{$own}")->assertOk();
    }

    public function test_admin_and_pic_gudang_see_all_departments(): void
    {
        $this->archive($this->hcs, 'Box Milik HCS');
        $this->archive($this->fat, 'Box Rahasia FAT');

        foreach (['admin', 'pic_gudang'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/archives')->assertOk()
                ->assertSee('Box Milik HCS')->assertSee('Box Rahasia FAT');
        }
    }

    public function test_laravel7_columns_labels_and_status_filters(): void
    {
        $this->archive($this->hcs, 'Box Antre', 'pending_verification');
        $this->archive($this->hcs, 'Box Keluar', 'taken');
        $this->archive($this->hcs, 'Box Revisi', 'draft', ['rejection_note' => 'Scan buram']);

        $this->actingAs($this->picHcs)->get('/archives')->assertOk()
            ->assertSeeInOrder(['NO. BOX ARSIP', 'JUDUL BERKAS &amp; DEPT', 'PERIODE', 'KONDISI FISIK', 'LOKASI RAK GUDANG', 'MASA SIMPAN (EXPIRY)', 'STATUS', 'BERKAS', 'AKSI'], false)
            ->assertSee('Antrean Verifikasi')->assertSee('Keluar (Out)')->assertSee('Perlu Revisi')
            ->assertSee('Catatan Revisi Gudang:')->assertSee('Revisi &amp; Ajukan', false)
            ->assertSee('data-confirm-title="Hapus Draft Berkas Arsip"', false)
            ->assertSee('Menampilkan');

        $this->actingAs($this->picHcs)->get('/archives?status=out')->assertOk()
            ->assertSee('Box Keluar')->assertDontSee('Box Antre');
        $this->actingAs($this->picHcs)->get('/archives?search=Antre')->assertOk()
            ->assertSee('Box Antre')->assertDontSee('Box Keluar');
    }

    public function test_full_page_of_50_rows_stays_within_the_dom_budget(): void
    {
        for ($i = 1; $i <= 50; $i++) {
            $this->archive($this->hcs, "Box {$i}", $i % 2 ? 'draft' : 'in_warehouse');
        }

        $html = $this->actingAs($this->picHcs)->get('/archives?per_page=500')->assertOk()->getContent();

        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $this->assertSame(50, substr_count($html, 'aria-label="Pilih Box '), 'per_page is capped at 50');
        $this->assertLessThanOrEqual(1500, $dom->getElementsByTagName('*')->length);
    }
}
