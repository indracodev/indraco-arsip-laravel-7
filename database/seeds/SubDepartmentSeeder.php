<?php

use App\Models\Department;
use App\Models\SubDepartment;
use Illuminate\Database\Seeder;

class SubDepartmentSeeder extends Seeder
{
    public function run()
    {
        $data = [
            'FIN' => [
                ['code' => 'ACC', 'name' => 'Akuntansi & Pembukuan', 'description' => 'Sub-dept pencatatan jurnal, neraca, rugi laba, dan pembukuan keuangan', 'retention_years' => 10, 'sidar_id' => 'SIDAR-FIN-01'],
                ['code' => 'TAX', 'name' => 'Perpajakan (Tax & E-Faktur)', 'description' => 'Sub-dept rekonsiliasi PPN, PPh, SPT Tahunan, dan audit pajak', 'retention_years' => 10, 'sidar_id' => 'SIDAR-FIN-02'],
                ['code' => 'TRS', 'name' => 'Keuangan & Treasury', 'description' => 'Sub-dept kasir, kas operasional, dan cash flow perbankan', 'retention_years' => 5, 'sidar_id' => 'SIDAR-FIN-03'],
            ],
            'HRD' => [
                ['code' => 'REC', 'name' => 'Recruitment & Personalia', 'description' => 'Sub-dept berkas lamaran, kontrak kerja PKWT, dan BPJS', 'retention_years' => 5, 'sidar_id' => 'SIDAR-HRD-01'],
                ['code' => 'LGL', 'name' => 'Legal & Perizinan', 'description' => 'Sub-dept akta, MoU, sertifikat hak paten, dan perizinan usaha', 'retention_years' => 15, 'sidar_id' => 'SIDAR-HRD-02'],
                ['code' => 'GA', 'name' => 'General Affairs', 'description' => 'Sub-dept fasilitas, utilitas, dan perawatan kantor', 'retention_years' => 3, 'sidar_id' => 'SIDAR-HRD-03'],
            ],
            'MKT' => [
                ['code' => 'DOM', 'name' => 'Sales & Distribusi Domestik', 'description' => 'Sub-dept penjualan dan penagihan distributor nasional', 'retention_years' => 5, 'sidar_id' => 'SIDAR-MKT-01'],
                ['code' => 'EXP', 'name' => 'Ekspor & Kerjasama Internasional', 'description' => 'Sub-dept kontrak ekspor kopi & sertifikasi ekspor internasional', 'retention_years' => 7, 'sidar_id' => 'SIDAR-MKT-02'],
                ['code' => 'PR', 'name' => 'Branding & Digital Marketing', 'description' => 'Sub-dept periklanan, campaign promo, dan endorsement', 'retention_years' => 3, 'sidar_id' => 'SIDAR-MKT-03'],
            ],
            'LOG' => [
                ['code' => 'WH', 'name' => 'Gudang & Inventaris', 'description' => 'Sub-dept opname stok dan kartu gudang barang jadi', 'retention_years' => 5, 'sidar_id' => 'SIDAR-LOG-01'],
                ['code' => 'EXP', 'name' => 'Ekspedisi & Pengiriman', 'description' => 'Sub-dept surat jalan dan delivery order armada', 'retention_years' => 5, 'sidar_id' => 'SIDAR-LOG-02'],
            ],
            'PROD' => [
                ['code' => 'COF', 'name' => 'Pengolahan Kopi (Coffee Roastery)', 'description' => 'Sub-dept batching & roasting kopi bubuk/biji', 'retention_years' => 5, 'sidar_id' => 'SIDAR-PROD-01'],
                ['code' => 'TEA', 'name' => 'Pengolahan Teh & Minuman', 'description' => 'Sub-dept pencampuran & pengemasan teh', 'retention_years' => 5, 'sidar_id' => 'SIDAR-PROD-02'],
                ['code' => 'QC', 'name' => 'Quality Control & Lab', 'description' => 'Sub-dept uji sampel, sertifikasi halal & BPOM pangan', 'retention_years' => 10, 'sidar_id' => 'SIDAR-PROD-03'],
            ],
        ];

        foreach ($data as $deptCode => $subDepts) {
            $department = Department::where('code', $deptCode)->first();
            if (!$department) {
                continue;
            }

            foreach ($subDepts as $sub) {
                SubDepartment::updateOrCreate(
                    [
                        'department_id' => $department->id,
                        'code' => $sub['code'],
                    ],
                    [
                        'name' => $sub['name'],
                        'description' => $sub['description'],
                        'retention_years' => $sub['retention_years'],
                        'sidar_id' => $sub['sidar_id'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
