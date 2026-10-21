<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\ArchiveItem;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\WarehouseEntryLog;
use App\Models\WarehouseLocation;
use App\Services\ActivityLogger;
use App\Services\NumberingService;
use App\Services\MultiBoxExcelService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArchiveController extends Controller
{
    use \App\Http\Controllers\Traits\HandlesSingleBoxCreation;
    use \App\Http\Controllers\Traits\HandlesMultiBoxCreation;
    use \App\Http\Controllers\Traits\HandlesSingleBoxUpdate;
    use \App\Http\Controllers\Traits\HandlesMultiBoxUpdate;
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Archive::with([
            'department', 
            'subDepartment', 
            'location.warehouse', 
            'rackSlot',
            'creator',
            'extensionLogs',
            'borrowingLogs' => function ($q) {
                $q->orderBy('created_at', 'desc')->orderBy('id', 'desc');
            },
            'borrowingLogs.borrower',
            'borrowingLogs.departmentApprovedBy'
        ]);

        if ($user->isPicDept()) {
            $query->where('department_id', $user->department_id);
        }

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search, $user) {
                if ($user && $user->isPicGudang()) {
                    // Role Gudang HANYA boleh mencari nomor box, judul label box, dan lokasi rak
                    $q->where('box_number', 'like', "%{$search}%")
                      ->orWhere('title', 'like', "%{$search}%")
                      ->orWhereHas('location', function ($lq) use ($search) {
                          $lq->where('rack_code', 'like', "%{$search}%");
                      });
                } else {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('custom_doc_name', 'like', "%{$search}%")
                      ->orWhere('box_number', 'like', "%{$search}%")
                      ->orWhere('periode_doc', 'like', "%{$search}%")
                      ->orWhere('period_text', 'like', "%{$search}%")
                      ->orWhere('content_description', 'like', "%{$search}%")
                      ->orWhereHas('location', function ($lq) use ($search) {
                          $lq->where('rack_code', 'like', "%{$search}%");
                      });
                }
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('sub_department_id')) {
            $query->where('sub_department_id', $request->sub_department_id);
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->filled('status')) {
            if ($request->status === 'borrow_requested') {
                $query->whereHas('borrowingLogs', function ($q) {
                    $q->whereIn('status', ['requested', 'dept_approved', 'approved']);
                });
            } elseif ($request->status === 'borrowed' || $request->status === 'out') {
                $query->whereIn('status', ['borrowed', 'taken']);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('expiry_filter')) {
            if ($request->expiry_filter === 'expiring_soon') {
                $query->whereNotNull('retention_expiry_date')
                      ->whereNotIn('status', ['destroyed', 'draft'])
                      ->whereDate('retention_expiry_date', '>=', Carbon::today())
                      ->whereDate('retention_expiry_date', '<=', Carbon::now()->addDays(90));
            } elseif ($request->expiry_filter === 'expired') {
                $query->whereNotNull('retention_expiry_date')
                      ->whereNotIn('status', ['destroyed', 'draft'])
                      ->whereDate('retention_expiry_date', '<', Carbon::today());
            }
        }

        // Sorting: Data yang diajukan / aktif (non-draft) selalu di atas diurutkan berdasarkan updated_at terbaru
        if ($request->filled('sort')) {
            $sortColumn = $request->get('sort');
            $sortDirection = strtolower($request->get('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
            $allowedSorts = ['box_number', 'title', 'created_at', 'updated_at', 'retention_expiry_date', 'status'];

            if (in_array($sortColumn, $allowedSorts)) {
                $query->orderBy($sortColumn, $sortDirection);
                if (in_array($sortColumn, ['created_at', 'updated_at'])) {
                    $query->orderBy('id', $sortDirection);
                }
            } else {
                $query->orderByRaw("CASE WHEN status = 'draft' THEN 1 ELSE 0 END ASC")
                      ->orderBy('updated_at', 'desc')
                      ->orderBy('id', 'desc');
            }
        } else {
            // Default: Non-draft (pengajuan baru, penarikan, perpanjangan, pemusnahan, dll) diletakkan di paling atas sendiri by updated_at terbaru, draft di bawah
            $query->orderByRaw("CASE WHEN status = 'draft' THEN 1 ELSE 0 END ASC")
                  ->orderBy('updated_at', 'desc')
                  ->orderBy('id', 'desc');
        }

        $perPage = (int) $request->get('per_page', 10);
        $archives = $query->paginate($perPage > 0 ? $perPage : 10)->withQueryString();

        $isSuperAdmin = $user && $user->isSuperAdmin();
        $deptQuery = Department::with(['subDepartments' => function ($q) use ($isSuperAdmin) {
            if (!$isSuperAdmin) {
                $q->where('is_active', true);
            }
        }]);
        if (!$isSuperAdmin) {
            $deptQuery->where('is_active', true);
        }
        if ($user && $user->isPicDept()) {
            $deptQuery->where('id', $user->department_id);
        }
        $departments = $deptQuery->get();

        // Pending Borrowing Requests Count for PIC Gudang Alert Banner
        $pendingBorrowingRequestsCount = 0;
        if ($user && ($user->isPicGudang() || $user->isSuperAdmin())) {
            $pendingBorrowingRequestsCount = \App\Models\BorrowingLog::whereIn('status', ['requested', 'dept_approved', 'approved'])->count();
        }

        // Distinct document types from MasterArchive and Archive for cascading filter
        $masterDocTypes = \App\Models\MasterArchive::where('is_active', true)
            ->when($user && $user->isPicDept(), function ($q) use ($user) {
                return $q->where('department_id', $user->department_id);
            })
            ->whereNotNull('document_type')
            ->where('document_type', '!=', '')
            ->select('department_id', 'sub_department_id', 'document_type')
            ->distinct()
            ->get();

        $archiveDocTypes = Archive::query()
            ->when($user && $user->isPicDept(), function ($q) use ($user) {
                return $q->where('department_id', $user->department_id);
            })
            ->whereNotNull('document_type')
            ->where('document_type', '!=', '')
            ->select('department_id', 'sub_department_id', 'document_type')
            ->distinct()
            ->get();

        $documentTypes = $masterDocTypes->concat($archiveDocTypes)
            ->unique(function ($item) {
                return ($item->department_id ?? 0) . '-' . ($item->sub_department_id ?? 0) . '-' . trim($item->document_type);
            })
            ->values();

        return view('archives.index', compact('archives', 'departments', 'pendingBorrowingRequestsCount', 'documentTypes'));
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        if ($user && $user->isPicGudang()) {
            abort(403, 'Akses Ditolak: PIC Gudang tidak memiliki hak untuk membuat pengajuan baru. Wewenang gudang hanya untuk verifikasi fisik dan approval.');
        }
        $departments = Department::with(['subDepartments' => function ($q) {
            $q->where('is_active', true);
        }, 'masterArchives' => function ($q) {
            $q->where('is_active', true)->with('subDepartment')->orderBy('name', 'asc');
        }])->where('is_active', true)->get();

        $preloadedBoxes = [];
        $isReStore = false;
        $originBorrowingIds = [];
        $targetDeptId = null;

        if ($request->has('re_store') && ($request->filled('borrowing_ids') || $request->filled('borrowing_id'))) {
            $isReStore = true;
            $rawIds = $request->input('borrowing_ids', []);
            if (empty($rawIds) && $request->filled('borrowing_id')) {
                $rawIds = [$request->input('borrowing_id')];
            }
            if (!is_array($rawIds)) {
                $rawIds = explode(',', $rawIds);
            }
            $rawIds = array_filter(array_map('intval', (array)$rawIds));

            $borrowingLogs = \App\Models\BorrowingLog::with(['archive.department', 'archive.subDepartment', 'archive.items'])
                ->whereIn('id', $rawIds)
                ->get();

            if ($borrowingLogs->isEmpty()) {
                return redirect()->route('borrowings.index')->with('error', 'Riwayat penarikan yang dipilih tidak ditemukan.');
            }

            // Validasi keberadaan box & pencegahan pengajuan ganda
            foreach ($borrowingLogs as $bLog) {
                $eligibility = $bLog->restore_eligibility;
                if (!$eligibility['eligible']) {
                    return redirect()->route('borrowings.index')
                        ->with('error', "Gagal mengajukan penyimpanan kembali: Box [{$bLog->effective_box_number}] {$eligibility['reason']}");
                }
            }

            $firstLog = $borrowingLogs->first();
            $targetDeptId = $firstLog->archive ? $firstLog->archive->department_id : null;

            $preloadedBoxes = $borrowingLogs->map(function ($bLog, $idx) {
                $arc = $bLog->archive;
                $items = $bLog->parsed_snapshot_items;
                $pStart = date('Y-m');
                $pEnd = date('Y-m');
                if ($arc && $arc->period_start_date) {
                    $pStart = \Carbon\Carbon::parse($arc->period_start_date)->format('Y-m');
                }
                if ($arc && $arc->period_end_date) {
                    $pEnd = \Carbon\Carbon::parse($arc->period_end_date)->format('Y-m');
                }

                $boxItems = [];
                if (!empty($items)) {
                    foreach ($items as $iIdx => $it) {
                        $boxItems[] = [
                            'id' => $iIdx + 1,
                            'document_name' => $it['name'] ?? ($it['document_name'] ?? ''),
                            'period_start' => !empty($it['period']) ? substr($it['period'], 0, 7) : $pStart,
                            'period_end' => !empty($it['period']) ? substr($it['period'], 0, 7) : $pEnd,
                            'notes' => $it['notes'] ?? '',
                        ];
                    }
                }
                if (empty($boxItems)) {
                    $boxItems = [
                        [
                            'id' => 1,
                            'document_name' => $bLog->effective_title,
                            'period_start' => $pStart,
                            'period_end' => $pEnd,
                            'notes' => 'Penyimpanan kembali dari penarikan #' . $bLog->id
                        ]
                    ];
                }

                return [
                    'id' => $idx + 1,
                    'title' => $bLog->effective_title,
                    'sub_department_id' => $arc ? $arc->sub_department_id : '',
                    'document_type' => $arc ? $arc->document_type : '',
                    'periode_bulan' => '1',
                    'follow_previous' => false,
                    'physical_condition' => $arc ? ($arc->physical_condition ?: 'Baik / Box Karton Standar TB 30g') : 'Baik / Box Karton Standar TB 30g',
                    'origin_borrowing_log_id' => $bLog->id,
                    'origin_archive_id' => $arc ? $arc->id : null,
                    'box_number' => $bLog->effective_box_number,
                    'items' => $boxItems
                ];
            })->values()->toArray();

            $originBorrowingIds = $rawIds;
        }

        $fromExcelImport = false;
        $totalItemsPreloaded = 0;
        if (($request->query('from') === 'excel_import' || $request->has('from_import')) && session()->has('excel_preloaded_boxes')) {
            $fromExcelImport = true;
            $preloadedBoxes = session('excel_preloaded_boxes', []);
            $targetDeptId = session('excel_target_dept_id') ?: $targetDeptId;
            $totalItemsPreloaded = session('excel_total_items', 0);
            session()->forget(['excel_preloaded_boxes', 'excel_target_dept_id', 'excel_total_items', 'excel_from_import']);
        }

        $from = $request->query('from') ?: (!empty($isReStore) ? 'borrowings' : 'archives');

        return view('archives.create', compact('user', 'departments', 'preloadedBoxes', 'isReStore', 'originBorrowingIds', 'targetDeptId', 'from', 'fromExcelImport', 'totalItemsPreloaded'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user && $user->isPicGudang()) {
            abort(403, 'Akses Ditolak: PIC Gudang tidak memiliki hak untuk membuat pengajuan baru. Wewenang gudang hanya untuk verifikasi fisik dan approval.');
        }

        if ($request->input('sub_department_id') === 'inti') {
            $request->merge(['sub_department_id' => null]);
        }
        if ($request->has('boxes') && is_array($request->input('boxes'))) {
            $boxes = $request->input('boxes');
            foreach ($boxes as $k => $b) {
                if (isset($b['sub_department_id']) && $b['sub_department_id'] === 'inti') {
                    $boxes[$k]['sub_department_id'] = null;
                }
            }
            $request->merge(['boxes' => $boxes]);
        }

        if ($request->has('boxes') && is_array($request->input('boxes')) && count($request->input('boxes')) > 0) {
            return $this->storeMultiBox($request, $user);
        }

        return $this->storeSingleBox($request, $user);
    }

    public function edit(Archive $archive)
    {
        $user = auth()->user();

        // Ensure PIC Dept can only edit archives from their own department
        if ($user->isPicDept() && (int)$archive->department_id !== (int)$user->department_id) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengedit arsip departemen ini.');
        }

        $archive->load(['department', 'subDepartment', 'items']);

        $departments = Department::with(['subDepartments' => function ($q) {
            $q->where('is_active', true);
        }, 'masterArchives' => function ($q) {
            $q->where('is_active', true)->with('subDepartment')->orderBy('name', 'asc');
        }])->where('is_active', true)->get();

        $draftBoxes = null;
        if ($archive->status === 'draft' && !empty($archive->content_description)) {
            $decoded = json_decode($archive->content_description, true);
            if (is_array($decoded) && !empty($decoded['is_multi_box_draft']) && !empty($decoded['boxes'])) {
                $draftBoxes = $decoded['boxes'];
            }
        }

        $from = request('from') ?: (!empty($archive->origin_borrowing_log_id) ? 'borrowings' : 'archives');

        return view('archives.edit', compact('user', 'archive', 'departments', 'draftBoxes', 'from'));
    }

    public function update(Request $request, Archive $archive)
    {
        $user = auth()->user();

        if ($request->input('sub_department_id') === 'inti') {
            $request->merge(['sub_department_id' => null]);
        }
        if ($request->has('boxes') && is_array($request->input('boxes'))) {
            $boxes = $request->input('boxes');
            foreach ($boxes as $k => $b) {
                if (isset($b['sub_department_id']) && $b['sub_department_id'] === 'inti') {
                    $boxes[$k]['sub_department_id'] = null;
                }
            }
            $request->merge(['boxes' => $boxes]);
        }

        if ($request->has('boxes') && is_array($request->input('boxes')) && count($request->input('boxes')) > 0) {
            return $this->updateMultiBox($request, $archive, $user);
        }

        return $this->updateSingleBox($request, $archive, $user);
    }

    /**
     * Delete an archive draft.
     */
    public function destroy(Request $request, Archive $archive)
    {
        $user = auth()->user();

        if ($user->isPicDept() && (int)$archive->department_id !== (int)$user->department_id) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus draft arsip ini.');
        }

        if ($archive->status !== 'draft') {
            return redirect()->back()->with('error', 'Hanya data berstatus draft yang dapat dihapus.');
        }

        $boxTitle = $archive->title ?: 'Draft Box Arsip';
        $archiveId = $archive->id;

        // Hapus items dokumen draft
        $archive->items()->delete();

        // Hapus file draft jika ada
        if ($archive->scan_input_form && \Illuminate\Support\Facades\Storage::disk('public')->exists($archive->scan_input_form)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($archive->scan_input_form);
        }
        if ($archive->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($archive->file_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($archive->file_path);
        }

        $archive->delete();

        ActivityLogger::log(
            'ARCHIVE_DRAFT_DELETE',
            "Menghapus data draft arsip '{$boxTitle}' oleh {$user->name}.",
            'DOKUMEN_ARSIP',
            ['archive_id' => $archiveId, 'title' => $boxTitle],
            $boxTitle,
            $user
        );

        $redirectParams = $this->getEmbedParams($request);
        return redirect()->route('archives.index', $redirectParams)
            ->with('success', "Data draft arsip '{$boxTitle}' berhasil dihapus.");
    }

    /**
     * Batch delete archive drafts.
     */
    public function batchDeleteDrafts(Request $request)
    {
        $user = auth()->user();
        $ids = $request->input('ids', []);
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }
        $ids = array_filter(array_map('intval', (array)$ids));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'Tidak ada data draft yang dipilih untuk dihapus.');
        }

        $query = Archive::whereIn('id', $ids)->where('status', 'draft');
        if ($user->isPicDept()) {
            $query->where('department_id', $user->department_id);
        }

        $drafts = $query->get();
        if ($drafts->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data draft valid yang dapat dihapus.');
        }

        $count = 0;
        foreach ($drafts as $draft) {
            $draft->items()->delete();
            if ($draft->scan_input_form && \Illuminate\Support\Facades\Storage::disk('public')->exists($draft->scan_input_form)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($draft->scan_input_form);
            }
            if ($draft->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($draft->file_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($draft->file_path);
            }
            $draft->delete();
            $count++;
        }

        ActivityLogger::log(
            'ARCHIVE_DRAFT_BATCH_DELETE',
            "Menghapus {$count} data draft arsip secara batch oleh {$user->name}.",
            'DOKUMEN_ARSIP',
            ['deleted_ids' => $ids, 'count' => $count],
            "Batch {$count} Draft",
            $user
        );

        $redirectParams = $this->getEmbedParams($request);
        return redirect()->route('archives.index', $redirectParams)
            ->with('success', "Berhasil menghapus {$count} data draft arsip.");
    }

    protected function getEmbedParams(Request $request)
    {
        if ($request->has('embed') || $request->input('embed') || $request->header('Sec-Fetch-Dest') === 'iframe' || \Illuminate\Support\Str::contains($request->header('referer', ''), 'embed=1')) {
            return ['embed' => 1];
        }
        return [];
    }

    public function show(Archive $archive)
    {
        $archive->load([
            'department',
            'subDepartment',
            'creator',
            'items',
            'location.warehouse',
            'rackSlot',
            'entryLogs.picGudang',
            'entryLogs.location.warehouse',
            'borrowingLogs.borrower',
            'borrowingLogs.picGudang',
            'destructionLog.proposedBy',
            'destructionLog.approvedBy'
        ]);

        $locations = WarehouseLocation::with('warehouse')
            ->where('location_type', 'rack')
            ->where('is_active', true)
            ->orderBy('warehouse_id')
            ->orderBy('rack_code')
            ->get();

        return view('archives.show', compact('archive', 'locations'));
    }

    public function apiGetSubDepartments(Department $department)
    {
        $subDepts = $department->subDepartments()->where('is_active', true)->get(['id', 'code', 'name', 'retention_years']);
        return response()->json([
            'success' => true,
            'sub_departments' => $subDepts,
            'department_retention_years' => $department->retention_years ?? 5,
        ]);
    }

    public function apiCalculateRetention(Request $request, \App\Services\ArchiveRetentionService $retentionService)
    {
        $periodeDoc = trim($request->query('periode_doc', ''));
        $deptId = $request->query('department_id');
        $subDeptId = $request->query('sub_department_id');
        $customYears = $request->query('masa_simpan_custom');

        $result = $retentionService->calculateRetention($periodeDoc, $deptId, $subDeptId, $customYears);
        return response()->json($result);
    }

    /**
     * Live similarity check for box title and items within the user's department scope.
     */
    public function apiCheckSimilarity(Request $request, \App\Services\ArchiveSimilarityService $similarityService)
    {
        $user = auth()->user();
        $title = trim($request->input('title', ''));
        $deptId = $request->input('department_id');

        if ($user && $user->isPicDept()) {
            $deptId = $user->department_id;
        }

        $excludeIds = [];
        if ($request->filled('exclude_archive_id')) $excludeIds[] = (int)$request->input('exclude_archive_id');
        if ($request->filled('exclude_id')) $excludeIds[] = (int)$request->input('exclude_id');
        if ($request->filled('origin_archive_id')) $excludeIds[] = (int)$request->input('origin_archive_id');

        $result = $similarityService->checkSimilarity($title, (int)$deptId, $excludeIds);
        return response()->json($result);
    }

    public function getMaxPeriodMonths($departmentId = null, $user = null): int
    {
        return app(\App\Services\ArchiveRetentionService::class)->getMaxPeriodMonths($departmentId, $user);
    }

    protected function isRunningTestEnvironment(): bool
    {
        return defined('PHPUNIT_COMPOSER_INSTALL') 
            || class_exists('PHPUnit\\Framework\\TestCase', false)
            || app()->runningUnitTests() 
            || app()->environment('testing');
    }
}

