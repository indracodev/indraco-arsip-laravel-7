<?php

namespace Tests\Feature;

use App\Models\Archive;
use App\Models\Department;
use App\Models\User;
use App\Services\MultiBoxExcelService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FatRetentionTenYearsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        \Illuminate\Support\Facades\Storage::fake('public');
    }

    public function test_fat_account_can_create_single_archive_with_120_months()
    {
        $fatDept = Department::firstOrCreate(['code' => 'FAT'], [
            'name' => 'FIN, ACC & TAX',
            'retention_years' => 10,
            'is_active' => true,
        ]);

        $fatUser = User::firstOrCreate(['email' => 'fin_test@indraco.com'], [
            'name' => 'PIC_FIN, ACC & TAX',
            'password' => bcrypt('password'),
            'role' => 'pic_dept',
            'department_id' => $fatDept->id,
        ]);

        $this->actingAs($fatUser);

        $response = $this->post(route('archives.store'), [
            'title' => 'Berkas Keuangan 10 Tahun FAT',
            'department_id' => $fatDept->id,
            'periode' => '120 Bulan',
            'tgl_penyerahan' => now()->format('Y-m-d'),
            'physical_condition' => 'Baik / Box Karton Standar TB 30g',
            'items' => [
                ['document_name' => 'Laporan Pajak & Neraca Tahunan', 'notes' => 'Arsip wajib simpan 10 thn'],
            ],
            'submit_action' => 'draft',
        ]);

        $response->assertSessionHasNoErrors();
        $arc = Archive::where('title', 'Berkas Keuangan 10 Tahun FAT')->first();
        $this->assertNotNull($arc);
        $this->assertEquals($fatDept->id, $arc->department_id);
        $this->assertEquals('120 Bulan', $arc->periode_doc);
    }

    public function test_non_fat_account_cannot_create_single_archive_exceeding_60_months()
    {
        $hrDept = Department::firstOrCreate(['code' => 'HR_TEST_' . rand(100, 999)], [
            'name' => 'Human Resources Test',
            'retention_years' => 5,
            'is_active' => true,
        ]);

        $hrUser = User::create([
            'name' => 'PIC HR ' . rand(100, 999),
            'email' => 'hr_test_' . uniqid() . '@indraco.com',
            'password' => bcrypt('password'),
            'role' => 'pic_dept',
            'department_id' => $hrDept->id,
        ]);

        $this->actingAs($hrUser);

        $response = $this->post(route('archives.store'), [
            'title' => 'Berkas HR 120 Bulan Non FAT',
            'department_id' => $hrDept->id,
            'periode' => '120 Bulan',
            'tgl_penyerahan' => now()->format('Y-m-d'),
            'physical_condition' => 'Baik / Box Karton Standar TB 30g',
            'items' => [
                ['document_name' => 'Berkas Pegawai', 'notes' => 'Testing'],
            ],
            'submit_action' => 'draft',
        ]);

        $response->assertSessionHasErrors(['periode']);
    }

    public function test_fat_account_can_create_multi_box_with_120_months()
    {
        $fatDept = Department::firstOrCreate(['code' => 'FAT'], [
            'name' => 'FIN, ACC & TAX',
            'retention_years' => 10,
            'is_active' => true,
        ]);

        $fatUser = User::firstOrCreate(['email' => 'fin_test2@indraco.com'], [
            'name' => 'PIC_FIN, ACC & TAX',
            'password' => bcrypt('password'),
            'role' => 'pic_dept',
            'department_id' => $fatDept->id,
        ]);

        $this->actingAs($fatUser);

        $response = $this->post(route('archives.store'), [
            'company_name' => 'PT INDRACO GLOBAL INDONESIA',
            'department_id' => $fatDept->id,
            'tgl_penyerahan' => now()->format('Y-m-d'),
            'action' => 'draft',
            'boxes' => [
                [
                    'title' => 'Multi Box FAT 120 Bulan',
                    'periode' => '120 Bulan',
                    'document_type' => 'Laporan Pajak',
                    'physical_condition' => 'Baik / Box Karton Standar TB 30g',
                    'items' => [
                        [
                            'document_name' => 'SPT Tahunan 2014-2024',
                            'period_start' => '2014-01',
                            'period_end' => '2023-12',
                            'notes' => 'Dokumen 10 tahun',
                        ]
                    ]
                ]
            ]
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('archives', [
            'title' => 'Multi Box FAT 120 Bulan',
            'department_id' => $fatDept->id,
            'periode' => '120 Bulan',
        ]);
    }

    public function test_fat_account_can_extend_up_to_120_months()
    {
        $fatDept = Department::firstOrCreate(['code' => 'FAT'], [
            'name' => 'FIN, ACC & TAX',
            'retention_years' => 10,
            'is_active' => true,
        ]);

        $fatUser = User::firstOrCreate(['email' => 'fin_test3@indraco.com'], [
            'name' => 'PIC_FIN, ACC & TAX',
            'password' => bcrypt('password'),
            'role' => 'pic_dept',
            'department_id' => $fatDept->id,
        ]);

        $arc = Archive::create([
            'department_id' => $fatDept->id,
            'created_by_user_id' => $fatUser->id,
            'title' => 'Arsip Pajak Extended 120 Bln',
            'box_number' => 'BOX-FAT-EXT-01',
            'status' => 'in_warehouse',
            'retention_years' => 10,
            'period_start_date' => now()->subYears(10)->startOfYear(),
            'period_end_date' => now()->subYears(1)->endOfYear(),
            'retention_expiry_date' => now()->endOfMonth(),
            'content_description' => 'Arsip audit pajak',
        ]);

        $this->actingAs($fatUser);

        $file = UploadedFile::fake()->create('form_perpanjangan_fat.pdf', 80, 'application/pdf');

        $response = $this->post(route('destructions.extend_store'), [
            'archive_ids' => [$arc->id],
            'additional_months' => 120,
            'extension_reason' => 'Perpanjangan dokumen pajak 10 tahun audit BPK/DJP.',
            'scan_extension_form' => $file,
        ]);

        $response->assertRedirect(route('destructions.index'));
        $response->assertSessionHasNoErrors();

        $arc->refresh();
        $this->assertEquals('pending_extension', $arc->status);

        $log = \App\Models\ArchiveExtensionLog::where('archive_id', $arc->id)->latest()->first();
        $this->assertNotNull($log);
        $this->assertEquals('pending', $log->approval_status);

        // Gudang melakukan ACC persetujuan
        $gudangUser = User::firstOrCreate(['email' => 'gudang_fat_test@indraco.com'], [
            'name' => 'PIC Gudang FAT Test',
            'password' => bcrypt('password'),
            'role' => 'pic_gudang',
            'department_id' => $fatDept->id,
        ]);
        $this->actingAs($gudangUser);
        $this->post(route('destructions.extensions.approve', $log->id));

        $arc->refresh();
        $this->assertEquals('in_warehouse', $arc->status);
        $this->assertEquals(20, $arc->retention_years); // 10 original + 10 added
    }

    public function test_non_fat_account_cannot_extend_beyond_60_months()
    {
        $itDept = Department::firstOrCreate(['code' => 'IT_TEST_' . rand(100, 999)], [
            'name' => 'IT Test Dept',
            'retention_years' => 5,
            'is_active' => true,
        ]);

        $itUser = User::create([
            'name' => 'PIC IT ' . rand(100, 999),
            'email' => 'it_test_' . uniqid() . '@indraco.com',
            'password' => bcrypt('password'),
            'role' => 'pic_dept',
            'department_id' => $itDept->id,
        ]);

        $arc = Archive::create([
            'department_id' => $itDept->id,
            'created_by_user_id' => $itUser->id,
            'title' => 'Arsip IT Test',
            'box_number' => 'BOX-IT-EXT-01',
            'status' => 'in_warehouse',
            'retention_years' => 5,
            'period_start_date' => now()->subYears(5)->startOfYear(),
            'period_end_date' => now()->subYears(1)->endOfYear(),
            'retention_expiry_date' => now()->endOfMonth(),
            'content_description' => 'Arsip log server',
        ]);

        $this->actingAs($itUser);

        $response = $this->post(route('destructions.extend_store'), [
            'archive_ids' => [$arc->id],
            'additional_months' => 120,
            'extension_reason' => 'Perpanjangan melebihi batas 60 bulan untuk non-FAT.',
        ]);

        $response->assertSessionHasErrors(['additional_months']);
    }

    public function test_excel_service_limits_fat_to_120_months_and_non_fat_to_60_months()
    {
        $service = app(MultiBoxExcelService::class);

        $fatDept = Department::firstOrCreate(['code' => 'FAT'], [
            'name' => 'FIN, ACC & TAX',
            'retention_years' => 10,
            'is_active' => true,
        ]);

        $otherDept = Department::firstOrCreate(['code' => 'OTHER_' . rand(100, 999)], [
            'name' => 'Other Dept',
            'retention_years' => 5,
            'is_active' => true,
        ]);

        $fatCsvContent = "NO_BOX_SEMENTARA,JUDUL_BOX,KODE_DEPARTEMEN,NAMA_DOKUMEN,PERIODE_DOK_MULAI,PERIODE_DOK_SELESAI\n" .
            "FAT-01,Box FAT,FAT,Dokumen Pajak,2010-01,2023-12\n";
        $fatFile = UploadedFile::fake()->createWithContent('fat_import.csv', $fatCsvContent);
        $resultFat = $service->parseFile($fatFile);
        $this->assertTrue($resultFat['success']);
        $this->assertNotEmpty($resultFat['boxes']);
        $this->assertEquals('120 Bulan', $resultFat['boxes'][0]['periode']);

        $otherCsvContent = "NO_BOX_SEMENTARA,JUDUL_BOX,KODE_DEPARTEMEN,NAMA_DOKUMEN,PERIODE_DOK_MULAI,PERIODE_DOK_SELESAI\n" .
            "OTH-01,Box Other,{$otherDept->code},Dokumen HR,2015-01,2023-12\n";
        $otherFile = UploadedFile::fake()->createWithContent('other_import.csv', $otherCsvContent);
        $resultOther = $service->parseFile($otherFile);
        $this->assertTrue($resultOther['success']);
        $this->assertNotEmpty($resultOther['boxes']);
        $this->assertEquals('60 Bulan', $resultOther['boxes'][0]['periode']);
    }
}
