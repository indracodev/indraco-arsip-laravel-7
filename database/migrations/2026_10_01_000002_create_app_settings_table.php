<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAppSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('group', 50)->default('general');
            $table->string('type', 20)->default('string');
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        // Seed default initial settings
        $now = now();
        DB::table('app_settings')->insert([
            [
                'key' => 'app_logo',
                'value' => 'images/logo-indraco.png',
                'group' => 'appearance',
                'type' => 'image',
                'description' => 'Logo utama aplikasi DMS Indraco',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'app_font_size',
                'value' => '19px',
                'group' => 'appearance',
                'type' => 'string',
                'description' => 'Ukuran basis font scaling global antarmuka aplikasi',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'app_name',
                'value' => 'DMS PT INDRACO',
                'group' => 'general',
                'type' => 'string',
                'description' => 'Nama institusi / sistem aplikasi',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_settings');
    }
}
