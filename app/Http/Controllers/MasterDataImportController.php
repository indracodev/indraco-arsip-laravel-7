<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MasterDataImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Import Excel Data Master on the Master Departemen page (Laravel 7 MasterDataImportController):
 * official template download, preview (nothing saved) and commit. Admin only (routes).
 * The upload is only parsed in memory, never stored.
 */
class MasterDataImportController extends Controller
{
    public function template(): BinaryFileResponse
    {
        return response()->download(base_path('template-import-master/template_import_data_master.xlsx'), 'template_import_data_master.xlsx');
    }

    public function preview(Request $request, MasterDataImportService $service): JsonResponse
    {
        return $this->handle($request, $service, false);
    }

    public function commit(Request $request, MasterDataImportService $service): JsonResponse
    {
        return $this->handle($request, $service, true);
    }

    private function handle(Request $request, MasterDataImportService $service, bool $commit): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:5120']]);
        $file = $request->file('file');
        if (! in_array(strtolower($file->getClientOriginalExtension()), ['xlsx', 'csv'], true)) {
            return response()->json(['success' => false, 'errors' => ['Format file tidak didukung. Harap unggah berkas berekstensi .xlsx atau .csv.']], 422);
        }

        /** @var User $user */
        $user = $request->user();
        $result = $service->process($file, $commit, $user);

        return response()->json($result, $commit && ! $result['success'] ? 422 : 200);
    }
}
