<?php

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DepartmentPicUserSeeder extends Seeder
{
    /**
     * Run database seeds for PIC Departemen of each department.
     */
    public function run()
    {
        $departments = Department::all();

        foreach ($departments as $index => $dept) {
            $code = strtoupper($dept->code);
            $picName = 'PIC_' . $dept->name;
            $email = strtolower($dept->code) . '@indraco.com';
            $phone = '08123456' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);

            // Check if user with this email or for this department already exists
            $existingUser = User::where('email', $email)
                ->orWhere(function ($q) use ($dept) {
                    $q->where('department_id', $dept->id)->where('role', 'pic_dept');
                })->first();

            if ($existingUser) {
                $existingUser->update([
                    'name' => $picName,
                    'email' => $email,
                    'department_id' => $dept->id,
                    'role' => 'pic_dept',
                    'phone' => $phone,
                ]);
            } else {
                User::create([
                    'name' => $picName,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'department_id' => $dept->id,
                    'role' => 'pic_dept',
                    'phone' => $phone,
                ]);
            }
        }
    }
}
