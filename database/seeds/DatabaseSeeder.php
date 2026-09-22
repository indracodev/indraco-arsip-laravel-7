<?php

use App\Models\Archive;
use App\Models\BorrowingLog;
use App\Models\Department;
use App\Models\DestructionLog;
use App\Models\NumberingFormat;
use App\Models\SubDepartment;
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
        // 1. Seed All 29 Indraco Departments
        $this->call(DepartmentSeeder::class);

        $deptFin = Department::where('code', 'FIN')->first();
        $deptHrd = Department::where('code', 'HRD')->first();
        $deptMkt = Department::where('code', 'MKT')->first();
        $deptLog = Department::where('code', 'LOG')->first();
        $deptProd = Department::where('code', 'PROD')->first();

        // 2. Seed Sub-Departments
        $this->call(SubDepartmentSeeder::class);

        $subTax = SubDepartment::where('code', 'TAX')->first();
        $subRec = SubDepartment::where('code', 'REC')->first();
        $subExp = SubDepartment::where('code', 'EXP')->where('department_id', $deptMkt->id)->first();

        // 3. Seed Users
        $admin = User::create([
            'name' => 'Super Admin (Webdev)',
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
            'name' => 'Siti Finance Curator (FAT)',
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

        // 4. Seed Dynamic Numbering Format
        NumberingFormat::create([
            'name' => 'Format Custom Default Box Arsip Indraco',
            'pattern' => '{COMPANY}/{DEPT}/{YEAR}/{ROMAN_MONTH}/{COUNTER}',
            'current_counter' => 5,
            'padding' => 4,
            'is_active' => true,
        ]);

        // 5. Seed Warehouses, Racks & 100 Slots (5 sap x 20 box TB 30g)
        $this->call(WarehouseLayoutSeeder::class);

        $locR1A = WarehouseLocation::where('rack_code', 'RAK-R1-A')->first();
        $locR1B = WarehouseLocation::where('rack_code', 'RAK-R1-B')->first();
        $locR3A = WarehouseLocation::where('rack_code', 'RAK-R3-A')->first();

        // 6. Seed Archives
        $arc1 = Archive::create([
            'box_number' => 'IND/FIN/2026/I/0001',
            'department_id' => $deptFin->id,
            'sub_department_id' => $subTax ? $subTax->id : null,
            'company_name' => 'PT Indraco Jaya Perkasa',
            'document_type' => 'FAKTUR_PAJAK',
            'created_by_user_id' => $picFin->id,
            'title' => 'Laporan Keuangan & Faktur Pajak Q1 2026',
            'period_start_date' => '2026-01-01',
            'period_end_date' => '2026-03-31',
            'period_text' => 'Januari - Maret 2026',
            'period_yy_mm' => '2026/01',
            'periode_doc' => '2026/01',
            'tgl_penyerahan' => '2026-04-05',
            'content_description' => "1. Bukti Kas Masuk Q1 2026\n2. Bukti Kas Keluar Cabang Surabaya\n3. Rekonsiliasi Bank BCA & Mandiri\n4. Faktur Pajak PPN & PPh Pasal 23\n5. Slip Setoran Pajak Masa Jan-Mar",
            'retention_years' => 10,
            'retention_expiry_date' => '2036-03-31',
            'physical_condition' => 'Baik / Binder Hardcover Plastik',
            'warehouse_location_id' => $locR1A ? $locR1A->id : null,
            'status' => 'in_warehouse',
        ]);

        if ($locR1A) {
            $slot1 = $locR1A->slots()->first();
            if ($slot1) {
                $slot1->update(['archive_id' => $arc1->id, 'status' => 'filled']);
                $arc1->update(['warehouse_rack_slot_id' => $slot1->id]);
            }

            WarehouseEntryLog::create([
                'archive_id' => $arc1->id,
                'pic_gudang_id' => $picGudang->id,
                'location_id' => $locR1A->id,
                'entry_date' => Carbon::now()->subDays(20),
                'notes' => 'Penerimaan fisik berkas dari PIC Keuangan. Box diserahterimakan lengkap.',
            ]);
        }

        Archive::create([
            'box_number' => null,
            'department_id' => $deptHrd->id,
            'sub_department_id' => $subRec ? $subRec->id : null,
            'company_name' => 'PT Indraco Jaya Perkasa',
            'document_type' => 'KONTRAK_KERJA',
            'created_by_user_id' => $picHrd->id,
            'title' => 'Berkas Kontrak Kerja & Evaluasi Karyawan Kontrak 2025',
            'period_start_date' => '2025-01-01',
            'period_end_date' => '2025-12-31',
            'period_text' => 'Januari - Desember 2025',
            'period_yy_mm' => '2025/01',
            'periode_doc' => '2025/01',
            'tgl_penyerahan' => '2026-01-10',
            'content_description' => "1. Surat Perjanjian Kerja Waktu Tertentu (PKWT) Batch 1\n2. Formulir Kepesertaan BPJS Ketenagakerjaan\n3. Hasil Evaluasi Kinerja 6 Bulanan",
            'retention_years' => 5,
            'retention_expiry_date' => '2030-12-31',
            'physical_condition' => 'Baik / Map Odner Biru',
            'warehouse_location_id' => null,
            'status' => 'pending_verification',
        ]);

        $arc3 = Archive::create([
            'box_number' => 'IND/MKT/2026/II/0002',
            'department_id' => $deptMkt->id,
            'sub_department_id' => $subExp ? $subExp->id : null,
            'company_name' => 'PT Indraco Jaya Perkasa',
            'document_type' => 'MOU_SPONSOR',
            'created_by_user_id' => $picMkt->id,
            'title' => 'Dokumen Kerjasama Sponsor & Kontrak Brand Ambassador 2025',
            'period_start_date' => '2025-06-01',
            'period_end_date' => '2025-12-31',
            'period_text' => 'Juni - Desember 2025',
            'period_yy_mm' => '2025/06',
            'periode_doc' => '2025/06',
            'tgl_penyerahan' => '2026-02-01',
            'content_description' => "1. Naskah asli MOU sponsorship event pameran kopi internasional\n2. Bukti transfer fee BA\n3. Dokumentasi serah terima produk",
            'retention_years' => 5,
            'retention_expiry_date' => '2030-12-31',
            'physical_condition' => 'Baik / Map Plastik',
            'warehouse_location_id' => $locR3A ? $locR3A->id : null,
            'status' => 'borrowed',
        ]);

        if ($locR3A) {
            $slot3 = $locR3A->slots()->first();
            if ($slot3) {
                $slot3->update(['archive_id' => $arc3->id, 'status' => 'filled']);
                $arc3->update(['warehouse_rack_slot_id' => $slot3->id]);
            }

            WarehouseEntryLog::create([
                'archive_id' => $arc3->id,
                'pic_gudang_id' => $picGudang->id,
                'location_id' => $locR3A->id,
                'entry_date' => Carbon::now()->subDays(15),
                'notes' => 'Penerimaan awal berkas.',
            ]);
        }

        BorrowingLog::create([
            'archive_id' => $arc3->id,
            'borrower_user_id' => $picMkt->id,
            'pic_gudang_id' => $picGudang->id,
            'request_date' => Carbon::now()->subDays(5),
            'borrow_date' => Carbon::now()->subDays(3),
            'expected_return_date' => Carbon::now()->addDays(7),
            'purpose' => 'Keperluan verifikasi tim audit internal untuk peninjauan klaim anggaran promosi tahunan.',
            'status' => 'dispatched',
            'approval_status' => 'approved',
            'is_approval_uploaded' => true,
            'scan_approval_borrow' => 'uploads/approvals/sample_borrow_approval.pdf',
            'notes' => 'Berkas fisik diserahkan langsung ke Ibu Dewi (PIC MKT).',
        ]);

        $arc4 = Archive::create([
            'box_number' => 'IND/FIN/2021/IX/0003',
            'department_id' => $deptFin->id,
            'sub_department_id' => $subTax ? $subTax->id : null,
            'company_name' => 'PT Indraco Jaya Perkasa',
            'document_type' => 'NOTA_PENJUALAN',
            'created_by_user_id' => $picFin->id,
            'title' => 'Nota Penjualan & Slip Pembayaran Tunai 2021',
            'period_start_date' => '2021-01-01',
            'period_end_date' => '2021-06-30',
            'period_text' => 'Januari - Juni 2021',
            'period_yy_mm' => '2021/01',
            'periode_doc' => '2021/01',
            'tgl_penyerahan' => '2021-07-15',
            'content_description' => "1. Arsip nota transaksi penjualan toko cabang\n2. Rekap kas kecil harian",
            'retention_years' => 5,
            'retention_expiry_date' => Carbon::now()->addDays(15)->format('Y-m-d'),
            'physical_condition' => 'Agak Kusam / Kertas Thermal',
            'warehouse_location_id' => $locR1B ? $locR1B->id : null,
            'status' => 'in_warehouse',
        ]);

        if ($locR1B) {
            $slot4 = $locR1B->slots()->first();
            if ($slot4) {
                $slot4->update(['archive_id' => $arc4->id, 'status' => 'filled']);
                $arc4->update(['warehouse_rack_slot_id' => $slot4->id]);
            }

            WarehouseEntryLog::create([
                'archive_id' => $arc4->id,
                'pic_gudang_id' => $picGudang->id,
                'location_id' => $locR1B->id,
                'entry_date' => Carbon::now()->subYears(4),
                'notes' => 'Penempatan berkas lama di Rak R1-B.',
            ]);
        }

        $arc5 = Archive::create([
            'box_number' => 'IND/LOG/2019/V/0004',
            'department_id' => $deptLog->id,
            'created_by_user_id' => $admin->id,
            'title' => 'Surat Jalan & Delivery Order (DO) Retur 2019',
            'period_start_date' => '2019-01-01',
            'period_end_date' => '2019-12-31',
            'period_text' => 'Januari - Desember 2019',
            'period_yy_mm' => '2019/01',
            'periode_doc' => '2019/01',
            'tgl_penyerahan' => '2020-01-20',
            'content_description' => 'Dokumen lembar jalan armada truk pengiriman barang retur expired pabrik.',
            'retention_years' => 5,
            'retention_expiry_date' => '2024-12-31',
            'physical_condition' => 'Rapuh / Terjadwal Pemusnahan',
            'warehouse_location_id' => null,
            'status' => 'destroyed',
        ]);

        DestructionLog::create([
            'archive_id' => $arc5->id,
            'proposed_by_user_id' => $picGudang->id,
            'approved_by_dept_pic_id' => $admin->id,
            'bap_number' => 'BAP/IND/2026/00001',
            'destruction_date' => '2026-02-15',
            'method' => 'Pencacahan Mesin Industrial Paper Shredder',
            'is_approval_uploaded' => true,
            'approval_status' => 'approved',
            'scan_approval_destruction' => 'uploads/approvals/sample_destruction_bap.pdf',
            'notes' => 'Pemusnahan disaksikan oleh perwakilan tim Manajemen & PIC Departemen Logistik.',
        ]);
    }
}
