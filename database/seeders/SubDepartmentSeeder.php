<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Laravel 7 SubDepartmentSeeder: [department code, code, name, description, retention years, sidar_id].
 */
class SubDepartmentSeeder extends Seeder
{
    /** @var list<array{0: string, 1: string, 2: string, 3: string, 4: int, 5: string}> */
    private const SUB_DEPARTMENTS = [
        ['FIN', 'ACC', 'Akuntansi & Pembukuan', 'Sub-dept pencatatan jurnal, neraca, rugi laba, dan pembukuan keuangan', 10, 'SIDAR-FIN-01'],
        ['FIN', 'TAX', 'Perpajakan (Tax & E-Faktur)', 'Sub-dept rekonsiliasi PPN, PPh, SPT Tahunan, dan audit pajak', 10, 'SIDAR-FIN-02'],
        ['FIN', 'TRS', 'Keuangan & Treasury', 'Sub-dept kasir, kas operasional, dan cash flow perbankan', 5, 'SIDAR-FIN-03'],
        ['HRD', 'REC', 'Recruitment & Personalia', 'Sub-dept berkas lamaran, kontrak kerja PKWT, dan BPJS', 5, 'SIDAR-HRD-01'],
        ['HRD', 'LGL', 'Legal & Perizinan', 'Sub-dept akta, MoU, sertifikat hak paten, dan perizinan usaha', 15, 'SIDAR-HRD-02'],
        ['HRD', 'GA', 'General Affairs', 'Sub-dept fasilitas, utilitas, dan perawatan kantor', 3, 'SIDAR-HRD-03'],
        ['MKT', 'DOM', 'Sales & Distribusi Domestik', 'Sub-dept penjualan dan penagihan distributor nasional', 5, 'SIDAR-MKT-01'],
        ['MKT', 'EXP', 'Ekspor & Kerjasama Internasional', 'Sub-dept kontrak ekspor kopi & sertifikasi ekspor internasional', 7, 'SIDAR-MKT-02'],
        ['MKT', 'PR', 'Branding & Digital Marketing', 'Sub-dept periklanan, campaign promo, dan endorsement', 3, 'SIDAR-MKT-03'],
        ['LOG', 'WH', 'Gudang & Inventaris', 'Sub-dept opname stok dan kartu gudang barang jadi', 5, 'SIDAR-LOG-01'],
        ['LOG', 'EXP', 'Ekspedisi & Pengiriman', 'Sub-dept surat jalan dan delivery order armada', 5, 'SIDAR-LOG-02'],
        ['PROD', 'COF', 'Pengolahan Kopi (Coffee Roastery)', 'Sub-dept batching & roasting kopi bubuk/biji', 5, 'SIDAR-PROD-01'],
        ['PROD', 'TEA', 'Pengolahan Teh & Minuman', 'Sub-dept pencampuran & pengemasan teh', 5, 'SIDAR-PROD-02'],
        ['PROD', 'QC', 'Quality Control & Lab', 'Sub-dept uji sampel, sertifikasi halal & BPOM pangan', 10, 'SIDAR-PROD-03'],
    ];

    public function run(): void
    {
        $departmentIds = DB::table('departments')->pluck('id', 'code');
        $now = now();
        $rows = [];

        foreach (self::SUB_DEPARTMENTS as $s) {
            if (! isset($departmentIds[$s[0]])) {
                continue;
            }
            $rows[] = [
                'department_id' => $departmentIds[$s[0]],
                'code' => $s[1],
                'name' => $s[2],
                'description' => $s[3],
                'retention_years' => $s[4],
                'sidar_id' => $s[5],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('sub_departments')->insert($rows);
    }
}
