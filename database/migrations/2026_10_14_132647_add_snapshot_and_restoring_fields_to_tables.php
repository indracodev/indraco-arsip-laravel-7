<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSnapshotAndRestoringFieldsToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('borrowing_logs', function (Blueprint $table) {
            $table->string('snapshot_box_number', 100)->nullable()->after('archive_id');
            $table->string('snapshot_title', 255)->nullable()->after('snapshot_box_number');
            $table->string('snapshot_department', 100)->nullable()->after('snapshot_title');
            $table->longText('snapshot_items')->nullable()->after('snapshot_department');
            $table->foreignId('active_restore_archive_id')->nullable()->after('notes')->constrained('archives')->nullOnDelete();
        });

        Schema::table('archives', function (Blueprint $table) {
            $table->foreignId('origin_borrowing_log_id')->nullable()->after('status')->constrained('borrowing_logs')->nullOnDelete();
            $table->longText('re_store_snapshot')->nullable()->after('origin_borrowing_log_id');
        });
    }

    public function down()
    {
        Schema::table('archives', function (Blueprint $table) {
            $table->dropForeign(['origin_borrowing_log_id']);
            $table->dropColumn(['origin_borrowing_log_id', 're_store_snapshot']);
        });

        Schema::table('borrowing_logs', function (Blueprint $table) {
            $table->dropForeign(['active_restore_archive_id']);
            $table->dropColumn([
                'snapshot_box_number',
                'snapshot_title',
                'snapshot_department',
                'snapshot_items',
                'active_restore_archive_id',
            ]);
        });
    }
}
