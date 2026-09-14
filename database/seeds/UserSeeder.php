<?php

// Seeder generated from c:/laragon/www/#Project2026/indracocoffee-v2-laravel-7/db/db-indracocoffee-v2-laravel-10.sql for users

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->truncate();

        DB::unprepared(<<<'SQL'
INSERT INTO `users` VALUES (1, 'Administrator Indraco', 'admin@indraco.com', '2026-07-30 02:04:35', '$2y$10$zR.ETPGHJzX4LlbD6T8QCeB..Dwr2/zdiv7vtul.a8kJ0H2kez3My', NULL, '2026-07-30 02:02:31', '2026-07-30 02:04:35');
SQL
        );

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
