<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArchivesTable extends Migration
{
    public function up()
    {
        Schema::create('archives', function (Blueprint $table) {
            $table->id();
            $table->string('box_number', 100)->nullable()->unique();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 255);
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->string('period_text', 100)->nullable();
            $table->text('content_description');
            $table->integer('retention_years')->default(5);
            $table->date('retention_expiry_date')->nullable();
            $table->string('physical_condition', 100)->default('Baik');
            $table->string('file_path', 255)->nullable();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->enum('status', [
                'draft',
                'pending_verification',
                'approved_booked',
                'in_warehouse',
                'borrowed',
                'pending_destruction',
                'destroyed'
            ])->default('draft');
            $table->text('rejection_note')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('archives');
    }
}
