<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveToWarehouseRackSlotsTable extends Migration
{
    public function up()
    {
        Schema::table('warehouse_rack_slots', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('status');
        });
    }

    public function down()
    {
        Schema::table('warehouse_rack_slots', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
}
