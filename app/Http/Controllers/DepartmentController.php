<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount('archives')->get();
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

        Department::create($validated);

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
}