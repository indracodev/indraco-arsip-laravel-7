<?php

namespace Tests\Feature;

use App\Models\Archive;
use App\Models\ArchiveItem;
use App\Models\Department;
use App\Models\User;
use App\Services\MultiBoxExcelService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MultiBoxImportTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        // Seed or find basic department
        Department::firstOrCreate(['code' => 'FIN'], [
            'name' => 'Finance',
            'retention_years' => 5,
            'is_active' => true,
        ]);
        Department::firstOrCreate(['code' => 'HRD'], [
            'name' => 'Human Resources',
            'retention_years' => 5,
            'is_active' => true,
        ]);
    }

    public function test_template_download_csv_and_xlsx()
    {
        $service = new MultiBoxExcelService();

        $csvRes = $service->generateTemplate('csv');
        $this->assertEquals(200, $csvRes->getStatusCode());
        $this->assertStringContainsString('NO_BOX_SEMENTARA', $csvRes->getContent());

        $xlsxRes = $service->generateTemplate('xlsx');
        $this->assertEquals(200, $xlsxRes->getStatusCode());
    }

    public function test_parse_and_commit_import_csv()
    {
        $user = User::create([
            'name' => 'PIC User',
            'email' => 'pic@indraco.com',
            'password' => bcrypt('password'),
            'role' => 'pic_dept',
            'department_id' => Department::where('code', 'FIN')->first()->id,
        ]);

        $csvContent = "\xEF\xBB\xBF" .
            "NO_BOX_SEMENTARA,JUDUL_BOX,KODE_DEPARTEMEN,KODE_SUB_DEPARTEMEN,PERIODE_BOX,MASA_SIMPAN_TAHUN,KONDISI_FISIK,NO_BUTIR,NAMA_DOKUMEN,PERIODE_DOK_MULAI,PERIODE_DOK_SELESAI,KETERANGAN_BUTIR\n" .
            "BOX-01,Voucher Kas Jan 2024,FIN,,1 Bulan,5,Baik,1,Kwitansi 001,2024-01,2024-01,Lunas\n" .
            "BOX-01,Voucher Kas Jan 2024,FIN,,,,,2,Kwitansi 002,2024-01,2024-01,Lunas\n" .
            "BOX-02,Berkas HRD Q1 2024,HRD,,,,Baik,1,Klaim Jan,2024-01,2024-01,Medis\n" .
            "BOX-02,Berkas HRD Q1 2024,HRD,,,,Baik,2,Klaim Mar,2024-03,2024-03,Medis\n";

        $tempCsv = tempnam(sys_get_temp_dir(), 'test_csv_') . '.csv';
        file_put_contents($tempCsv, $csvContent);

        $uploaded = new UploadedFile($tempCsv, 'test.csv', 'text/csv', null, true);

        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@indraco.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $service = new MultiBoxExcelService();
        $result = $service->parseFile($uploaded, $adminUser);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['total_boxes']);
        $this->assertEquals(4, $result['total_items']);

        // Check Box 2 auto calculation: 2024-01 to 2024-03 = 3 Bulan
        $box2 = $result['boxes'][1];
        $this->assertEquals('3 Bulan', $box2['periode']);
        $this->assertEquals('2024-01-01', $box2['period_start_date']);
        $this->assertEquals('2024-03-31', $box2['period_end_date']);

        // Commit import
        $commitResult = $service->commitImport($result['boxes'], 'draft', $adminUser);

        $this->assertEquals(2, $commitResult['boxes_count']);
        $this->assertEquals(4, $commitResult['items_count']);

        $this->assertDatabaseHas('archives', [
            'title' => 'Voucher Kas Jan 2024',
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('archive_items', [
            'document_name' => 'Kwitansi 001',
        ]);
        $this->assertDatabaseHas('archive_items', [
            'document_name' => 'Klaim Mar',
        ]);

        if (file_exists($tempCsv)) {
            @unlink($tempCsv);
        }
    }

    public function test_http_endpoints_preview_and_commit()
    {
        $adminUser = User::firstOrCreate(
            ['email' => 'admin_test_import@indraco.com'],
            [
                'name' => 'Admin Test',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        $csvContent = "\xEF\xBB\xBF" .
            "NO_BOX_SEMENTARA,JUDUL_BOX,KODE_DEPARTEMEN,KODE_SUB_DEPARTEMEN,PERIODE_BOX,MASA_SIMPAN_TAHUN,KONDISI_FISIK,NO_BUTIR,NAMA_DOKUMEN,PERIODE_DOK_MULAI,PERIODE_DOK_SELESAI,KETERANGAN_BUTIR\n" .
            "BOX-TEST,Box Testing Web,FIN,,1 Bulan,5,Baik,1,Item 001,2024-05,2024-05,Ket 1\n";

        $tempCsv = tempnam(sys_get_temp_dir(), 'test_http_') . '.csv';
        file_put_contents($tempCsv, $csvContent);
        $uploaded = new UploadedFile($tempCsv, 'test_http.csv', 'text/csv', null, true);

        // 1. Preview
        $previewRes = $this->actingAs($adminUser)->postJson(route('archives.import.preview'), [
            'file' => $uploaded,
        ]);

        $previewRes->assertStatus(200);
        $previewRes->assertJsonFragment(['success' => true, 'total_boxes' => 1, 'total_items' => 1]);

        $previewData = $previewRes->json();

        // 2. Commit as pending_verification
        $commitRes = $this->actingAs($adminUser)->postJson(route('archives.import.commit'), [
            'boxes' => $previewData['boxes'],
            'status' => 'pending_verification',
        ]);

        $commitRes->assertStatus(200);
        $commitRes->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('archives', [
            'title' => 'Box Testing Web',
            'status' => 'pending_verification',
        ]);

        if (file_exists($tempCsv)) {
            @unlink($tempCsv);
        }
    }

    public function test_pic_dept_cannot_import_for_different_department()
    {
        $picUser = User::firstOrCreate(
            ['email' => 'pic_fin_import_test@indraco.com'],
            [
                'name' => 'PIC Finance Test',
                'password' => bcrypt('password'),
                'role' => 'pic_dept',
                'department_id' => Department::where('code', 'FIN')->first()->id,
            ]
        );

        // Attempt to commit a box belonging to HRD
        $boxes = [
            [
                'temp_box_id' => 'BOX-HRD',
                'name' => 'Box HRD Nyasar',
                'department_code' => 'HRD',
                'retention_years' => 5,
                'items' => [
                    ['name' => 'Dokumen HRD', 'item_number' => 1]
                ]
            ]
        ];

        $res = $this->actingAs($picUser)->postJson(route('archives.import.commit'), [
            'boxes' => $boxes,
            'status' => 'draft',
        ]);

        $res->assertStatus(403);
    }
}
