<?php

namespace Tests\Feature;

use App\Models\Archive;
use App\Models\ArchiveItem;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use App\Models\User;
use App\Services\MultiBoxExcelService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UserDocumentControlImportTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        // Ensure HCS department and GA subdept exist
        $hcs = Department::firstOrCreate(['code' => 'HCS'], [
            'name' => 'Human Capital Services',
            'retention_years' => 5,
            'is_active' => true,
        ]);

        SubDepartment::firstOrCreate(['code' => 'GA', 'department_id' => $hcs->id], [
            'sidar_id' => 'SIDAR-HCS-GA',
            'name' => 'General Affairs',
            'retention_years' => 3,
            'is_active' => true,
        ]);
    }

    public function test_can_parse_and_commit_user_document_control_excel()
    {
        $filePath = 'C:/Users/Pongo/Downloads/LIst untuk excel dokuken kontrol.xlsx';
        if (!file_exists($filePath)) {
            $this->markTestSkipped('Sample file not found on system.');
        }

        $uploaded = new UploadedFile(
            $filePath,
            'LIst untuk excel dokuken kontrol.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin_doc_control_test@indraco.com'],
            ['name' => 'Admin Doc Control', 'password' => bcrypt('password'), 'role' => 'admin']
        );

        $service = new MultiBoxExcelService();
        $parseResult = $service->parseFile($uploaded, $admin);

        $this->assertTrue($parseResult['success'], 'Parse result should be successful: ' . json_encode($parseResult['errors']));
        $this->assertEquals(2, $parseResult['total_boxes'], 'Should identify exactly 2 boxes');
        $this->assertEquals(3, $parseResult['total_items'], 'Should identify exactly 3 items');

        // Check Box 1
        $box1 = $parseResult['boxes'][0];
        $this->assertEquals('Box 1', $box1['temp_box_id']);
        $this->assertEquals(2, count($box1['items']), 'Box 1 should have 2 items via grouping');
        $this->assertEquals('Form Utilitas', $box1['items'][0]['document_name']);
        $this->assertEquals('Data Maintenance kendaraan', $box1['items'][1]['document_name']);
        $this->assertEquals('2025-01-01', $box1['period_start_date']);
        $this->assertEquals('2025-12-31', $box1['period_end_date']);
        $this->assertEquals('R5', $box1['location_room']);
        $this->assertEquals('BB', $box1['location_rack']);
        $this->assertEquals('1', $box1['location_slot']);

        // Check Box 2
        $box2 = $parseResult['boxes'][1];
        $this->assertEquals('Box 2', $box2['temp_box_id']);
        $this->assertEquals(1, count($box2['items']));
        $this->assertEquals('Checklist kebersihan', $box2['items'][0]['document_name']);
        $this->assertEquals('2025-09-01', $box2['period_start_date']);
        $this->assertEquals('2025-12-31', $box2['period_end_date']);
        $this->assertEquals('2', $box2['location_slot']);

        // Commit Import
        $commitResult = $service->commitImport($parseResult['boxes'], 'pending_verification', $admin);
        $this->assertEquals(2, $commitResult['boxes_count']);
        $this->assertEquals(3, $commitResult['items_count']);

        $this->assertDatabaseHas('archive_items', ['document_name' => 'Form Utilitas']);
        $this->assertDatabaseHas('archive_items', ['document_name' => 'Data Maintenance kendaraan']);
        $this->assertDatabaseHas('archive_items', ['document_name' => 'Checklist kebersihan']);
    }
}
