<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Master data for a NEW database only (the launcher runs `migrate --seed` only when it has
 * just created database.sqlite). Ported from Laravel 7 database/seeds without its sample
 * archives, borrowings, destructions and warehouse entries (test data).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->call([
                DepartmentSeeder::class,
                SubDepartmentSeeder::class,
                MasterArchiveSeeder::class,
                UserSeeder::class,
                WarehouseLayoutSeeder::class,
            ]);

            $now = now();
            $stamps = ['created_at' => $now, 'updated_at' => $now];

            // Laravel 7 seeded '{COMPANY}/{DEPT}/{YEAR}/{ROMAN_MONTH}/{COUNTER}' with counter 5
            // (after its 4 sample boxes); production uses the pattern below. No boxes yet: 0.
            DB::table('numbering_formats')->insert([
                'name' => 'Format Custom Default Box Arsip Indraco',
                'pattern' => '{COMPANY}/{DEPT}/{YEAR}/{COUNTER}',
                'current_counter' => 0,
                'padding' => 4,
                'is_active' => true,
            ] + $stamps);

            // Same keys and values as the Laravel 7 defaults (AppSetting::get fallbacks).
            DB::table('app_settings')->insert(array_map(fn (array $s): array => [
                'key' => $s[0], 'value' => $s[1], 'group' => $s[2], 'type' => $s[3], 'description' => $s[4],
            ] + $stamps, [
                ['app_logo', 'images/logo-indraco.png', 'appearance', 'image', 'Logo utama aplikasi DMS Indraco'],
                ['app_font_size', '14px', 'appearance', 'string', 'Ukuran font global antarmuka'],
                ['app_name', 'DMS PT INDRACO', 'general', 'string', 'Nama institusi / sistem aplikasi'],
                ['ping_alert_enabled', '0', 'system', 'boolean', 'Status aktifkan notifikasi peringatan latensi LAN'],
                ['ping_alert_threshold_ms', '500', 'system', 'integer', 'Ambang batas latensi LAN (ms) untuk memicu peringatan'],
            ]));
        });
    }
}
