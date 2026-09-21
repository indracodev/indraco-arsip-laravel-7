<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModifyDocumentTypeColumnToText extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Use raw SQL to modify column type to TEXT safely without requiring doctrine/dbal on MySQL/MariaDB
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE archives MODIFY COLUMN document_type TEXT NULL");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE archives MODIFY COLUMN document_type VARCHAR(100) NULL");
        }
    }
}
