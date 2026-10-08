<?php

namespace Tests\Feature;

use App\Models\Archive;
use App\Models\ArchiveItem;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\User;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Revisi1ValidationTest extends TestCase
{
    use DatabaseTransactions;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::first() ?? User::factory()->create([
            'role' => 'admin',
            'username' => 'admin_test'
        ]);
    }

    /** @test */
    public function test_archive_items_relation_and_structure()
    {
        $dept = Department::firstOrCreate(
            ['code' => 'TEST_DEPT'],
            ['name' => 'Departemen Pengujian']
        );

        $archive = Archive::create([
            'box_number' => 'BOX-TEST-' . time(),
            'title' => 'Pengujian Box Revisi 1',
            'department_id' => $dept->id,
            'company_name' => 'PT INDRACO',
            'document_type' => 'Laporan',
            'tgl_penyerahan' => '2026-09-25',
            'periode_doc' => '2026/09',
            'period_start_date' => '2026-09-01',
            'period_end_date' => '2026-09-30',
            'content_description' => "1. maintenance kendaraan op 1\n2. perawatan ac sentral",
            'status' => 'in_warehouse',
            'created_by_user_id' => $this->adminUser->id,
        ]);

        $item1 = ArchiveItem::create([
            'archive_id' => $archive->id,
            'item_number' => 1,
            'document_name' => 'maintenance kendaraan op 1',
            'period_text' => 'juni - agustus 2026',
            'notes' => 'Berkas asli bertanda tangan'
        ]);

        $item2 = ArchiveItem::create([
            'archive_id' => $archive->id,
            'item_number' => 2,
            'document_name' => 'perawatan ac sentral',
            'period_text' => 'januari - desember 2026',
            'notes' => 'Faktur dan kwitansi'
        ]);

        $this->assertCount(2, $archive->fresh()->items);
        $this->assertEquals('maintenance kendaraan op 1', $archive->items->first()->document_name);
        $this->assertStringContainsString('maintenance kendaraan op 1', $archive->formatted_items_summary);
    }

    /** @test */
    public function test_department_archives_api_endpoint()
    {
        $this->actingAs($this->adminUser);

        $dept = Department::first();
        if ($dept) {
            $response = $this->getJson("/api/departments/{$dept->id}/archives");
            $response->assertStatus(200)
                     ->assertJsonStructure([
                         'department' => ['id', 'code', 'name', 'total_box', 'total_items'],
                         'archives' => [
                             '*' => ['id', 'box_number', 'items', 'items_count']
                         ]
                     ]);
        }
    }

    /** @test */
    public function test_search_api_finds_archive_item_keywords()
    {
        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/search-archives?q=maintenance');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'type',
                     'items' => [
                         '*' => ['id', 'title', 'document_name', 'box_number', 'url']
                     ]
                 ]);
    }

    /** @test */
    public function test_archive_creation_with_repeater_items()
    {
        $this->actingAs($this->adminUser);
        Storage::fake('public');

        $dept = Department::first();

        $payload = [
            'department_id' => $dept->id,
            'company_name' => 'PT INDRACO JAYA',
            'document_type' => 'Operasional',
            'tgl_penyerahan' => '2026-09-25',
            'physical_condition' => 'Baik / Box Karton Standar TB 30g',
            'scan_input_form' => UploadedFile::fake()->create('form_penyerahan.pdf', 100),
            'scan_approval_input' => UploadedFile::fake()->create('approval.pdf', 100),
            'items' => [
                [
                    'document_name' => 'Voucher Pembayaran Pajak PPh 21',
                    'period_start' => '2026-01',
                    'period_end' => '2026-03',
                    'notes' => 'Nomor Seri 001-050'
                ],
                [
                    'document_name' => 'Rekapitulasi Gaji dan Tunjangan',
                    'period_start' => '2026-01',
                    'period_end' => '2026-03',
                    'notes' => 'Lampiran Bank Transfer'
                ]
            ]
        ];

        $response = $this->post(route('archives.store'), $payload);
        $response->assertRedirect();

        $createdArchive = Archive::where('company_name', 'PT INDRACO JAYA')->latest()->first();
        $this->assertNotNull($createdArchive);
        $this->assertCount(2, $createdArchive->items);
        $this->assertEquals('Voucher Pembayaran Pajak PPh 21', $createdArchive->items->first()->document_name);
        $this->assertEquals('2026-01', $createdArchive->items->first()->period_start);
        $this->assertEquals('2026-03', $createdArchive->items->first()->period_end);
    }

    /** @test */
    public function test_monthly_period_retention_and_h30_calculation()
    {
        $this->actingAs($this->adminUser);

        $dept = Department::firstOrCreate(
            ['code' => 'ACC_FIN'],
            ['name' => 'Accounting & Finance']
        );

        $payload = [
            'department_id' => $dept->id,
            'company_name' => 'PT INDRACO GLOBAL INDONESIA',
            'document_type' => 'KEUANGAN',
            'title' => 'Berkas Bulanan 1 Bln',
            'tgl_penyerahan' => '2026-10-08',
            'periode' => '1 Bulan',
            'physical_condition' => 'Baik',
            'submit_action' => 'draft',
            'items' => [
                [
                    'document_name' => 'Kwitansi Transaksi Oktober',
                    'notes' => 'Asli'
                ]
            ]
        ];

        $response = $this->post(route('archives.store'), $payload);
        $response->assertRedirect();

        $archive = Archive::where('title', 'Berkas Bulanan 1 Bln')->latest()->first();
        $this->assertNotNull($archive);

        // 1. Durasi harus menampilkan 1 Bulan (bukan 1 Thn)
        $this->assertEquals('1 Bulan', $archive->retention_display);
        $this->assertEquals('1 Bulan', $archive->retention_duration_label);

        // 2. Expiry date harus jatuh pada tanggal terakhir di bulan expiry (2026-10-31)
        $this->assertEquals('2026-10-31', \Carbon\Carbon::parse($archive->retention_expiry_date)->format('Y-m-d'));

        // 3. Formatted expiry date di UI tanpa tanggal (M Y)
        $this->assertEquals('Oct 2026', $archive->formatted_expiry_date);

        // 4. Perhitungan H-30 dari tanggal terakhir di bulan expiry (31 Okt - 30 hari = 1 Okt)
        $expiryEndOfMonth = \Carbon\Carbon::parse($archive->retention_expiry_date)->endOfMonth()->endOfDay();
        $h30Threshold = $expiryEndOfMonth->copy()->subDays(30)->startOfDay();
        $this->assertEquals('2026-10-01', $h30Threshold->format('Y-m-d'));
        $this->assertTrue(\Carbon\Carbon::parse('2026-10-08')->gte($h30Threshold));
    }

    /** @test */
    public function test_anti_idm_preview_stream_endpoint()
    {
        $this->actingAs($this->adminUser);

        // Buat dummy PDF di disk public
        Storage::disk('public')->put('archive_scans/test_anti_idm.pdf', '%PDF-1.4 test content');

        $token = base64_encode('archive_scans/test_anti_idm.pdf');
        $response = $this->get('/files/preview-stream?token=' . urlencode($token));

        $response->assertStatus(200);
        // Header harus application/octet-stream dan generic preview filename agar IDM tidak mencegat
        $response->assertHeader('Content-Type', 'application/octet-stream');
        $this->assertStringContainsString('preview_stream.dat', $response->headers->get('Content-Disposition'));

        // Cleanup
        Storage::disk('public')->delete('archive_scans/test_anti_idm.pdf');
    }
}

