<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNumberingFormatsTable extends Migration
{
    public function up()
    {
        Schema::create('numbering_formats', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('pattern', 255);
            $table->integer('current_counter')->default(0);
            $table->integer('padding')->default(4);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('numbering_formats');
    }
}
