<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRevisi1FieldsToArchivesTable extends Migration
{
    public function up()
    {
        Schema::table('archives', function (Blueprint $table) {
            $table->foreignId('sub_department_id')->nullable()->after('department_id')->constrained('sub_departments')->nullOnDelete();
            $table->string('periode_doc', 7)->nullable()->after('period_yy_mm'); // YYYY/MM
            $table->date('tgl_penyerahan')->nullable()->after('periode_doc');
            $table->boolean('is_custom_doc_name')->default(false)->after('title');
            $table->string('custom_doc_name', 255)->nullable()->after('is_custom_doc_name');
            $table->integer('masa_simpan_custom')->nullable()->after('retention_years');
        });
    }

    public function down()
    {
        Schema::table('archives', function (Blueprint $table) {
            $table->dropForeign(['sub_department_id']);
            $table->dropColumn([
                'sub_department_id',
                'periode_doc',
                'tgl_penyerahan',
                'is_custom_doc_name',
                'custom_doc_name',
                'masa_simpan_custom',
            ]);
        });
    }
}
