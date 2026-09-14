<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDestructionLogsTable extends Migration
{
    public function up()
    {
        Schema::create('destruction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_id')->constrained('archives')->cascadeOnDelete();
            $table->foreignId('proposed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by_dept_pic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('bap_number', 100);
            $table->date('destruction_date');
            $table->string('method', 100)->default('Pencacahan / Shading Standard');
            $table->string('certificate_file', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('destruction_logs');
    }
}
