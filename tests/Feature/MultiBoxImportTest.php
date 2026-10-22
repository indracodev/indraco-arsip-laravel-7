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
        $this->assertStringContainsString('JUDUL_BOX', $csvRes->getContent());

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

    public function test_superadmin_commit_in_warehouse_generates_box_number_and_log()
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin_import_test@indraco.com'],
            [
                'name' => 'Super Admin Test',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        $warehouse = \App\Models\Warehouse::firstOrCreate(
            ['code' => 'WH-TEST'],
            ['name' => 'Gudang GA', 'address' => 'Jl. Test']
        );

        $location = \App\Models\WarehouseLocation::firstOrCreate(
            ['warehouse_id' => $warehouse->id, 'rack_code' => 'RAK-01'],
            [
                'location_type' => 'rack',
                'room_sector' => 'GUDANG GA',
                'shelf_code' => 'RAK-01',
                'box_capacity' => 50,
                'total_sap' => 5,
                'boxes_per_sap' => 10,
                'canvas_x' => 10,
                'canvas_y' => 10,
                'canvas_width' => 100,
                'canvas_height' => 30,
            ]
        );

        // Format baru tanpa NO_BOX_SEMENTARA, tanpa NO_BUTIR, dan dengan kolom gudang/rak
        $csvContent = "\xEF\xBB\xBF" .
            "JUDUL_BOX,KODE_DEPARTEMEN,KODE_SUB_DEPARTEMEN,NAMA_DOKUMEN,PERIODE_DOK_MULAI,PERIODE_DOK_SELESAI,KETERANGAN_DOKUMEN,MASA_SIMPAN_BULAN,KONDISI_FISIK,LOKASI_RUANG,LOKASI_RAK,NO_SLOT\n" .
            "Box Arsip Gudang Langsung,FIN,,Bukti Pembayaran,2024-01,2024-03,Keterangan Dokumen,12,Baik,GUDANG GA,RAK-01,1\n" .
            ",,,Faktur Pajak,2024-01,2024-03,Faktur Pajak Seri A,12,Baik,GUDANG GA,RAK-01,1\n";

        $tempCsv = tempnam(sys_get_temp_dir(), 'test_wh_') . '.csv';
        file_put_contents($tempCsv, $csvContent);
        $uploaded = new UploadedFile($tempCsv, 'test_wh.csv', 'text/csv', null, true);

        $service = new MultiBoxExcelService();
        $preview = $service->parseFile($uploaded, $superAdmin);

        $this->assertTrue($preview['success']);
        $this->assertEquals(1, $preview['total_boxes']);
        $this->assertEquals(2, $preview['total_items']);

        // Pastikan penomoran berkas otomatis urut 1 dan 2
        $boxData = $preview['boxes'][0];
        $this->assertEquals(1, $boxData['items'][0]['item_number']);
        $this->assertEquals(2, $boxData['items'][1]['item_number']);

        // Commit dengan status in_warehouse
        $commitResult = $service->commitImport($preview['boxes'], 'in_warehouse', $superAdmin);
        $this->assertEquals(1, $commitResult['boxes_count']);
        $this->assertEquals(2, $commitResult['items_count']);

        // Assert database archive status in_warehouse and box_number is generated
        $archive = Archive::where('title', 'Box Arsip Gudang Langsung')->first();
        $this->assertNotNull($archive);
        $this->assertEquals('in_warehouse', $archive->status);
        $this->assertNotNull($archive->box_number);
        $this->assertNotEmpty($archive->box_number);

        // Assert items
        $this->assertEquals(2, $archive->items()->count());

        // Assert WarehouseEntryLog exists
        $this->assertDatabaseHas('warehouse_entry_logs', [
            'archive_id' => $archive->id,
            'location_id' => $location->id,
        ]);

        if (file_exists($tempCsv)) {
            @unlink($tempCsv);
        }
    }

    public function test_smart_match_master_archive_and_template_sheet2_master_docs()
    {
        $finDept = Department::where('code', 'FIN')->first();
        $dmDept = Department::firstOrCreate(['code' => 'DM'], ['name' => 'DIGITAL & MARKETING']);

        \App\Models\MasterArchive::create([
            'department_id' => $finDept->id,
            'name' => 'Faktur Pajak Masukan & Keluaran',
            'code' => 'FP-01',
            'document_type' => 'Keuangan & Pajak',
            'retention_years' => 10,
            'is_active' => true,
        ]);

        \App\Models\MasterArchive::create([
            'department_id' => $finDept->id,
            'name' => 'Surat Setoran Pajak (SSP / e-Billing)',
            'code' => 'SSP-01',
            'document_type' => 'Keuangan & Pajak',
            'retention_years' => 10,
            'is_active' => true,
        ]);

        \App\Models\MasterArchive::create([
            'department_id' => $dmDept->id,
            'name' => 'Plastik & Paper Cup',
            'code' => 'MKT-01',
            'document_type' => 'Marketing',
            'retention_years' => 5,
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'PIC User Smart',
            'email' => 'picsmart@indraco.com',
            'password' => bcrypt('password'),
            'role' => 'pic_dept',
            'department_id' => $finDept->id,
        ]);

        $service = new MultiBoxExcelService();

        // 1. Uji template xlsx memuat tabel master berkas di Sheet 2
        $templateService = new \App\Services\MultiBoxTemplateService();
        $xlsxRes = $templateService->generateTemplate('xlsx', $user);
        $this->assertEquals(200, $xlsxRes->getStatusCode());

        // 2. Uji parsing dengan berbagai variasi penulisan:
        // - Item 1: huruf kecil + simbol beda ("faktur pajak masukan dan keluaran")
        // - Item 2: salah urutan kata ("Pajak Masukan & Keluaran Faktur")
        // - Item 3: typo huruf tertukar/salah ("Faktur Pajak Maskuan & Keluran")
        // - Item 4: kurang kata ("Faktur Pajak Masukan")
        // - Item 5: kelebihan kata ("Faktur Pajak Masukan & Keluaran Asli 2024")
        // - Item 6: kustom / tidak ada di master ("Perjanjian Sewa Kantor Baru")
        // - Item 7: kata tunggal / prefix ("faktur" -> "Faktur Pajak Masukan & Keluaran")
        // - Item 8: singkatan / akronim ("ssp" -> "Surat Setoran Pajak (SSP / e-Billing)")
        $csvContent = "\xEF\xBB\xBF" .
            "JUDUL_BOX,KODE_DEPARTEMEN,KODE_SUB_DEPARTEMEN,NAMA_DOKUMEN,PERIODE_DOK_MULAI,PERIODE_DOK_SELESAI,KETERANGAN_DOKUMEN,MASA_SIMPAN_BULAN,KONDISI_FISIK\n" .
            "Box Pajak 2024,FIN,,faktur pajak masukan dan keluaran,2024-01,2024-03,Keterangan,120,Baik\n" .
            "Box Pajak 2024,FIN,,Pajak Masukan & Keluaran Faktur,2024-01,2024-03,Keterangan,120,Baik\n" .
            "Box Pajak 2024,FIN,,Faktur Pajak Maskuan & Keluran,2024-01,2024-03,Keterangan,120,Baik\n" .
            "Box Pajak 2024,FIN,,Faktur Pajak Masukan,2024-01,2024-03,Keterangan,120,Baik\n" .
            "Box Pajak 2024,FIN,,Faktur Pajak Masukan & Keluaran Asli 2024,2024-01,2024-03,Keterangan,120,Baik\n" .
            "Box Pajak 2024,FIN,,Perjanjian Sewa Kantor Baru,2024-01,2024-03,Keterangan,120,Baik\n" .
            "Box Pajak 2024,FIN,,faktur,2024-01,2024-03,Keterangan,120,Baik\n" .
            "Box Pajak 2024,FIN,,ssp,2024-01,2024-03,Keterangan,120,Baik\n";

        $tempCsv = tempnam(sys_get_temp_dir(), 'test_smart_') . '.csv';
        file_put_contents($tempCsv, $csvContent);
        $uploaded = new UploadedFile($tempCsv, 'test_smart.csv', 'text/csv', null, true);

        $preview = $service->parseFile($uploaded, $user);
        $this->assertTrue($preview['success']);
        
        $items = $preview['boxes'][0]['items'];
        $this->assertEquals(8, count($items));

        // Item 1: Beda simbol dan huruf kecil -> diselaraskan
        $this->assertEquals('Faktur Pajak Masukan & Keluaran', $items[0]['document_name']);
        // Item 2: Urutan kata terbalik -> diselaraskan
        $this->assertEquals('Faktur Pajak Masukan & Keluaran', $items[1]['document_name']);
        // Item 3: Typo huruf -> diselaraskan
        $this->assertEquals('Faktur Pajak Masukan & Keluaran', $items[2]['document_name']);
        // Item 4: Kurang kata -> diselaraskan
        $this->assertEquals('Faktur Pajak Masukan & Keluaran', $items[3]['document_name']);
        // Item 5: Kelebihan kata -> diselaraskan
        $this->assertEquals('Faktur Pajak Masukan & Keluaran', $items[4]['document_name']);
        // Item 6: Kustom / sama sekali tidak ada di master -> tetap aman, tidak error, disimpan sesuai nama aslinya
        $this->assertEquals('Perjanjian Sewa Kantor Baru', $items[5]['document_name']);
        // Item 7: Prefix / root word "faktur" -> diselaraskan ke "Faktur Pajak Masukan & Keluaran"
        $this->assertEquals('Faktur Pajak Masukan & Keluaran', $items[6]['document_name']);
        // Item 8: Akronim "ssp" -> diselaraskan ke "Surat Setoran Pajak (SSP / e-Billing)"
        $this->assertEquals('Surat Setoran Pajak (SSP / e-Billing)', $items[7]['document_name']);

        // Document type box harus otomatis terisi dari MasterArchive
        $this->assertEquals('Keuangan & Pajak', $preview['boxes'][0]['document_type']);

        // Uji prefix "plast" untuk Super Admin & Dept Marketing (DM)
        $adminUser = User::where('role', 'superadmin')->first();
        $adminCsv = "\xEF\xBB\xBF" .
            "JUDUL_BOX,KODE_DEPARTEMEN,KODE_SUB_DEPARTEMEN,NAMA_DOKUMEN,PERIODE_DOK_MULAI,PERIODE_DOK_SELESAI,KETERANGAN_DOKUMEN,MASA_SIMPAN_BULAN,KONDISI_FISIK\n" .
            "Box Plastik,DM,,plast,2024-01,2024-03,Keterangan,60,Baik\n";
        $adminTemp = tempnam(sys_get_temp_dir(), 'test_plast_') . '.csv';
        file_put_contents($adminTemp, $adminCsv);
        $adminUploaded = new UploadedFile($adminTemp, 'test_plast.csv', 'text/csv', null, true);
        $adminPreview = $service->parseFile($adminUploaded, $adminUser);
        $this->assertTrue($adminPreview['success']);
        $this->assertEquals('Plastik & Paper Cup', $adminPreview['boxes'][0]['items'][0]['document_name']);

        if (file_exists($tempCsv)) @unlink($tempCsv);
        if (file_exists($adminTemp)) @unlink($adminTemp);
    }
}
