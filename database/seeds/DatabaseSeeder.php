<?php

use App\Models\Archive;
use App\Models\BorrowingLog;
use App\Models\Department;
use App\Models\DestructionLog;
use App\Models\NumberingFormat;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseEntryLog;
use App\Models\WarehouseLocation;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1. Seed Departments
        $deptFin = Department::create([
            'code' => 'FIN',
            'name' => 'Keuangan & Akuntansi',
            'description' => 'Departemen Keuangan, Perpajakan, dan Akuntansi Perusahaan',
        ]);

        $deptHrd = Department::create([
            'code' => 'HRD',
            'name' => 'Human Resources & Legal',
            'description' => 'Departemen SDM, Ketenagakerjaan, dan Legalitas',
        ]);

        $deptMkt = Department::create([
            'code' => 'MKT',
            'name' => 'Marketing & Sales',
            'description' => 'Departemen Pemasaran, Promosi, dan Penjualan Product',
        ]);

        $deptLog = Department::create([
            'code' => 'LOG',
            'name' => 'Logistik & Rantai Pasok',
            'description' => 'Departemen Pergudangan, Pengiriman, & Supply Chain',
        ]);

        $deptProd = Department::create([
            'code' => 'PROD',
            'name' => 'Produksi & Operation',
            'description' => 'Departemen Pengolahan Kopi, Teh, dan Manufaktur',
        ]);

        // 2. Seed Users
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@indraco.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '081234567890',
        ]);

        $picGudang = User::create([
            'name' => 'Budi Warehouse Specialist',
            'email' => 'gudang@indraco.com',
            'password' => Hash::make('password'),
            'role' => 'pic_gudang',
            'phone' => '081234567891',
        ]);

        $picFin = User::create([
            'name' => 'Siti Finance Curator',
            'email' => 'fin@indraco.com',
            'password' => Hash::make('password'),
            'department_id' => $deptFin->id,
            'role' => 'pic_dept',
            'phone' => '081234567892',
        ]);

        $picHrd = User::create([
            'name' => 'Rina HR Manager',
            'email' => 'hrd@indraco.com',
            'password' => Hash::make('password'),
            'department_id' => $deptHrd->id,
            'role' => 'pic_dept',
            'phone' => '081234567893',
        ]);

        $picMkt = User::create([
            'name' => 'Dewi Marketing PIC',
            'email' => 'mkt@indraco.com',
            'password' => Hash::make('password'),
            'department_id' => $deptMkt->id,
            'role' => 'pic_dept',
            'phone' => '081234567894',
        ]);

        // 3. Seed Warehouses & Interactive Canvas Layout
        $this->call(WarehouseLayoutSeeder::class);

        $locA1 = WarehouseLocation::where('location_type', 'rack')->first();
        $locA2 = WarehouseLocation::where('location_type', 'rack')->skip(1)->first();
        $locB1 = WarehouseLocation::where('location_type', 'rack')->skip(2)->first();

        // 4. Seed Dynamic Numbering Format
        NumberingFormat::create([
            'name' => 'Format Custom Default Box Arsip Indraco',
            'pattern' => '{COMPANY}/{DEPT}/{YEAR}/{ROMAN_MONTH}/{COUNTER}',
            'current_counter' => 5,
            'padding' => 4,
            'is_active' => true,
        ]);

        // 5. Seed Archives
        $arc1 = Archive::create([
            'box_number' => 'IND/FIN/2026/I/0001',
            'department_id' => $deptFin->id,
            'created_by_user_id' => $picFin->id,
            'title' => 'Laporan Keuangan & Faktur Pajak Q1 2026',
            'period_start_date' => '2026-01-01',
            'period_end_date' => '2026-03-31',
            'period_text' => 'Januari - Maret 2026',
            'content_description' => 'Berkas bukti kas masuk, kas keluar, e-faktur pajak pertambahan nilai (PPN), dan rekonsiliasi bank cabang Surabaya & Sidoarjo.',
            'retention_years' => 5,
            'retention_expiry_date' => '2031-03-31',
            'physical_condition' => 'Baik / Binder Hardcover Plastik',
            'warehouse_location_id' => $locA1->id,
            'status' => 'in_warehouse',
        ]);

        WarehouseEntryLog::create([
            'archive_id' => $arc1->id,
            'pic_gudang_id' => $picGudang->id,
            'location_id' => $locA1->id,
            'entry_date' => Carbon::now()->subDays(20),
            'notes' => 'Penerimaan fisik berkas dari PIC Keuangan. Box diserahterimakan lengkap.',
        ]);

        Archive::create([
            'box_number' => null,
            'department_id' => $deptHrd->id,
            'created_by_user_id' => $picHrd->id,
            'title' => 'Berkas Kontrak Kerja & Evaluasi Karyawan Kontrak 2025',
            'period_start_date' => '2025-01-01',
            'period_end_date' => '2025-12-31',
            'period_text' => 'Januari - Desember 2025',
            'content_description' => 'Surat Perjanjian Kerja Waktu Tertentu (PKWT), hasil appraisal kinerja bulanan, & formulir BPJS Ketenagakerjaan.',
            'retention_years' => 3,
            'retention_expiry_date' => '2028-12-31',
            'physical_condition' => 'Baik / Map Odner Biru',
            'warehouse_location_id' => null,
            'status' => 'pending_verification',
        ]);

        $arc3 = Archive::create([
            'box_number' => 'IND/MKT/2026/II/0002',
            'department_id' => $deptMkt->id,
            'created_by_user_id' => $picMkt->id,
            'title' => 'Dokumen Kerjasama Sponsor & Kontrak Brand Ambassador 2025',
            'period_start_date' => '2025-06-01',
            'period_end_date' => '2025-12-31',
            'period_text' => 'Juni - Desember 2025',
            'content_description' => 'Naskah asli MOU sponsorship event pameran kopi internasional, bukti transfer fee BA, & dokumentasi penyerahan produk.',
            'retention_years' => 5,
            'retention_expiry_date' => '2030-12-31',
            'physical_condition' => 'Baik / Map Plastik',
            'warehouse_location_id' => $locA1->id,
            'status' => 'borrowed',
        ]);

        WarehouseEntryLog::create([
            'archive_id' => $arc3->id,
            'pic_gudang_id' => $picGudang->id,
            'location_id' => $locA1->id,
            'entry_date' => Carbon::now()->subDays(15),
            'notes' => 'Penerimaan awal berkas.',
        ]);

        BorrowingLog::create([
            'archive_id' => $arc3->id,
            'borrower_user_id' => $picMkt->id,
            'pic_gudang_id' => $picGudang->id,
            'request_date' => Carbon::now()->subDays(5),
            'borrow_date' => Carbon::now()->subDays(3),
            'expected_return_date' => Carbon::now()->addDays(7),
            'purpose' => 'Keperluan verifikasi tim audit internal untuk peninjauan klaim anggaran promosi tahunan.',
            'status' => 'dispatched',
            'notes' => 'Berkas fisik diserahkan langsung ke Ibu Dewi (PIC MKT).',
        ]);

        $arc4 = Archive::create([
            'box_number' => 'IND/FIN/2021/IX/0003',
            'department_id' => $deptFin->id,
            'created_by_user_id' => $picFin->id,
            'title' => 'Nota Penjualan & Slip Pembayaran Tunai 2021',
            'period_start_date' => '2021-01-01',
            'period_end_date' => '2021-06-30',
            'period_text' => 'Januari - Juni 2021',
            'content_description' => 'Arsip nota transaksi penjualan toko cabang & rekap kas kecil harian.',
            'retention_years' => 5,
            'retention_expiry_date' => Carbon::now()->addDays(15)->format('Y-m-d'),
            'physical_condition' => 'Agak Kusam / Kertas Thermal',
            'warehouse_location_id' => $locA2->id,
            'status' => 'in_warehouse',
        ]);

        WarehouseEntryLog::create([
            'archive_id' => $arc4->id,
            'pic_gudang_id' => $picGudang->id,
            'location_id' => $locA2->id,
            'entry_date' => Carbon::now()->subYears(4),
            'notes' => 'Penempatan berkas lama di Rak A1-Baris 02.',
        ]);

        $arc5 = Archive::create([
            'box_number' => 'IND/LOG/2019/V/0004',
            'department_id' => $deptLog->id,
            'created_by_user_id' => $admin->id,
            'title' => 'Surat Jalan & Delivery Order (DO) Retur 2019',
            'period_start_date' => '2019-01-01',
            'period_end_date' => '2019-12-31',
            'period_text' => 'Januari - Desember 2019',
            'content_description' => 'Dokumen lembar jalan armada truk pengiriman barang retur expired pabrik.',
            'retention_years' => 5,
            'retention_expiry_date' => '2024-12-31',
            'physical_condition' => 'Rapuh / Terjadwal Pemusnahan',
            'warehouse_location_id' => $locB1->id,
            'status' => 'destroyed',
        ]);

        DestructionLog::create([
            'archive_id' => $arc5->id,
            'proposed_by_user_id' => $picGudang->id,
            'approved_by_dept_pic_id' => $admin->id,
            'bap_number' => 'BAP/IND/2026/00001',
            'destruction_date' => '2026-02-15',
            'method' => 'Pencacahan Mesin Industrial Paper Shredder',
            'notes' => 'Pemusnahan disaksikan oleh perwakilan tim Manajemen & PIC Departemen Logistik.',
        ]);

    }
}
