<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterArchiveRequest;
use App\Models\Department;
use App\Models\MasterArchive;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Kelola Departemen > Master Berkas & Dokumen Baku (Laravel 7 MasterArchiveController, same
 * messages and activity logs). Admin only (routes); update/destroy only reach Master Berkas of
 * the department in the URL (scoped binding). The page calls these with fetch() (JSON).
 */
class MasterArchiveController extends Controller
{
    public function store(MasterArchiveRequest $request, Department $department): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $data['retention_years'] = ($data['retention_years'] ?? null) ?: ($department->retention_years ?: 5);
        $data['code'] = filled($data['code'] ?? null) ? strtoupper((string) $data['code']) : null;
        $data['is_active'] = true;
        $master = new MasterArchive($data);
        $master->department_id = $department->id;
        $master->save();

        ActivityLogger::log('MASTER_ARCHIVE_CREATE', "Menambahkan Master Arsip '{$master->name}' pada Departemen {$department->name} ({$department->code}).", 'MASTER_DEPT',
            ['master_archive_id' => $master->id, 'department_id' => $department->id] + $master->only(['name', 'code', 'retention_years']), $master->name);

        return $this->respond("Master Arsip '{$master->name}' berhasil ditambahkan.");
    }

    public function update(MasterArchiveRequest $request, Department $department, MasterArchive $masterArchive): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        if (filled($data['code'] ?? null)) {
            $data['code'] = strtoupper((string) $data['code']);
        }
        $masterArchive->update($data);

        ActivityLogger::log('MASTER_ARCHIVE_UPDATE', "Memperbarui Master Arsip '{$masterArchive->name}' pada Departemen {$department->name}.", 'MASTER_DEPT',
            ['master_archive_id' => $masterArchive->id, 'department_id' => $department->id] + $masterArchive->only(['name', 'code']), $masterArchive->name);

        return $this->respond("Master Arsip '{$masterArchive->name}' berhasil diperbarui.");
    }

    public function destroy(Department $department, MasterArchive $masterArchive): JsonResponse|RedirectResponse
    {
        $name = $masterArchive->name;
        $id = $masterArchive->id;
        $masterArchive->delete();

        ActivityLogger::log('MASTER_ARCHIVE_DELETE', "Menghapus Master Arsip '{$name}' dari Departemen {$department->name}.", 'MASTER_DEPT',
            ['master_archive_id' => $id, 'department_id' => $department->id, 'name' => $name], $name);

        return $this->respond("Master Arsip '{$name}' berhasil dihapus.");
    }

    /** "+ Tambah Batch (Multi-Line)": one Master Berkas per line, one INSERT per 100 rows. */
    public function storeBatch(MasterArchiveRequest $request, Department $department): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $names = $request->batchNames();
        if ($names === []) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada nama berkas arsip yang valid untuk disimpan.'], 422);
        }

        $subId = $data['sub_department_id'] ?? null;
        $now = now();
        $rows = array_map(fn (string $name): array => [
            'department_id' => $department->id, 'sub_department_id' => $subId, 'name' => $name,
            'document_type' => ($data['document_type'] ?? null) ?: 'UMUM',
            'retention_years' => ($data['retention_years'] ?? null) ?: ($department->retention_years ?: 5),
            'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ], $names);
        DB::transaction(function () use ($rows): void {
            foreach (array_chunk($rows, 100) as $chunk) {
                MasterArchive::insert($chunk);
            }
        });
        $count = count($rows);

        ActivityLogger::log('MASTER_ARCHIVE_BATCH_CREATE', "Menambahkan {$count} Master Arsip secara batch pada Departemen {$department->name} ({$department->code}).", 'MASTER_DEPT',
            ['department_id' => $department->id, 'sub_department_id' => $subId, 'total_created' => $count]);

        return $this->respond("Berhasil menambahkan {$count} Master Berkas Arsip baru secara batch.", ['total' => $count]);
    }

    /** @param array<string, mixed> $extra */
    private function respond(string $message, array $extra = []): JsonResponse|RedirectResponse
    {
        return request()->expectsJson()
            ? response()->json(['status' => 'success', 'message' => $message] + $extra)
            : back()->with('success', $message);
    }
}
