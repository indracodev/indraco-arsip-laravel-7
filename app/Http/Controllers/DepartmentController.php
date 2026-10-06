<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\MasterArchive;
use App\Models\SubDepartment;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DepartmentController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isSuperAdmin = $user && $user->isSuperAdmin();

        $query = Department::with(['subDepartments' => function ($q) use ($isSuperAdmin) {
            if (!$isSuperAdmin) {
                $q->where('is_active', true);
            }
            $q->withCount('archives')->orderBy('code', 'asc');
        }, 'picUsers', 'masterArchives.subDepartment'])
        ->withCount(['archives', 'subDepartments', 'masterArchives', 'picUsers'])
        ->withCount(['archives as unassigned_archives_count' => function ($q) {
            $q->whereNull('sub_department_id');
        }])
        ->orderBy('code', 'asc');

        if (!$isSuperAdmin) {
            $query->where('is_active', true);
        }

        $departments = $query->get();
        $allUsers = User::orderBy('name', 'asc')->get();

        return view('master.departments', compact('departments', 'allUsers'));
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

        $dept = Department::create($validated);

        ActivityLogger::log('MASTER_DEPT_CREATE', "Menambahkan departemen baru {$dept->name} ({$dept->code})", 'master', [
            'id' => $dept->id,
            'code' => $dept->code,
            'name' => $dept->name,
            'retention_years' => $dept->retention_years,
        ], $dept->id);

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

        $oldData = $department->only(['code', 'name', 'retention_years']);
        $department->update($validated);

        ActivityLogger::log('MASTER_DEPT_UPDATE', "Memperbarui departemen {$department->name} ({$department->code})", 'master', [
            'id' => $department->id,
            'old' => $oldData,
            'new' => $validated,
        ], $department->id);

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

        $name = $department->name;
        $code = $department->code;
        $id = $department->id;
        $department->delete();

        ActivityLogger::log('MASTER_DEPT_DELETE', "Menghapus departemen {$name} ({$code})", 'master', [
            'id' => $id,
            'code' => $code,
            'name' => $name,
        ], $id);

        return redirect()->route('master.departments')
            ->with('success', "Departemen {$name} berhasil dihapus.");
    }

    public function toggleActive(Request $request, Department $department)
    {
        $department->is_active = !$department->is_active;
        $department->save();

        $statusText = $department->is_active ? 'diaktifkan' : 'dinonaktifkan';

        ActivityLogger::log('MASTER_DEPT_TOGGLE_ACTIVE', "Departemen {$department->name} ({$department->code}) {$statusText}", 'master', [
            'id' => $department->id,
            'code' => $department->code,
            'name' => $department->name,
            'is_active' => (bool)$department->is_active,
        ], $department->id);

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Departemen {$department->name} berhasil {$statusText}.",
                'is_active' => (bool)$department->is_active,
            ]);
        }

        return redirect()->route('master.departments')
            ->with('success', "Departemen {$department->name} berhasil {$statusText}.");
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

        $sub = SubDepartment::create($validated);

        ActivityLogger::log('MASTER_SUBDEPT_CREATE', "Menambahkan sub-departemen baru {$sub->name} ({$sub->code})", 'master', [
            'id' => $sub->id,
            'department_id' => $sub->department_id,
            'code' => $sub->code,
            'name' => $sub->name,
        ], $sub->id);

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

        $oldData = $subDepartment->only(['department_id', 'code', 'name']);
        $subDepartment->update($validated);

        ActivityLogger::log('MASTER_SUBDEPT_UPDATE', "Memperbarui sub-departemen {$subDepartment->name} ({$subDepartment->code})", 'master', [
            'id' => $subDepartment->id,
            'old' => $oldData,
            'new' => $validated,
        ], $subDepartment->id);

        return redirect()->route('master.departments')
            ->with('success', "Sub-Departemen {$validated['name']} berhasil diperbarui.");
    }

    public function destroySubDepartment(SubDepartment $subDepartment)
    {
        if ($subDepartment->archives()->count() > 0) {
            return back()->with('error', "Sub-Departemen {$subDepartment->name} tidak dapat dihapus karena memiliki data arsip terkait.");
        }

        $name = $subDepartment->name;
        $code = $subDepartment->code;
        $id = $subDepartment->id;
        $subDepartment->delete();

        ActivityLogger::log('MASTER_SUBDEPT_DELETE', "Menghapus sub-departemen {$name} ({$code})", 'master', [
            'id' => $id,
            'code' => $code,
            'name' => $name,
        ], $id);

        return redirect()->route('master.departments')
            ->with('success', "Sub-Departemen {$name} berhasil dihapus.");
    }

    public function toggleSubDepartmentActive(Request $request, SubDepartment $subDepartment)
    {
        $subDepartment->is_active = !$subDepartment->is_active;
        $subDepartment->save();

        $statusText = $subDepartment->is_active ? 'diaktifkan' : 'dinonaktifkan';

        ActivityLogger::log('MASTER_SUBDEPT_TOGGLE_ACTIVE', "Sub-Departemen {$subDepartment->name} ({$subDepartment->code}) {$statusText}", 'master', [
            'id' => $subDepartment->id,
            'code' => $subDepartment->code,
            'name' => $subDepartment->name,
            'is_active' => (bool)$subDepartment->is_active,
        ], $subDepartment->id);

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Sub-Departemen {$subDepartment->name} berhasil {$statusText}.",
                'is_active' => (bool)$subDepartment->is_active,
            ]);
        }

        return redirect()->route('master.departments')
            ->with('success', "Sub-Departemen {$subDepartment->name} berhasil {$statusText}.");
    }

    public function apiGetDepartmentArchives(Request $request, Department $department)
    {
        $query = $department->archives()
            ->with(['subDepartment', 'items', 'location.warehouse', 'rackSlot', 'creator']);

        if ($request->get('filter') === 'unassigned') {
            $query->whereNull('sub_department_id');
        }

        $archives = $query->latest()
            ->get()
            ->map(function ($archive) {
                return [
                    'id' => $archive->id,
                    'box_number' => $archive->box_number ?? 'Penomoran Pending',
                    'title' => $archive->effective_title,
                    'sub_department' => $archive->subDepartment ? $archive->subDepartment->name : 'Induk / Umum',
                    'sub_department_code' => $archive->subDepartment ? $archive->subDepartment->code : '-',
                    'periode_doc' => $archive->periode_doc ?? $archive->period_text ?? '-',
                    'tgl_penyerahan' => $archive->tgl_penyerahan ? $archive->tgl_penyerahan->format('d/m/Y') : '-',
                    'physical_condition' => $archive->physical_condition ?? 'Baik',
                    'location' => $archive->display_location,
                    'rack_code' => $archive->location ? $archive->location->rack_code : '-',
                    'slot_code' => $archive->rackSlot ? $archive->rackSlot->slot_code : '-',
                    'status' => $archive->status,
                    'status_label' => $archive->status_label,
                    'status_badge' => $archive->status_badge,
                    'items_count' => $archive->items->count(),
                    'items' => $archive->items->map(function ($it) {
                        return [
                            'item_number' => $it->item_number,
                            'document_name' => $it->document_name,
                            'period_text' => $it->period_text ?? '-',
                            'notes' => $it->notes ?? '',
                        ];
                    }),
                    'url' => route('archives.show', $archive->id),
                ];
            });

        return response()->json([
            'department' => [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
                'total_box' => $archives->count(),
                'total_items' => $archives->sum('items_count'),
                'filter' => $request->get('filter'),
            ],
            'archives' => $archives,
        ]);
    }

    public function apiGetSubDepartmentArchives(SubDepartment $subDepartment)
    {
        $archives = $subDepartment->archives()
            ->with(['department', 'items', 'location.warehouse', 'rackSlot', 'creator'])
            ->latest()
            ->get()
            ->map(function ($archive) use ($subDepartment) {
                return [
                    'id' => $archive->id,
                    'box_number' => $archive->box_number ?? 'Penomoran Pending',
                    'title' => $archive->effective_title,
                    'sub_department' => $subDepartment->name,
                    'sub_department_code' => $subDepartment->code,
                    'periode_doc' => $archive->periode_doc ?? $archive->period_text ?? '-',
                    'tgl_penyerahan' => $archive->tgl_penyerahan ? $archive->tgl_penyerahan->format('d/m/Y') : '-',
                    'physical_condition' => $archive->physical_condition ?? 'Baik',
                    'location' => $archive->display_location,
                    'rack_code' => $archive->location ? $archive->location->rack_code : '-',
                    'slot_code' => $archive->rackSlot ? $archive->rackSlot->slot_code : '-',
                    'status' => $archive->status,
                    'status_label' => $archive->status_label,
                    'status_badge' => $archive->status_badge,
                    'items_count' => $archive->items->count(),
                    'items' => $archive->items->map(function ($it) {
                        return [
                            'item_number' => $it->item_number,
                            'document_name' => $it->document_name,
                            'period_text' => $it->period_text ?? '-',
                            'notes' => $it->notes ?? '',
                        ];
                    }),
                    'url' => route('archives.show', $archive->id),
                ];
            });

        return response()->json([
            'sub_department' => [
                'id' => $subDepartment->id,
                'code' => $subDepartment->code,
                'name' => $subDepartment->name,
                'department_code' => $subDepartment->department->code ?? '',
                'department_name' => $subDepartment->department->name ?? '',
                'total_box' => $archives->count(),
                'total_items' => $archives->sum('items_count'),
            ],
            'archives' => $archives,
        ]);
    }

    /**
     * API endpoint to get management data (PIC users, available users, and master archives) for a department.
     */
    public function apiGetManageData(Department $department)
    {
        $department->load([
            'subDepartments' => function ($q) {
                $q->orderBy('code', 'asc');
            },
            'masterArchives' => function ($q) {
                $q->with('subDepartment')->orderBy('name', 'asc');
            }
        ]);

        $picUsers = User::where('department_id', $department->id)
            ->where('role', 'pic_dept')
            ->orderBy('name', 'asc')
            ->get();

        $availableUsers = User::where(function ($q) use ($department) {
                $q->whereNull('department_id')
                  ->orWhere('department_id', '!=', $department->id);
            })
            ->where('role', '!=', 'admin')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'department' => [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
                'description' => $department->description,
                'retention_years' => $department->retention_years,
            ],
            'sub_departments' => $department->subDepartments,
            'pic_users' => $picUsers,
            'available_users' => $availableUsers,
            'master_archives' => $department->masterArchives,
        ]);
    }

    /**
     * Assign existing or new user as PIC for the specified department.
     */
    public function assignPic(Request $request, Department $department)
    {
        if ($request->filled('user_id')) {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);

            $user = User::findOrFail($validated['user_id']);
            $user->update([
                'department_id' => $department->id,
                'role' => 'pic_dept',
            ]);

            ActivityLogger::log(
                'MASTER_DEPT_PIC_ASSIGN',
                "Menugaskan user '{$user->name}' sebagai PIC Departemen {$department->name} ({$department->code}).",
                'MASTER_DEPT',
                [
                    'department_id' => $department->id,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                ],
                $department->name
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "User {$user->name} berhasil ditugaskan sebagai PIC Departemen {$department->name}.",
                    'user' => $user,
                ]);
            }

            return redirect()->back()->with('success', "User {$user->name} berhasil ditugaskan sebagai PIC.");
        } else {
            // Create a new user as PIC
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'phone' => 'nullable|string|max:20',
            ]);

            $validated['password'] = Hash::make($validated['password']);
            $validated['department_id'] = $department->id;
            $validated['role'] = 'pic_dept';

            $newUser = User::create($validated);

            ActivityLogger::log(
                'MASTER_DEPT_PIC_CREATE',
                "Membuat user baru '{$newUser->name}' sebagai PIC Departemen {$department->name} ({$department->code}).",
                'MASTER_DEPT',
                [
                    'department_id' => $department->id,
                    'user_id' => $newUser->id,
                    'user_name' => $newUser->name,
                ],
                $department->name
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "User PIC baru {$newUser->name} berhasil dibuat dan ditugaskan ke {$department->name}.",
                    'user' => $newUser,
                ]);
            }

            return redirect()->back()->with('success', "User PIC baru {$newUser->name} berhasil dibuat.");
        }
    }

    /**
     * Remove / unassign user from department PIC.
     */
    public function removePic(Request $request, Department $department, User $user)
    {
        if ($user->department_id == $department->id) {
            $userName = $user->name;
            $user->update([
                'department_id' => null,
            ]);

            ActivityLogger::log(
                'MASTER_DEPT_PIC_REMOVE',
                "Melepaskan penugasan PIC '{$userName}' dari Departemen {$department->name}.",
                'MASTER_DEPT',
                [
                    'department_id' => $department->id,
                    'user_id' => $user->id,
                    'user_name' => $userName,
                ],
                $department->name
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Penugasan PIC {$userName} dari {$department->name} berhasil dilepaskan.",
                ]);
            }

            return redirect()->back()->with('success', "Penugasan PIC {$userName} berhasil dilepaskan.");
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'error',
                'message' => "User tidak terdaftar pada departemen ini.",
            ], 422);
        }

        return redirect()->back()->with('error', "User tidak terdaftar pada departemen ini.");
    }
}