<?php

namespace Tests\Feature;

use App\Models\Archive;
use App\Models\BorrowingLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MultiBoxBorrowingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake('public');
    }

    public function test_can_submit_single_box_borrowing()
    {
        $dept = Department::firstOrCreate(['code' => 'FIN'], ['name' => 'Finance & Accounting']);
        $user = User::firstOrCreate(
            ['email' => 'pic_fin_borrow_1@indraco.com'],
            [
                'name' => 'PIC Finance 1',
                'password' => bcrypt('password'),
                'role' => 'pic_dept',
                'department_id' => $dept->id,
            ]
        );

        $archive = Archive::create([
            'department_id' => $dept->id,
            'created_by_user_id' => $user->id,
            'title' => 'Dokumen Keuangan 2023',
            'box_number' => 'BOX-FIN-01',
            'status' => 'in_warehouse',
            'physical_condition' => 'Baik',
            'periode' => '12 Bulan',
            'period_start_date' => now()->startOfYear(),
            'period_end_date' => now()->endOfYear(),
            'period_yy_mm' => '2026/01 - 2026/12',
            'content_description' => 'Isi berkas arsip pengujian',
            'tgl_penyerahan' => now()->format('Y-m-d'),
        ]);

        $file = UploadedFile::fake()->create('approval.pdf', 100, 'application/pdf');

        $payload = [
            'archive_id' => $archive->id,
            'purpose' => 'Audit Pajak Tahunan',
            'approval_file' => $file,
        ];

        $response = $this->actingAs($user)->post(route('borrowings.store'), $payload);

        $response->assertRedirect(route('borrowings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('borrowing_logs', [
            'archive_id' => $archive->id,
            'borrower_user_id' => $user->id,
            'status' => 'requested',
            'purpose' => 'Audit Pajak Tahunan',
        ]);
    }

    public function test_can_submit_multi_box_borrowing()
    {
        $dept = Department::firstOrCreate(['code' => 'FIN'], ['name' => 'Finance & Accounting']);
        $user = User::firstOrCreate(
            ['email' => 'pic_fin_borrow_multi@indraco.com'],
            [
                'name' => 'PIC Finance Multi',
                'password' => bcrypt('password'),
                'role' => 'pic_dept',
                'department_id' => $dept->id,
            ]
        );

        $archive1 = Archive::create([
            'department_id' => $dept->id,
            'created_by_user_id' => $user->id,
            'title' => 'Box Pajak 2021',
            'box_number' => 'BOX-FIN-10',
            'status' => 'in_warehouse',
            'physical_condition' => 'Baik',
            'periode' => '12 Bulan',
            'period_start_date' => now()->startOfYear(),
            'period_end_date' => now()->endOfYear(),
            'period_yy_mm' => '2026/01 - 2026/12',
            'content_description' => 'Isi berkas arsip pengujian',
            'tgl_penyerahan' => now()->format('Y-m-d'),
        ]);

        $archive2 = Archive::create([
            'department_id' => $dept->id,
            'created_by_user_id' => $user->id,
            'title' => 'Box Pajak 2022',
            'box_number' => 'BOX-FIN-11',
            'status' => 'in_warehouse',
            'physical_condition' => 'Baik',
            'periode' => '12 Bulan',
            'period_start_date' => now()->startOfYear(),
            'period_end_date' => now()->endOfYear(),
            'period_yy_mm' => '2026/01 - 2026/12',
            'content_description' => 'Isi berkas arsip pengujian',
            'tgl_penyerahan' => now()->format('Y-m-d'),
        ]);

        $file = UploadedFile::fake()->create('approval_multi.pdf', 150, 'application/pdf');

        $payload = [
            'archive_ids' => [$archive1->id, $archive2->id],
            'purpose' => 'Pemeriksaan Kantor Pajak (2 Tahun Buku)',
            'approval_file' => $file,
        ];

        $response = $this->actingAs($user)->post(route('borrowings.store'), $payload);

        $response->assertRedirect(route('borrowings.index'));
        $response->assertSessionHas('success');

        // Memastikan masing-masing box memiliki record borrowing log
        $this->assertDatabaseHas('borrowing_logs', [
            'archive_id' => $archive1->id,
            'borrower_user_id' => $user->id,
            'status' => 'requested',
            'purpose' => 'Pemeriksaan Kantor Pajak (2 Tahun Buku)',
        ]);

        $this->assertDatabaseHas('borrowing_logs', [
            'archive_id' => $archive2->id,
            'borrower_user_id' => $user->id,
            'status' => 'requested',
            'purpose' => 'Pemeriksaan Kantor Pajak (2 Tahun Buku)',
        ]);

        $log1 = BorrowingLog::where('archive_id', $archive1->id)->first();
        $log2 = BorrowingLog::where('archive_id', $archive2->id)->first();
        // Berkas approval dishare sama persis
        $this->assertEquals($log1->approval_file, $log2->approval_file);
    }

    public function test_multi_box_rejects_archives_not_in_warehouse()
    {
        $dept = Department::firstOrCreate(['code' => 'FIN'], ['name' => 'Finance & Accounting']);
        $user = User::firstOrCreate(
            ['email' => 'pic_fin_borrow_err@indraco.com'],
            [
                'name' => 'PIC Finance Err',
                'password' => bcrypt('password'),
                'role' => 'pic_dept',
                'department_id' => $dept->id,
            ]
        );

        $archive1 = Archive::create([
            'department_id' => $dept->id,
            'created_by_user_id' => $user->id,
            'title' => 'Box Pajak Ada',
            'box_number' => 'BOX-FIN-20',
            'status' => 'in_warehouse',
            'physical_condition' => 'Baik',
            'period_start_date' => now()->startOfYear(),
            'period_end_date' => now()->endOfYear(),
            'period_yy_mm' => '2026/01 - 2026/12',
            'content_description' => 'Isi berkas arsip pengujian',
        ]);

        $archive2 = Archive::create([
            'department_id' => $dept->id,
            'created_by_user_id' => $user->id,
            'title' => 'Box Pajak Belum Masuk Gudang',
            'box_number' => 'BOX-FIN-21',
            'status' => 'pending_verification', // Bukan in_warehouse
            'physical_condition' => 'Baik',
            'period_start_date' => now()->startOfYear(),
            'period_end_date' => now()->endOfYear(),
            'period_yy_mm' => '2026/01 - 2026/12',
            'content_description' => 'Isi berkas arsip pengujian 2',
        ]);

        $file = UploadedFile::fake()->create('approval.pdf', 100, 'application/pdf');

        $payload = [
            'archive_ids' => [$archive1->id, $archive2->id],
            'purpose' => 'Uji Validasi',
            'approval_file' => $file,
        ];

        $response = $this->actingAs($user)->from(route('borrowings.create'))->post(route('borrowings.store'), $payload);

        $response->assertRedirect(route('borrowings.create'));
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('borrowing_logs', [
            'archive_id' => $archive1->id,
        ]);
    }

    public function test_cannot_borrow_archive_with_active_borrowing_request()
    {
        $dept = Department::firstOrCreate(['code' => 'HRD'], ['name' => 'Human Resources']);
        $user = User::firstOrCreate(
            ['email' => 'pic_hrd_active_borrow@indraco.com'],
            [
                'name' => 'PIC HRD',
                'password' => bcrypt('password'),
                'role' => 'pic_dept',
                'department_id' => $dept->id,
            ]
        );

        $archive = Archive::create([
            'department_id' => $dept->id,
            'created_by_user_id' => $user->id,
            'title' => 'Berkas Absensi HRD 2026',
            'box_number' => 'BOX-HRD-99',
            'status' => 'in_warehouse',
            'physical_condition' => 'Baik',
            'period_start_date' => now()->startOfYear(),
            'period_end_date' => now()->endOfYear(),
            'period_yy_mm' => '2026/01 - 2026/12',
            'content_description' => 'Isi berkas absensi',
        ]);

        // Create an active borrowing log
        BorrowingLog::create([
            'archive_id' => $archive->id,
            'borrower_user_id' => $user->id,
            'request_date' => now()->toDateString(),
            'borrow_date' => now(),
            'status' => 'requested',
            'purpose' => 'Peminjaman pertama sedang berjalan',
            'approval_file' => 'approvals/fake.pdf',
        ]);

        $file = UploadedFile::fake()->create('approval2.pdf', 100, 'application/pdf');

        $payload = [
            'archive_ids' => [$archive->id],
            'purpose' => 'Mencoba meminjam ulang box yang sedang diproses',
            'approval_file' => $file,
        ];

        $response = $this->actingAs($user)->from(route('borrowings.create'))->post(route('borrowings.store'), $payload);

        $response->assertRedirect(route('borrowings.create'));
        $response->assertSessionHas('error');
    }

    public function test_can_delete_draft_archive()
    {
        $dept = Department::firstOrCreate(['code' => 'MKT'], ['name' => 'Marketing']);
        $user = User::firstOrCreate(
            ['email' => 'pic_mkt_draft_del@indraco.com'],
            [
                'name' => 'PIC Marketing',
                'password' => bcrypt('password'),
                'role' => 'pic_dept',
                'department_id' => $dept->id,
            ]
        );

        $draft = Archive::create([
            'department_id' => $dept->id,
            'created_by_user_id' => $user->id,
            'title' => 'Draft Brosur Kampanye',
            'status' => 'draft',
            'physical_condition' => 'Baik',
            'period_start_date' => now()->startOfMonth(),
            'period_end_date' => now()->endOfMonth(),
            'content_description' => 'Isi draft brosur',
        ]);

        $response = $this->actingAs($user)->delete(route('archives.destroy', $draft));
        $response->assertRedirect(route('archives.index'));

        $this->assertDatabaseMissing('archives', [
            'id' => $draft->id,
        ]);
    }

    public function test_can_batch_delete_draft_archives()
    {
        $dept = Department::firstOrCreate(['code' => 'OPS'], ['name' => 'Operations']);
        $user = User::firstOrCreate(
            ['email' => 'pic_ops_batch_del@indraco.com'],
            [
                'name' => 'PIC Ops',
                'password' => bcrypt('password'),
                'role' => 'pic_dept',
                'department_id' => $dept->id,
            ]
        );

        $draft1 = Archive::create([
            'department_id' => $dept->id,
            'created_by_user_id' => $user->id,
            'title' => 'Draft Ops 1',
            'status' => 'draft',
            'physical_condition' => 'Baik',
            'period_start_date' => now()->startOfMonth(),
            'period_end_date' => now()->endOfMonth(),
            'content_description' => 'Isi draft 1',
        ]);

        $draft2 = Archive::create([
            'department_id' => $dept->id,
            'created_by_user_id' => $user->id,
            'title' => 'Draft Ops 2',
            'status' => 'draft',
            'physical_condition' => 'Baik',
            'period_start_date' => now()->startOfMonth(),
            'period_end_date' => now()->endOfMonth(),
            'content_description' => 'Isi draft 2',
        ]);

        $response = $this->actingAs($user)->post(route('archives.batch_delete_drafts'), [
            'ids' => [$draft1->id, $draft2->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('archives', ['id' => $draft1->id]);
        $this->assertDatabaseMissing('archives', ['id' => $draft2->id]);
    }

    public function test_similarity_check_excludes_origin_archive()
    {
        $dept = Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT Dept']);
        $user = User::firstOrCreate(
            ['email' => 'pic_it_similarity@indraco.com'],
            [
                'name' => 'PIC IT',
                'password' => bcrypt('password'),
                'role' => 'pic_dept',
                'department_id' => $dept->id,
            ]
        );

        $archive = Archive::create([
            'department_id' => $dept->id,
            'created_by_user_id' => $user->id,
            'title' => 'Dokumen Server Backup 2026',
            'box_number' => 'BOX-IT-01',
            'status' => 'in_warehouse',
            'physical_condition' => 'Baik',
            'period_start_date' => now()->startOfMonth(),
            'period_end_date' => now()->endOfMonth(),
            'content_description' => 'Backup server bulanan',
        ]);

        // When similarity checked without exclude, it flags identical
        $res1 = $this->actingAs($user)->postJson(route('api.archives.check_similarity'), [
            'department_id' => $dept->id,
            'title' => 'Dokumen Server Backup 2026',
        ]);
        $res1->assertJson(['has_similarity' => true, 'similarity_percentage' => 100]);

        // When exclude_archive_id is provided, it ignores itself
        $res2 = $this->actingAs($user)->postJson(route('api.archives.check_similarity'), [
            'department_id' => $dept->id,
            'title' => 'Dokumen Server Backup 2026',
            'exclude_archive_id' => $archive->id,
        ]);
        $res2->assertJson(['has_similarity' => false]);
    }
}
