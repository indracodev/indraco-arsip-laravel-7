<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Font size per account (decision ASK 13 / "Ukuran teks per akun"): px 10-26, NULL = not chosen
 * yet (the browser value or app_settings.app_font_size applies). A nullable column is added in
 * place (ALTER TABLE ADD COLUMN, no table rebuild), so existing Laravel 7 databases keep their
 * rows and Laravel 7 itself ignores the column.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'font_size')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unsignedTinyInteger('font_size')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'font_size')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('font_size');
            });
        }
    }
};
