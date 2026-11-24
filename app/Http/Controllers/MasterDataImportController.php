<?php

namespace App\Http\Controllers;

use App\Services\MasterDataImportService;
use Illuminate\Http\Request;

class MasterDataImportController extends Controller
{
    /**
     * Download template resmi Import Data Master.
     */
    public function template()
    {
        return response()->download(
            base_path('template-import-master/template_import_data_master.xlsx'),
            'template_import_data_master.xlsx'
        );
    }

    public function preview(Request $request, MasterDataImportService $service)
    {
        return $this->handle($request, $service, false);
    }

    public function commit(Request $request, MasterDataImportService $service)
    {
        return $this->handle($request, $service, true);
    }

    protected function handle(Request $request, MasterDataImportService $service, bool $commit)
    {
        $request->validate([
            'file' => 'required|file|max:5120',
        ]);

        $file = $request->file('file');
        if (!in_array(strtolower($file->getClientOriginalExtension()), ['xlsx', 'csv'])) {
            return response()->json([
                'success' => false,
                'errors' => ['Format file tidak didukung. Harap unggah berkas berekstensi .xlsx atau .csv.'],
            ], 422);
        }

        $result = $service->process($file, $commit, auth()->user());

        return response()->json($result, $commit && !$result['success'] ? 422 : 200);
    }
}
