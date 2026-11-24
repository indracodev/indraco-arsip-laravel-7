<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubDepartmentRequest;
use App\Models\SubDepartment;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sub-Departemen CRUD on the Master Departemen page (Laravel 7
 * DepartmentController@*SubDepartment*, same messages and activity logs). Admin only (routes).
 */
class SubDepartmentController extends Controller
{
    public function store(SubDepartmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $sub = new SubDepartment($data);
        $sub->department_id = (int) $data['department_id'];
        $sub->save();

        ActivityLogger::log('MASTER_SUBDEPT_CREATE', "Menambahkan sub-departemen baru {$sub->name} ({$sub->code})", 'master',
            $sub->only(['id', 'department_id', 'code', 'name']), (string) $sub->id);

        return redirect()->route('master.departments')->with('success', "Sub-Departemen {$sub->name} ({$sub->code}) berhasil ditambahkan.");
    }

    public function update(SubDepartmentRequest $request, SubDepartment $subDepartment): RedirectResponse
    {
        $data = $request->validated();
        $old = $subDepartment->only(['department_id', 'code', 'name']);
        $subDepartment->fill($data);
        $subDepartment->department_id = (int) $data['department_id'];
        $subDepartment->save();

        ActivityLogger::log('MASTER_SUBDEPT_UPDATE', "Memperbarui sub-departemen {$subDepartment->name} ({$subDepartment->code})", 'master',
            ['id' => $subDepartment->id, 'old' => $old, 'new' => $data], (string) $subDepartment->id);

        return redirect()->route('master.departments')->with('success', "Sub-Departemen {$subDepartment->name} berhasil diperbarui.");
    }

    public function destroy(SubDepartment $subDepartment): RedirectResponse
    {
        if ($subDepartment->archives()->exists()) {
            return back()->with('error', "Sub-Departemen {$subDepartment->name} tidak dapat dihapus karena memiliki data arsip terkait.");
        }
        $logged = $subDepartment->only(['id', 'code', 'name']);
        $subDepartment->delete();

        ActivityLogger::log('MASTER_SUBDEPT_DELETE', "Menghapus sub-departemen {$logged['name']} ({$logged['code']})", 'master', $logged, (string) $logged['id']);

        return redirect()->route('master.departments')->with('success', "Sub-Departemen {$logged['name']} berhasil dihapus.");
    }

    public function toggleActive(Request $request, SubDepartment $subDepartment): JsonResponse|RedirectResponse
    {
        $subDepartment->forceFill(['is_active' => ! $subDepartment->is_active])->save();
        $status = $subDepartment->is_active ? 'diaktifkan' : 'dinonaktifkan';

        ActivityLogger::log('MASTER_SUBDEPT_TOGGLE_ACTIVE', "Sub-Departemen {$subDepartment->name} ({$subDepartment->code}) {$status}", 'master',
            $subDepartment->only(['id', 'code', 'name', 'is_active']), (string) $subDepartment->id);

        return DepartmentController::toggled($request, "Sub-Departemen {$subDepartment->name} berhasil {$status}.", $subDepartment->is_active);
    }
}
