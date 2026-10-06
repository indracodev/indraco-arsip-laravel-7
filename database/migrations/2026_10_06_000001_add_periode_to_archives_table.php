<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPeriodeToArchivesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('archives', function (Blueprint $table) {
            if (!Schema::hasColumn('archives', 'periode')) {
                $table->string('periode', 100)->nullable()->after('title');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('archives', function (Blueprint $table) {
            if (Schema::hasColumn('archives', 'periode')) {
                $table->dropColumn('periode');
            }
        });
    }
}
