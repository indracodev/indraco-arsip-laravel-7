<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCatalogsAndSubDepartmentsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Create companies table
        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->nullable();
                $table->string('name', 150)->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });

            // Seed default companies
            $now = now();
            DB::table('companies')->insert([
                ['code' => 'IND', 'name' => 'PT Indraco Global', 'description' => 'Holding & Global Operations', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'TRD', 'name' => 'PT Indraco Trading', 'description' => 'Distribusi & Perdagangan Komoditas', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'ENT', 'name' => 'PT Indraco Enterprise', 'description' => 'Unit Usaha Korporasi & Bisnis', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'INT', 'name' => 'PT Indraco International', 'description' => 'Ekspor & Relasi Internasional', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        // 2. Create document_types table (Catalog)
        if (!Schema::hasTable('document_types')) {
            Schema::create('document_types', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->string('description', 255)->nullable();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->boolean('is_preset')->default(false);
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });

            // Seed initial document types presets
            $now = now();
            $presets = [
                ['code' => 'PR', 'name' => 'PR (Purchase Requisition)', 'description' => 'Permintaan Pembelian', 'is_preset' => true],
                ['code' => 'PO', 'name' => 'PO (Purchase Order)', 'description' => 'Pesanan Pembelian', 'is_preset' => true],
                ['code' => 'SURAT JALAN', 'name' => 'Surat Jalan (DO)', 'description' => 'Bukti Kirim & Terima', 'is_preset' => true],
                ['code' => 'FAKTUR', 'name' => 'Faktur / Invoice', 'description' => 'Tagihan Pembelian/Jual', 'is_preset' => true],
                ['code' => 'FAKTUR PAJAK', 'name' => 'Faktur Pajak', 'description' => 'Faktur Pajak Standar', 'is_preset' => true],
                ['code' => 'ABSENSI', 'name' => 'Absensi / Payroll', 'description' => 'Presensi & Rekap Gaji', 'is_preset' => true],
                ['code' => 'KONTRAK', 'name' => 'Kontrak / SPK', 'description' => 'Perjanjian & Legalitas', 'is_preset' => true],
                ['code' => 'UTILITY', 'name' => 'Utility / Bukti Bayar', 'description' => 'Tagihan Operasional', 'is_preset' => true],
                ['code' => 'DATA SAMPLE', 'name' => 'Data Sample', 'description' => 'Uji Lab & Quality Control', 'is_preset' => true],
                ['code' => 'LAINNYA', 'name' => 'Lainnya (Spesifik)', 'description' => 'Dokumen spesifik lain', 'is_preset' => true],
            ];

            foreach ($presets as $p) {
                $p['created_at'] = $now;
                $p['updated_at'] = $now;
                DB::table('document_types')->insert($p);
            }
        }

        // 3. Create sub_departments table
        if (!Schema::hasTable('sub_departments')) {
            Schema::create('sub_departments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
                $table->string('code', 20)->nullable();
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->timestamps();
            });

            $now = now();

            // Ensure department DNM exists
            $dnm = DB::table('departments')->where('code', 'DNM')->first();
            if (!$dnm) {
                $dnmId = DB::table('departments')->insertGetId([
                    'code' => 'DNM',
                    'name' => 'Design, Marketing & Communication',
                    'description' => 'Divisi Desain Kreatif, Pemasaran & Hubungan Komunikasi',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $dnmId = $dnm->id;
            }

            // Seed FAT Subdepartments (Finance, Accounting, Tax)
            $fat = DB::table('departments')->where('code', 'FAT')->first();
            if ($fat) {
                DB::table('sub_departments')->insert([
                    ['department_id' => $fat->id, 'code' => 'FIN', 'name' => 'Finance', 'description' => 'Keuangan, Kas & Pembayaran', 'created_at' => $now, 'updated_at' => $now],
                    ['department_id' => $fat->id, 'code' => 'ACC', 'name' => 'Accounting', 'description' => 'Akuntansi, Pembukuan & Laporan Finansial', 'created_at' => $now, 'updated_at' => $now],
                    ['department_id' => $fat->id, 'code' => 'TAX', 'name' => 'Tax', 'description' => 'Perpajakan, SPT & Faktur Pajak', 'created_at' => $now, 'updated_at' => $now],
                ]);
            }

            // Seed DNM Subdepartments (Design, Marketing & Communication)
            DB::table('sub_departments')->insert([
                ['department_id' => $dnmId, 'code' => 'DSG', 'name' => 'Design', 'description' => 'Desain Grafis & Multimedia', 'created_at' => $now, 'updated_at' => $now],
                ['department_id' => $dnmId, 'code' => 'MKT', 'name' => 'Marketing', 'description' => 'Pemasaran & Strategi Promosi', 'created_at' => $now, 'updated_at' => $now],
                ['department_id' => $dnmId, 'code' => 'COMM', 'name' => 'Communication', 'description' => 'Komunikasi & Hubungan Publik (PR)', 'created_at' => $now, 'updated_at' => $now],
            ]);

            // Seed HRD Subdepartments
            $hrd = DB::table('departments')->where('code', 'HRD')->first();
            if ($hrd) {
                DB::table('sub_departments')->insert([
                    ['department_id' => $hrd->id, 'code' => 'HR', 'name' => 'Human Resources', 'description' => 'Rekrutmen & Pengelolaan SDM', 'created_at' => $now, 'updated_at' => $now],
                    ['department_id' => $hrd->id, 'code' => 'GA', 'name' => 'General Affairs', 'description' => 'Umum & Operasional Kantor', 'created_at' => $now, 'updated_at' => $now],
                    ['department_id' => $hrd->id, 'code' => 'LGL', 'name' => 'Legal', 'description' => 'Perizinan & Hukum Perusahaan', 'created_at' => $now, 'updated_at' => $now],
                ]);
            }

            // Seed LOG Subdepartments
            $log = DB::table('departments')->where('code', 'LOG')->first();
            if ($log) {
                DB::table('sub_departments')->insert([
                    ['department_id' => $log->id, 'code' => 'WH', 'name' => 'Warehouse', 'description' => 'Pergudangan & Stok Barang', 'created_at' => $now, 'updated_at' => $now],
                    ['department_id' => $log->id, 'code' => 'PRC', 'name' => 'Purchasing', 'description' => 'Pengadaan & Pembelian Material', 'created_at' => $now, 'updated_at' => $now],
                ]);
            }

            // Seed PROD Subdepartments
            $prod = DB::table('departments')->where('code', 'PROD')->first();
            if ($prod) {
                DB::table('sub_departments')->insert([
                    ['department_id' => $prod->id, 'code' => 'PROD', 'name' => 'Production Operation', 'description' => 'Operasional Pabrik & Pengolahan', 'created_at' => $now, 'updated_at' => $now],
                    ['department_id' => $prod->id, 'code' => 'QC', 'name' => 'Quality Control', 'description' => 'Pengujian Kualitas Produk & Lab', 'created_at' => $now, 'updated_at' => $now],
                ]);
            }
        }

        // 4. Add sub_department_id to archives table
        if (Schema::hasTable('archives') && !Schema::hasColumn('archives', 'sub_department_id')) {
            Schema::table('archives', function (Blueprint $table) {
                $table->foreignId('sub_department_id')->nullable()->after('department_id')->constrained('sub_departments')->nullOnDelete();
            });
        }

        // 5. Add sub_department_id to users table
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'sub_department_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('sub_department_id')->nullable()->after('department_id')->constrained('sub_departments')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'sub_department_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['sub_department_id']);
                $table->dropColumn('sub_department_id');
            });
        }

        if (Schema::hasTable('archives') && Schema::hasColumn('archives', 'sub_department_id')) {
            Schema::table('archives', function (Blueprint $table) {
                $table->dropForeign(['sub_department_id']);
                $table->dropColumn('sub_department_id');
            });
        }

        Schema::dropIfExists('sub_departments');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('companies');
    }
}
