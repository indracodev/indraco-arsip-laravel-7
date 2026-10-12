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
}
