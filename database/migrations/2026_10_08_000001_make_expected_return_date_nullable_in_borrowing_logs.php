<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeExpectedReturnDateNullableInBorrowingLogs extends Migration
{
    public function up()
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            DB::statement('CREATE TABLE "borrowing_logs_temp" (
                "id" integer not null primary key autoincrement,
                "archive_id" integer not null,
                "borrower_user_id" integer not null,
                "pic_gudang_id" integer null,
                "request_date" datetime not null,
                "borrow_date" datetime null,
                "expected_return_date" date null,
                "actual_return_date" datetime null,
                "purpose" text not null,
                "status" varchar not null default \'requested\',
                "notes" text null,
                "created_at" datetime null,
                "updated_at" datetime null,
                "department_approval_by" integer null,
                "department_approved_at" datetime null,
                "scan_approval_borrow" varchar null,
                "approval_file" varchar null,
                "is_approval_uploaded" tinyint(1) not null default \'0\',
                "approval_status" varchar not null default \'pending\',
                "approval_notes" text null,
                foreign key("archive_id") references "archives"("id") on delete cascade,
                foreign key("borrower_user_id") references "users"("id") on delete cascade,
                foreign key("pic_gudang_id") references "users"("id") on delete set null
            );');

            DB::statement('INSERT INTO "borrowing_logs_temp" SELECT * FROM "borrowing_logs";');
            DB::statement('DROP TABLE "borrowing_logs";');
            DB::statement('ALTER TABLE "borrowing_logs_temp" RENAME TO "borrowing_logs";');
            DB::statement('PRAGMA foreign_keys = ON;');
        }
    }

    public function down()
    {
        // Reversible if needed
    }
}
