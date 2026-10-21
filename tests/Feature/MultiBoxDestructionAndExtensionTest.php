<?php

namespace Tests\Feature;

use App\Models\Archive;
use App\Models\Department;
use App\Models\DestructionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MultiBoxDestructionAndExtensionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake('public');
    }

    public function test_can_render_extend_and_propose_forms_with_multiple_archive_ids()
    {
        $dept = Department::firstOrCreate(['code' => 'ACC_' . rand(100, 999)], ['name' => 'Accounting Test']);
        $user = User::create([
            'name' => 'PIC Accounting ' . rand(100, 999),
            'email' => 'pic_acc_' . uniqid() . '@indraco.com',
            'password' => bcrypt('password'),
            'role' => 'pic_dept',
            'department_id' => $dept->id,
        ]);

        $arc1 = Archive::create([
            'department_id' => $dept->id,
            'created_by_user_id' => $user->id,
            'title' => 'Arsip Pajak 2024 Box A',
            'box_number' => 'BOX-ACC-01',
            'status' => 'in_warehouse',
            'retention_years' => 5,
            'period_start_date' => now()->startOfYear(),
            'period_end_date' => now()->endOfYear(),
            'retention_expiry_date' => now()->addMonth(),
            'content_description' => 'Isi berkas arsip pengujian A',
        ]);

        $arc2 = Archive::create([
            'department_id' => $dept->id,
            'created_by_user_id' => $user->id,
            'title' => 'Arsip Pajak 2024 Box B',
            'box_number' => 'BOX-ACC-02',
            'status' => 'in_warehouse',
            'retention_years' => 5,
            'period_start_date' => now()->startOfYear(),
            'period_end_date' => now()->endOfYear(),
            'retention_expiry_date' => now()->addMonth(),
            'content_description' => 'Isi berkas arsip pengujian B',
        ]);

        $this->actingAs($user);

        // Test extend form with multiple archive_ids (with embed=1 to render internal view)
        $responseExtend = $this->get(route('destructions.extend_form', [
            'archive_ids' => [$arc1->id, $arc2->id],
            'embed' => 1,
        ]));
        $responseExtend->assertStatus(200);
        $responseExtend->assertSee('Formulir Pengajuan Perpanjangan Masa Simpan Box Arsip');
        $responseExtend->assertSee('BOX-ACC-01');

        // Test propose destruction form with multiple archive_ids (with embed=1 to render internal view)
        $responsePropose = $this->get(route('destructions.propose', [
            'archive_ids' => [$arc1->id, $arc2->id],
            'embed' => 1,
        ]));
        $responsePropose->assertStatus(200);
        $responsePropose->assertSee('Formulir Pengajuan Usulan Pemusnahan Box Arsip');
        $responsePropose->assertSee('BOX-ACC-01');
    }

    public function test_can_submit_multi_box_extension()
    {
        $dept = Department::firstOrCreate(['code' => 'TAX_' . rand(100, 999)], ['name' => 'Taxation Test']);
        $user = User::create([
            'name' => 'PIC Tax ' . rand(100, 999),
            'email' => 'pic_tax_' . uniqid() . '@indraco.com',
            'password' => bcrypt('password'),
            'role' => 'pic_dept',
            'department_id' => $dept->id,
        ]);

        $arc1 = Archive::create([
            'department_id' => $dept->id,
            'created_by_user_id' => $user->id,
            'title' => 'Berkas PPN 2023 Box 1',
            'box_number' => 'BOX-TAX-01',
            'status' => 'in_warehouse',
            'retention_years' => 5,
            'period_start_date' => now()->startOfYear(),
            'period_end_date' => now()->endOfYear(),
            'retention_expiry_date' => now()->endOfMonth(),
            'content_description' => 'Isi berkas PPN Box 1',
        ]);

        $arc2 = Archive::create([
            'department_id' => $dept->id,
            'created_by_user_id' => $user->id,
            'title' => 'Berkas PPN 2023 Box 2',
            'box_number' => 'BOX-TAX-02',
            'status' => 'in_warehouse',
            'retention_years' => 5,
            'period_start_date' => now()->startOfYear(),
            'period_end_date' => now()->endOfYear(),
            'retention_expiry_date' => now()->endOfMonth(),
            'content_description' => 'Isi berkas PPN Box 2',
        ]);

        $this->actingAs($user);

        $file = UploadedFile::fake()->create('form_perpanjangan.pdf', 80, 'application/pdf');

        $response = $this->post(route('destructions.extend_store'), [
            'archive_ids' => [$arc1->id, $arc2->id],
            'additional_months' => 24,
            'extension_reason' => 'Pemeriksaan lanjutan kantor pajak daerah.',
            'scan_extension_form' => $file,
        ]);

        $response->assertRedirect(route('destructions.index'));
        $response->assertSessionHas('active_tab', 'extensions');

        $arc1->refresh();
        $arc2->refresh();

        $this->assertEquals('pending_extension', $arc1->status);
        $this->assertEquals('pending_extension', $arc2->status);

        $gudang = User::create([
            'name' => 'Petugas Gudang ' . rand(100, 999),
            'email' => 'gudang_' . uniqid() . '@indraco.com',
            'password' => bcrypt('password'),
            'role' => 'pic_gudang',
        ]);
        $this->actingAs($gudang);

        $logs = \App\Models\ArchiveExtensionLog::whereIn('archive_id', [$arc1->id, $arc2->id])->get();
        $this->assertCount(2, $logs);
        foreach ($logs as $log) {
            $this->assertEquals('pending', $log->approval_status);
            $appr = $this->post(route('destructions.extensions.approve', $log->id));
            $appr->assertRedirect();
        }

        $arc1->refresh();
        $arc2->refresh();

        $this->assertEquals('in_warehouse', $arc1->status);
        $this->assertEquals('in_warehouse', $arc2->status);
        $this->assertEquals(7, $arc1->retention_years); // 5 + 2
        $this->assertEquals(7, $arc2->retention_years);
        $this->assertEquals('Pemeriksaan lanjutan kantor pajak daerah.', $arc1->extension_reason);
        $this->assertEquals('Pemeriksaan lanjutan kantor pajak daerah.', $arc2->extension_reason);
        $this->assertNotNull($arc1->scan_extension_form);
        $this->assertNotNull($arc2->scan_extension_form);
    }

    public function test_can_submit_multi_box_destruction_proposal()
    {
        $dept = Department::firstOrCreate(['code' => 'LEG_' . rand(100, 999)], ['name' => 'Legal Test']);
        $user = User::create([
            'name' => 'PIC Legal ' . rand(100, 999),
            'email' => 'pic_leg_' . uniqid() . '@indraco.com',
            'password' => bcrypt('password'),
            'role' => 'pic_dept',
            'department_id' => $dept->id,
        ]);

        $arc1 = Archive::create([
            'department_id' => $dept->id,
            'created_by_user_id' => $user->id,
            'title' => 'Kontrak Usang 2018 Box 1',
            'box_number' => 'BOX-LEG-01',
            'status' => 'in_warehouse',
            'retention_years' => 5,
            'period_start_date' => now()->subYears(6)->startOfYear(),
            'period_end_date' => now()->subYears(6)->endOfYear(),
            'retention_expiry_date' => now()->subMonth(),
            'content_description' => 'Isi berkas legal usang 1',
        ]);

        $arc2 = Archive::create([
            'department_id' => $dept->id,
            'created_by_user_id' => $user->id,
            'title' => 'Kontrak Usang 2018 Box 2',
            'box_number' => 'BOX-LEG-02',
            'status' => 'in_warehouse',
            'retention_years' => 5,
            'period_start_date' => now()->subYears(6)->startOfYear(),
            'period_end_date' => now()->subYears(6)->endOfYear(),
            'retention_expiry_date' => now()->subMonth(),
            'content_description' => 'Isi berkas legal usang 2',
        ]);

        $this->actingAs($user);

        $file = UploadedFile::fake()->create('approval_pemusnahan.pdf', 120, 'application/pdf');

        $response = $this->post(route('destructions.store'), [
            'archive_ids' => [$arc1->id, $arc2->id],
            'destruction_date' => now()->format('Y-m-d'),
            'method' => 'Pencacahan Mesin Industrial Paper Shredder',
            'notes' => 'Telah melewati batas retensi 5 tahun.',
            'approval_file' => $file,
        ]);

        $response->assertRedirect(route('destructions.index'));
        $response->assertSessionHas('active_tab', 'destructions');

        $arc1->refresh();
        $arc2->refresh();

        // For PIC dept, status becomes pending_destruction
        $this->assertEquals('pending_destruction', $arc1->status);
        $this->assertEquals('pending_destruction', $arc2->status);

        $log1 = DestructionLog::where('archive_id', $arc1->id)->first();
        $log2 = DestructionLog::where('archive_id', $arc2->id)->first();

        $this->assertNotNull($log1);
        $this->assertNotNull($log2);
        $this->assertEquals('pending', $log1->approval_status);
        $this->assertEquals('pending', $log2->approval_status);
        $this->assertNotNull($log1->approval_file);
        $this->assertNotNull($log2->approval_file);

        // Verify destructions index page renders without error when logs exist (testing embed=1)
        $responseIndex = $this->get(route('destructions.index', ['embed' => 1]));
        $responseIndex->assertStatus(200);

        // Pastikan arsip yang diajukan pemusnahan TIDAK MUNCUL di expiredArchives (tabel Berkas Jatuh Tempo H-30)
        $expiredInView = $responseIndex->viewData('expiredArchives');
        $expiredIds = collect($expiredInView)->pluck('id')->toArray();
        $this->assertNotContains($arc1->id, $expiredIds);
        $this->assertNotContains($arc2->id, $expiredIds);

        // Pastikan tidak bisa diajukan pemusnahan ganda
        $responseDuplicate = $this->post(route('destructions.store'), [
            'archive_ids' => [$arc1->id],
            'destruction_date' => now()->format('Y-m-d'),
            'method' => 'Pencacahan Mesin Industrial Paper Shredder',
            'approval_file' => $file,
        ]);
        $responseDuplicate->assertSessionHasErrors('archive_ids');
    }
}
