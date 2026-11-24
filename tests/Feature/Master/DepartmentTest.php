<?php

namespace Tests\Feature\Master;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Archive\ArchiveFixtures;
use Tests\TestCase;

/**
 * Master Departemen (Laravel 7 DepartmentController): admin only page, department and
 * sub-department CRUD with Laravel 7 messages, delete guards, active switch, drill-down JSON
 * closed to other roles (findings #5).
 */
class DepartmentTest extends TestCase
{
    use ArchiveFixtures;
    use RefreshDatabase;

    // ArchiveFixtures is set up by Laravel (setUpArchiveFixtures runs automatically for the trait).

    public function test_only_admin_opens_the_page_and_the_department_json(): void
    {
        $this->actingAs($this->admin)->get('/master/departments')->assertOk()->assertSee('Master Departemen &amp; Sub-Departemen', false)->assertSee('HCS');

        foreach ([$this->picHcs, $this->gudang] as $user) {
            $this->actingAs($user)->get('/master/departments')->assertForbidden();
            $this->actingAs($user)->getJson("/api/departments/{$this->hcs}/manage-data")->assertForbidden();
            $this->actingAs($user)->getJson("/api/departments/{$this->fat}/archives")->assertForbidden();
            $this->actingAs($user)->getJson("/api/sub-departments/{$this->hcsSub}/archives")->assertForbidden();
            $this->actingAs($user)->post('/master/departments', ['code' => 'X', 'name' => 'X'])->assertForbidden();
        }
    }

    public function test_store_upper_cases_the_code_and_refuses_a_duplicate_in_any_case(): void
    {
        $this->actingAs($this->admin)->post('/master/departments', ['code' => 'log', 'name' => 'Logistik', 'description' => 'Gudang'])
            ->assertRedirect('/master/departments')->assertSessionHas('success', 'Departemen Logistik (LOG) berhasil ditambahkan.');
        $this->assertSame(['LOG', 5, 1], array_values((array) DB::table('departments')->where('code', 'LOG')->first(['code', 'retention_years', 'is_active'])));
        $this->assertSame('MASTER_DEPT_CREATE', DB::table('activity_logs')->value('action'));

        $this->actingAs($this->admin)->post('/master/departments', ['code' => 'hcs', 'name' => 'Duplikat'])->assertSessionHasErrors('code');
        $this->actingAs($this->admin)->post('/master/departments', ['code' => 'ABCDEFGHIJK', 'name' => 'X'])->assertSessionHasErrors('code');
        $this->actingAs($this->admin)->post('/master/departments', ['code' => '', 'name' => ''])->assertSessionHasErrors(['code', 'name']);
        $this->assertSame(1, DB::table('departments')->where('code', 'HCS')->count());
    }

    public function test_update_keeps_the_retention_years_the_form_does_not_send(): void
    {
        $this->actingAs($this->admin)->put("/master/departments/{$this->hcs}", ['code' => 'HCS', 'name' => 'Human Capital Services', 'description' => ''])
            ->assertSessionHas('success', 'Departemen Human Capital Services berhasil diperbarui.');

        $this->assertSame(['Human Capital Services', 3], array_values((array) DB::table('departments')->where('id', $this->hcs)->first(['name', 'retention_years'])));
    }

    public function test_delete_is_refused_while_the_department_or_a_sub_department_holds_boxes(): void
    {
        // A box of another department filed under an HCS sub-department (Laravel 7 checked both).
        $this->box($this->fat, $this->hcsSub);
        $this->actingAs($this->admin)->from('/master/departments')->delete("/master/departments/{$this->hcs}")
            ->assertSessionHas('error', 'Departemen Human Capital tidak dapat dihapus karena salah satu sub-departemennya memiliki data arsip.');
        $this->actingAs($this->admin)->delete("/master/departments/{$this->fat}")
            ->assertSessionHas('error', 'Departemen Finance tidak dapat dihapus karena memiliki data arsip terkait.');
        $this->assertSame(2, DB::table('departments')->count());
    }

    public function test_delete_removes_subs_and_master_berkas_and_detaches_users(): void
    {
        $this->actingAs($this->admin)->delete("/master/departments/{$this->hcs}")->assertSessionHas('success', 'Departemen Human Capital berhasil dihapus.');

        $this->assertSame(0, DB::table('sub_departments')->where('department_id', $this->hcs)->count());
        $this->assertSame(0, DB::table('master_archives')->where('department_id', $this->hcs)->count());
        $this->assertNull($this->picHcs->fresh()->department_id);
        $this->assertSame('MASTER_DEPT_DELETE', DB::table('activity_logs')->value('action'));
    }

    public function test_toggle_answers_json_for_the_page(): void
    {
        $this->actingAs($this->admin)->postJson("/master/departments/{$this->hcs}/toggle-active")
            ->assertOk()->assertExactJson(['success' => true, 'message' => 'Departemen Human Capital berhasil dinonaktifkan.', 'is_active' => false]);
        $this->actingAs($this->admin)->postJson("/master/sub-departments/{$this->hcsSub}/toggle-active")
            ->assertJson(['is_active' => false, 'message' => 'Sub-Departemen Payroll berhasil dinonaktifkan.']);
        $this->assertSame(0, (int) DB::table('departments')->where('id', $this->hcs)->value('is_active'));
    }

    public function test_sub_department_code_is_unique_within_its_department_only(): void
    {
        $this->actingAs($this->admin)->post('/master/sub-departments', ['department_id' => $this->hcs, 'code' => 'pay', 'name' => 'Dobel'])
            ->assertSessionHasErrors(['code' => 'Kode sub-departemen PAY sudah digunakan pada departemen ini.']);
        $this->actingAs($this->admin)->post('/master/sub-departments', ['department_id' => $this->fat, 'code' => 'pay', 'name' => 'Payable'])
            ->assertSessionHas('success', 'Sub-Departemen Payable (PAY) berhasil ditambahkan.');
        $this->actingAs($this->admin)->put("/master/sub-departments/{$this->hcsSub}", ['department_id' => $this->hcs, 'code' => 'PAY', 'name' => 'Payroll Baru'])
            ->assertSessionHas('success', 'Sub-Departemen Payroll Baru berhasil diperbarui.');

        $this->box($this->hcs, $this->hcsSub);
        $this->actingAs($this->admin)->delete("/master/sub-departments/{$this->hcsSub}")
            ->assertSessionHas('error', 'Sub-Departemen Payroll Baru tidak dapat dihapus karena memiliki data arsip terkait.');
    }

    public function test_drill_down_lists_the_boxes_of_a_department(): void
    {
        $this->box($this->hcs, null);
        $this->box($this->hcs, $this->hcsSub);

        $this->actingAs($this->admin)->getJson("/api/departments/{$this->hcs}/archives")->assertOk()
            ->assertJsonPath('department.total_box', 2)->assertJsonCount(2, 'archives');
        $this->actingAs($this->admin)->getJson("/api/departments/{$this->hcs}/archives?filter=unassigned")
            ->assertJsonPath('department.total_box', 1)->assertJsonPath('archives.0.sub_department', 'Induk / Umum');
        $this->actingAs($this->admin)->getJson("/api/sub-departments/{$this->hcsSub}/archives")
            ->assertJsonPath('sub_department.department_code', 'HCS')->assertJsonPath('archives.0.items.0.document_name', 'Kontrak Kerja');
    }

    private function box(int $departmentId, ?int $subId): int
    {
        $id = DB::table('archives')->insertGetId([
            'department_id' => $departmentId, 'sub_department_id' => $subId, 'created_by_user_id' => $this->admin->id,
            'title' => 'Box', 'status' => 'in_warehouse', 'period_start_date' => '2026-01-01', 'period_end_date' => '2026-06-30', 'content_description' => '1. Kontrak Kerja', 'periode' => '6 Bulan',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('archive_items')->insert(['archive_id' => $id, 'item_number' => 1, 'document_name' => 'Kontrak Kerja']);

        return $id;
    }
}
