<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The 29 departments of Laravel 7 DepartmentSeeder: [code, name, description, retention years].
 */
class DepartmentSeeder extends Seeder
{
    /** @var list<array{0: string, 1: string, 2: string, 3: int}> */
    private const DEPARTMENTS = [
        ['DSG', 'DESIGN', 'Departemen Desain Kreatif, Packaging, Visual & Grafis', 5],
        ['DMKT', 'DIGITAL & MARKETING', 'Departemen Digital Marketing, Social Media & Campaign Online', 5],
        ['EDP', 'EDP', 'Departemen Electronic Data Processing & Operasional Komputer', 5],
        ['EKSP', 'EKSPEDISI', 'Departemen Ekspedisi, Delivery, & Pengiriman Barang', 5],
        ['EXP', 'EXPORT', 'Departemen Perdagangan Ekspor & Hubungan Internasional', 7],
        ['FCT', 'FACTORY', 'Departemen Operasional & Fasilitas Pabrik', 5],
        ['FATCLM', 'FATCLAIM', 'Departemen Klaim Keuangan, Asuransi & Penggantian Biaya (FAT)', 10],
        ['FIN', 'FIN, ACC & TAX', 'Departemen Keuangan, Akuntansi, Perpajakan & Treasury (FAT)', 10],
        ['HCS', 'HCS', 'Departemen Human Capital & Services', 5],
        ['HRD', 'HRGA & LEGAL', 'Departemen Human Resources, General Affairs, Hubungan Industrial & Legalitas', 10],
        ['IA', 'INTERNAL AUDIT', 'Departemen Pemeriksaan Internal, Audit SOP & Kepatuhan Perusahaan', 10],
        ['LOG', 'LOGISTIC', 'Departemen Logistik, Manajemen Rantai Pasok & Distribusi', 5],
        ['MKT', 'MARKETING', 'Departemen Pemasaran, Strategi Brand & Riset Pasar', 5],
        ['MIS', 'MIS', 'Departemen Management Information System & Analisis Data', 5],
        ['OUT', 'OUTLET', 'Departemen Manajemen Outlet, Cafe, & Retail Direct Store', 5],
        ['OWN', 'OWNER', 'Sekretariat Direksi, Dewan Komisaris & Dokumen Kepemilikan Perusahaan', 15],
        ['PPIC', 'PPIC', 'Departemen Perencanaan Produksi dan Pengendalian Persediaan', 5],
        ['PRD', 'PRODUCTION', 'Departemen Produksi Manufaktur Produk Makanan & Minuman', 5],
        ['PROD', 'PRODUKSI', 'Departemen Produksi Pengolahan Kopi, Teh, & Beverage', 5],
        ['PRM', 'PROMOSI', 'Departemen Promosi, Event, Sponsorship & Aktivasi Penjualan', 5],
        ['PUR', 'PURCHASING', 'Departemen Pengadaan Bahan Baku, Kemasan, & Pembelian Perlengkapan', 5],
        ['QA', 'QA', 'Departemen Quality Assurance, Standar ISO, Sertifikasi Halal & BPOM', 10],
        ['QC', 'QC', 'Departemen Quality Control, Pengujian Laboratorium & Inspeksi Kualitas', 10],
        ['RND', 'R & D', 'Departemen Penelitian & Pengembangan Produk Baru (Research & Development)', 10],
        ['RDI', 'RDI', 'Departemen Research, Development & Innovation', 10],
        ['SLM', 'SALES & MARKETING', 'Departemen Penjualan Komersial & Pemasaran Multi-Channel', 5],
        ['TECH', 'TECHNIC', 'Departemen Teknik, Pemeliharaan Mesin & Utilitas Pabrik', 5],
        ['WH', 'WAREHOUSE', 'Departemen Pergudangan Bahan Baku, Kemasan & Barang Jadi', 5],
        ['WEB', 'WEB DEV', 'Departemen Web Development, Aplikasi Digital & Infrastruktur IT', 5],
    ];

    public function run(): void
    {
        $now = now();

        DB::table('departments')->insert(array_map(fn (array $d): array => [
            'code' => $d[0],
            'name' => $d[1],
            'description' => $d[2],
            'retention_years' => $d[3],
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], self::DEPARTMENTS));
    }
}
