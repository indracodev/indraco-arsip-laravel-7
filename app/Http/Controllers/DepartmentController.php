<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\SubDepartment;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with(['subDepartments' => function ($q) {
            $q->withCount('archives')->orderBy('code', 'asc');
        }])->withCount(['archives', 'subDepartments'])->orderBy('code', 'asc')->get();

        return view('master.departments', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sidar_id' => 'nullable|string|max:50',
            'code' => 'required|string|max:10|unique:departments,code',
            'name' => 'required|string|max:100',
            'retention_years' => 'nullable|integer|min:1|max:100',
            'description' => 'nullable|string',
        ]);

        $validated['code'] = strtoupper($validated['code']);
        if (empty($validated['retention_years'])) {
            $validated['retention_years'] = 5;
        }

        Department::create($validated);

        return redirect()->route('master.departments')
            ->with('success', "Departemen {$validated['name']} ({$validated['code']}) berhasil ditambahkan.");
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'sidar_id' => 'nullable|string|max:50',
            'code' => 'required|string|max:10|unique:departments,code,' . $department->id,
            'name' => 'required|string|max:100',
            'retention_years' => 'nullable|integer|min:1|max:100',
            'description' => 'nullable|string',
        ]);

        $validated['code'] = strtoupper($validated['code']);
        if (empty($validated['retention_years'])) {
            $validated['retention_years'] = 5;
        }

        $department->update($validated);

        return redirect()->route('master.departments')
            ->with('success', "Departemen {$validated['name']} berhasil diperbarui.");
    }

    public function destroy(Department $department)
    {
        if ($department->archives()->count() > 0) {
            return back()->with('error', "Departemen {$department->name} tidak dapat dihapus karena memiliki data arsip terkait.");
        }

        if ($department->subDepartments()->count() > 0) {
            foreach ($department->subDepartments as $sub) {
                if ($sub->archives()->count() > 0) {
                    return back()->with('error', "Departemen {$department->name} tidak dapat dihapus karena salah satu sub-departemennya memiliki data arsip.");
                }
            }
        }

        $department->delete();

        return redirect()->route('master.departments')
            ->with('success', "Departemen {$department->name} berhasil dihapus.");
    }

    public function storeSubDepartment(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'sidar_id' => 'nullable|string|max:50',
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:100',
            'retention_years' => 'nullable|integer|min:1|max:100',
            'description' => 'nullable|string',
        ]);

        $validated['code'] = strtoupper($validated['code']);

        $exists = SubDepartment::where('department_id', $validated['department_id'])
            ->where('code', $validated['code'])
            ->exists();

        if ($exists) {
            return back()->with('error', "Kode sub-departemen {$validated['code']} sudah digunakan pada departemen ini.");
        }

        SubDepartment::create($validated);

        return redirect()->route('master.departments')
            ->with('success', "Sub-Departemen {$validated['name']} ({$validated['code']}) berhasil ditambahkan.");
    }

    public function updateSubDepartment(Request $request, SubDepartment $subDepartment)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'sidar_id' => 'nullable|string|max:50',
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:100',
            'retention_years' => 'nullable|integer|min:1|max:100',
            'description' => 'nullable|string',
        ]);

        $validated['code'] = strtoupper($validated['code']);

        $exists = SubDepartment::where('department_id', $validated['department_id'])
            ->where('code', $validated['code'])
            ->where('id', '!=', $subDepartment->id)
            ->exists();

        if ($exists) {
            return back()->with('error', "Kode sub-departemen {$validated['code']} sudah digunakan pada departemen ini.");
        }

        $subDepartment->update($validated);

        return redirect()->route('master.departments')
            ->with('success', "Sub-Departemen {$validated['name']} berhasil diperbarui.");
    }

    public function destroySubDepartment(SubDepartment $subDepartment)
    {
        if ($subDepartment->archives()->count() > 0) {
            return back()->with('error', "Sub-Departemen {$subDepartment->name} tidak dapat dihapus karena memiliki data arsip terkait.");
        }

        $subDepartment->delete();

        return redirect()->route('master.departments')
            ->with('success', "Sub-Departemen {$subDepartment->name} berhasil dihapus.");
    }
}