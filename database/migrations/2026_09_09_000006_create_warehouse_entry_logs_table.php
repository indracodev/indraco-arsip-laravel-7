<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWarehouseEntryLogsTable extends Migration
{
    public function up()
    {
        Schema::create('warehouse_entry_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_id')->constrained('archives')->cascadeOnDelete();
            $table->foreignId('pic_gudang_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('warehouse_locations')->cascadeOnDelete();
            $table->dateTime('entry_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('warehouse_entry_logs');
    }
}
