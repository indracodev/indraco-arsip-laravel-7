<?php

namespace App\Actions\Master;

use App\Models\Department;
use App\Models\User;
use App\Services\ActivityLogger;
use DomainException;

/**
 * Kelola Departemen > User PIC (Laravel 7 DepartmentController@assignPic / @removePic):
 * assign an existing user or create a new one as PIC Departemen of the department, or release
 * a PIC. An admin account cannot be assigned (Laravel 7 only hid admins from the list, the
 * server would have demoted an admin - even the one doing it - to PIC Departemen).
 */
class AssignDepartmentPic
{
    /** @return string success message */
    public function assign(Department $department, User $user): string
    {
        if ($user->isAdmin()) {
            throw new DomainException('Akun Super Admin tidak dapat ditugaskan sebagai PIC Departemen.');
        }
        $user->forceFill(['department_id' => $department->id, 'role' => User::ROLE_PIC_DEPT])->save();
        $this->log('MASTER_DEPT_PIC_ASSIGN', "Menugaskan user '{$user->name}' sebagai PIC Departemen {$department->name} ({$department->code}).", $department, $user);

        return "User {$user->name} berhasil ditugaskan sebagai PIC Departemen {$department->name}.";
    }

    /**
     * @param  array{name: string, email: string, password: string, phone?: string|null}  $data
     * @return string success message
     */
    public function create(Department $department, array $data): string
    {
        $user = new User($data);
        $user->forceFill(['department_id' => $department->id, 'role' => User::ROLE_PIC_DEPT])->save();
        $this->log('MASTER_DEPT_PIC_CREATE', "Membuat user baru '{$user->name}' sebagai PIC Departemen {$department->name} ({$department->code}).", $department, $user);

        return "User PIC baru {$user->name} berhasil dibuat dan ditugaskan ke {$department->name}.";
    }

    /** @return string success message */
    public function remove(Department $department, User $user): string
    {
        if ((int) $user->department_id !== $department->id) {
            throw new DomainException('User tidak terdaftar pada departemen ini.');
        }
        $user->forceFill(['department_id' => null])->save();
        $this->log('MASTER_DEPT_PIC_REMOVE', "Melepaskan penugasan PIC '{$user->name}' dari Departemen {$department->name}.", $department, $user);

        return "Penugasan PIC {$user->name} dari {$department->name} berhasil dilepaskan.";
    }

    private function log(string $action, string $description, Department $department, User $user): void
    {
        ActivityLogger::log($action, $description, 'MASTER_DEPT', ['department_id' => $department->id, 'user_id' => $user->id, 'user_name' => $user->name], $department->name);
    }
}
