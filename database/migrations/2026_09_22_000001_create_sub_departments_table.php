<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubDepartmentsTable extends Migration
{
    public function up()
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('sidar_id', 50)->nullable()->after('id');
            $table->integer('retention_years')->default(5)->after('description');
            $table->boolean('is_active')->default(true)->after('retention_years');
        });

        Schema::create('sub_departments', function (Blueprint $table) {
            $table->id();
            $table->string('sidar_id', 50)->nullable();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->integer('retention_years')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['department_id', 'code']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sub_departments');

        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['sidar_id', 'retention_years', 'is_active']);
        });
    }
}
