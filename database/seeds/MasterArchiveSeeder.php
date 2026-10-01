<?php

use App\Models\Department;
use App\Models\MasterArchive;
use App\Models\SubDepartment;
use Illuminate\Database\Seeder;

class MasterArchiveSeeder extends Seeder
{
    public function run()
    {
        $data = [
            'FIN' => [
                ['code' => 'FIN-FP-01', 'name' => 'Faktur Pajak Masukan & Keluaran', 'type' => 'FAKTUR_PAJAK', 'retention' => 10, 'desc' => 'Faktur pajak dan bukti potong PPh/PPN'],
                ['code' => 'FIN-RK-02', 'name' => 'Laporan Rekapitulasi Kas & Bank Harian', 'type' => 'FAKTUR_PAJAK', 'retention' => 7, 'desc' => 'Buku kas dan rekening koran bulanan'],
                ['code' => 'FIN-AP-03', 'name' => 'Voucher Pembayaran Vendor & Supplier (AP)', 'type' => 'FAKTUR_PAJAK', 'retention' => 5, 'desc' => 'Bukti transaksi pelunasan hutang dagang'],
                ['code' => 'FIN-AR-04', 'name' => 'Invoice & Surat Penagihan Piutang (AR)', 'type' => 'FAKTUR_PAJAK', 'retention' => 5, 'desc' => 'Bukti penagihan piutang pelanggan'],
                ['code' => 'FIN-SP-05', 'name' => 'Surat Setoran Pajak (SSP / e-Billing)', 'type' => 'FAKTUR_PAJAK', 'retention' => 10, 'desc' => 'Bukti lapor dan setor pajak negara'],
            ],
            'HRD' => [
                ['code' => 'HRD-PKWT-01', 'name' => 'Surat Perjanjian Kerja Waktu Tertentu (PKWT)', 'type' => 'KONTRAK_KERJA', 'retention' => 10, 'desc' => 'Kontrak kerja karyawan kontrak dan magang'],
                ['code' => 'HRD-ABS-02', 'name' => 'Rekapitulasi Absensi & Lembur Karyawan', 'type' => 'KONTRAK_KERJA', 'retention' => 3, 'desc' => 'Laporan kehadiran dan surat perintah lembur'],
                ['code' => 'HRD-BPJS-03', 'name' => 'Dokumen BPJS Ketenagakerjaan & Kesehatan', 'type' => 'KONTRAK_KERJA', 'retention' => 5, 'desc' => 'Formulir kepesertaan dan iuran BPJS'],
                ['code' => 'HRD-CV-04', 'name' => 'Berkas Rekrutmen & Biodata Karyawan', 'type' => 'KONTRAK_KERJA', 'retention' => 5, 'desc' => 'Curriculum Vitae dan berkas lamaran kerja'],
            ],
            'EDP' => [
                ['code' => 'EDP-MNT-01', 'name' => 'Maintenance & Perawatan Hardware / AC Server', 'type' => 'MAINTENANCE', 'retention' => 3, 'desc' => 'Kartu riwayat service dan perawatan berkala'],
                ['code' => 'EDP-LOG-02', 'name' => 'Log Backup Data & Recovery System', 'type' => 'MAINTENANCE', 'retention' => 5, 'desc' => 'Laporan verifikasi restore database berkala'],
                ['code' => 'EDP-AST-03', 'name' => 'Berita Acara Serah Terima Aset IT (BAST)', 'type' => 'UMUM', 'retention' => 5, 'desc' => 'Form penyerahan laptop/komputer kerja ke user'],
            ],
            'EXP' => [
                ['code' => 'EXP-SJ-01', 'name' => 'Surat Jalan Pengiriman Barang & Ekspedisi', 'type' => 'SURAT_JALAN', 'retention' => 5, 'desc' => 'Surat jalan resmi cap basah dan tanda terima'],
                ['code' => 'EXP-POD-02', 'name' => 'Bukti Terima Barang (Proof of Delivery)', 'type' => 'SURAT_JALAN', 'retention' => 5, 'desc' => 'Resi dan surat jalan balik dari ekspedisi rekanan'],
                ['code' => 'EXP-KLAIM-03', 'name' => 'Formulir Klaim & Retur Ekspedisi', 'type' => 'SURAT_JALAN', 'retention' => 3, 'desc' => 'Laporan barang rusak / hilang dalam perjalanan'],
            ],
            'MKT' => [
                ['code' => 'MKT-MOU-01', 'name' => 'MoU Kerjasama Sponsorship & Event', 'type' => 'MOU_SPONSOR', 'retention' => 5, 'desc' => 'Kontrak sponsor promosi dan pameran'],
                ['code' => 'MKT-PO-02', 'name' => 'Purchase Order & Nota Pembelian Media Promo', 'type' => 'MOU_SPONSOR', 'retention' => 3, 'desc' => 'Bukti order cetak banner, merchandise, dan iklan'],
            ],
            'FACTORY' => [
                ['code' => 'FCT-QC-01', 'name' => 'Laporan Quality Control (QC) & Uji Lab Kopi', 'type' => 'PRODUKSI_QC', 'retention' => 5, 'desc' => 'Hasil uji kelayakan bahan baku dan produk jadi'],
                ['code' => 'FCT-MSN-02', 'name' => 'Kartu Riwayat Maintenance Mesin Roasting & Packing', 'type' => 'MAINTENANCE', 'retention' => 5, 'desc' => 'Jadwal servis dan penggantian spare part mesin'],
            ]
        ];

        foreach ($data as $deptCode => $archives) {
            $dept = Department::where('code', $deptCode)->first();
            if ($dept) {
                foreach ($archives as $arc) {
                    MasterArchive::firstOrCreate(
                        [
                            'department_id' => $dept->id,
                            'name' => $arc['name'],
                        ],
                        [
                            'code' => $arc['code'],
                            'document_type' => $arc['type'],
                            'retention_years' => $arc['retention'],
                            'description' => $arc['desc'],
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}
