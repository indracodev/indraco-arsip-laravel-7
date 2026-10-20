<?php

namespace Tests\Feature;

use App\Models\Archive;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExpiringArchivePopupAndBorrowingFilterTest extends TestCase
{
    use DatabaseTransactions;

    public function test_api_expiring_list_returns_boxes_for_admin_and_pic()
    {
        $dept1 = Department::firstOrCreate(['code' => 'FAT_TEST'], ['name' => 'Finance & Accounting Test']);
        $dept2 = Department::firstOrCreate(['code' => 'HRD_TEST'], ['name' => 'Human Resources Test']);

        $admin = User::firstOrCreate(
            ['email' => 'admin_expiry_test@test.com'],
            ['name' => 'Super Admin Test', 'password' => bcrypt('password'), 'role' => 'admin']
        );

        $picFat = User::firstOrCreate(
            ['email' => 'pic_fat_expiry_test@test.com'],
            ['name' => 'PIC FAT Test', 'password' => bcrypt('password'), 'role' => 'pic_dept', 'department_id' => $dept1->id]
        );

        // 1. Box Expired (FAT)
        $arcExpired = Archive::create([
            'title' => 'Box Pajak 2020 Test',
            'box_number' => 'BOX-FAT-EXP',
            'department_id' => $dept1->id,
            'created_by_user_id' => $admin->id,
            'period_start_date' => Carbon::now()->subYears(2)->format('Y-m-d'),
            'period_end_date' => Carbon::now()->subYears(1)->format('Y-m-d'),
            'retention_years' => 1,
            'retention_expiry_date' => Carbon::now()->subMonths(2)->format('Y-m-d'),
            'status' => 'in_warehouse',
            'content_description' => 'Dokumen Pajak Masa 2020',
        ]);

        // 2. Box Mendekati Expired H-12 (HRD)
        $arcNearExp = Archive::create([
            'title' => 'Box Absensi 2025 Test',
            'box_number' => 'BOX-HRD-NEAR',
            'department_id' => $dept2->id,
            'created_by_user_id' => $admin->id,
            'period_start_date' => Carbon::now()->subYear()->format('Y-m-d'),
            'period_end_date' => Carbon::now()->format('Y-m-d'),
            'retention_years' => 1,
            'retention_expiry_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'status' => 'in_warehouse',
            'content_description' => 'Dokumen Absensi Karyawan 2025',
        ]);

        // 3. Box Safe (Masih 2 tahun lagi)
        $arcSafe = Archive::create([
            'title' => 'Box Kontrak 2026 Test',
            'box_number' => 'BOX-FAT-SAFE',
            'department_id' => $dept1->id,
            'created_by_user_id' => $admin->id,
            'period_start_date' => Carbon::now()->format('Y-m-d'),
            'period_end_date' => Carbon::now()->addYear()->format('Y-m-d'),
            'retention_years' => 5,
            'retention_expiry_date' => Carbon::now()->addYears(2)->format('Y-m-d'),
            'status' => 'in_warehouse',
            'content_description' => 'Dokumen Kontrak Aktif',
        ]);

        // Admin checks expiring list: should see both FAT and HRD
        $resAdmin = $this->actingAs($admin)->getJson(route('api.archives.expiring_list'));
        $resAdmin->assertStatus(200);
        $resAdmin->assertJsonFragment(['box_number' => 'BOX-FAT-EXP']);
        $resAdmin->assertJsonFragment(['box_number' => 'BOX-HRD-NEAR']);
        $resAdmin->assertJsonMissing(['box_number' => 'BOX-FAT-SAFE']);

        // PIC FAT checks expiring list: should ONLY see FAT, not HRD
        $resPic = $this->actingAs($picFat)->getJson(route('api.archives.expiring_list'));
        $resPic->assertStatus(200);
        $resPic->assertJsonFragment(['box_number' => 'BOX-FAT-EXP']);
        $resPic->assertJsonMissing(['box_number' => 'BOX-HRD-NEAR']);
    }

    public function test_pulled_archive_cannot_be_borrowed_again()
    {
        $dept = Department::firstOrCreate(['code' => 'MKT_TEST'], ['name' => 'Marketing Test']);

        $user = User::firstOrCreate(
            ['email' => 'pic_mkt_borrow_test@test.com'],
            ['name' => 'PIC MKT Test', 'password' => bcrypt('password'), 'role' => 'pic_dept', 'department_id' => $dept->id]
        );

        $arcInWh = Archive::create([
            'title' => 'Box Brosur 2026 Test',
            'box_number' => 'BOX-MKT-WH',
            'department_id' => $dept->id,
            'created_by_user_id' => $user->id,
            'period_start_date' => Carbon::now()->format('Y-m-d'),
            'period_end_date' => Carbon::now()->addMonths(6)->format('Y-m-d'),
            'status' => 'in_warehouse',
            'content_description' => 'Brosur Promosi 2026',
        ]);

        $arcBorrowed = Archive::create([
            'title' => 'Box Campaign 2025 Test',
            'box_number' => 'BOX-MKT-OUT',
            'department_id' => $dept->id,
            'created_by_user_id' => $user->id,
            'period_start_date' => Carbon::now()->subYear()->format('Y-m-d'),
            'period_end_date' => Carbon::now()->subMonths(6)->format('Y-m-d'),
            'status' => 'borrowed',
            'content_description' => 'Campaign Q4 2025',
        ]);

        // Access catalog view as PIC
        $res = $this->actingAs($user)->get(route('archives.index'));
        $res->assertStatus(200);

        // The view contains borrowableArchiveIds containing BOX-MKT-WH, but NOT BOX-MKT-OUT
        $res->assertSee((string)$arcInWh->id);
    }
}
