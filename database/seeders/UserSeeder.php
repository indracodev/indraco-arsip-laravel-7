<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Final state of Laravel 7 DatabaseSeeder (admin + PIC Gudang) + DepartmentPicUserSeeder
 * (one PIC per department: <code>@indraco.com, "PIC_<name>"). Password = the Laravel 7
 * default (owner decision: kept, no forced change). Hashed once for all rows.
 */
class UserSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'password';

    public function run(): void
    {
        $password = Hash::make(self::DEFAULT_PASSWORD);
        $now = now();
        $base = ['password' => $password, 'created_at' => $now, 'updated_at' => $now];

        $rows = [
            ['name' => 'Super Admin (Webdev)', 'email' => 'admin@indraco.com', 'role' => 'admin', 'department_id' => null, 'phone' => '081234567890'] + $base,
            ['name' => 'Budi Warehouse Specialist', 'email' => 'gudang@indraco.com', 'role' => 'pic_gudang', 'department_id' => null, 'phone' => '081234567891'] + $base,
        ];

        $departments = DB::table('departments')->orderBy('id')->get(['id', 'code', 'name']);
        foreach ($departments as $index => $department) {
            $rows[] = [
                'name' => 'PIC_'.$department->name,
                'email' => strtolower($department->code).'@indraco.com',
                'role' => 'pic_dept',
                'department_id' => $department->id,
                'phone' => '08123456'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
            ] + $base;
        }

        DB::table('users')->insert($rows);
    }
}
