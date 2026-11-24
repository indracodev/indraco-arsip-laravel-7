<?php

namespace App\Actions\Master;

use App\Models\Archive;
use App\Models\Department;
use App\Models\User;
use App\Services\ActivityLogger;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Hapus Departemen (Laravel 7 DepartmentController@destroy): refused while the department or one
 * of its sub-departments has boxes. Sub-departments and Master Berkas go with it (FK cascade);
 * users of the department are detached (Laravel 7 left them pointing at a deleted id).
 */
class DeleteDepartment
{
    /** @return string success message */
    public function handle(Department $department): string
    {
        $name = $department->name;
        $code = $department->code;
        $id = $department->id;

        DB::transaction(function () use ($department, $name): void {
            if ($department->archives()->exists()) {
                throw new DomainException("Departemen {$name} tidak dapat dihapus karena memiliki data arsip terkait.");
            }
            if (Archive::query()->whereIn('sub_department_id', $department->subDepartments()->select('id'))->exists()) {
                throw new DomainException("Departemen {$name} tidak dapat dihapus karena salah satu sub-departemennya memiliki data arsip.");
            }
            User::query()->where('department_id', $department->id)->update(['department_id' => null]);
            $department->delete();
        });

        ActivityLogger::log('MASTER_DEPT_DELETE', "Menghapus departemen {$name} ({$code})", 'master', ['id' => $id, 'code' => $code, 'name' => $name], (string) $id);

        return "Departemen {$name} berhasil dihapus.";
    }
}
