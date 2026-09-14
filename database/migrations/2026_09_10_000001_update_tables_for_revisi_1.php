<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTablesForRevisi1 extends Migration
{
    public function up()
    {
        Schema::table('archives', function (Blueprint $table) {
            $table->string('company_name', 150)->nullable()->after('department_id');
            $table->string('document_type', 100)->nullable()->after('company_name');
            $table->string('period_yy_mm', 7)->nullable()->after('period_end_date');
            $table->string('scan_input_form', 255)->nullable()->after('file_path');
            $table->string('scan_approval_input', 255)->nullable()->after('scan_input_form');
            $table->text('extension_reason')->nullable()->after('rejection_note');
            $table->string('scan_extension_form', 255)->nullable()->after('extension_reason');
        });

        Schema::table('borrowing_logs', function (Blueprint $table) {
            $table->foreignId('department_approval_by')->nullable()->after('borrower_user_id')->constrained('users')->nullOnDelete();
            $table->dateTime('department_approved_at')->nullable()->after('department_approval_by');
            $table->string('scan_approval_borrow', 255)->nullable()->after('notes');
        });

        Schema::table('destruction_logs', function (Blueprint $table) {
            $table->foreignId('department_approval_by')->nullable()->after('proposed_by_user_id')->constrained('users')->nullOnDelete();
            $table->dateTime('department_approved_at')->nullable()->after('department_approval_by');
            $table->string('scan_approval_destruction', 255)->nullable()->after('certificate_file');
            $table->text('extension_reason')->nullable()->after('notes');
            $table->string('scan_extension_form', 255)->nullable()->after('extension_reason');
        });
    }

    public function down()
    {
        Schema::table('archives', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'document_type',
                'period_yy_mm',
                'scan_input_form',
                'scan_approval_input',
                'extension_reason',
                'scan_extension_form',
            ]);
        });

        Schema::table('borrowing_logs', function (Blueprint $table) {
            $table->dropForeign(['department_approval_by']);
            $table->dropColumn([
                'department_approval_by',
                'department_approved_at',
                'scan_approval_borrow',
            ]);
        });

        Schema::table('destruction_logs', function (Blueprint $table) {
            $table->dropForeign(['department_approval_by']);
            $table->dropColumn([
                'department_approval_by',
                'department_approved_at',
                'scan_approval_destruction',
                'extension_reason',
                'scan_extension_form',
            ]);
        });
    }
}
