<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Laravel 7 MasterArchiveSeeder: [department code, code, name, document type, retention years, description].
 * Rows whose department code does not exist are skipped, as in Laravel 7 (the two
 * "FACTORY" rows: that department's code is FCT).
 */
class MasterArchiveSeeder extends Seeder
{
    /** @var list<array{0: string, 1: string, 2: string, 3: string, 4: int, 5: string}> */
    private const MASTER_ARCHIVES = [
        ['FIN', 'FIN-FP-01', 'Faktur Pajak Masukan & Keluaran', 'FAKTUR_PAJAK', 10, 'Faktur pajak dan bukti potong PPh/PPN'],
        ['FIN', 'FIN-RK-02', 'Laporan Rekapitulasi Kas & Bank Harian', 'FAKTUR_PAJAK', 7, 'Buku kas dan rekening koran bulanan'],
        ['FIN', 'FIN-AP-03', 'Voucher Pembayaran Vendor & Supplier (AP)', 'FAKTUR_PAJAK', 5, 'Bukti transaksi pelunasan hutang dagang'],
        ['FIN', 'FIN-AR-04', 'Invoice & Surat Penagihan Piutang (AR)', 'FAKTUR_PAJAK', 5, 'Bukti penagihan piutang pelanggan'],
        ['FIN', 'FIN-SP-05', 'Surat Setoran Pajak (SSP / e-Billing)', 'FAKTUR_PAJAK', 10, 'Bukti lapor dan setor pajak negara'],
        ['HRD', 'HRD-PKWT-01', 'Surat Perjanjian Kerja Waktu Tertentu (PKWT)', 'KONTRAK_KERJA', 10, 'Kontrak kerja karyawan kontrak dan magang'],
        ['HRD', 'HRD-ABS-02', 'Rekapitulasi Absensi & Lembur Karyawan', 'KONTRAK_KERJA', 3, 'Laporan kehadiran dan surat perintah lembur'],
        ['HRD', 'HRD-BPJS-03', 'Dokumen BPJS Ketenagakerjaan & Kesehatan', 'KONTRAK_KERJA', 5, 'Formulir kepesertaan dan iuran BPJS'],
        ['HRD', 'HRD-CV-04', 'Berkas Rekrutmen & Biodata Karyawan', 'KONTRAK_KERJA', 5, 'Curriculum Vitae dan berkas lamaran kerja'],
        ['EDP', 'EDP-MNT-01', 'Maintenance & Perawatan Hardware / AC Server', 'MAINTENANCE', 3, 'Kartu riwayat service dan perawatan berkala'],
        ['EDP', 'EDP-LOG-02', 'Log Backup Data & Recovery System', 'MAINTENANCE', 5, 'Laporan verifikasi restore database berkala'],
        ['EDP', 'EDP-AST-03', 'Berita Acara Serah Terima Aset IT (BAST)', 'UMUM', 5, 'Form penyerahan laptop/komputer kerja ke user'],
        ['EXP', 'EXP-SJ-01', 'Surat Jalan Pengiriman Barang & Ekspedisi', 'SURAT_JALAN', 5, 'Surat jalan resmi cap basah dan tanda terima'],
        ['EXP', 'EXP-POD-02', 'Bukti Terima Barang (Proof of Delivery)', 'SURAT_JALAN', 5, 'Resi dan surat jalan balik dari ekspedisi rekanan'],
        ['EXP', 'EXP-KLAIM-03', 'Formulir Klaim & Retur Ekspedisi', 'SURAT_JALAN', 3, 'Laporan barang rusak / hilang dalam perjalanan'],
        ['MKT', 'MKT-MOU-01', 'MoU Kerjasama Sponsorship & Event', 'MOU_SPONSOR', 5, 'Kontrak sponsor promosi dan pameran'],
        ['MKT', 'MKT-PO-02', 'Purchase Order & Nota Pembelian Media Promo', 'MOU_SPONSOR', 3, 'Bukti order cetak banner, merchandise, dan iklan'],
        ['FACTORY', 'FCT-QC-01', 'Laporan Quality Control (QC) & Uji Lab Kopi', 'PRODUKSI_QC', 5, 'Hasil uji kelayakan bahan baku dan produk jadi'],
        ['FACTORY', 'FCT-MSN-02', 'Kartu Riwayat Maintenance Mesin Roasting & Packing', 'MAINTENANCE', 5, 'Jadwal servis dan penggantian spare part mesin'],
    ];

    public function run(): void
    {
        $departmentIds = DB::table('departments')->pluck('id', 'code');
        $now = now();
        $rows = [];

        foreach (self::MASTER_ARCHIVES as $m) {
            if (! isset($departmentIds[$m[0]])) {
                continue;
            }
            $rows[] = [
                'department_id' => $departmentIds[$m[0]],
                'code' => $m[1],
                'name' => $m[2],
                'document_type' => $m[3],
                'retention_years' => $m[4],
                'description' => $m[5],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('master_archives')->insert($rows);
    }
}
