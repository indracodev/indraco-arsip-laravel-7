<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateApprovalFieldsInTransactionLogs extends Migration
{
    public function up()
    {
        Schema::table('borrowing_logs', function (Blueprint $table) {
            $table->string('approval_file', 255)->nullable()->after('scan_approval_borrow');
            $table->boolean('is_approval_uploaded')->default(false)->after('approval_file');
            $table->string('approval_status', 50)->default('pending')->after('is_approval_uploaded'); // 'pending', 'approved', 'rejected'
            $table->text('approval_notes')->nullable()->after('approval_status');
        });

        Schema::table('destruction_logs', function (Blueprint $table) {
            $table->string('approval_file', 255)->nullable()->after('scan_approval_destruction');
            $table->boolean('is_approval_uploaded')->default(false)->after('approval_file');
            $table->string('approval_status', 50)->default('pending')->after('is_approval_uploaded'); // 'pending', 'approved', 'rejected'
            $table->text('approval_notes')->nullable()->after('approval_status');
        });
    }

    public function down()
    {
        Schema::table('borrowing_logs', function (Blueprint $table) {
            $table->dropColumn([
                'approval_file',
                'is_approval_uploaded',
                'approval_status',
                'approval_notes',
            ]);
        });

        Schema::table('destruction_logs', function (Blueprint $table) {
            $table->dropColumn([
                'approval_file',
                'is_approval_uploaded',
                'approval_status',
                'approval_notes',
            ]);
        });
    }
}
