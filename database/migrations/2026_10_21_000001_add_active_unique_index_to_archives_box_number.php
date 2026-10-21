<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddActiveUniqueIndexToArchivesBoxNumber extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS "idx_archives_box_number_active";');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS "idx_archives_box_number_active" ON "archives" ("box_number") WHERE "box_number" IS NOT NULL AND "box_number" != \'\' AND "status" NOT IN (\'taken\', \'destroyed\', \'borrowed\');');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS "idx_archives_box_number_active";');
        }
    }
}
