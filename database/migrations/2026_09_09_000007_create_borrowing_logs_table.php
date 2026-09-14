<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBorrowingLogsTable extends Migration
{
    public function up()
    {
        Schema::create('borrowing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_id')->constrained('archives')->cascadeOnDelete();
            $table->foreignId('borrower_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pic_gudang_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('request_date');
            $table->dateTime('borrow_date')->nullable();
            $table->date('expected_return_date');
            $table->dateTime('actual_return_date')->nullable();
            $table->text('purpose');
            $table->string('status', 50)->default('requested');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('borrowing_logs');
    }
}
