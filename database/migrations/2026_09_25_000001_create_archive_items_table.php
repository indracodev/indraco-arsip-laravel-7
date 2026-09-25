<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArchiveItemsTable extends Migration
{
    public function up()
    {
        Schema::create('archive_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_id')->constrained('archives')->cascadeOnDelete();
            $table->unsignedInteger('item_number')->default(1);
            $table->string('document_name', 255);
            $table->string('period_start', 50)->nullable();
            $table->string('period_end', 50)->nullable();
            $table->string('period_text', 150)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['archive_id', 'item_number']);
            $table->index('document_name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('archive_items');
    }
}
