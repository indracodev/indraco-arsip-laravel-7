<?php

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds for all 29 Indraco Departments.
     *
     * @return void
     */
    public function run()
    {
        $departments = [
            [
                'code' => 'DSG',
                'name' => 'DESIGN',
                'description' => 'Departemen Desain Kreatif, Packaging, Visual & Grafis',
                'retention_years' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'DMKT',
                'name' => 'DIGITAL & MARKETING',
                'description' => 'Departemen Digital Marketing, Social Media & Campaign Online',
                'retention_years' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'EDP',
                'name' => 'EDP',
                'description' => 'Departemen Electronic Data Processing & Operasional Komputer',
                'retention_years' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'EKSP',
                'name' => 'EKSPEDISI',
                'description' => 'Departemen Ekspedisi, Delivery, & Pengiriman Barang',
                'retention_years' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'EXP',
                'name' => 'EXPORT',
                'description' => 'Departemen Perdagangan Ekspor & Hubungan Internasional',
                'retention_years' => 7,
                'is_active' => true,
            ],
            [
                'code' => 'FCT',
                'name' => 'FACTORY',
                'description' => 'Departemen Operasional & Fasilitas Pabrik',
                'retention_years' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'FATCLM',
                'name' => 'FATCLAIM',
                'description' => 'Departemen Klaim Keuangan, Asuransi & Penggantian Biaya (FAT)',
                'retention_years' => 10,
                'is_active' => true,
            ],
            [
                'code' => 'FIN',
                'name' => 'FIN, ACC & TAX',
                'description' => 'Departemen Keuangan, Akuntansi, Perpajakan & Treasury (FAT)',
                'retention_years' => 10,
                'is_active' => true,
            ],
            [
                'code' => 'HCS',
                'name' => 'HCS',
                'description' => 'Departemen Human Capital & Services',
                'retention_years' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'HRD',
                'name' => 'HRGA & LEGAL',
                'description' => 'Departemen Human Resources, General Affairs, Hubungan Industrial & Legalitas',
                'retention_years' => 10,
                'is_active' => true,
            ],
            [
                'code' => 'IA',
                'name' => 'INTERNAL AUDIT',
                'description' => 'Departemen Pemeriksaan Internal, Audit SOP & Kepatuhan Perusahaan',
                'retention_years' => 10,
                'is_active' => true,
            ],
            [
                'code' => 'LOG',
                'name' => 'LOGISTIC',
                'description' => 'Departemen Logistik, Manajemen Rantai Pasok & Distribusi',
                'retention_years' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'MKT',
                'name' => 'MARKETING',
                'description' => 'Departemen Pemasaran, Strategi Brand & Riset Pasar',
                'retention_years' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'MIS',
                'name' => 'MIS',
                'description' => 'Departemen Management Information System & Analisis Data',
                'retention_years' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'OUT',
                'name' => 'OUTLET',
                'description' => 'Departemen Manajemen Outlet, Cafe, & Retail Direct Store',
                'retention_years' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'OWN',
                'name' => 'OWNER',
                'description' => 'Sekretariat Direksi, Dewan Komisaris & Dokumen Kepemilikan Perusahaan',
                'retention_years' => 15,
                'is_active' => true,
            ],
            [
                'code' => 'PPIC',
                'name' => 'PPIC',
                'description' => 'Departemen Perencanaan Produksi dan Pengendalian Persediaan',
                'retention_years' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'PRD',
                'name' => 'PRODUCTION',
                'description' => 'Departemen Produksi Manufaktur Produk Makanan & Minuman',
                'retention_years' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'PROD',
                'name' => 'PRODUKSI',
                'description' => 'Departemen Produksi Pengolahan Kopi, Teh, & Beverage',
                'retention_years' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'PRM',
                'name' => 'PROMOSI',
                'description' => 'Departemen Promosi, Event, Sponsorship & Aktivasi Penjualan',
                'retention_years' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'PUR',
                'name' => 'PURCHASING',
                'description' => 'Departemen Pengadaan Bahan Baku, Kemasan, & Pembelian Perlengkapan',
                'retention_years' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'QA',
                'name' => 'QA',
                'description' => 'Departemen Quality Assurance, Standar ISO, Sertifikasi Halal & BPOM',
                'retention_years' => 10,
                'is_active' => true,
            ],
            [
                'code' => 'QC',
                'name' => 'QC',
                'description' => 'Departemen Quality Control, Pengujian Laboratorium & Inspeksi Kualitas',
                'retention_years' => 10,
                'is_active' => true,
            ],
            [
                'code' => 'RND',
                'name' => 'R & D',
                'description' => 'Departemen Penelitian & Pengembangan Produk Baru (Research & Development)',
                'retention_years' => 10,
                'is_active' => true,
            ],
            [
                'code' => 'RDI',
                'name' => 'RDI',
                'description' => 'Departemen Research, Development & Innovation',
                'retention_years' => 10,
                'is_active' => true,
            ],
            [
                'code' => 'SLM',
                'name' => 'SALES & MARKETING',
                'description' => 'Departemen Penjualan Komersial & Pemasaran Multi-Channel',
                'retention_years' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'TECH',
                'name' => 'TECHNIC',
                'description' => 'Departemen Teknik, Pemeliharaan Mesin & Utilitas Pabrik',
                'retention_years' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'WH',
                'name' => 'WAREHOUSE',
                'description' => 'Departemen Pergudangan Bahan Baku, Kemasan & Barang Jadi',
                'retention_years' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'WEB',
                'name' => 'WEB DEV',
                'description' => 'Departemen Web Development, Aplikasi Digital & Infrastruktur IT',
                'retention_years' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['code' => $dept['code']],
                [
                    'name' => $dept['name'],
                    'description' => $dept['description'],
                    'retention_years' => $dept['retention_years'],
                    'is_active' => $dept['is_active'],
                ]
            );
        }
    }
}
