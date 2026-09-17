<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\SubDepartment;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with('subDepartments')
            ->withCount(['archives', 'subDepartments'])
            ->get();

        return view('master.departments', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:departments,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $validated['code'] = strtoupper($validated['code']);

        $dept = Department::create($validated);

        return redirect()->route('master.departments')
            ->with('success', "Departemen {$validated['name']} ({$validated['code']}) berhasil ditambahkan.");
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:departments,code,' . $department->id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $validated['code'] = strtoupper($validated['code']);

        $department->update($validated);

        return redirect()->route('master.departments')
            ->with('success', "Departemen {$validated['name']} berhasil diperbarui.");
    }

    public function destroy(Department $department)
    {
        if ($department->archives()->count() > 0) {
            return back()->with('error', "Departemen {$department->name} tidak dapat dihapus karena memiliki data arsip terkait.");
        }

        $department->delete();

        return redirect()->route('master.departments')
            ->with('success', "Departemen {$department->name} berhasil dihapus.");
    }

    // SubDepartment Management (Full Super Admin Control)
    public function storeSubDepartment(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'Nama subdepartemen wajib diisi.',
        ]);

        if (!empty($validated['code'])) {
            $validated['code'] = strtoupper(trim($validated['code']));
        }
        $validated['department_id'] = $department->id;
        $validated['name'] = trim($validated['name']);

        $sub = SubDepartment::create($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'sub_department' => $sub,
                'message' => "Subdepartemen {$sub->name} berhasil ditambahkan ke departemen {$department->code}.",
            ]);
        }

        return redirect()->route('master.departments')
            ->with('success', "Subdepartemen {$sub->name} berhasil ditambahkan ke departemen {$department->code}.");
    }

    public function updateSubDepartment(Request $request, SubDepartment $subDepartment)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        if (!empty($validated['code'])) {
            $validated['code'] = strtoupper(trim($validated['code']));
        }
        $validated['name'] = trim($validated['name']);

        $subDepartment->update($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'sub_department' => $subDepartment,
                'message' => "Subdepartemen {$subDepartment->name} berhasil diperbarui.",
            ]);
        }

        return redirect()->route('master.departments')
            ->with('success', "Subdepartemen {$subDepartment->name} berhasil diperbarui.");
    }

    public function destroySubDepartment(SubDepartment $subDepartment)
    {
        $inUse = $subDepartment->archives()->count();
        if ($inUse > 0) {
            return back()->with('error', "Subdepartemen {$subDepartment->name} tidak dapat dihapus karena tercatat pada {$inUse} berkas arsip.");
        }

        $deptCode = $subDepartment->department ? $subDepartment->department->code : '';
        $name = $subDepartment->name;
        $subDepartment->delete();

        return redirect()->route('master.departments')
            ->with('success', "Subdepartemen {$name} dari departemen {$deptCode} berhasil dihapus.");
    }

    public function apiSubDepartments(Department $department)
    {
        return response()->json([
            'success' => true,
            'department' => $department,
            'sub_departments' => $department->subDepartments()->orderBy('name')->get(),
        ]);
    }
}