<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\MasterArchive;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class MasterArchiveController extends Controller
{
    /**
     * API endpoint to get active master archives for a specific department.
     */
    public function apiGetByDepartment(Department $department)
    {
        $archives = $department->masterArchives()
            ->with('subDepartment')
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'department' => [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
            ],
            'master_archives' => $archives,
        ]);
    }

    /**
     * Store a newly created master archive in database.
     */
    public function store(Request $request, Department $department)
    {
        $validated = $request->validate([
            'sub_department_id' => 'nullable|exists:sub_departments,id',
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'document_type' => 'nullable|string|max:50',
            'retention_years' => 'nullable|integer|min:1|max:100',
            'description' => 'nullable|string',
        ]);

        if (empty($validated['retention_years'])) {
            $validated['retention_years'] = $department->retention_years ?: 5;
        }

        $validated['department_id'] = $department->id;
        $validated['is_active'] = true;

        if (!empty($validated['code'])) {
            $validated['code'] = strtoupper($validated['code']);
        }

        $masterArchive = MasterArchive::create($validated);

        ActivityLogger::log(
            'MASTER_ARCHIVE_CREATE',
            "Menambahkan Master Arsip '{$masterArchive->name}' pada Departemen {$department->name} ({$department->code}).",
            'MASTER_DEPT',
            [
                'master_archive_id' => $masterArchive->id,
                'department_id' => $department->id,
                'name' => $masterArchive->name,
                'code' => $masterArchive->code,
                'retention_years' => $masterArchive->retention_years,
            ],
            $masterArchive->name
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => "Master Arsip '{$masterArchive->name}' berhasil ditambahkan.",
                'data' => $masterArchive->load('subDepartment'),
            ]);
        }

        return redirect()->back()->with('success', "Master Arsip '{$masterArchive->name}' berhasil ditambahkan.");
    }

    /**
     * Update an existing master archive in database.
     */
    public function update(Request $request, Department $department, MasterArchive $masterArchive)
    {
        $validated = $request->validate([
            'sub_department_id' => 'nullable|exists:sub_departments,id',
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'document_type' => 'nullable|string|max:50',
            'retention_years' => 'nullable|integer|min:1|max:100',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if (!empty($validated['code'])) {
            $validated['code'] = strtoupper($validated['code']);
        }

        $masterArchive->update($validated);

        ActivityLogger::log(
            'MASTER_ARCHIVE_UPDATE',
            "Memperbarui Master Arsip '{$masterArchive->name}' pada Departemen {$department->name}.",
            'MASTER_DEPT',
            [
                'master_archive_id' => $masterArchive->id,
                'department_id' => $department->id,
                'name' => $masterArchive->name,
                'code' => $masterArchive->code,
            ],
            $masterArchive->name
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => "Master Arsip '{$masterArchive->name}' berhasil diperbarui.",
                'data' => $masterArchive->load('subDepartment'),
            ]);
        }

        return redirect()->back()->with('success', "Master Arsip '{$masterArchive->name}' berhasil diperbarui.");
    }

    /**
     * Remove the specified master archive from database.
     */
    public function destroy(Request $request, Department $department, MasterArchive $masterArchive)
    {
        $name = $masterArchive->name;
        $id = $masterArchive->id;
        $masterArchive->delete();

        ActivityLogger::log(
            'MASTER_ARCHIVE_DELETE',
            "Menghapus Master Arsip '{$name}' dari Departemen {$department->name}.",
            'MASTER_DEPT',
            [
                'master_archive_id' => $id,
                'department_id' => $department->id,
                'name' => $name,
            ],
            $name
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => "Master Arsip '{$name}' berhasil dihapus.",
            ]);
        }

        return redirect()->back()->with('success', "Master Arsip '{$name}' berhasil dihapus.");
    }

    /**
     * Store multiple master archives in batch from newline-separated input or array.
     */
    public function storeBatch(Request $request, Department $department)
    {
        $validated = $request->validate([
            'sub_department_id' => 'nullable|exists:sub_departments,id',
            'raw_text' => 'nullable|string',
            'items' => 'nullable|array',
            'document_type' => 'nullable|string|max:50',
            'retention_years' => 'nullable|integer|min:1|max:100',
        ]);

        $retentionYears = $validated['retention_years'] ?? ($department->retention_years ?: 5);
        $subDeptId = $validated['sub_department_id'] ?? null;
        $docType = $validated['document_type'] ?? 'UMUM';

        $names = [];
        if (!empty($validated['items']) && is_array($validated['items'])) {
            $names = $validated['items'];
        } elseif (!empty($validated['raw_text'])) {
            $lines = preg_split('/[\r\n]+/', $validated['raw_text']);
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '') {
                    $names[] = $trimmed;
                }
            }
        }

        if (empty($names)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada nama berkas arsip yang valid untuk disimpan.',
            ], 422);
        }

        $now = now();
        $batchToInsert = [];

        foreach ($names as $name) {
            $name = trim($name);
            if ($name === '') continue;

            $batchToInsert[] = [
                'department_id' => $department->id,
                'sub_department_id' => $subDeptId,
                'name' => $name,
                'document_type' => $docType,
                'retention_years' => $retentionYears,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($batchToInsert)) {
            MasterArchive::insert($batchToInsert);
        }
        $createdCount = count($batchToInsert);

        ActivityLogger::log(
            'MASTER_ARCHIVE_BATCH_CREATE',
            "Menambahkan {$createdCount} Master Arsip secara batch pada Departemen {$department->name} ({$department->code}).",
            'MASTER_DEPT',
            [
                'department_id' => $department->id,
                'sub_department_id' => $subDeptId,
                'total_created' => $createdCount,
            ]
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => "Berhasil menambahkan {$createdCount} Master Berkas Arsip baru secara batch.",
                'total' => $createdCount,
            ]);
        }

        return redirect()->back()->with('success', "Berhasil menambahkan {$createdCount} Master Berkas Arsip baru.");
    }
}
