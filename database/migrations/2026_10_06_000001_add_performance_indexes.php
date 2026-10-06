<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerformanceIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Indexing archives table for lightning-fast filtering, dashboard counting, and retention checks
        Schema::table('archives', function (Blueprint $table) {
            $table->index('status', 'idx_archives_status');
            $table->index('retention_expiry_date', 'idx_archives_retention_expiry');
            $table->index(['department_id', 'status'], 'idx_archives_dept_status');
            $table->index('periode_doc', 'idx_archives_periode_doc');
        });

        // 2. Indexing borrowing_logs for realtime tracking and status filtering
        Schema::table('borrowing_logs', function (Blueprint $table) {
            $table->index('status', 'idx_borrowing_status');
            $table->index(['archive_id', 'status'], 'idx_borrowing_archive_status');
            $table->index('request_date', 'idx_borrowing_request_date');
        });

        // 3. Indexing warehouse_rack_slots for visual warehouse lookup and archive linkage
        Schema::table('warehouse_rack_slots', function (Blueprint $table) {
            $table->index('archive_id', 'idx_rack_slots_archive_id');
            $table->index(['warehouse_location_id', 'status'], 'idx_rack_slots_location_status');
            $table->index('slot_code', 'idx_rack_slots_slot_code');
        });

        // 4. Indexing warehouse_locations for room & rack relationship
        Schema::table('warehouse_locations', function (Blueprint $table) {
            $table->index('warehouse_id', 'idx_locations_warehouse_id');
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
            $table->dropIndex('idx_locations_warehouse_id');
        });

        Schema::table('warehouse_rack_slots', function (Blueprint $table) {
            $table->dropIndex('idx_rack_slots_archive_id');
            $table->dropIndex('idx_rack_slots_location_status');
            $table->dropIndex('idx_rack_slots_slot_code');
        });

        Schema::table('borrowing_logs', function (Blueprint $table) {
            $table->dropIndex('idx_borrowing_status');
            $table->dropIndex('idx_borrowing_archive_status');
            $table->dropIndex('idx_borrowing_request_date');
        });

        Schema::table('archives', function (Blueprint $table) {
            $table->dropIndex('idx_archives_status');
            $table->dropIndex('idx_archives_retention_expiry');
            $table->dropIndex('idx_archives_dept_status');
            $table->dropIndex('idx_archives_periode_doc');
        });
    }
}
