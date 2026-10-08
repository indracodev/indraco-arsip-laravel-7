<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddTakenToArchivesStatusEnum extends Migration
{
    public function up()
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
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
                "status" varchar check ("status" in (\'draft\', \'pending_verification\', \'approved_booked\', \'in_warehouse\', \'borrowed\', \'taken\', \'pending_destruction\', \'destroyed\')) not null default \'draft\', 
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
                foreign key("department_id") references "departments"("id") on delete cascade, 
                foreign key("created_by_user_id") references "users"("id") on delete cascade, 
                foreign key("warehouse_location_id") references "warehouse_locations"("id") on delete set null
            );');

            DB::statement('INSERT INTO "archives_temp" SELECT * FROM "archives";');
            DB::statement('DROP TABLE "archives";');
            DB::statement('ALTER TABLE "archives_temp" RENAME TO "archives";');
            DB::statement('PRAGMA foreign_keys = ON;');
        }
    }

    public function down()
    {
        // Reversible if needed
    }
}
