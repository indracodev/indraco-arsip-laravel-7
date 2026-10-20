<?php

namespace Tests\Feature;

use App\Models\Archive;
use App\Models\ArchiveItem;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MultiBoxCreateTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake('public');

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

    public function test_can_create_multi_box_as_draft()
    {
        $dept = Department::where('code', 'FIN')->first();
        $user = User::firstOrCreate(
            ['email' => 'pic_fin_create_test1@indraco.com'],
            [
                'name' => 'PIC Finance 1',
                'password' => bcrypt('password'),
                'role' => 'pic_dept',
                'department_id' => $dept->id,
            ]
        );

        $payload = [
            'department_id' => $dept->id,
            'company_name' => 'PT INDRACO GLOBAL INDONESIA',
            'tgl_penyerahan' => date('Y-m-d'),
            'submit_action' => 'draft',
            'boxes' => [
                [
                    'title' => 'Box 1: Voucher Kas Jan-Feb 2024',
                    'physical_condition' => 'Baik / Box Karton Standar TB 30g',
                    'items' => [
                        ['document_name' => 'Kwitansi 001', 'period_start' => '2024-01', 'period_end' => '2024-01', 'notes' => 'Lunas'],
                        ['document_name' => 'Kwitansi 002', 'period_start' => '2024-02', 'period_end' => '2024-02', 'notes' => 'Lunas'],
                    ]
                ],
                [
                    'title' => 'Box 2: Faktur Pajak Q1 2024',
                    'physical_condition' => 'Baik / Box Karton Standar TB 30g',
                    'items' => [
                        ['document_name' => 'Faktur Masukan', 'period_start' => '2024-01', 'period_end' => '2024-03', 'notes' => 'Lengkap'],
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($user)->post(route('archives.store'), $payload);

        $response->assertRedirect(route('archives.index'));
        $response->assertSessionHas('success');

        $draft = Archive::where('status', 'draft')->where('title', 'like', '%Box 1: Voucher Kas Jan-Feb 2024%')->first();
        $this->assertNotNull($draft);
        $this->assertEquals('draft', $draft->status);
        $this->assertStringContainsString('is_multi_box_draft', $draft->content_description);

        $decoded = json_decode($draft->content_description, true);
        $this->assertTrue($decoded['is_multi_box_draft']);
        $this->assertCount(2, $decoded['boxes']);

        // Test editing this multi-box draft via archives.update
        $updatePayload = [
            'department_id' => $dept->id,
            'company_name' => 'PT INDRACO GLOBAL INDONESIA',
            'tgl_penyerahan' => date('Y-m-d'),
            'submit_action' => 'draft',
            'boxes' => [
                [
                    'title' => 'Box 1: Voucher Kas Revisi',
                    'physical_condition' => 'Baik / Box Karton Standar TB 30g',
                    'items' => [
                        ['document_name' => 'Kwitansi 001 Revisi', 'period_start' => '2024-01', 'period_end' => '2024-01', 'notes' => 'Lunas Revisi'],
                    ]
                ],
                [
                    'title' => 'Box 2: Faktur Pajak Revisi',
                    'physical_condition' => 'Baik / Box Karton Standar TB 30g',
                    'items' => [
                        ['document_name' => 'Faktur Masukan Revisi', 'period_start' => '2024-01', 'period_end' => '2024-03', 'notes' => 'Lengkap Revisi'],
                    ]
                ]
            ]
        ];

        $updateRes = $this->actingAs($user)->put(route('archives.update', $draft->id), $updatePayload);
        $updateRes->assertRedirect(route('archives.index'));
        $updateRes->assertSessionHas('success');

        $draft->refresh();
        $this->assertStringContainsString('Box 1: Voucher Kas Revisi', $draft->title);
        $decodedUpdated = json_decode($draft->content_description, true);
        $this->assertEquals('Box 1: Voucher Kas Revisi', $decodedUpdated['boxes'][0]['title']);
    }

    public function test_can_submit_multi_box_for_verification_with_scan_form()
    {
        $dept = Department::where('code', 'FIN')->first();
        $user = User::firstOrCreate(
            ['email' => 'pic_fin_create_test2@indraco.com'],
            [
                'name' => 'PIC Finance 2',
                'password' => bcrypt('password'),
                'role' => 'pic_dept',
                'department_id' => $dept->id,
            ]
        );

        $fakeScan = UploadedFile::fake()->create('formulir_serah_terima.pdf', 150, 'application/pdf');

        $payload = [
            'department_id' => $dept->id,
            'company_name' => 'PT INDRACO GLOBAL INDONESIA',
            'tgl_penyerahan' => date('Y-m-d'),
            'submit_action' => 'submit',
            'scan_input_form' => $fakeScan,
            'boxes' => [
                [
                    'title' => 'Box 1: Rekening Koran 2023',
                    'physical_condition' => 'Baik / Box Karton Standar TB 30g',
                    'items' => [
                        ['document_name' => 'BCA Jan-Des', 'period_start' => '2023-01', 'period_end' => '2023-12', 'notes' => 'Asli'],
                    ]
                ],
                [
                    'title' => 'Box 2: Petty Cash 2023',
                    'physical_condition' => 'Baik / Box Karton Standar TB 30g',
                    'items' => [
                        ['document_name' => 'PC Voucher', 'period_start' => '2023-01', 'period_end' => '2023-06', 'notes' => 'Lengkap'],
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($user)->post(route('archives.store'), $payload);

        $response->assertRedirect(route('archives.index'));
        $response->assertSessionHas('success');

        $boxes = Archive::whereIn('title', ['Box 1: Rekening Koran 2023', 'Box 2: Petty Cash 2023'])->get();
        $this->assertCount(2, $boxes);

        foreach ($boxes as $box) {
            $this->assertEquals('pending_verification', $box->status);
            $this->assertNotNull($box->scan_input_form);
            $this->assertTrue(Storage::disk('public')->exists($box->scan_input_form));
        }
    }

    public function test_pic_dept_is_locked_to_own_department_on_multi_box()
    {
        $finDept = Department::where('code', 'FIN')->first();
        $hrdDept = Department::where('code', 'HRD')->first();

        $user = User::firstOrCreate(
            ['email' => 'pic_fin_create_test3@indraco.com'],
            [
                'name' => 'PIC Finance 3',
                'password' => bcrypt('password'),
                'role' => 'pic_dept',
                'department_id' => $finDept->id,
            ]
        );

        // Trying to submit for HRD department ID
        $payload = [
            'department_id' => $hrdDept->id,
            'company_name' => 'PT INDRACO GLOBAL INDONESIA',
            'tgl_penyerahan' => date('Y-m-d'),
            'submit_action' => 'draft',
            'boxes' => [
                [
                    'title' => 'Box Finance Nyasar',
                    'items' => [
                        ['document_name' => 'Item 1', 'period_start' => '2024-01', 'period_end' => '2024-01', 'notes' => '-']
                    ]
                ]
            ]
        ];

        $this->actingAs($user)->post(route('archives.store'), $payload);

        // Verify that the created archive is forced to user's FIN department
        $created = Archive::where('title', 'Box Finance Nyasar')->first();
        $this->assertNotNull($created);
        $this->assertEquals($finDept->id, $created->department_id);
    }

    public function test_similarity_detection_identifies_identical_and_similar_titles()
    {
        $finDept = Department::where('code', 'FIN')->first();
        $user = User::firstOrCreate(
            ['email' => 'pic_fin_sim_test@indraco.com'],
            [
                'name' => 'PIC Finance Sim',
                'password' => bcrypt('password'),
                'role' => 'pic_dept',
                'department_id' => $finDept->id,
            ]
        );

        $existingArchive = Archive::create([
            'department_id' => $finDept->id,
            'title' => 'Voucher Kas Kecil Jan-Mar 2024',
            'status' => 'pending_verification',
            'created_by_user_id' => $user->id,
            'physical_condition' => 'Baik',
            'periode' => '3 Bulan',
            'period_start_date' => '2024-01-01',
            'period_end_date' => '2024-03-31',
            'period_yy_mm' => '2024/01 - 2024/03',
            'tgl_penyerahan' => '2024-04-01',
            'content_description' => 'Voucher Kas Kecil Jan-Mar 2024',
        ]);

        // 1. Exact match test
        $resExact = $this->actingAs($user)->postJson(route('api.archives.check_similarity'), [
            'department_id' => $finDept->id,
            'title' => 'Voucher Kas Kecil Jan-Mar 2024',
        ]);
        $resExact->assertStatus(200);
        $resExact->assertJsonFragment(['has_similarity' => true, 'match_type' => 'identical']);

        // 2. Similar match test (with suffix/minor difference)
        $resSimilar = $this->actingAs($user)->postJson(route('api.archives.check_similarity'), [
            'department_id' => $finDept->id,
            'title' => 'Voucher Kas Kecil Jan-Mar 2024 Salinan',
        ]);
        $resSimilar->assertStatus(200);
        $resSimilar->assertJsonFragment(['has_similarity' => true, 'match_type' => 'similar']);

        // 3. Unique title test
        $resUnique = $this->actingAs($user)->postJson(route('api.archives.check_similarity'), [
            'department_id' => $finDept->id,
            'title' => 'Surat Keputusan Direksi HRD Tahun 2020',
        ]);
        $resUnique->assertStatus(200);
        $resUnique->assertJsonFragment(['has_similarity' => false]);
    }

    public function test_can_save_draft_with_empty_fields_without_restrictions()
    {
        $dept = Department::where('code', 'FIN')->first();
        $user = User::firstOrCreate(
            ['email' => 'pic_fin_draft_empty@indraco.com'],
            [
                'name' => 'PIC Finance Draft',
                'password' => bcrypt('password'),
                'role' => 'pic_dept',
                'department_id' => $dept->id,
            ]
        );

        // Completely empty payload with draft submit_action
        $payload = [
            'submit_action' => 'draft',
            'department_id' => '',
            'company_name' => '',
            'tgl_penyerahan' => '',
            'boxes' => [
                [
                    'title' => '',
                    'document_type' => '',
                    'items' => [
                        ['document_name' => '', 'period_start' => '', 'period_end' => '', 'notes' => '']
                    ]
                ],
                [
                    'title' => '',
                    'items' => []
                ]
            ]
        ];

        $response = $this->actingAs($user)->post(route('archives.store'), $payload);

        $response->assertRedirect(route('archives.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();

        // Sesuai requirement baru: Draft multi-box disimpan sebagai 1 data arsip tunggal
        $this->assertDatabaseHas('archives', [
            'status' => 'draft',
            'department_id' => $dept->id,
            'title' => 'Draft Pengajuan Arsip ' . date('Y/m') . ' (2 Box)',
        ]);
        $this->assertEquals(1, Archive::where('department_id', $dept->id)->where('status', 'draft')->where('title', 'like', 'Draft Pengajuan Arsip%')->count());
    }

    public function test_can_submit_multi_box_with_both_scan_form_and_box_photo_and_custom_periods()
    {
        $dept = Department::where('code', 'FIN')->first();
        $user = User::firstOrCreate(
            ['email' => 'pic_fin_two_files@indraco.com'],
            [
                'name' => 'PIC Finance Two Files',
                'password' => bcrypt('password'),
                'role' => 'pic_dept',
                'department_id' => $dept->id,
            ]
        );

        $fakeScan = UploadedFile::fake()->create('scan_pengajuan.pdf', 100, 'application/pdf');
        $fakeBoxPhoto = UploadedFile::fake()->image('foto_kardus.jpg', 600, 600);

        $payload = [
            'department_id' => $dept->id,
            'company_name' => 'PT INDRACO GLOBAL INDONESIA',
            'tgl_penyerahan' => date('Y-m-d'),
            'submit_action' => 'submit',
            'scan_input_form' => $fakeScan,
            'file' => $fakeBoxPhoto,
            'boxes' => [
                [
                    'title' => 'Box 1: Dokumen Pajak 2024',
                    'periode' => '12 Bulan',
                    'physical_condition' => 'Kardus Tebal Standar',
                    'items' => [
                        [
                            'document_name' => 'Faktur Pajak Seri A',
                            'period_start' => '2024-01',
                            'period_end' => '2024-12',
                            'notes' => 'Catatan berkas khusus baris 1'
                        ],
                    ]
                ],
                [
                    'title' => 'Box 2: Dokumen Pajak 2024 (Lanjutan)',
                    'periode' => '12 Bulan',
                    'physical_condition' => 'Kardus Tebal Standar',
                    'items' => [
                        [
                            'document_name' => 'Faktur Pajak Seri B',
                            'period_start' => '2024-01',
                            'period_end' => '2024-12',
                            'notes' => 'Faktur Pajak Seri B'
                        ],
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($user)->post(route('archives.store'), $payload);

        $response->assertRedirect(route('archives.index'));
        $response->assertSessionHas('success');

        $boxes = Archive::whereIn('title', ['Box 1: Dokumen Pajak 2024', 'Box 2: Dokumen Pajak 2024 (Lanjutan)'])->get();
        $this->assertCount(2, $boxes);

        foreach ($boxes as $box) {
            $this->assertEquals('pending_verification', $box->status);
            $this->assertNotNull($box->scan_input_form);
            $this->assertTrue(Storage::disk('public')->exists($box->scan_input_form));
            $this->assertNotNull($box->file_path);
            $this->assertTrue(Storage::disk('public')->exists($box->file_path));
            $this->assertEquals('12 Bulan', $box->periode);
        }

        $box1 = Archive::where('title', 'Box 1: Dokumen Pajak 2024')->first();
        $item1 = ArchiveItem::where('archive_id', $box1->id)->first();
        $this->assertEquals('Catatan berkas khusus baris 1', $item1->notes);
    }

    public function test_duplicate_box_name_is_rejected_in_submission()
    {
        Storage::fake('public');
        $dept = Department::where('code', 'FIN')->first();
        $user = User::firstOrCreate(
            ['email' => 'pic_fin_dup_test@indraco.com'],
            [
                'name' => 'PIC Finance Dup',
                'password' => bcrypt('password'),
                'role' => 'pic_dept',
                'department_id' => $dept->id,
            ]
        );

        $scanFile = UploadedFile::fake()->create('scan_form.pdf', 100, 'application/pdf');

        // Payload with 2 boxes having identical names in the same batch
        $payloadBatchDup = [
            'department_id' => $dept->id,
            'company_name' => 'PT INDRACO GLOBAL INDONESIA',
            'tgl_penyerahan' => date('Y-m-d'),
            'submit_action' => 'submit',
            'scan_input_form' => $scanFile,
            'boxes' => [
                [
                    'title' => 'BOX-FIN-2026-SAME',
                    'physical_condition' => 'Baik / Box Karton Standar TB 30g',
                    'items' => [
                        ['document_name' => 'Dokumen A', 'period_start' => '2024-01', 'period_end' => '2024-01', 'notes' => 'Catatan Dokumen A']
                    ]
                ],
                [
                    'title' => 'BOX-FIN-2026-SAME',
                    'physical_condition' => 'Baik / Box Karton Standar TB 30g',
                    'items' => [
                        ['document_name' => 'Dokumen B', 'period_start' => '2024-01', 'period_end' => '2024-01', 'notes' => 'Catatan Dokumen B']
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($user)->post(route('archives.store'), $payloadBatchDup);
        $response->assertSessionHasErrors(['boxes']);
        $this->assertDatabaseMissing('archives', ['title' => 'BOX-FIN-2026-SAME']);
    }

    public function test_restored_box_from_borrowing_allows_same_box_name()
    {
        Storage::fake('public');
        $dept = Department::where('code', 'FIN')->first();
        $user = User::firstOrCreate(
            ['email' => 'pic_fin_restore_test@indraco.com'],
            [
                'name' => 'PIC Finance Restore',
                'password' => bcrypt('password'),
                'role' => 'pic_dept',
                'department_id' => $dept->id,
            ]
        );

        // Buat arsip awal yang sudah pernah ditarik
        $originalArchive = Archive::create([
            'department_id' => $dept->id,
            'title' => 'BOX-FIN-2026-PULANG',
            'box_number' => 'BOX-FIN-2026-PULANG',
            'status' => 'borrowed',
            'created_by_user_id' => $user->id,
            'tgl_penyerahan' => date('Y-m-d'),
            'periode' => '1 Bulan',
            'period_start_date' => date('Y-m-01'),
            'period_end_date' => date('Y-m-t'),
            'periode_doc' => date('Y/m'),
            'content_description' => '1. Arsip Lama',
            'physical_condition' => 'Baik / Box Karton Standar TB 30g',
        ]);

        $borrowingLog = \App\Models\BorrowingLog::create([
            'archive_id' => $originalArchive->id,
            'borrower_user_id' => $user->id,
            'request_date' => date('Y-m-d'),
            'purpose' => 'Audit Pajak',
            'status' => 'approved',
            'borrow_date' => date('Y-m-d'),
        ]);

        $scanFile = UploadedFile::fake()->create('scan_form_restore.pdf', 100, 'application/pdf');

        // Pengajuan kembali dengan box yang sama (origin_archive_id dan origin_borrowing_log_id disertakan)
        $payloadRestore = [
            'department_id' => $dept->id,
            'company_name' => 'PT INDRACO GLOBAL INDONESIA',
            'tgl_penyerahan' => date('Y-m-d'),
            'submit_action' => 'submit',
            'scan_input_form' => $scanFile,
            'boxes' => [
                [
                    'title' => 'BOX-FIN-2026-PULANG',
                    'physical_condition' => 'Baik / Box Karton Standar TB 30g',
                    'origin_archive_id' => $originalArchive->id,
                    'origin_borrowing_log_id' => $borrowingLog->id,
                    'items' => [
                        ['document_name' => 'Dokumen Pulang Kembali', 'period_start' => '2024-01', 'period_end' => '2024-01', 'notes' => 'Dokumen Pulang Selesai Pinjam']
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($user)->post(route('archives.store'), $payloadRestore);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('borrowings.index'));

        // Cek bahwa pengajuan baru berhasil dibuat dengan status pending_verification
        $restoredArchive = Archive::where('title', 'BOX-FIN-2026-PULANG')
            ->where('status', 'pending_verification')
            ->first();
        $this->assertNotNull($restoredArchive);
        $this->assertEquals($originalArchive->box_number, $restoredArchive->box_number);
    }
}

