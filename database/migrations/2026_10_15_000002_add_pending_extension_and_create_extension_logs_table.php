<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPendingExtensionAndCreateExtensionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');

            // 1. Recreate archives table with 'pending_extension' in status check constraint
            DB::statement('CREATE TABLE "archives_temp" (
                "id" integer not null primary key autoincrement, 
                "box_number" varchar null, 
                "department_id" integer not null, 
                "created_by_user_id" integer not null, 
                "title" varchar not null, 
                "period_start_date" date not null, 
                "period_end_date" date not null, 
                "period_text" varchar null, 
                "content_description" text not null, 
                "retention_years" integer not null default \'5\', 
                "retention_expiry_date" date null, 
                "physical_condition" varchar not null default \'Baik\', 
                "file_path" varchar null, 
                "warehouse_location_id" integer null, 
                "status" varchar check ("status" in (\'draft\', \'pending_verification\', \'approved_booked\', \'in_warehouse\', \'borrowed\', \'taken\', \'pending_destruction\', \'pending_extension\', \'destroyed\')) not null default \'draft\', 
                "rejection_note" text null, 
                "created_at" datetime null, 
                "updated_at" datetime null, 
                "company_name" varchar null, 
                "document_type" varchar null, 
                "period_yy_mm" varchar null, 
                "scan_input_form" varchar null, 
                "scan_approval_input" varchar null, 
                "extension_reason" text null, 
                "scan_extension_form" varchar null, 
                "sub_department_id" integer null, 
                "periode_doc" varchar null, 
                "tgl_penyerahan" date null, 
                "is_custom_doc_name" tinyint(1) not null default \'0\', 
                "custom_doc_name" varchar null, 
                "masa_simpan_custom" integer null, 
                "warehouse_rack_slot_id" integer null, 
                "periode" varchar(100) null, 
                "origin_borrowing_log_id" integer null, 
                "re_store_snapshot" text null, 
                foreign key("department_id") references "departments"("id") on delete cascade, 
                foreign key("created_by_user_id") references "users"("id") on delete cascade, 
                foreign key("warehouse_location_id") references "warehouse_locations"("id") on delete set null
            );');

            DB::statement('INSERT INTO "archives_temp" SELECT * FROM "archives";');
            DB::statement('DROP TABLE "archives";');
            DB::statement('ALTER TABLE "archives_temp" RENAME TO "archives";');

            // Re-apply indexes on archives
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_archives_status" ON "archives" ("status");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_archives_retention_expiry" ON "archives" ("retention_expiry_date");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_archives_dept_status" ON "archives" ("department_id", "status");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_archives_periode_doc" ON "archives" ("periode_doc");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_archives_box_number" ON "archives" ("box_number");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_archives_created_by" ON "archives" ("created_by_user_id");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_archives_location" ON "archives" ("warehouse_location_id");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_archives_rack_slot" ON "archives" ("warehouse_rack_slot_id");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_archives_sub_dept" ON "archives" ("sub_department_id");');

            DB::statement('PRAGMA foreign_keys = ON;');
        }

        // 2. Create archive_extension_logs table
        if (!Schema::hasTable('archive_extension_logs')) {
            Schema::create('archive_extension_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('archive_id');
                $table->unsignedBigInteger('proposed_by_user_id');
                $table->integer('additional_months')->default(6);
                $table->decimal('additional_years', 4, 1)->nullable();
                $table->date('previous_expiry_date')->nullable();
                $table->date('new_expiry_date')->nullable();
                $table->text('extension_reason');
                $table->string('scan_extension_form')->nullable();
                $table->string('approval_status')->default('pending'); // pending, approved, rejected
                $table->unsignedBigInteger('approved_by_user_id')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->text('rejection_note')->nullable();
                $table->timestamps();

                $table->foreign('archive_id')->references('id')->on('archives')->onDelete('cascade');
                $table->foreign('proposed_by_user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('approved_by_user_id')->references('id')->on('users')->onDelete('set null');

                $table->index('archive_id', 'idx_ext_logs_archive_id');
                $table->index('approval_status', 'idx_ext_logs_status');
                $table->index('proposed_by_user_id', 'idx_ext_logs_proposed_by');
                $table->index('created_at', 'idx_ext_logs_created_at');
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
        Schema::dropIfExists('archive_extension_logs');
    }
}
