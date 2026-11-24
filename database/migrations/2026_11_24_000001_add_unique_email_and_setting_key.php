<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * R4: Laravel 7 lost the UNIQUE indexes on users.email and app_settings.key when it rebuilt
 * those tables. Restore them on new and existing databases.
 *
 * - users.email is unique case-insensitively after trimming (same normalization as
 *   App\Models\User::normalizeEmail(), used by the model mutator and login).
 * - Existing duplicates are never cleaned up automatically (owner decision): the migration
 *   stops with the list so the owner can decide. `IF NOT EXISTS` skips an index already present.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->refuseDuplicates('users', 'lower(trim(email))');
        $this->refuseDuplicates('app_settings', '"key"');

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS "users_email_unique" ON "users" (lower(trim("email")))');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS "app_settings_key_unique" ON "app_settings" ("key")');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS "users_email_unique"');
        DB::statement('DROP INDEX IF EXISTS "app_settings_key_unique"');
    }

    private function refuseDuplicates(string $table, string $expression): void
    {
        $duplicates = DB::table($table)
            ->selectRaw("{$expression} AS value, COUNT(*) AS total")
            ->groupByRaw($expression)
            ->havingRaw('COUNT(*) > 1')
            ->pluck('value')
            ->all();

        if ($duplicates !== []) {
            throw new RuntimeException("Tidak bisa memasang UNIQUE index: nilai ganda di {$table}: "
                .implode(', ', $duplicates).'. Rapikan data dulu (keputusan pemilik), lalu jalankan migrate lagi.');
        }
    }
};
