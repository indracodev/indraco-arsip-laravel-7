<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Services\MultiBoxExcelService;
use Illuminate\Http\Request;

class ArchiveImportController extends Controller
{
    /**
     * Download multi-box import template (XLSX or CSV).
     */
    public function downloadImportTemplate(Request $request, MultiBoxExcelService $excelService)
    {
        $user = auth()->user();
        if ($user && $user->isPicGudang()) {
            abort(403, 'Akses Ditolak: PIC Gudang tidak memiliki hak untuk mengimpor pengajuan box arsip.');
        }
        $format = $request->input('format', 'xlsx');
        return $excelService->generateTemplate($format, $user);
    }

    /**
     * Parse and preview multi-box spreadsheet.
     */
    public function previewImport(Request $request, MultiBoxExcelService $excelService)
    {
        $user = auth()->user();
        if ($user && $user->isPicGudang()) {
            return response()->json([
                'success' => false,
                'errors' => ['Akses Ditolak: PIC Gudang tidak memiliki hak untuk mengimpor pengajuan box arsip.'],
            ], 403);
        }

        $request->validate([
            'file' => 'required|file|max:5120',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xlsm', 'csv'])) {
            return response()->json([
                'success' => false,
                'errors' => ['Format file tidak didukung. Harap unggah berkas berekstensi .xlsx atau .csv.'],
            ], 422);
        }

        $result = $excelService->parseFile($file, $user);

        return response()->json($result);
    }

    /**
     * Batch commit parsed multi-box archives to database.
     */
    public function commitImport(Request $request, MultiBoxExcelService $excelService)
    {
        $user = auth()->user();
        if ($user && $user->isPicGudang()) {
            return response()->json([
                'success' => false,
                'errors' => ['Akses Ditolak: PIC Gudang tidak memiliki hak untuk mengimpor pengajuan box arsip.'],
            ], 403);
        }

        $request->validate([
            'boxes' => 'required|array|min:1',
            'status' => 'required|in:draft,pending_verification,in_warehouse',
        ]);

        $boxes = $request->input('boxes');
        $status = $request->input('status');

        // Enforce PIC Dept security
        if ($user->isPicDept()) {
            // PIC Dept tidak boleh langsung bypass ke in_warehouse
            if ($status === 'in_warehouse') {
                $status = 'pending_verification';
            }

            foreach ($boxes as $box) {
                $targetDeptId = $box['department_id'] ?? null;
                if (!$targetDeptId && !empty($box['department_code'])) {
                    $foundDept = Department::where('code', strtoupper($box['department_code']))->first();
                    $targetDeptId = $foundDept ? $foundDept->id : null;
                }

                if ((int)$targetDeptId !== (int)$user->department_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Akses ditolak: Anda hanya dapat mengimpor box untuk departemen Anda.',
                    ], 403);
                }
            }
        }

        $result = $excelService->commitImport($boxes, $status, $user);

        return response()->json([
            'success' => true,
            'message' => "Berhasil mengimpor {$result['boxes_count']} box arsip dengan {$result['items_count']} butir dokumen.",
            'data' => $result,
        ]);
    }

    /**
     * Transfer parsed multi-box Excel data into create pengajuan form session.
     */
    public function loadImportToCreateForm(Request $request)
    {
        $user = auth()->user();
        if ($user && $user->isPicGudang()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses Ditolak: PIC Gudang tidak memiliki hak untuk membuat pengajuan baru.',
            ], 403);
        }

        $request->validate([
            'boxes' => 'required|array|min:1',
        ]);

        $rawBoxes = $request->input('boxes');
        $preloadedBoxes = [];
        $targetDeptId = null;
        $totalItems = 0;

        foreach ($rawBoxes as $idx => $b) {
            if (!$targetDeptId && !empty($b['department_id'])) {
                $targetDeptId = $b['department_id'];
            }

            $boxItems = [];
            if (!empty($b['items']) && is_array($b['items'])) {
                foreach ($b['items'] as $iIdx => $it) {
                    $boxItems[] = [
                        'id' => $iIdx + 1,
                        'document_name' => $it['document_name'] ?? $it['name'] ?? '',
                        'period_start' => $it['period_start'] ?? date('Y-m'),
                        'period_end' => $it['period_end'] ?? date('Y-m'),
                        'notes' => $it['notes'] ?? $it['description'] ?? '',
                    ];
                    $totalItems++;
                }
            }

            if (empty($boxItems)) {
                $boxItems = [
                    ['id' => 1, 'document_name' => '', 'period_start' => date('Y-m'), 'period_end' => date('Y-m'), 'notes' => '']
                ];
            }

            $preloadedBoxes[] = [
                'id' => $idx + 1,
                'temp_box_id' => $b['temp_box_id'] ?? ('BOX-' . str_pad($idx + 1, 2, '0', STR_PAD_LEFT)),
                'title' => $b['title'] ?? $b['name'] ?? '',
                'sub_department_id' => !empty($b['sub_department_id']) ? (string)$b['sub_department_id'] : '',
                'document_type' => $b['document_type'] ?? '',
                'periode_bulan' => !empty($b['retention_years']) ? (string)($b['retention_years'] * 12) : '1',
                'follow_previous' => false,
                'physical_condition' => $b['physical_condition'] ?? 'Baik / Box Karton Standar TB 30g',
                'items' => $boxItems,
            ];
        }

        session([
            'excel_preloaded_boxes' => $preloadedBoxes,
            'excel_target_dept_id' => $targetDeptId,
            'excel_total_items' => $totalItems,
            'excel_from_import' => true,
        ]);

        return response()->json([
            'success' => true,
            'redirect_url' => route('archives.create', ['from' => 'excel_import']),
        ]);
    }
}
