<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAssignedDepartmentIdsToWarehouseLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('warehouse_locations', function (Blueprint $table) {
            $table->text('assigned_department_ids')->nullable()->after('assigned_department_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('warehouse_locations', function (Blueprint $table) {
            $table->dropColumn('assigned_department_ids');
        });
    }
}
