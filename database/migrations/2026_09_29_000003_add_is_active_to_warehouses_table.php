<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveToWarehousesTable extends Migration
{
    public function up()
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('is_fat_locked');
        });

        Schema::table('warehouse_locations', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('is_fat_locked');
        });
    }

    public function down()
    {
        Schema::table('warehouse_locations', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
}
