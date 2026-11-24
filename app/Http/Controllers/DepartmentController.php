<?php

namespace App\Http\Controllers;

use App\Actions\Master\DeleteDepartment;
use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Services\ActivityLogger;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Master Departemen & Sub-Departemen page and department CRUD (Laravel 7 DepartmentController,
 * same messages and activity logs). Admin only (routes). Sub-departments: SubDepartmentController;
 * PIC: DepartmentPicController; Master Berkas: MasterArchiveController; JSON: DepartmentApiController.
 */
class DepartmentController extends Controller
{
    public function index(): View
    {
        $departments = Department::query()
            ->with(['subDepartments' => fn ($q) => $q->withCount('archives')->orderBy('code')])
            ->withCount(['archives', 'archives as unassigned_archives_count' => fn ($q) => $q->whereNull('sub_department_id')])
            ->orderBy('code')->get()
            ->map(fn (Department $d): array => $d->only(['id', 'code', 'name', 'description', 'retention_years', 'is_active', 'archives_count', 'unassigned_archives_count']) + [
                'sub_departments' => $d->subDepartments->map(fn (SubDepartment $s): array => $s->only(['id', 'department_id', 'code', 'name', 'description', 'retention_years', 'is_active', 'archives_count']))->all(),
            ])->all();

        return view('master.departments.index', ['departments' => $departments]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['retention_years'] = ($data['retention_years'] ?? null) ?: 5;
        $department = Department::create($data);

        ActivityLogger::log('MASTER_DEPT_CREATE', "Menambahkan departemen baru {$department->name} ({$department->code})", 'master',
            $department->only(['id', 'code', 'name', 'retention_years']), (string) $department->id);

        return redirect()->route('master.departments')->with('success', "Departemen {$department->name} ({$department->code}) berhasil ditambahkan.");
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $data = $request->validated();
        // The edit form has no retention field: Laravel 7 reset every edited department to 5 years.
        if (array_key_exists('retention_years', $data)) {
            $data['retention_years'] = $data['retention_years'] ?: 5;
        }
        $old = $department->only(['code', 'name', 'retention_years']);
        $department->update($data);

        ActivityLogger::log('MASTER_DEPT_UPDATE', "Memperbarui departemen {$department->name} ({$department->code})", 'master',
            ['id' => $department->id, 'old' => $old, 'new' => $data], (string) $department->id);

        return redirect()->route('master.departments')->with('success', "Departemen {$department->name} berhasil diperbarui.");
    }

    public function destroy(Department $department, DeleteDepartment $action): RedirectResponse
    {
        try {
            return redirect()->route('master.departments')->with('success', $action->handle($department));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function toggleActive(Request $request, Department $department): JsonResponse|RedirectResponse
    {
        $department->forceFill(['is_active' => ! $department->is_active])->save();
        $status = $department->is_active ? 'diaktifkan' : 'dinonaktifkan';

        ActivityLogger::log('MASTER_DEPT_TOGGLE_ACTIVE', "Departemen {$department->name} ({$department->code}) {$status}", 'master',
            $department->only(['id', 'code', 'name', 'is_active']), (string) $department->id);

        return self::toggled($request, "Departemen {$department->name} berhasil {$status}.", $department->is_active);
    }

    /** Laravel 7 answered the page's fetch() with JSON and a plain form with a redirect. */
    public static function toggled(Request $request, string $message, bool $active): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'is_active' => $active]);
        }

        return redirect()->route('master.departments')->with('success', $message);
    }
}
