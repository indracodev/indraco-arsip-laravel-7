<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_events', function (Blueprint $table) {
            $table->id(); // Monotonic incremental sequence number
            $table->string('event_type', 50); // e.g. archive_created, status_changed, borrowing_updated
            $table->string('entity_type', 50)->nullable(); // e.g. Archive, BorrowingLog
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->text('payload')->nullable(); // JSON metadata
            $table->timestamp('created_at')->useCurrent();

            $table->index(['id', 'department_id'], 'idx_events_seq_dept');
            $table->index('created_at', 'idx_events_created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_events');
    }
}
