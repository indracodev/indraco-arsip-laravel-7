<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RestorePerformanceIndexes extends Migration
{
    /**
     * Run the migrations.
     * Restores critical database performance indexes wiped out during SQLite table recreation.
     *
     * @return void
     */
    public function up()
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // 1. Table archives
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_archives_status" ON "archives" ("status");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_archives_retention_expiry" ON "archives" ("retention_expiry_date");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_archives_dept_status" ON "archives" ("department_id", "status");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_archives_periode_doc" ON "archives" ("periode_doc");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_archives_box_number" ON "archives" ("box_number");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_archives_created_by" ON "archives" ("created_by_user_id");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_archives_location" ON "archives" ("warehouse_location_id");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_archives_rack_slot" ON "archives" ("warehouse_rack_slot_id");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_archives_sub_dept" ON "archives" ("sub_department_id");');

            // 2. Table archive_items (Crucial for with('items') relational queries)
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_archive_items_archive_id" ON "archive_items" ("archive_id");');

            // 3. Table borrowing_logs
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_borrowing_status" ON "borrowing_logs" ("status");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_borrowing_archive_status" ON "borrowing_logs" ("archive_id", "status");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_borrowing_borrower" ON "borrowing_logs" ("borrower_user_id");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_borrowing_request_date" ON "borrowing_logs" ("request_date");');

            // 4. Table destruction_logs
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_destruction_archive_id" ON "destruction_logs" ("archive_id");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_destruction_approval_status" ON "destruction_logs" ("approval_status");');

            // 5. Table activity_logs
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_activity_action" ON "activity_logs" ("action");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_activity_user_id" ON "activity_logs" ("user_id");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_activity_department_id" ON "activity_logs" ("department_id");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_activity_created_at" ON "activity_logs" ("created_at");');

            // 6. Table warehouse_rack_slots
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_rack_slots_archive_id" ON "warehouse_rack_slots" ("archive_id");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_rack_slots_location_status" ON "warehouse_rack_slots" ("warehouse_location_id", "status");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_rack_slots_slot_code" ON "warehouse_rack_slots" ("slot_code");');

            // 7. Table warehouse_locations
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_locations_warehouse_id" ON "warehouse_locations" ("warehouse_id");');
        } else {
            Schema::table('archives', function (Blueprint $table) {
                $table->index('status', 'idx_archives_status');
                $table->index('retention_expiry_date', 'idx_archives_retention_expiry');
                $table->index(['department_id', 'status'], 'idx_archives_dept_status');
                $table->index('periode_doc', 'idx_archives_periode_doc');
                $table->index('box_number', 'idx_archives_box_number');
                $table->index('created_by_user_id', 'idx_archives_created_by');
                $table->index('warehouse_location_id', 'idx_archives_location');
                $table->index('warehouse_rack_slot_id', 'idx_archives_rack_slot');
                $table->index('sub_department_id', 'idx_archives_sub_dept');
            });

            Schema::table('archive_items', function (Blueprint $table) {
                $table->index('archive_id', 'idx_archive_items_archive_id');
            });

            Schema::table('borrowing_logs', function (Blueprint $table) {
                $table->index('status', 'idx_borrowing_status');
                $table->index(['archive_id', 'status'], 'idx_borrowing_archive_status');
                $table->index('borrower_user_id', 'idx_borrowing_borrower');
                $table->index('request_date', 'idx_borrowing_request_date');
            });

            Schema::table('destruction_logs', function (Blueprint $table) {
                $table->index('archive_id', 'idx_destruction_archive_id');
                $table->index('approval_status', 'idx_destruction_approval_status');
            });

            Schema::table('activity_logs', function (Blueprint $table) {
                $table->index('action', 'idx_activity_action');
                $table->index('user_id', 'idx_activity_user_id');
                $table->index('department_id', 'idx_activity_department_id');
                $table->index('created_at', 'idx_activity_created_at');
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
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS "idx_archives_status";');
            DB::statement('DROP INDEX IF EXISTS "idx_archives_retention_expiry";');
            DB::statement('DROP INDEX IF EXISTS "idx_archives_dept_status";');
            DB::statement('DROP INDEX IF EXISTS "idx_archives_periode_doc";');
            DB::statement('DROP INDEX IF EXISTS "idx_archives_box_number";');
            DB::statement('DROP INDEX IF EXISTS "idx_archives_created_by";');
            DB::statement('DROP INDEX IF EXISTS "idx_archives_location";');
            DB::statement('DROP INDEX IF EXISTS "idx_archives_rack_slot";');
            DB::statement('DROP INDEX IF EXISTS "idx_archives_sub_dept";');
            DB::statement('DROP INDEX IF EXISTS "idx_archive_items_archive_id";');
            DB::statement('DROP INDEX IF EXISTS "idx_borrowing_status";');
            DB::statement('DROP INDEX IF EXISTS "idx_borrowing_archive_status";');
            DB::statement('DROP INDEX IF EXISTS "idx_borrowing_borrower";');
            DB::statement('DROP INDEX IF EXISTS "idx_borrowing_request_date";');
            DB::statement('DROP INDEX IF EXISTS "idx_destruction_archive_id";');
            DB::statement('DROP INDEX IF EXISTS "idx_destruction_approval_status";');
            DB::statement('DROP INDEX IF EXISTS "idx_activity_action";');
            DB::statement('DROP INDEX IF EXISTS "idx_activity_user_id";');
            DB::statement('DROP INDEX IF EXISTS "idx_activity_department_id";');
            DB::statement('DROP INDEX IF EXISTS "idx_activity_created_at";');
        } else {
            Schema::table('archives', function (Blueprint $table) {
                $table->dropIndex('idx_archives_status');
                $table->dropIndex('idx_archives_retention_expiry');
                $table->dropIndex('idx_archives_dept_status');
                $table->dropIndex('idx_archives_periode_doc');
                $table->dropIndex('idx_archives_box_number');
                $table->dropIndex('idx_archives_created_by');
                $table->dropIndex('idx_archives_location');
                $table->dropIndex('idx_archives_rack_slot');
                $table->dropIndex('idx_archives_sub_dept');
            });

            Schema::table('archive_items', function (Blueprint $table) {
                $table->dropIndex('idx_archive_items_archive_id');
            });

            Schema::table('borrowing_logs', function (Blueprint $table) {
                $table->dropIndex('idx_borrowing_status');
                $table->dropIndex('idx_borrowing_archive_status');
                $table->dropIndex('idx_borrowing_borrower');
                $table->dropIndex('idx_borrowing_request_date');
            });

            Schema::table('destruction_logs', function (Blueprint $table) {
                $table->dropIndex('idx_destruction_archive_id');
                $table->dropIndex('idx_destruction_approval_status');
            });

            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropIndex('idx_activity_action');
                $table->dropIndex('idx_activity_user_id');
                $table->dropIndex('idx_activity_department_id');
                $table->dropIndex('idx_activity_created_at');
            });
        }
    }
}
