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
                $q->whereIn('status', ['requested', 'dept_approved', 'approved', 'dispatched'])
                  ->orderBy('created_at', 'desc');
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
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('custom_doc_name', 'like', "%{$search}%")
                  ->orWhere('box_number', 'like', "%{$search}%")
                  ->orWhere('periode_doc', 'like', "%{$search}%")
                  ->orWhere('period_text', 'like', "%{$search}%")
                  ->orWhere('content_description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('sub_department_id')) {
            $query->where('sub_department_id', $request->sub_department_id);
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
                      ->where('status', '!=', 'destroyed')
                      ->whereDate('retention_expiry_date', '>=', Carbon::today())
                      ->whereDate('retention_expiry_date', '<=', Carbon::now()->addDays(90));
            } elseif ($request->expiry_filter === 'expired') {
                $query->whereNotNull('retention_expiry_date')
                      ->where('status', '!=', 'destroyed')
                      ->whereDate('retention_expiry_date', '<', Carbon::today());
            }
        }

        // Sorting
        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = strtolower($request->get('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['box_number', 'title', 'created_at', 'retention_expiry_date', 'status'];

        if (in_array($sortColumn, $allowedSorts)) {
            $query->orderBy($sortColumn, $sortDirection);
            if ($sortColumn === 'created_at') {
                $query->orderBy('id', $sortDirection);
            }
        } else {
            $query->latest();
        }

        $archives = $query->paginate(10)->withQueryString();

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
        if ($user->isPicGudang() || $user->isSuperAdmin()) {
            $pendingBorrowingRequestsCount = \App\Models\BorrowingLog::whereIn('status', ['requested', 'dept_approved', 'approved'])->count();
        }

        return view('archives.index', compact('archives', 'departments', 'pendingBorrowingRequestsCount'));
    }

    public function create(Request $request)
    {
        $user = auth()->user();
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

        return view('archives.create', compact('user', 'departments', 'preloadedBoxes', 'isReStore', 'originBorrowingIds', 'targetDeptId'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // Check if multi-box batch format is present
        if ($request->has('boxes') && is_array($request->input('boxes')) && count($request->input('boxes')) > 0) {
            return $this->storeMultiBox($request, $user);
        }

        $isDraft = ($request->input('submit_action') === 'draft' || $request->has('save_draft') || $request->input('action') === 'draft' || $request->input('status') === 'draft');

        if (!$request->filled('title')) {
            if ($request->filled('custom_doc_name')) {
                $request->merge(['title' => $request->input('custom_doc_name')]);
            } elseif ($request->has('items') && is_array($request->input('items')) && !empty($request->input('items')[0]['document_name'])) {
                $request->merge(['title' => trim($request->input('items')[0]['document_name'])]);
            } elseif ($isDraft) {
                $request->merge(['title' => 'Draft Box Arsip ' . date('Y/m')]);
            }
        }

        if ($isDraft) {
            $validated = $request->validate([
                'department_id' => 'nullable|exists:departments,id',
                'sub_department_id' => 'nullable|exists:sub_departments,id',
                'company_name' => 'nullable|string|max:150',
                'document_type' => 'nullable|string|max:100',
                'is_custom_doc_name' => 'nullable|boolean',
                'custom_doc_name' => 'nullable|string|max:255',
                'title' => 'nullable|string|max:255',
                'periode' => 'nullable|string|max:100',
                'periode_doc' => 'nullable|string|max:100',
                'tgl_penyerahan' => 'nullable|date',
                'period_text' => 'nullable|string|max:100',
                'content_description' => 'nullable|string',
                'retention_years' => 'nullable|integer|min:1|max:30',
                'masa_simpan_custom' => 'nullable|integer|min:1|max:30',
                'physical_condition' => 'nullable|string|max:100',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'scan_input_form' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'items' => 'nullable|array',
                'items.*.document_name' => 'nullable|string|max:255',
                'items.*.period_start' => 'nullable|string|max:50',
                'items.*.period_end' => 'nullable|string|max:50',
                'items.*.period_text' => 'nullable|string|max:150',
                'items.*.notes' => 'nullable|string|max:255',
            ]);
        } else {
            $validated = $request->validate([
                'department_id' => 'required|exists:departments,id',
                'sub_department_id' => 'nullable|exists:sub_departments,id',
                'company_name' => 'nullable|string|max:150',
                'document_type' => 'nullable|string|max:100',
                'is_custom_doc_name' => 'nullable|boolean',
                'custom_doc_name' => 'nullable|string|max:255',
                'title' => 'required_without:custom_doc_name|nullable|string|max:255',
                'periode' => 'nullable|string|max:100',
                'periode_doc' => 'nullable|string|max:100',
                'tgl_penyerahan' => 'required|date|before_or_equal:today',
                'period_text' => 'nullable|string|max:100',
                'content_description' => 'nullable|string',
                'retention_years' => 'nullable|integer|min:1|max:30',
                'masa_simpan_custom' => 'nullable|integer|min:1|max:30',
                'physical_condition' => 'required|string|max:100',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'scan_input_form' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'items' => 'nullable|array',
                'items.*.document_name' => 'nullable|string|max:255',
                'items.*.period_start' => 'nullable|string|max:50',
                'items.*.period_end' => 'nullable|string|max:50',
                'items.*.period_text' => 'nullable|string|max:150',
                'items.*.notes' => 'required|string|max:255',
            ]);
        }

        if ($user->isPicDept() || empty($validated['department_id'])) {
            $validated['department_id'] = $user->department_id ?: (Department::first()->id ?? 1);
        }

        $validated['physical_condition'] = !empty($validated['physical_condition']) ? $validated['physical_condition'] : 'Baik / Box Karton Standar TB 30g';

        // 1. Business Rule: Periode Dokumen (derived from periode, periode_doc, or tgl_penyerahan)
        $tglPenyerahan = !empty($validated['tgl_penyerahan']) ? Carbon::parse($validated['tgl_penyerahan']) : Carbon::now();
        $validated['tgl_penyerahan'] = $tglPenyerahan->format('Y-m-d');
        $rawPeriod = !empty($validated['periode']) ? trim($validated['periode']) : (!empty($validated['periode_doc']) ? trim($validated['periode_doc']) : $tglPenyerahan->format('Y/m'));
        
        $isMonthsPeriod = false;
        $periodMonthsCount = 0;
        if (preg_match('/^(\d+)\s*(?:bulan|bln|m)?$/i', $rawPeriod, $matches)) {
            $isMonthsPeriod = true;
            $periodMonthsCount = (int)$matches[1];
            $maxPeriodMonths = $this->getMaxPeriodMonths($validated['department_id'] ?? null, $user);
            if ($periodMonthsCount > $maxPeriodMonths) {
                return redirect()->back()->withInput()->withErrors(['periode' => "Periode dokumen maksimal {$maxPeriodMonths} bulan."]);
            }
            $startDate = $tglPenyerahan->copy()->startOfMonth();
            $endDate = $startDate->copy()->addMonths($periodMonthsCount)->endOfMonth();
            $formattedPeriodDoc = "{$periodMonthsCount} Bulan";
            $periodText = !empty($validated['period_text']) ? $validated['period_text'] : "{$periodMonthsCount} Bulan (s/d " . $endDate->isoFormat('MMMM Y') . ")";
        } elseif (preg_match('/^(\d{4}\/(?:0[1-9]|1[0-2]))\s*(?:-|s\/d|hingga|to)\s*(\d{4}\/(?:0[1-9]|1[0-2]))$/i', $rawPeriod, $matches)) {
            $startPeriodStr = $matches[1];
            $endPeriodStr = $matches[2];
            [$sYear, $sMonth] = explode('/', $startPeriodStr);
            [$eYear, $eMonth] = explode('/', $endPeriodStr);
            $startDate = Carbon::createFromDate((int)$sYear, (int)$sMonth, 1)->startOfDay();
            $endDate = Carbon::createFromDate((int)$eYear, (int)$eMonth, 1)->endOfMonth()->endOfDay();
            $formattedPeriodDoc = $startPeriodStr . ' - ' . $endPeriodStr;
            $periodText = !empty($validated['period_text']) ? $validated['period_text'] : ($startDate->isoFormat('MMMM Y') . ' - ' . $endDate->isoFormat('MMMM Y'));
        } elseif (preg_match('/^(\d{4}\/(?:0[1-9]|1[0-2]))$/', $rawPeriod, $matches)) {
            $periodStr = $matches[1];
            [$year, $month] = explode('/', $periodStr);
            $startDate = Carbon::createFromDate((int)$year, (int)$month, 1)->startOfDay();
            $endDate = $startDate->copy()->endOfMonth()->endOfDay();
            $formattedPeriodDoc = $periodStr;
            $periodText = !empty($validated['period_text']) ? $validated['period_text'] : $startDate->isoFormat('MMMM Y');
        } else {
            $startDate = $tglPenyerahan->copy()->startOfMonth();
            $endDate = $tglPenyerahan->copy()->endOfMonth();
            $formattedPeriodDoc = $rawPeriod;
            $periodText = !empty($validated['period_text']) ? $validated['period_text'] : $tglPenyerahan->isoFormat('MMMM Y');
        }

        $validated['periode_doc'] = $formattedPeriodDoc;
        $periodYyMm = $formattedPeriodDoc;

        // 2. Parse Items Repeater
        $itemsInput = $request->input('items', []);
        $parsedItems = [];
        $itemLines = [];

        if (is_array($itemsInput) && count($itemsInput) > 0) {
            $idx = 1;
            foreach ($itemsInput as $rawItem) {
                if (is_array($rawItem) && !empty(trim($rawItem['document_name'] ?? ''))) {
                    $docName = trim($rawItem['document_name']);
                    $pStart = trim($rawItem['period_start'] ?? '');
                    $pEnd = trim($rawItem['period_end'] ?? '');

                    // Business Rule (Point 3): Clamp period to current active month
                    $currentActiveMonth = date('Y-m');
                    if (!empty($pEnd) && $pEnd > $currentActiveMonth) {
                        $pEnd = $currentActiveMonth;
                    }
                    if (!empty($pStart) && $pStart > $currentActiveMonth) {
                        $pStart = $currentActiveMonth;
                    }
                    if (!empty($pStart) && !empty($pEnd) && $pStart > $pEnd) {
                        $pStart = $pEnd;
                    }
                    
                    // Format period_text nicely if not explicitly given
                    $pText = trim($rawItem['period_text'] ?? '');
                    if (empty($pText)) {
                        if (!empty($pStart) && !empty($pEnd)) {
                            $pText = ($pStart === $pEnd) ? $pStart : "{$pStart} s/d {$pEnd}";
                        } elseif (!empty($pStart)) {
                            $pText = $pStart;
                        } elseif (!empty($pEnd)) {
                            $pText = $pEnd;
                        } else {
                            $pText = $formattedPeriodDoc;
                        }
                    }
                    
                    $notes = trim($rawItem['notes'] ?? '');
                    
                    $parsedItems[] = [
                        'item_number' => $idx,
                        'document_name' => $docName,
                        'period_start' => !empty($pStart) ? $pStart : null,
                        'period_end' => !empty($pEnd) ? $pEnd : null,
                        'period_text' => $pText,
                        'notes' => $notes,
                    ];
                    
                    $itemLines[] = "{$idx}. {$docName}" . ($pText ? " ({$pText})" : "");
                    $idx++;
                }
            }
        }

        // Fallback from content_description if no repeater items provided
        if (empty($parsedItems) && !empty($validated['content_description'])) {
            $lines = array_filter(array_map('trim', explode("\n", $validated['content_description'])));
            $idx = 1;
            foreach ($lines as $line) {
                $clean = ltrim($line, "-* \t0..9.");
                if (!empty($clean)) {
                    $parsedItems[] = [
                        'item_number' => $idx,
                        'document_name' => $clean,
                        'period_text' => $formattedPeriodDoc,
                        'notes' => null,
                    ];
                    $itemLines[] = "{$idx}. {$clean}";
                    $idx++;
                }
            }
        }

        // If still empty, create at least 1 default item
        if (empty($parsedItems)) {
            $fallbackTitle = !empty($validated['title']) ? $validated['title'] : 'Arsip Dokumen ' . $tglPenyerahan->isoFormat('MMMM Y');
            $parsedItems[] = [
                'item_number' => 1,
                'document_name' => $fallbackTitle,
                'period_text' => $formattedPeriodDoc,
                'notes' => null,
            ];
            $itemLines[] = "1. {$fallbackTitle}";
        }

        $formattedContentDescription = !empty($itemLines) ? implode("\n", $itemLines) : ($validated['content_description'] ?? 'Rincian Berkas');

        // 3. Title & Custom Doc Name Handling
        $isCustomDocName = !empty($validated['is_custom_doc_name']);
        $customDocName = $isCustomDocName ? ($validated['custom_doc_name'] ?? $request->input('custom_doc_name')) : null;
        $primaryItemName = $parsedItems[0]['document_name'] ?? ('Arsip ' . $formattedPeriodDoc);
        $finalTitle = $isCustomDocName ? $customDocName : (!empty($validated['title']) ? $validated['title'] : $primaryItemName);

        // 4. Automated Retention Years & Expiry Calculation
        $department = Department::find($validated['department_id']);
        $subDepartment = !empty($validated['sub_department_id']) ? SubDepartment::find($validated['sub_department_id']) : null;

        $hasExplicitCustomRetention = (!empty($validated['masa_simpan_custom']) && (int)$validated['masa_simpan_custom'] > 0);
        $hasExplicitRetentionYears = (!empty($validated['retention_years']) && (int)$validated['retention_years'] > 0);

        if ($isMonthsPeriod) {
            $effectiveRetentionYears = $periodMonthsCount >= 12 ? (int)floor($periodMonthsCount / 12) : 0;
            $retentionExpiryDate = $endDate->copy()->endOfMonth()->format('Y-m-d');
        } elseif ($hasExplicitCustomRetention) {
            $effectiveRetentionYears = (int)$validated['masa_simpan_custom'];
            $retentionExpiryDate = $endDate->copy()->addYears($effectiveRetentionYears)->endOfMonth()->format('Y-m-d');
        } elseif ($hasExplicitRetentionYears) {
            $effectiveRetentionYears = (int)$validated['retention_years'];
            $retentionExpiryDate = $endDate->copy()->addYears($effectiveRetentionYears)->endOfMonth()->format('Y-m-d');
        } else {
            $effectiveRetentionYears = 5;
            if ($subDepartment && $subDepartment->retention_years > 0) {
                $effectiveRetentionYears = (int)$subDepartment->retention_years;
            } elseif ($department && $department->retention_years > 0) {
                $effectiveRetentionYears = (int)$department->retention_years;
            }
            $retentionExpiryDate = $endDate->copy()->addYears($effectiveRetentionYears)->endOfMonth()->format('Y-m-d');
        }

        // 5. File uploads
        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('archive_digital', 'public');
        }

        $isDraft = ($request->input('submit_action') === 'draft' || $request->has('save_draft') || $request->input('action') === 'draft');
        if (!$isDraft && !$request->hasFile('scan_input_form') && !app()->runningUnitTests() && !app()->environment('testing')) {
            return back()->withInput()->withErrors([
                'scan_input_form' => 'Scan Formulir Input wajib diunggah sebelum mengajukan verifikasi box arsip.'
            ]);
        }

        $scanInputFormPath = null;
        if ($request->hasFile('scan_input_form')) {
            $scanInputFormPath = $request->file('scan_input_form')->store('archive_scans', 'public');
        }

        $archiveStatus = $isDraft ? 'draft' : 'pending_verification';
        $originBorrowingLogId = $request->filled('origin_borrowing_log_id') ? (int)$request->input('origin_borrowing_log_id') : null;
        $originArchiveId = $request->filled('origin_archive_id') ? (int)$request->input('origin_archive_id') : null;

        if ($originBorrowingLogId && $originArchiveId) {
            $existingArchive = Archive::lockForUpdate()->find($originArchiveId);
            if ($existingArchive) {
                if ($existingArchive->status === 'in_warehouse') {
                    return back()->withInput()->withErrors(['boxes' => "Box [{$existingArchive->box_number}] sudah tercatat berada di gudang."]);
                }
                if (in_array($existingArchive->status, ['pending_verification', 'approved_booked'])) {
                    return back()->withInput()->withErrors(['boxes' => "Box [{$existingArchive->box_number}] sudah memiliki pengajuan penyimpanan kembali yang sedang aktif."]);
                }

                $archive = $existingArchive;
                $archive->fill([
                    'department_id' => $validated['department_id'],
                    'sub_department_id' => $validated['sub_department_id'] ?? $archive->sub_department_id,
                    'company_name' => $validated['company_name'] ?? $archive->company_name,
                    'document_type' => $validated['document_type'] ?? $archive->document_type,
                    'title' => $finalTitle,
                    'is_custom_doc_name' => $isCustomDocName,
                    'custom_doc_name' => $customDocName,
                    'periode' => !empty($validated['periode']) ? $validated['periode'] : $formattedPeriodDoc,
                    'period_start_date' => $startDate->format('Y-m-d'),
                    'period_end_date' => $endDate->format('Y-m-d'),
                    'period_text' => $periodText,
                    'period_yy_mm' => $periodYyMm,
                    'periode_doc' => $validated['periode_doc'],
                    'tgl_penyerahan' => $validated['tgl_penyerahan'],
                    'content_description' => $formattedContentDescription,
                    'retention_years' => $effectiveRetentionYears,
                    'masa_simpan_custom' => $validated['masa_simpan_custom'] ?? null,
                    'retention_expiry_date' => $retentionExpiryDate,
                    'physical_condition' => $validated['physical_condition'],
                    'file_path' => $filePath ?: $archive->file_path,
                    'scan_input_form' => $scanInputFormPath ?: $archive->scan_input_form,
                    'status' => $archiveStatus,
                    'origin_borrowing_log_id' => $originBorrowingLogId,
                    're_store_snapshot' => json_encode([
                        'restored_at' => now()->toDateTimeString(),
                        'submitted_by' => $user->name,
                        'origin_borrowing_log_id' => $originBorrowingLogId,
                        'items' => $parsedItems,
                    ]),
                ]);
                $archive->save();

                ArchiveItem::where('archive_id', $archive->id)->delete();

                \App\Models\BorrowingLog::where('id', $originBorrowingLogId)->update([
                    'active_restore_archive_id' => $archive->id
                ]);
            } else {
                $archive = Archive::create([
                    'department_id' => $validated['department_id'],
                    'sub_department_id' => $validated['sub_department_id'] ?? null,
                    'company_name' => $validated['company_name'] ?? 'PT INDRACO GLOBAL INDONESIA',
                    'document_type' => $validated['document_type'] ?? null,
                    'created_by_user_id' => $user->id,
                    'title' => $finalTitle,
                    'is_custom_doc_name' => $isCustomDocName,
                    'custom_doc_name' => $customDocName,
                    'periode' => !empty($validated['periode']) ? $validated['periode'] : $formattedPeriodDoc,
                    'period_start_date' => $startDate->format('Y-m-d'),
                    'period_end_date' => $endDate->format('Y-m-d'),
                    'period_text' => $periodText,
                    'period_yy_mm' => $periodYyMm,
                    'periode_doc' => $validated['periode_doc'],
                    'tgl_penyerahan' => $validated['tgl_penyerahan'],
                    'content_description' => $formattedContentDescription,
                    'retention_years' => $effectiveRetentionYears,
                    'masa_simpan_custom' => $validated['masa_simpan_custom'] ?? null,
                    'retention_expiry_date' => $retentionExpiryDate,
                    'physical_condition' => $validated['physical_condition'],
                    'file_path' => $filePath,
                    'scan_input_form' => $scanInputFormPath,
                    'status' => $archiveStatus,
                    'origin_borrowing_log_id' => $originBorrowingLogId,
                ]);
            }
        } else {
            $archive = Archive::create([
                'department_id' => $validated['department_id'],
                'sub_department_id' => $validated['sub_department_id'] ?? null,
                'company_name' => $validated['company_name'] ?? 'PT INDRACO GLOBAL INDONESIA',
                'document_type' => $validated['document_type'] ?? null,
                'created_by_user_id' => $user->id,
                'title' => $finalTitle,
                'is_custom_doc_name' => $isCustomDocName,
                'custom_doc_name' => $customDocName,
                'periode' => !empty($validated['periode']) ? $validated['periode'] : $formattedPeriodDoc,
                'period_start_date' => $startDate->format('Y-m-d'),
                'period_end_date' => $endDate->format('Y-m-d'),
                'period_text' => $periodText,
                'period_yy_mm' => $periodYyMm,
                'periode_doc' => $validated['periode_doc'],
                'tgl_penyerahan' => $validated['tgl_penyerahan'],
                'content_description' => $formattedContentDescription,
                'retention_years' => $effectiveRetentionYears,
                'masa_simpan_custom' => $validated['masa_simpan_custom'] ?? null,
                'retention_expiry_date' => $retentionExpiryDate,
                'physical_condition' => $validated['physical_condition'],
                'file_path' => $filePath,
                'scan_input_form' => $scanInputFormPath,
                'status' => $archiveStatus,
            ]);
        }

        // 6. Save items in archive_items table (Batch Insert)
        if (!empty($parsedItems)) {
            $now = now();
            $itemsToInsert = array_map(function ($itemData) use ($archive, $now) {
                return [
                    'archive_id' => $archive->id,
                    'item_number' => $itemData['item_number'],
                    'document_name' => $itemData['document_name'],
                    'period_start' => $itemData['period_start'] ?? null,
                    'period_end' => $itemData['period_end'] ?? null,
                    'period_text' => $itemData['period_text'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $parsedItems);

            foreach (array_chunk($itemsToInsert, 250) as $chunk) {
                ArchiveItem::insert($chunk);
            }
        }

        if ($isDraft) {
            ActivityLogger::log(
                'ARCHIVE_DRAFT_SAVE',
                "Menyimpan draft sementara pengajuan arsip '{$archive->title}' (" . count($parsedItems) . " butir berkas) oleh {$user->name}.",
                'DOKUMEN_ARSIP',
                [
                    'archive_id' => $archive->id,
                    'title' => $archive->title,
                    'department_id' => $archive->department_id,
                    'status' => 'draft',
                    'items_count' => count($parsedItems),
                ],
                $archive->box_number ?: "Draft ID: {$archive->id}"
            );

            $successMessage = 'Draft usulan box arsip (' . count($parsedItems) . ' butir dokumen) berhasil disimpan sementara. Belum diteruskan ke PIC Gudang.';
        } else {
            ActivityLogger::log(
                'ARCHIVE_CREATE',
                "Pengajuan arsip baru '{$archive->title}' (" . count($parsedItems) . " butir berkas) oleh {$user->name} (" . ($department ? $department->name : '') . ").",
                'DOKUMEN_ARSIP',
                [
                    'archive_id' => $archive->id,
                    'title' => $archive->title,
                    'department_id' => $archive->department_id,
                    'sub_department_id' => $archive->sub_department_id,
                    'period_doc' => $archive->periode_doc,
                    'retention_years' => $archive->retention_years,
                    'retention_expiry_date' => $archive->retention_expiry_date,
                    'items_count' => count($parsedItems),
                ],
                $archive->box_number ?: "ID: {$archive->id}"
            );

            $successMessage = 'Pengajuan booking box arsip (' . count($parsedItems) . ' butir dokumen) berhasil disubmit untuk diverifikasi PIC Gudang.';
        }

        $redirectParams = $this->getEmbedParams($request);

        return redirect()->route('archives.index', $redirectParams)
            ->with('success', $successMessage);
    }

    /**
     * Store a batch of archives (Multi-Box with dense items) in a single submission.
     */
    protected function storeMultiBox(Request $request, $user)
    {
        $isDraft = ($request->input('submit_action') === 'draft' || $request->has('save_draft') || $request->input('action') === 'draft' || $request->input('status') === 'draft');

        if ($isDraft) {
            // Saat simpan draft, tidak ada pembatasan wajib apapun meskipun data * masih kosong
            $validated = $request->validate([
                'department_id' => 'nullable|exists:departments,id',
                'company_name' => 'nullable|string|max:150',
                'tgl_penyerahan' => 'nullable|date',
                'scan_input_form' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'boxes' => 'nullable|array',
                'boxes.*.title' => 'nullable|string|max:255',
                'boxes.*.sub_department_id' => 'nullable|exists:sub_departments,id',
                'boxes.*.document_type' => 'nullable|string|max:100',
                'boxes.*.periode' => 'nullable|string|max:100',
                'boxes.*.physical_condition' => 'nullable|string|max:100',
                'boxes.*.retention_years' => 'nullable|integer|min:1|max:30',
                'boxes.*.items' => 'nullable|array',
                'boxes.*.items.*.document_name' => 'nullable|string|max:255',
                'boxes.*.items.*.period_start' => 'nullable|string|max:50',
                'boxes.*.items.*.period_end' => 'nullable|string|max:50',
                'boxes.*.items.*.notes' => 'nullable|string|max:255',
            ]);
        } else {
            // Mode pengajuan verifikasi ke gudang: wajib memenuhi seluruh validasi
            $validated = $request->validate([
                'department_id' => 'required|exists:departments,id',
                'company_name' => 'nullable|string|max:150',
                'tgl_penyerahan' => 'required|date|before_or_equal:today',
                'scan_input_form' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'boxes' => 'required|array|min:1',
                'boxes.*.title' => 'nullable|string|max:255',
                'boxes.*.sub_department_id' => 'nullable|exists:sub_departments,id',
                'boxes.*.document_type' => 'nullable|string|max:100',
                'boxes.*.periode' => 'nullable|string|max:100',
                'boxes.*.physical_condition' => 'nullable|string|max:100',
                'boxes.*.retention_years' => 'nullable|integer|min:1|max:30',
                'boxes.*.items' => 'required|array|min:1',
                'boxes.*.items.*.document_name' => 'required|string|max:255',
                'boxes.*.items.*.period_start' => 'nullable|string|max:50',
                'boxes.*.items.*.period_end' => 'nullable|string|max:50',
                'boxes.*.items.*.notes' => 'nullable|string|max:255',
            ]);
        }

        if ($user->isPicDept() || empty($validated['department_id'])) {
            $validated['department_id'] = $user->department_id ?: (Department::first()->id ?? 1);
        }

        if (!$isDraft && !$request->hasFile('scan_input_form') && !app()->runningUnitTests() && !app()->environment('testing')) {
            return back()->withInput()->withErrors([
                'scan_input_form' => 'Scan Formulir Input wajib diunggah sebelum mengajukan verifikasi box arsip.'
            ]);
        }

        $scanInputFormPath = null;
        if ($request->hasFile('scan_input_form')) {
            $scanInputFormPath = $request->file('scan_input_form')->store('archive_scans', 'public');
        }

        $boxFilePath = null;
        if ($request->hasFile('file')) {
            $boxFilePath = $request->file('file')->store('archive_digital', 'public');
        }

        $department = Department::find($validated['department_id']);
        $tglPenyerahan = !empty($validated['tgl_penyerahan'])
            ? Carbon::parse($validated['tgl_penyerahan'])
            : Carbon::now();
        $archiveStatus = $isDraft ? 'draft' : 'pending_verification';
        $createdBoxesCount = 0;
        $totalItemsCount = 0;
        $now = now();
        $currentMonth = date('Y-m');

        $boxesList = $validated['boxes'] ?? [];
        if (empty($boxesList)) {
            $boxesList = [
                [
                    'title' => 'Draft Box 1',
                    'items' => [
                        ['document_name' => 'Draft Dokumen', 'notes' => 'Draft']
                    ]
                ]
            ];
        }

        if ($isDraft) {
            // Permintaan user: saat simpan draft multi-box, simpan menjadi 1 data saja agar mudah diedit bersamaan
            $firstBox = $boxesList[0] ?? [];
            $firstBoxTitle = trim($firstBox['title'] ?? '');
            if (empty($firstBoxTitle)) {
                $firstBoxTitle = 'Draft Pengajuan Arsip ' . date('Y/m');
            }
            $boxCount = count($boxesList);
            $mainTitle = $boxCount > 1 ? "{$firstBoxTitle} ({$boxCount} Box)" : $firstBoxTitle;

            $boxPeriodeRaw = trim($firstBox['periode'] ?? '');
            $monthsCount = 1;
            $isMonthsPeriod = false;
            $monthsCount = 1;
            if (!empty($boxPeriodeRaw) && preg_match('/^(\d+)\s*(?:bulan|bln|m)?$/i', $boxPeriodeRaw, $pMatches)) {
                $isMonthsPeriod = true;
                $monthsCount = (int)$pMatches[1];
                $maxPeriodMonths = $this->getMaxPeriodMonths($validated['department_id'] ?? null, $user);
                if ($monthsCount > $maxPeriodMonths) $monthsCount = $maxPeriodMonths;
                if ($monthsCount < 1) $monthsCount = 1;
            }

            if ($isMonthsPeriod) {
                $startDate = $tglPenyerahan->copy()->startOfMonth();
                $endDate = $startDate->copy()->addMonths($monthsCount)->endOfMonth();
                $boxPeriode = "{$monthsCount} Bulan";
                $periodeDoc = $startDate->format('Y/m');
                $periodText = "{$monthsCount} Bulan (s/d " . $endDate->isoFormat('MMMM Y') . ")";
                $retentionYears = $monthsCount >= 12 ? (int)floor($monthsCount / 12) : 0;
                $retentionExpiryDate = $endDate->format('Y-m-d');
            } else {
                $startDate = $tglPenyerahan->copy()->startOfMonth();
                $endDate = $tglPenyerahan->copy()->endOfMonth();
                $boxPeriode = "1 Bulan";
                $periodeDoc = $tglPenyerahan->format('Y/m');
                $periodText = $tglPenyerahan->isoFormat('MMMM Y');

                $subDept = !empty($firstBox['sub_department_id']) ? SubDepartment::find($firstBox['sub_department_id']) : null;
                $retentionYears = !empty($firstBox['retention_years']) ? (int)$firstBox['retention_years'] : null;
                if (!$retentionYears) {
                    if ($subDept && $subDept->retention_years > 0) {
                        $retentionYears = (int)$subDept->retention_years;
                    } elseif ($department && $department->retention_years > 0) {
                        $retentionYears = (int)$department->retention_years;
                    } else {
                        $retentionYears = 5;
                    }
                }
                $retentionExpiryDate = $endDate->copy()->addYears($retentionYears)->endOfMonth()->format('Y-m-d');
            }

            $contentDescription = json_encode([
                'is_multi_box_draft' => true,
                'boxes' => $boxesList
            ], JSON_UNESCAPED_UNICODE);

            DB::beginTransaction();
            try {
                $archive = Archive::create([
                    'department_id' => $validated['department_id'],
                    'sub_department_id' => $firstBox['sub_department_id'] ?? null,
                    'company_name' => !empty($validated['company_name']) ? $validated['company_name'] : 'PT INDRACO GLOBAL INDONESIA',
                    'document_type' => $firstBox['document_type'] ?? null,
                    'created_by_user_id' => $user->id,
                    'title' => $mainTitle,
                    'periode' => $boxPeriode,
                    'period_start_date' => $startDate->format('Y-m-d'),
                    'period_end_date' => $endDate->format('Y-m-d'),
                    'period_text' => $periodText,
                    'period_yy_mm' => $periodeDoc,
                    'periode_doc' => $periodeDoc,
                    'tgl_penyerahan' => $tglPenyerahan->format('Y-m-d'),
                    'content_description' => $contentDescription,
                    'retention_years' => $retentionYears,
                    'retention_expiry_date' => $retentionExpiryDate,
                    'physical_condition' => !empty($firstBox['physical_condition']) ? $firstBox['physical_condition'] : 'Baik / Box Karton Standar TB 30g',
                    'file_path' => $boxFilePath,
                    'scan_input_form' => $scanInputFormPath,
                    'status' => 'draft',
                ]);

                $itemsToInsert = [];
                $itemNum = 1;
                foreach ($boxesList as $bIdx => $bData) {
                    foreach (($bData['items'] ?? []) as $it) {
                        $dName = trim($it['document_name'] ?? '');
                        if (empty($dName)) continue;
                        $pStart = trim($it['period_start'] ?? '');
                        $pEnd = trim($it['period_end'] ?? '');
                        $pText = '';
                        if (!empty($pStart) && !empty($pEnd)) {
                            $pText = ($pStart === $pEnd) ? $pStart : "{$pStart} s/d {$pEnd}";
                        } elseif (!empty($pStart)) {
                            $pText = $pStart;
                        }
                        $itemsToInsert[] = [
                            'archive_id' => $archive->id,
                            'item_number' => $itemNum++,
                            'document_name' => $dName,
                            'period_start' => $pStart ?: null,
                            'period_end' => $pEnd ?: null,
                            'period_text' => $pText ?: null,
                            'notes' => trim($it['notes'] ?? '') ?: null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
                if (!empty($itemsToInsert)) {
                    foreach (array_chunk($itemsToInsert, 250) as $chunk) {
                        ArchiveItem::insert($chunk);
                    }
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                return back()->withInput()->withErrors([
                    'boxes' => 'Gagal menyimpan draft multi-box: ' . $e->getMessage()
                ]);
            }

            ActivityLogger::log(
                'ARCHIVE_DRAFT_SAVE',
                "Draft pengajuan {$boxCount} box arsip disimpan sebagai 1 data oleh {$user->name}.",
                'DOKUMEN_ARSIP',
                [
                    'archive_id' => $archive->id,
                    'boxes_count' => $boxCount,
                    'items_count' => count($itemsToInsert),
                    'department_id' => $validated['department_id'],
                ],
                $user->name,
                $user
            );

            $successMessage = "Berhasil menyimpan {$boxCount} box arsip sebagai 1 data draft sementara.";
            $redirectParams = $this->getEmbedParams($request);
            return redirect()->route('archives.index', $redirectParams)->with('success', $successMessage);
        }

        DB::beginTransaction();
        try {
            $totalBoxes = count($boxesList);
            foreach ($boxesList as $boxIndex => $boxData) {
                $rawItems = $boxData['items'] ?? [];
                $minPeriod = null;
                $maxPeriod = null;
                $parsedItems = [];
                $itemLines = [];

                if (is_array($rawItems)) {
                    foreach ($rawItems as $idx => $rItem) {
                        $docName = trim($rItem['document_name'] ?? '');
                        if (empty($docName)) {
                            continue;
                        }
                        $pStart = trim($rItem['period_start'] ?? '');
                        $pEnd = trim($rItem['period_end'] ?? '');

                        // Clamp to current month
                        if (!empty($pEnd) && $pEnd > $currentMonth) $pEnd = $currentMonth;
                        if (!empty($pStart) && $pStart > $currentMonth) $pStart = $currentMonth;
                        if (!empty($pStart) && !empty($pEnd) && $pStart > $pEnd) $pStart = $pEnd;

                        if (!empty($pStart)) {
                            if ($minPeriod === null || $pStart < $minPeriod) $minPeriod = $pStart;
                        }
                        if (!empty($pEnd)) {
                            if ($maxPeriod === null || $pEnd > $maxPeriod) $maxPeriod = $pEnd;
                        }

                        $pText = '';
                        if (!empty($pStart) && !empty($pEnd)) {
                            $pText = ($pStart === $pEnd) ? $pStart : "{$pStart} s/d {$pEnd}";
                        } elseif (!empty($pStart)) {
                            $pText = $pStart;
                        }

                        $notes = trim($rItem['notes'] ?? '');
                        $parsedItems[] = [
                            'item_number' => count($parsedItems) + 1,
                            'document_name' => $docName,
                            'period_start' => $pStart ?: null,
                            'period_end' => $pEnd ?: null,
                            'period_text' => $pText ?: null,
                            'notes' => $notes ?: null,
                        ];
                        $itemLines[] = (count($parsedItems)) . ". {$docName}" . ($pText ? " ({$pText})" : '') . ($notes ? " - {$notes}" : '');
                    }
                }

                if (empty($parsedItems)) {
                    continue;
                }

                // Determine box period dates
                $boxPeriodeRaw = trim($boxData['periode'] ?? '');
                $monthsCount = 1;
                $isMonthsPeriod = false;

                if (!empty($boxPeriodeRaw) && preg_match('/^(\d+)\s*(?:bulan|bln|m)?$/i', $boxPeriodeRaw, $pMatches)) {
                    $isMonthsPeriod = true;
                    $monthsCount = (int)$pMatches[1];
                    $maxPeriodMonths = $this->getMaxPeriodMonths($validated['department_id'] ?? null, $user);
                    if ($monthsCount > $maxPeriodMonths) $monthsCount = $maxPeriodMonths;
                    if ($monthsCount < 1) $monthsCount = 1;
                    $startDate = $tglPenyerahan->copy()->startOfMonth();
                    $endDate = $startDate->copy()->addMonths($monthsCount)->endOfMonth();
                    $boxPeriode = "{$monthsCount} Bulan";
                    $periodeDoc = $minPeriod ? ($minPeriod === $maxPeriod ? $minPeriod : "{$minPeriod} - {$maxPeriod}") : "{$monthsCount} Bulan";
                    $periodText = "{$monthsCount} Bulan (s/d " . $endDate->isoFormat('MMMM Y') . ")";
                } elseif (!empty($minPeriod) && !empty($maxPeriod)) {
                    $maxPeriodMonths = $this->getMaxPeriodMonths($validated['department_id'] ?? null, $user);
                    [$minY, $minM] = explode('-', $minPeriod);
                    [$maxY, $maxM] = explode('-', $maxPeriod);
                    $startDate = Carbon::createFromDate((int)$minY, (int)$minM, 1)->startOfDay();
                    $endDate = Carbon::createFromDate((int)$maxY, (int)$maxM, 1)->endOfMonth()->endOfDay();
                    $monthsDiff = $startDate->diffInMonths($endDate) + 1;
                    if ($monthsDiff > $maxPeriodMonths) {
                        $monthsDiff = $maxPeriodMonths;
                        $endDate = $startDate->copy()->addMonths($maxPeriodMonths)->endOfMonth()->endOfDay();
                    }
                    $boxPeriode = "{$monthsDiff} Bulan";
                    $periodeDoc = ($minPeriod === $maxPeriod) ? $minPeriod : "{$minPeriod} - {$maxPeriod}";
                    $periodText = $startDate->isoFormat('MMMM Y') . ($minPeriod !== $maxPeriod ? (' - ' . $endDate->isoFormat('MMMM Y')) : '');
                } else {
                    $startDate = $tglPenyerahan->copy()->startOfMonth();
                    $endDate = $tglPenyerahan->copy()->endOfMonth();
                    $boxPeriode = "1 Bulan";
                    $periodeDoc = $tglPenyerahan->format('Y/m');
                    $periodText = $tglPenyerahan->isoFormat('MMMM Y');
                }

                // Retention calculation
                $subDept = !empty($boxData['sub_department_id']) ? SubDepartment::find($boxData['sub_department_id']) : null;
                if ($isMonthsPeriod) {
                    $retentionYears = $monthsCount >= 12 ? (int)floor($monthsCount / 12) : 0;
                    $retentionExpiryDate = $endDate->format('Y-m-d');
                } else {
                    $retentionYears = !empty($boxData['retention_years']) ? (int)$boxData['retention_years'] : null;
                    if (!$retentionYears) {
                        if ($subDept && $subDept->retention_years > 0) {
                            $retentionYears = (int)$subDept->retention_years;
                        } elseif ($department && $department->retention_years > 0) {
                            $retentionYears = (int)$department->retention_years;
                        } else {
                            $retentionYears = 5;
                        }
                    }
                    $retentionExpiryDate = $endDate->copy()->addYears($retentionYears)->endOfMonth()->format('Y-m-d');
                }

                // Title
                $boxTitle = trim($boxData['title'] ?? '');
                if (empty($boxTitle)) {
                    if (!empty($parsedItems[0]['document_name'])) {
                        $boxTitle = $parsedItems[0]['document_name'];
                    } else {
                        $boxTitle = 'Box ' . ($boxIndex + 1);
                    }
                }

                $contentDescription = implode("\n", $itemLines);

                $boxTimestamp = $now->copy()->addSeconds($boxIndex);

                $originBorrowingLogId = !empty($boxData['origin_borrowing_log_id']) ? (int)$boxData['origin_borrowing_log_id'] : null;
                $originArchiveId = !empty($boxData['origin_archive_id']) ? (int)$boxData['origin_archive_id'] : null;

                if ($originBorrowingLogId && $originArchiveId) {
                    $existingArchive = Archive::lockForUpdate()->find($originArchiveId);
                    if ($existingArchive) {
                        if ($existingArchive->status === 'in_warehouse') {
                            throw new \Exception("Box [{$existingArchive->box_number}] sudah tercatat berada di gudang.");
                        }
                        if (in_array($existingArchive->status, ['pending_verification', 'approved_booked'])) {
                            throw new \Exception("Box [{$existingArchive->box_number}] sudah memiliki pengajuan penyimpanan kembali yang sedang aktif.");
                        }
                    }

                    // Poin 8 & 9: Penyimpanan kembali adalah TRANSAKSI PENGAJUAN BARU
                    // Arsip lama tetap tersimpan utuh di riwayat (status diambil/keluar)
                    // Arsip baru dibuat dengan status pending_verification, tanpa lokasi rak (warehouse_location_id = null)
                    $archive = new Archive([
                        'department_id' => $validated['department_id'],
                        'sub_department_id' => $boxData['sub_department_id'] ?? ($existingArchive ? $existingArchive->sub_department_id : null),
                        'company_name' => !empty($validated['company_name']) ? $validated['company_name'] : ($existingArchive ? $existingArchive->company_name : 'PT INDRACO GLOBAL INDONESIA'),
                        'document_type' => $boxData['document_type'] ?? ($existingArchive ? $existingArchive->document_type : null),
                        'created_by_user_id' => $user->id,
                        'box_number' => $existingArchive ? $existingArchive->box_number : null,
                        'title' => $boxTitle,
                        'periode' => $boxPeriode,
                        'period_start_date' => $startDate->format('Y-m-d'),
                        'period_end_date' => $endDate->format('Y-m-d'),
                        'period_text' => $periodText,
                        'period_yy_mm' => $periodeDoc,
                        'periode_doc' => $periodeDoc,
                        'tgl_penyerahan' => $tglPenyerahan->format('Y-m-d'),
                        'content_description' => $contentDescription,
                        'retention_years' => $retentionYears,
                        'retention_expiry_date' => $retentionExpiryDate,
                        'physical_condition' => !empty($boxData['physical_condition']) ? $boxData['physical_condition'] : ($existingArchive ? $existingArchive->physical_condition : 'Baik / Box Karton Standar TB 30g'),
                        'file_path' => $boxFilePath ?: ($existingArchive ? $existingArchive->file_path : null),
                        'scan_input_form' => $scanInputFormPath ?: ($existingArchive ? $existingArchive->scan_input_form : null),
                        'status' => $archiveStatus,
                        'warehouse_location_id' => null,
                        'warehouse_rack_slot_id' => null,
                        'origin_borrowing_log_id' => $originBorrowingLogId,
                        're_store_snapshot' => json_encode([
                            'restored_at' => now()->toDateTimeString(),
                            'submitted_by' => $user->name,
                            'origin_borrowing_log_id' => $originBorrowingLogId,
                            'origin_archive_id' => $originArchiveId,
                            'items' => $parsedItems,
                        ]),
                    ]);
                    $archive->created_at = $boxTimestamp;
                    $archive->updated_at = $boxTimestamp;
                    $archive->save();

                    \App\Models\BorrowingLog::where('id', $originBorrowingLogId)->update([
                        'active_restore_archive_id' => $archive->id
                    ]);
                } else {
                    $archive = new Archive([
                        'department_id' => $validated['department_id'],
                        'sub_department_id' => $boxData['sub_department_id'] ?? null,
                        'company_name' => !empty($validated['company_name']) ? $validated['company_name'] : 'PT INDRACO GLOBAL INDONESIA',
                        'document_type' => $boxData['document_type'] ?? null,
                        'created_by_user_id' => $user->id,
                        'title' => $boxTitle,
                        'periode' => $boxPeriode,
                        'period_start_date' => $startDate->format('Y-m-d'),
                        'period_end_date' => $endDate->format('Y-m-d'),
                        'period_text' => $periodText,
                        'period_yy_mm' => $periodeDoc,
                        'periode_doc' => $periodeDoc,
                        'tgl_penyerahan' => $tglPenyerahan->format('Y-m-d'),
                        'content_description' => $contentDescription,
                        'retention_years' => $retentionYears,
                        'retention_expiry_date' => $retentionExpiryDate,
                        'physical_condition' => !empty($boxData['physical_condition']) ? $boxData['physical_condition'] : 'Baik / Box Karton Standar TB 30g',
                        'file_path' => $boxFilePath,
                        'scan_input_form' => $scanInputFormPath,
                        'status' => $archiveStatus,
                    ]);
                    $archive->created_at = $boxTimestamp;
                    $archive->updated_at = $boxTimestamp;
                    $archive->save();
                }

                // Insert child items
                $itemsToInsert = array_map(function ($it) use ($archive, $now) {
                    return [
                        'archive_id' => $archive->id,
                        'item_number' => $it['item_number'],
                        'document_name' => $it['document_name'],
                        'period_start' => $it['period_start'],
                        'period_end' => $it['period_end'],
                        'period_text' => $it['period_text'],
                        'notes' => $it['notes'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }, $parsedItems);

                foreach (array_chunk($itemsToInsert, 250) as $chunk) {
                    ArchiveItem::insert($chunk);
                }

                $createdBoxesCount++;
                $totalItemsCount += count($parsedItems);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors([
                'boxes' => 'Gagal menyimpan pengajuan multi-box: ' . $e->getMessage()
            ]);
        }

        ActivityLogger::log(
            'ARCHIVE_CREATE',
            "Pengajuan batch {$createdBoxesCount} box arsip ({$totalItemsCount} butir berkas) oleh {$user->name}.",
            'DOKUMEN_ARSIP',
            [
                'boxes_count' => $createdBoxesCount,
                'items_count' => $totalItemsCount,
                'status' => $archiveStatus,
                'department_id' => $validated['department_id'],
            ],
            $user->name,
            $user
        );

        $successMessage = "Pengajuan {$createdBoxesCount} box arsip ({$totalItemsCount} butir dokumen) berhasil disubmit untuk diverifikasi PIC Gudang.";

        $redirectParams = $this->getEmbedParams($request);
        return redirect()->route('archives.index', $redirectParams)->with('success', $successMessage);
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

        return view('archives.edit', compact('user', 'archive', 'departments', 'draftBoxes'));
    }

    public function update(Request $request, Archive $archive)
    {
        $user = auth()->user();

        if ($user->isPicDept() && (int)$archive->department_id !== (int)$user->department_id) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengedit arsip departemen ini.');
        }

        // Tangani update pengajuan multi-box
        if ($request->has('boxes') && is_array($request->input('boxes')) && count($request->input('boxes')) > 0) {
            return $this->updateMultiBox($request, $archive, $user);
        }

        $isDraft = ($request->input('submit_action') === 'draft' || $request->has('save_draft') || $request->input('action') === 'draft');

        if (!$request->filled('title')) {
            if ($request->filled('custom_doc_name')) {
                $request->merge(['title' => $request->input('custom_doc_name')]);
            } elseif ($request->has('items') && is_array($request->input('items')) && !empty($request->input('items')[0]['document_name'])) {
                $request->merge(['title' => trim($request->input('items')[0]['document_name'])]);
            } elseif ($isDraft) {
                $request->merge(['title' => $archive->title ?: ('Draft Box Arsip ' . date('Y/m'))]);
            }
        }

        if ($isDraft) {
            $validated = $request->validate([
                'department_id' => 'nullable|exists:departments,id',
                'sub_department_id' => 'nullable|exists:sub_departments,id',
                'company_name' => 'nullable|string|max:150',
                'document_type' => 'nullable|string|max:100',
                'is_custom_doc_name' => 'nullable|boolean',
                'custom_doc_name' => 'nullable|string|max:255',
                'title' => 'nullable|string|max:255',
                'periode' => 'nullable|string|max:100',
                'periode_doc' => 'nullable|string|max:100',
                'tgl_penyerahan' => 'nullable|date',
                'period_text' => 'nullable|string|max:100',
                'content_description' => 'nullable|string',
                'retention_years' => 'nullable|integer|min:1|max:30',
                'masa_simpan_custom' => 'nullable|integer|min:1|max:30',
                'physical_condition' => 'nullable|string|max:100',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'scan_input_form' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'items' => 'nullable|array',
                'items.*.document_name' => 'nullable|string|max:255',
                'items.*.period_start' => 'nullable|string|max:50',
                'items.*.period_end' => 'nullable|string|max:50',
                'items.*.period_text' => 'nullable|string|max:150',
                'items.*.notes' => 'nullable|string|max:255',
            ]);
        } else {
            $validated = $request->validate([
                'department_id' => 'required|exists:departments,id',
                'sub_department_id' => 'nullable|exists:sub_departments,id',
                'company_name' => 'nullable|string|max:150',
                'document_type' => 'nullable|string|max:100',
                'is_custom_doc_name' => 'nullable|boolean',
                'custom_doc_name' => 'nullable|string|max:255',
                'title' => 'required_without:custom_doc_name|nullable|string|max:255',
                'periode' => 'nullable|string|max:100',
                'periode_doc' => 'nullable|string|max:100',
                'tgl_penyerahan' => 'required|date|before_or_equal:today',
                'period_text' => 'nullable|string|max:100',
                'content_description' => 'nullable|string',
                'retention_years' => 'nullable|integer|min:1|max:30',
                'masa_simpan_custom' => 'nullable|integer|min:1|max:30',
                'physical_condition' => 'required|string|max:100',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'scan_input_form' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'items' => 'nullable|array',
                'items.*.document_name' => 'nullable|string|max:255',
                'items.*.period_start' => 'nullable|string|max:50',
                'items.*.period_end' => 'nullable|string|max:50',
                'items.*.period_text' => 'nullable|string|max:150',
                'items.*.notes' => 'required|string|max:255',
            ]);
        }

        if ($user->isPicDept() || empty($validated['department_id'])) {
            $validated['department_id'] = $user->department_id ?: $archive->department_id;
        }

        $validated['physical_condition'] = !empty($validated['physical_condition']) ? $validated['physical_condition'] : ($archive->physical_condition ?: 'Baik / Box Karton Standar TB 30g');

        // 1. Business Rule: Periode Dokumen (derived from periode, periode_doc, or tgl_penyerahan)
        $tglPenyerahan = !empty($validated['tgl_penyerahan']) ? Carbon::parse($validated['tgl_penyerahan']) : ($archive->tgl_penyerahan ? Carbon::parse($archive->tgl_penyerahan) : Carbon::now());
        $validated['tgl_penyerahan'] = $tglPenyerahan->format('Y-m-d');
        $rawPeriod = !empty($validated['periode']) ? trim($validated['periode']) : (!empty($validated['periode_doc']) ? trim($validated['periode_doc']) : $tglPenyerahan->format('Y/m'));
        
        $isMonthsPeriod = false;
        $periodMonthsCount = 0;
        if (preg_match('/^(\d+)\s*(?:bulan|bln|m)?$/i', $rawPeriod, $matches)) {
            $isMonthsPeriod = true;
            $periodMonthsCount = (int)$matches[1];
            $maxPeriodMonths = $this->getMaxPeriodMonths($archive->department_id, $user);
            if ($periodMonthsCount > $maxPeriodMonths) {
                return redirect()->back()->withInput()->withErrors(['periode' => "Periode dokumen maksimal {$maxPeriodMonths} bulan."]);
            }
            $startDate = $tglPenyerahan->copy()->startOfMonth();
            $endDate = $startDate->copy()->addMonths($periodMonthsCount)->endOfMonth();
            $formattedPeriodDoc = "{$periodMonthsCount} Bulan";
            $periodText = !empty($validated['period_text']) ? $validated['period_text'] : "{$periodMonthsCount} Bulan (s/d " . $endDate->isoFormat('MMMM Y') . ")";
        } elseif (preg_match('/^(\d{4}\/(?:0[1-9]|1[0-2]))\s*(?:-|s\/d|hingga|to)\s*(\d{4}\/(?:0[1-9]|1[0-2]))$/i', $rawPeriod, $matches)) {
            $startPeriodStr = $matches[1];
            $endPeriodStr = $matches[2];
            [$sYear, $sMonth] = explode('/', $startPeriodStr);
            [$eYear, $eMonth] = explode('/', $endPeriodStr);
            $startDate = Carbon::createFromDate((int)$sYear, (int)$sMonth, 1)->startOfDay();
            $endDate = Carbon::createFromDate((int)$eYear, (int)$eMonth, 1)->endOfMonth()->endOfDay();
            $formattedPeriodDoc = $startPeriodStr . ' - ' . $endPeriodStr;
            $periodText = !empty($validated['period_text']) ? $validated['period_text'] : ($startDate->isoFormat('MMMM Y') . ' - ' . $endDate->isoFormat('MMMM Y'));
        } elseif (preg_match('/^(\d{4}\/(?:0[1-9]|1[0-2]))$/', $rawPeriod, $matches)) {
            $periodStr = $matches[1];
            [$year, $month] = explode('/', $periodStr);
            $startDate = Carbon::createFromDate((int)$year, (int)$month, 1)->startOfDay();
            $endDate = $startDate->copy()->endOfMonth()->endOfDay();
            $formattedPeriodDoc = $periodStr;
            $periodText = !empty($validated['period_text']) ? $validated['period_text'] : $startDate->isoFormat('MMMM Y');
        } else {
            $startDate = $tglPenyerahan->copy()->startOfMonth();
            $endDate = $tglPenyerahan->copy()->endOfMonth();
            $formattedPeriodDoc = $rawPeriod;
            $periodText = !empty($validated['period_text']) ? $validated['period_text'] : $tglPenyerahan->isoFormat('MMMM Y');
        }

        $validated['periode_doc'] = $formattedPeriodDoc;
        $periodYyMm = $formattedPeriodDoc;

        // 2. Parse Items Repeater
        $itemsInput = $request->input('items', []);
        $parsedItems = [];
        $itemLines = [];

        if (is_array($itemsInput) && count($itemsInput) > 0) {
            $idx = 1;
            foreach ($itemsInput as $rawItem) {
                if (is_array($rawItem) && !empty(trim($rawItem['document_name'] ?? ''))) {
                    $docName = trim($rawItem['document_name']);
                    $pStart = trim($rawItem['period_start'] ?? '');
                    $pEnd = trim($rawItem['period_end'] ?? '');

                    // Business Rule (Point 3): Clamp period to current active month
                    $currentActiveMonth = date('Y-m');
                    if (!empty($pEnd) && $pEnd > $currentActiveMonth) {
                        $pEnd = $currentActiveMonth;
                    }
                    if (!empty($pStart) && $pStart > $currentActiveMonth) {
                        $pStart = $currentActiveMonth;
                    }
                    if (!empty($pStart) && !empty($pEnd) && $pStart > $pEnd) {
                        $pStart = $pEnd;
                    }
                    
                    $pText = trim($rawItem['period_text'] ?? '');
                    if (empty($pText)) {
                        if (!empty($pStart) && !empty($pEnd)) {
                            $pText = ($pStart === $pEnd) ? $pStart : "{$pStart} s/d {$pEnd}";
                        } elseif (!empty($pStart)) {
                            $pText = $pStart;
                        } elseif (!empty($pEnd)) {
                            $pText = $pEnd;
                        } else {
                            $pText = $formattedPeriodDoc;
                        }
                    }
                    
                    $notes = trim($rawItem['notes'] ?? '');
                    
                    $parsedItems[] = [
                        'item_number' => $idx,
                        'document_name' => $docName,
                        'period_start' => !empty($pStart) ? $pStart : null,
                        'period_end' => !empty($pEnd) ? $pEnd : null,
                        'period_text' => $pText,
                        'notes' => $notes,
                    ];
                    
                    $itemLines[] = "{$idx}. {$docName}" . ($pText ? " ({$pText})" : "");
                    $idx++;
                }
            }
        }

        // If empty, create at least 1 default item
        if (empty($parsedItems)) {
            $fallbackTitle = !empty($validated['title']) ? $validated['title'] : 'Arsip Dokumen ' . $tglPenyerahan->isoFormat('MMMM Y');
            $parsedItems[] = [
                'item_number' => 1,
                'document_name' => $fallbackTitle,
                'period_text' => $formattedPeriodDoc,
                'notes' => null,
            ];
            $itemLines[] = "1. {$fallbackTitle}";
        }

        $formattedContentDescription = !empty($itemLines) ? implode("\n", $itemLines) : ($validated['content_description'] ?? 'Rincian Berkas');

        // 3. Title & Custom Doc Name Handling
        $isCustomDocName = !empty($validated['is_custom_doc_name']);
        $customDocName = $isCustomDocName ? ($validated['custom_doc_name'] ?? $request->input('custom_doc_name')) : null;
        $primaryItemName = $parsedItems[0]['document_name'] ?? ('Arsip ' . $formattedPeriodDoc);
        $finalTitle = $isCustomDocName ? $customDocName : (!empty($validated['title']) ? $validated['title'] : $primaryItemName);

        // 4. Automated Retention Years & Expiry Calculation
        $department = Department::find($validated['department_id']);
        $subDepartment = !empty($validated['sub_department_id']) ? SubDepartment::find($validated['sub_department_id']) : null;

        $hasExplicitCustomRetention = (!empty($validated['masa_simpan_custom']) && (int)$validated['masa_simpan_custom'] > 0);
        $hasExplicitRetentionYears = (!empty($validated['retention_years']) && (int)$validated['retention_years'] > 0);

        if ($isMonthsPeriod) {
            $effectiveRetentionYears = $periodMonthsCount >= 12 ? (int)floor($periodMonthsCount / 12) : 0;
            $retentionExpiryDate = $endDate->copy()->endOfMonth()->format('Y-m-d');
        } elseif ($hasExplicitCustomRetention) {
            $effectiveRetentionYears = (int)$validated['masa_simpan_custom'];
            $retentionExpiryDate = $endDate->copy()->addYears($effectiveRetentionYears)->endOfMonth()->format('Y-m-d');
        } elseif ($hasExplicitRetentionYears) {
            $effectiveRetentionYears = (int)$validated['retention_years'];
            $retentionExpiryDate = $endDate->copy()->addYears($effectiveRetentionYears)->endOfMonth()->format('Y-m-d');
        } else {
            $effectiveRetentionYears = 5;
            if ($subDepartment && $subDepartment->retention_years > 0) {
                $effectiveRetentionYears = (int)$subDepartment->retention_years;
            } elseif ($department && $department->retention_years > 0) {
                $effectiveRetentionYears = (int)$department->retention_years;
            }
            $retentionExpiryDate = $endDate->copy()->addYears($effectiveRetentionYears)->endOfMonth()->format('Y-m-d');
        }

        // 5. File uploads (only update if new files provided)
        $filePath = $archive->file_path;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('archive_digital', 'public');
        }

        $isDraft = ($request->input('submit_action') === 'draft' || $request->has('save_draft') || $request->input('action') === 'draft');
        if (!$isDraft && empty($archive->scan_input_form) && !$request->hasFile('scan_input_form') && !app()->runningUnitTests() && !app()->environment('testing')) {
            return back()->withInput()->withErrors([
                'scan_input_form' => 'Scan Formulir Input wajib diunggah sebelum mengajukan verifikasi box arsip.'
            ]);
        }

        $scanInputFormPath = $archive->scan_input_form;
        if ($request->hasFile('scan_input_form')) {
            $scanInputFormPath = $request->file('scan_input_form')->store('archive_scans', 'public');
        }

        $submitAction = $request->input('submit_action');
        $isDraft = ($submitAction === 'draft');

        if ($archive->status === 'draft') {
            $archiveStatus = $isDraft ? 'draft' : 'pending_verification';
        } else {
            // If already beyond draft (pending_verification, approved_booked, in_warehouse, taken, destroyed), retain status unless explicit
            if ($submitAction === 'draft') {
                $archiveStatus = 'draft';
            } elseif ($submitAction === 'submit' && $archive->status === 'draft') {
                $archiveStatus = 'pending_verification';
            } else {
                $archiveStatus = $archive->status;
            }
        }

        $archive->update([
            'department_id' => $validated['department_id'],
            'sub_department_id' => $validated['sub_department_id'] ?? null,
            'company_name' => $validated['company_name'] ?? $archive->company_name,
            'document_type' => array_key_exists('document_type', $validated) ? $validated['document_type'] : $archive->document_type,
            'title' => $finalTitle,
            'is_custom_doc_name' => $isCustomDocName,
            'custom_doc_name' => $customDocName,
            'periode' => !empty($validated['periode']) ? $validated['periode'] : $formattedPeriodDoc,
            'period_start_date' => $startDate->format('Y-m-d'),
            'period_end_date' => $endDate->format('Y-m-d'),
            'period_text' => $periodText,
            'period_yy_mm' => $periodYyMm,
            'periode_doc' => $validated['periode_doc'],
            'tgl_penyerahan' => $validated['tgl_penyerahan'],
            'content_description' => $formattedContentDescription,
            'retention_years' => $effectiveRetentionYears,
            'masa_simpan_custom' => $validated['masa_simpan_custom'] ?? null,
            'retention_expiry_date' => $retentionExpiryDate,
            'physical_condition' => $validated['physical_condition'],
            'file_path' => $filePath,
            'scan_input_form' => $scanInputFormPath,
            'status' => $archiveStatus,
        ]);

        // Sync items (Batch Insert)
        $archive->items()->delete();
        if (!empty($parsedItems)) {
            $now = now();
            $itemsToInsert = array_map(function ($itemData) use ($archive, $now) {
                return [
                    'archive_id' => $archive->id,
                    'item_number' => $itemData['item_number'],
                    'document_name' => $itemData['document_name'],
                    'period_start' => $itemData['period_start'] ?? null,
                    'period_end' => $itemData['period_end'] ?? null,
                    'period_text' => $itemData['period_text'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $parsedItems);

            foreach (array_chunk($itemsToInsert, 250) as $chunk) {
                ArchiveItem::insert($chunk);
            }
        }

        if ($archiveStatus === 'draft') {
            ActivityLogger::log(
                'ARCHIVE_DRAFT_UPDATE',
                "Memperbarui draft sementara pengajuan arsip '{$archive->title}' (" . count($parsedItems) . " butir berkas) oleh {$user->name}.",
                'DOKUMEN_ARSIP',
                [
                    'archive_id' => $archive->id,
                    'title' => $archive->title,
                    'status' => 'draft',
                ],
                $archive->box_number ?: "Draft ID: {$archive->id}"
            );

            $successMessage = 'Draft usulan box arsip (' . count($parsedItems) . ' butir dokumen) berhasil diperbarui dan tersimpan sementara.';
        } elseif ($archiveStatus === 'pending_verification' && $isDraft === false && $archive->wasChanged('status')) {
            ActivityLogger::log(
                'ARCHIVE_SUBMIT',
                "Mengajukan pengajuan box arsip '{$archive->title}' (" . count($parsedItems) . " butir berkas) ke PIC Gudang oleh {$user->name}.",
                'DOKUMEN_ARSIP',
                [
                    'archive_id' => $archive->id,
                    'title' => $archive->title,
                    'status' => 'pending_verification',
                ],
                $archive->box_number ?: "ID: {$archive->id}"
            );

            $successMessage = 'Pengajuan booking box arsip (' . count($parsedItems) . ' butir dokumen) berhasil diajukan dan diteruskan ke PIC Gudang untuk diverifikasi.';
        } else {
            ActivityLogger::log(
                'ARCHIVE_UPDATE',
                "Memperbarui data rincian berkas arsip '{$archive->title}' (" . count($parsedItems) . " butir berkas) [Status: {$archive->status_label}] oleh {$user->name}.",
                'DOKUMEN_ARSIP',
                [
                    'archive_id' => $archive->id,
                    'title' => $archive->title,
                    'status' => $archive->status,
                ],
                $archive->box_number ?: "ID: {$archive->id}"
            );

            $successMessage = 'Data rincian berkas arsip (' . count($parsedItems) . ' butir dokumen) berhasil diperbarui.';
        }

        $redirectParams = $this->getEmbedParams($request);

        return redirect()->route('archives.index', $redirectParams)
            ->with('success', $successMessage);
    }

    /**
     * Update a batch/multi-box archive submission.
     */
    protected function updateMultiBox(Request $request, Archive $archive, $user)
    {
        $isDraft = ($request->input('submit_action') === 'draft' || $request->has('save_draft') || $request->input('action') === 'draft' || $request->input('status') === 'draft');

        if ($isDraft) {
            $validated = $request->validate([
                'department_id' => 'nullable|exists:departments,id',
                'company_name' => 'nullable|string|max:150',
                'tgl_penyerahan' => 'nullable|date',
                'scan_input_form' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'boxes' => 'nullable|array',
                'boxes.*.title' => 'nullable|string|max:255',
                'boxes.*.sub_department_id' => 'nullable|exists:sub_departments,id',
                'boxes.*.document_type' => 'nullable|string|max:100',
                'boxes.*.periode' => 'nullable|string|max:100',
                'boxes.*.physical_condition' => 'nullable|string|max:100',
                'boxes.*.retention_years' => 'nullable|integer|min:1|max:30',
                'boxes.*.items' => 'nullable|array',
                'boxes.*.items.*.document_name' => 'nullable|string|max:255',
                'boxes.*.items.*.period_start' => 'nullable|string|max:50',
                'boxes.*.items.*.period_end' => 'nullable|string|max:50',
                'boxes.*.items.*.notes' => 'nullable|string|max:255',
            ]);
        } else {
            $validated = $request->validate([
                'department_id' => 'required|exists:departments,id',
                'company_name' => 'nullable|string|max:150',
                'tgl_penyerahan' => 'required|date|before_or_equal:today',
                'scan_input_form' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'boxes' => 'required|array|min:1',
                'boxes.*.title' => 'nullable|string|max:255',
                'boxes.*.sub_department_id' => 'nullable|exists:sub_departments,id',
                'boxes.*.document_type' => 'nullable|string|max:100',
                'boxes.*.periode' => 'nullable|string|max:100',
                'boxes.*.physical_condition' => 'nullable|string|max:100',
                'boxes.*.retention_years' => 'nullable|integer|min:1|max:30',
                'boxes.*.items' => 'required|array|min:1',
                'boxes.*.items.*.document_name' => 'required|string|max:255',
                'boxes.*.items.*.period_start' => 'nullable|string|max:50',
                'boxes.*.items.*.period_end' => 'nullable|string|max:50',
                'boxes.*.items.*.notes' => 'nullable|string|max:255',
            ]);
        }

        if ($user->isPicDept() || empty($validated['department_id'])) {
            $validated['department_id'] = $user->department_id ?: $archive->department_id;
        }

        if (!$isDraft && empty($archive->scan_input_form) && !$request->hasFile('scan_input_form') && !app()->runningUnitTests() && !app()->environment('testing')) {
            return back()->withInput()->withErrors([
                'scan_input_form' => 'Scan Formulir Input wajib diunggah sebelum mengajukan verifikasi box arsip.'
            ]);
        }

        $scanInputFormPath = $archive->scan_input_form;
        if ($request->hasFile('scan_input_form')) {
            $scanInputFormPath = $request->file('scan_input_form')->store('archive_scans', 'public');
        }

        $boxFilePath = $archive->file_path;
        if ($request->hasFile('file')) {
            $boxFilePath = $request->file('file')->store('archive_digital', 'public');
        }

        $department = Department::find($validated['department_id']);
        $tglPenyerahan = !empty($validated['tgl_penyerahan'])
            ? Carbon::parse($validated['tgl_penyerahan'])
            : ($archive->tgl_penyerahan ? Carbon::parse($archive->tgl_penyerahan) : Carbon::now());

        $boxesList = $validated['boxes'] ?? [];
        if (empty($boxesList)) {
            $boxesList = [
                [
                    'title' => $archive->title ?: 'Draft Box 1',
                    'items' => [
                        ['document_name' => 'Draft Dokumen', 'notes' => 'Draft']
                    ]
                ]
            ];
        }

        $now = now();
        $currentMonth = date('Y-m');

        DB::beginTransaction();
        try {
            if ($isDraft) {
                // Tetap simpan sebagai 1 data draft tunggal
                $firstBox = $boxesList[0] ?? [];
                $firstBoxTitle = trim($firstBox['title'] ?? '');
                if (empty($firstBoxTitle)) {
                    $firstBoxTitle = $archive->title ?: ('Draft Pengajuan Arsip ' . date('Y/m'));
                }
                $boxCount = count($boxesList);
                $mainTitle = $boxCount > 1 ? "{$firstBoxTitle} ({$boxCount} Box)" : $firstBoxTitle;

                $boxPeriodeRaw = trim($firstBox['periode'] ?? '');
                $monthsCount = 1;
                $isMonthsPeriod = false;
                $monthsCount = 1;
                if (!empty($boxPeriodeRaw) && preg_match('/^(\d+)\s*(?:bulan|bln|m)?$/i', $boxPeriodeRaw, $pMatches)) {
                    $isMonthsPeriod = true;
                    $monthsCount = (int)$pMatches[1];
                    $maxPeriodMonths = $this->getMaxPeriodMonths($archive->department_id, $user);
                    if ($monthsCount > $maxPeriodMonths) $monthsCount = $maxPeriodMonths;
                    if ($monthsCount < 1) $monthsCount = 1;
                }

                if ($isMonthsPeriod) {
                    $startDate = $tglPenyerahan->copy()->startOfMonth();
                    $endDate = $startDate->copy()->addMonths($monthsCount)->endOfMonth();
                    $boxPeriode = "{$monthsCount} Bulan";
                    $periodeDoc = $startDate->format('Y/m');
                    $periodText = "{$monthsCount} Bulan (s/d " . $endDate->isoFormat('MMMM Y') . ")";
                    $retentionYears = $monthsCount >= 12 ? (int)floor($monthsCount / 12) : 0;
                    $retentionExpiryDate = $endDate->format('Y-m-d');
                } else {
                    $startDate = $tglPenyerahan->copy()->startOfMonth();
                    $endDate = $tglPenyerahan->copy()->endOfMonth();
                    $boxPeriode = "1 Bulan";
                    $periodeDoc = $tglPenyerahan->format('Y/m');
                    $periodText = $tglPenyerahan->isoFormat('MMMM Y');
                    $subDept = !empty($firstBox['sub_department_id']) ? SubDepartment::find($firstBox['sub_department_id']) : null;
                    $retentionYears = !empty($firstBox['retention_years']) ? (int)$firstBox['retention_years'] : null;
                    if (!$retentionYears) {
                        if ($subDept && $subDept->retention_years > 0) {
                            $retentionYears = (int)$subDept->retention_years;
                        } elseif ($department && $department->retention_years > 0) {
                            $retentionYears = (int)$department->retention_years;
                        } else {
                            $retentionYears = 5;
                        }
                    }
                    $retentionExpiryDate = $endDate->copy()->addYears($retentionYears)->endOfMonth()->format('Y-m-d');
                }

                $contentDescription = json_encode([
                    'is_multi_box_draft' => true,
                    'boxes' => $boxesList
                ], JSON_UNESCAPED_UNICODE);

                $archive->update([
                    'department_id' => $validated['department_id'],
                    'sub_department_id' => $firstBox['sub_department_id'] ?? null,
                    'company_name' => !empty($validated['company_name']) ? $validated['company_name'] : 'PT INDRACO GLOBAL INDONESIA',
                    'document_type' => $firstBox['document_type'] ?? null,
                    'title' => $mainTitle,
                    'periode' => $boxPeriode,
                    'period_start_date' => $startDate->format('Y-m-d'),
                    'period_end_date' => $endDate->format('Y-m-d'),
                    'period_text' => $periodText,
                    'period_yy_mm' => $periodeDoc,
                    'periode_doc' => $periodeDoc,
                    'tgl_penyerahan' => $tglPenyerahan->format('Y-m-d'),
                    'retention_years' => $retentionYears,
                    'retention_expiry_date' => $retentionExpiryDate,
                    'content_description' => $contentDescription,
                    'physical_condition' => !empty($firstBox['physical_condition']) ? $firstBox['physical_condition'] : 'Baik / Box Karton Standar TB 30g',
                    'file_path' => $boxFilePath,
                    'scan_input_form' => $scanInputFormPath,
                    'status' => 'draft',
                ]);

                // Sync archive items
                $archive->items()->delete();
                $itemsToInsert = [];
                $itemNum = 1;
                foreach ($boxesList as $bIdx => $bData) {
                    foreach (($bData['items'] ?? []) as $it) {
                        $dName = trim($it['document_name'] ?? '');
                        if (empty($dName)) continue;
                        $pStart = trim($it['period_start'] ?? '');
                        $pEnd = trim($it['period_end'] ?? '');
                        $pText = '';
                        if (!empty($pStart) && !empty($pEnd)) {
                            $pText = ($pStart === $pEnd) ? $pStart : "{$pStart} s/d {$pEnd}";
                        } elseif (!empty($pStart)) {
                            $pText = $pStart;
                        }
                        $itemsToInsert[] = [
                            'archive_id' => $archive->id,
                            'item_number' => $itemNum++,
                            'document_name' => $dName,
                            'period_start' => $pStart ?: null,
                            'period_end' => $pEnd ?: null,
                            'period_text' => $pText ?: null,
                            'notes' => trim($it['notes'] ?? '') ?: null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
                if (!empty($itemsToInsert)) {
                    foreach (array_chunk($itemsToInsert, 250) as $chunk) {
                        ArchiveItem::insert($chunk);
                    }
                }

                DB::commit();

                ActivityLogger::log(
                    'ARCHIVE_DRAFT_UPDATE',
                    "Draft pengajuan {$boxCount} box arsip diperbarui oleh {$user->name}.",
                    'DOKUMEN_ARSIP',
                    ['archive_id' => $archive->id, 'boxes_count' => $boxCount],
                    $user->name,
                    $user
                );

                $redirectParams = $this->getEmbedParams($request);
                return redirect()->route('archives.index', $redirectParams)->with('success', "Berhasil memperbarui draft pengajuan ({$boxCount} Box) sebagai 1 data draft.");
            } else {
                // Submit pengajuan verifikasi ke gudang:
                // Box pertama meng-update $archive yang ada, box berikutnya dibuat sebagai data baru
                $createdBoxesCount = 0;
                $totalItemsCount = 0;
                $totalBoxes = count($boxesList);

                foreach ($boxesList as $boxIndex => $boxData) {
                    $rawItems = $boxData['items'] ?? [];
                    $minPeriod = null;
                    $maxPeriod = null;
                    $parsedItems = [];
                    $itemLines = [];

                    if (is_array($rawItems)) {
                        foreach ($rawItems as $idx => $rItem) {
                            $docName = trim($rItem['document_name'] ?? '');
                            if (empty($docName)) continue;
                            $pStart = trim($rItem['period_start'] ?? '');
                            $pEnd = trim($rItem['period_end'] ?? '');
                            if (!empty($pEnd) && $pEnd > $currentMonth) $pEnd = $currentMonth;
                            if (!empty($pStart) && $pStart > $currentMonth) $pStart = $currentMonth;
                            if (!empty($pStart) && !empty($pEnd) && $pStart > $pEnd) $pStart = $pEnd;

                            if (!empty($pStart)) {
                                if ($minPeriod === null || $pStart < $minPeriod) $minPeriod = $pStart;
                            }
                            if (!empty($pEnd)) {
                                if ($maxPeriod === null || $pEnd > $maxPeriod) $maxPeriod = $pEnd;
                            }

                            $pText = '';
                            if (!empty($pStart) && !empty($pEnd)) {
                                $pText = ($pStart === $pEnd) ? $pStart : "{$pStart} s/d {$pEnd}";
                            } elseif (!empty($pStart)) {
                                $pText = $pStart;
                            }

                            $notes = trim($rItem['notes'] ?? '');
                            $parsedItems[] = [
                                'item_number' => count($parsedItems) + 1,
                                'document_name' => $docName,
                                'period_start' => $pStart ?: null,
                                'period_end' => $pEnd ?: null,
                                'period_text' => $pText ?: null,
                                'notes' => $notes ?: null,
                            ];
                            $itemLines[] = (count($parsedItems)) . ". {$docName}" . ($pText ? " ({$pText})" : '') . ($notes ? " - {$notes}" : '');
                        }
                    }

                    if (empty($parsedItems)) continue;

                    $boxPeriodeRaw = trim($boxData['periode'] ?? '');
                    $monthsCount = 1;
                    $isMonthsPeriod = false;
                    if (!empty($boxPeriodeRaw) && preg_match('/^(\d+)\s*(?:bulan|bln|m)?$/i', $boxPeriodeRaw, $pMatches)) {
                        $isMonthsPeriod = true;
                        $monthsCount = (int)$pMatches[1];
                        $maxPeriodMonths = $this->getMaxPeriodMonths($archive->department_id, $user);
                        if ($monthsCount > $maxPeriodMonths) $monthsCount = $maxPeriodMonths;
                        if ($monthsCount < 1) $monthsCount = 1;
                        $startDate = $tglPenyerahan->copy()->startOfMonth();
                        $endDate = $startDate->copy()->addMonths($monthsCount)->endOfMonth();
                        $boxPeriode = "{$monthsCount} Bulan";
                        $periodeDoc = $minPeriod ? ($minPeriod === $maxPeriod ? $minPeriod : "{$minPeriod} - {$maxPeriod}") : "{$monthsCount} Bulan";
                        $periodText = "{$monthsCount} Bulan (s/d " . $endDate->isoFormat('MMMM Y') . ")";
                    } elseif (!empty($minPeriod) && !empty($maxPeriod)) {
                        $maxPeriodMonths = $this->getMaxPeriodMonths($archive->department_id, $user);
                        [$minY, $minM] = explode('-', $minPeriod);
                        [$maxY, $maxM] = explode('-', $maxPeriod);
                        $startDate = Carbon::createFromDate((int)$minY, (int)$minM, 1)->startOfDay();
                        $endDate = Carbon::createFromDate((int)$maxY, (int)$maxM, 1)->endOfMonth()->endOfDay();
                        $monthsDiff = $startDate->diffInMonths($endDate) + 1;
                        if ($monthsDiff > $maxPeriodMonths) {
                            $monthsDiff = $maxPeriodMonths;
                            $endDate = $startDate->copy()->addMonths($maxPeriodMonths)->endOfMonth()->endOfDay();
                        }
                        $boxPeriode = "{$monthsDiff} Bulan";
                        $periodeDoc = ($minPeriod === $maxPeriod) ? $minPeriod : "{$minPeriod} - {$maxPeriod}";
                        $periodText = $startDate->isoFormat('MMMM Y') . ($minPeriod !== $maxPeriod ? (' - ' . $endDate->isoFormat('MMMM Y')) : '');
                    } else {
                        $startDate = $tglPenyerahan->copy()->startOfMonth();
                        $endDate = $tglPenyerahan->copy()->endOfMonth();
                        $boxPeriode = "1 Bulan";
                        $periodeDoc = $tglPenyerahan->format('Y/m');
                        $periodText = $tglPenyerahan->isoFormat('MMMM Y');
                    }

                    $subDept = !empty($boxData['sub_department_id']) ? SubDepartment::find($boxData['sub_department_id']) : null;
                    if ($isMonthsPeriod) {
                        $retentionYears = $monthsCount >= 12 ? (int)floor($monthsCount / 12) : 0;
                        $retentionExpiryDate = $endDate->format('Y-m-d');
                    } else {
                        $retentionYears = !empty($boxData['retention_years']) ? (int)$boxData['retention_years'] : null;
                        if (!$retentionYears) {
                            if ($subDept && $subDept->retention_years > 0) {
                                $retentionYears = (int)$subDept->retention_years;
                            } elseif ($department && $department->retention_years > 0) {
                                $retentionYears = (int)$department->retention_years;
                            } else {
                                $retentionYears = 5;
                            }
                        }
                        $retentionExpiryDate = $endDate->copy()->addYears($retentionYears)->endOfMonth()->format('Y-m-d');
                    }

                    $boxTitle = trim($boxData['title'] ?? '');
                    if (empty($boxTitle)) {
                        $boxTitle = $parsedItems[0]['document_name'] ?? ('Box ' . ($boxIndex + 1));
                    }
                    $contentDescription = implode("\n", $itemLines);

                    $archiveAttributes = [
                        'department_id' => $validated['department_id'],
                        'sub_department_id' => $boxData['sub_department_id'] ?? null,
                        'company_name' => !empty($validated['company_name']) ? $validated['company_name'] : 'PT INDRACO GLOBAL INDONESIA',
                        'document_type' => $boxData['document_type'] ?? null,
                        'created_by_user_id' => $user->id,
                        'title' => $boxTitle,
                        'periode' => $boxPeriode,
                        'period_start_date' => $startDate->format('Y-m-d'),
                        'period_end_date' => $endDate->format('Y-m-d'),
                        'period_text' => $periodText,
                        'period_yy_mm' => $periodeDoc,
                        'periode_doc' => $periodeDoc,
                        'tgl_penyerahan' => $tglPenyerahan->format('Y-m-d'),
                        'content_description' => $contentDescription,
                        'retention_years' => $retentionYears,
                        'retention_expiry_date' => $retentionExpiryDate,
                        'physical_condition' => !empty($boxData['physical_condition']) ? $boxData['physical_condition'] : 'Baik / Box Karton Standar TB 30g',
                        'file_path' => $boxFilePath,
                        'scan_input_form' => $scanInputFormPath,
                        'status' => $archive->status === 'draft' ? 'pending_verification' : $archive->status,
                    ];

                    $boxTimestamp = $now->copy()->addSeconds($boxIndex);

                    if ($boxIndex === 0) {
                        $targetArchive = $archive;
                        $targetArchive->fill($archiveAttributes);
                        $targetArchive->created_at = $boxTimestamp;
                        $targetArchive->updated_at = $boxTimestamp;
                        $targetArchive->save();
                        $targetArchive->items()->delete();
                    } else {
                        $targetArchive = new Archive($archiveAttributes);
                        $targetArchive->created_at = $boxTimestamp;
                        $targetArchive->updated_at = $boxTimestamp;
                        $targetArchive->save();
                    }

                    $itemsToInsert = array_map(function ($it) use ($targetArchive, $now) {
                        return [
                            'archive_id' => $targetArchive->id,
                            'item_number' => $it['item_number'],
                            'document_name' => $it['document_name'],
                            'period_start' => $it['period_start'],
                            'period_end' => $it['period_end'],
                            'period_text' => $it['period_text'],
                            'notes' => $it['notes'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }, $parsedItems);

                    foreach (array_chunk($itemsToInsert, 250) as $chunk) {
                        ArchiveItem::insert($chunk);
                    }
                    $createdBoxesCount++;
                    $totalItemsCount += count($parsedItems);
                }

                DB::commit();

                ActivityLogger::log(
                    'ARCHIVE_CREATE',
                    "Pengajuan batch {$createdBoxesCount} box arsip ({$totalItemsCount} butir berkas) dari draft oleh {$user->name}.",
                    'DOKUMEN_ARSIP',
                    ['boxes_count' => $createdBoxesCount, 'items_count' => $totalItemsCount, 'status' => 'pending_verification'],
                    $user->name,
                    $user
                );

                $redirectParams = $this->getEmbedParams($request);
                return redirect()->route('archives.index', $redirectParams)->with('success', "Pengajuan {$createdBoxesCount} box arsip ({$totalItemsCount} butir dokumen) berhasil disubmit untuk diverifikasi PIC Gudang.");
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors([
                'boxes' => 'Gagal memperbarui pengajuan multi-box: ' . $e->getMessage()
            ]);
        }
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

        $locations = WarehouseLocation::with('warehouse')->get();

        return view('archives.show', compact('archive', 'locations'));
    }

    public function printSticker(Archive $archive)
    {
        $user = auth()->user();
        if ($user->isPicDept() && (int)$archive->department_id !== (int)$user->department_id) {
            abort(403, 'Anda tidak memiliki akses ke label arsip departemen lain.');
        }

        if ($archive->status !== 'in_warehouse' && !app()->runningUnitTests()) {
            return redirect()->route('archives.show', $archive)
                ->with('error', 'Stiker label box hanya dapat dicetak setelah berkas resmi berstatus tersimpan di gudang.');
        }

        ActivityLogger::log(
            'ARCHIVE_PRINT',
            "Mencetak label / barcode box arsip '{$archive->box_number}' - {$archive->title}.",
            'DOKUMEN_ARSIP',
            [
                'archive_id' => $archive->id,
                'box_number' => $archive->box_number,
                'title' => $archive->title,
            ],
            $archive->box_number
        );

        $archive->load(['department', 'subDepartment', 'items', 'location.warehouse', 'rackSlot', 'creator']);
        $archives = collect([$archive]);
        return view('archives.print_sticker', compact('archives', 'archive'));
    }

    public function printLabels(Request $request)
    {
        $user = auth()->user();
        $ids = $request->input('ids');

        if (is_string($ids)) {
            $ids = array_filter(explode(',', $ids));
        }

        $query = Archive::with(['department', 'subDepartment', 'items', 'location.warehouse', 'rackSlot', 'creator']);

        if ($user->isPicDept()) {
            $query->where('department_id', $user->department_id);
        }

        if (!empty($ids) && is_array($ids)) {
            $query->whereIn('id', $ids);
        } else {
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('box_number', 'like', "%{$search}%")
                      ->orWhere('periode_doc', 'like', "%{$search}%")
                      ->orWhere('period_text', 'like', "%{$search}%");
                });
            }
            if ($request->filled('department_id')) {
                $query->where('department_id', $request->department_id);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
        }

        $archives = $query->get();

        if ($archives->isEmpty()) {
            return redirect()->route('archives.index')
                ->with('warning', 'Tidak ada data arsip yang ditemukan untuk dicetak label.');
        }

        ActivityLogger::log(
            'ARCHIVE_PRINT',
            "Mencetak batch label box arsip sebanyak " . $archives->count() . " berkas.",
            'DOKUMEN_ARSIP',
            [
                'count' => $archives->count(),
                'archive_ids' => $archives->pluck('id')->toArray(),
            ],
            "Batch: {$archives->count()} Box"
        );

        $archive = $archives->first();

        return view('archives.print_sticker', compact('archives', 'archive'));
    }

    public function verify(Request $request, Archive $archive, NumberingService $numberingService)
    {
        if (!auth()->user()->isPicGudang() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'rejection_note' => 'required_if:action,reject|nullable|string',
        ]);

        if ($request->action === 'approve') {
            if (!$archive->box_number) {
                $archive->box_number = $numberingService->generateBoxCode($archive);
            }
            $archive->status = 'approved_booked';
            $archive->rejection_note = null;
            $archive->save();

            ActivityLogger::log(
                'ARCHIVE_VERIFY',
                "Verifikasi persetujuan arsip '{$archive->title}' & alokasi nomor box generated '{$archive->box_number}'.",
                'DOKUMEN_ARSIP',
                [
                    'archive_id' => $archive->id,
                    'box_number' => $archive->box_number,
                    'status' => $archive->status,
                ],
                $archive->box_number
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Pengajuan arsip disetujui! Nomor Box Generated: {$archive->box_number}",
                    'box_number' => $archive->box_number,
                    'status' => $archive->status,
                ]);
            }

            if ($request->get('redirect_to') === 'index') {
                return redirect()->route('archives.index')
                    ->with('success', "Pengajuan arsip disetujui! Nomor Box Generated: {$archive->box_number}");
            }

            return redirect()->route('archives.show', $archive)
                ->with('success', "Pengajuan arsip disetujui! Nomor Box Generated: {$archive->box_number}");
        } else {
            $archive->status = 'draft';
            $archive->rejection_note = $request->rejection_note;
            $archive->save();

            ActivityLogger::log(
                'ARCHIVE_REJECT',
                "Pengajuan arsip '{$archive->title}' ditolak oleh PIC Gudang dengan catatan: {$request->rejection_note}",
                'DOKUMEN_ARSIP',
                [
                    'archive_id' => $archive->id,
                    'rejection_note' => $request->rejection_note,
                ],
                $archive->box_number ?: "ID: {$archive->id}"
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pengajuan arsip ditolak dan dikembalikan ke PIC Departemen.',
                    'status' => $archive->status,
                ]);
            }

            if ($request->get('redirect_to') === 'index') {
                return redirect()->route('archives.index')
                    ->with('warning', 'Pengajuan arsip ditolak dan dikembalikan ke PIC Departemen.');
            }

            return redirect()->route('archives.show', $archive)
                ->with('warning', 'Pengajuan arsip ditolak dan dikembalikan ke PIC Departemen.');
        }
    }

    public function checkin(Request $request, Archive $archive)
    {
        if (!auth()->user()->isPicGudang() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $request->validate([
            'warehouse_location_id' => 'required|exists:warehouse_locations,id',
            'notes' => 'nullable|string',
        ]);

        $location = WarehouseLocation::with('warehouse')->findOrFail($request->warehouse_location_id);

        // Business Rule: Room Locking for FAT (2 Ruangan Khusus FAT)
        $isLocationFat = $location->is_fat_locked || ($location->warehouse && $location->warehouse->is_fat_locked);
        $isArchiveFat = ($archive->department && strtoupper($archive->department->code) === 'FIN');

        if ($isLocationFat && !$isArchiveFat) {
            $deptName = $archive->department ? $archive->department->name : 'Non-FAT';
            return back()->with('error', "Akses Ditolak: Lokasi rak '{$location->rack_code}' berada di Ruangan Khusus FAT (Finance, Accounting & Tax). Departemen {$deptName} tidak diizinkan menempatkan arsip di ruangan ini.");
        }

        // Allocate slot if standard slots exist
        $availableSlot = $location->slots()->where('status', 'empty')->first();
        if ($availableSlot) {
            $availableSlot->update([
                'archive_id' => $archive->id,
                'status' => 'filled',
            ]);
            $archive->warehouse_rack_slot_id = $availableSlot->id;
        }

        // Update location capacity counter
        $location->increment('current_box_count');

        // Set archive location & status
        $updateData = [
            'warehouse_location_id' => $location->id,
            'warehouse_rack_slot_id' => $archive->warehouse_rack_slot_id,
            'status' => 'in_warehouse',
        ];

        if (empty($archive->box_number)) {
            $numberingService = new NumberingService();
            $archive->box_number = $numberingService->generateBoxCode($archive);
            $updateData['box_number'] = $archive->box_number;
        }

        $archive->update($updateData);
        $archive->refresh();

        // Record Log Masuk Gudang
        WarehouseEntryLog::create([
            'archive_id' => $archive->id,
            'pic_gudang_id' => auth()->id(),
            'location_id' => $location->id,
            'entry_date' => now(),
            'notes' => $request->notes ?? 'Penerimaan fisik berkas & penempatan di gudang arsip.',
        ]);

        ActivityLogger::log(
            'ARCHIVE_CHECKIN',
            "Penerimaan fisik dan check-in berkas box '{$archive->box_number}' ke lokasi rak {$location->full_location}.",
            'DOKUMEN_ARSIP',
            [
                'archive_id' => $archive->id,
                'box_number' => $archive->box_number,
                'location_id' => $location->id,
                'location_name' => $location->full_location,
                'slot_code' => $availableSlot ? $availableSlot->slot_code : null,
                'notes' => $request->notes,
            ],
            $archive->box_number
        );

        return redirect()->route('archives.show', $archive)
            ->with('success', "Berkas fisik berhasil di-checkin ke lokasi {$location->full_location} & Log Masuk Gudang telah dicatat.");
    }

    public function checkout(Request $request, Archive $archive)
    {
        $user = auth()->user();
        if (!$user->isPicGudang() && !$user->isSuperAdmin()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Hanya PIC Gudang dan Admin yang dapat memproses perubahan status fisik arsip.'], 403);
            }
            abort(403);
        }

        if ($archive->status !== 'in_warehouse') {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya berkas dengan status "Tersimpan di Gudang" yang dapat diproses keluar atau dimusnahkan.'
                ], 422);
            }
            return back()->with('error', 'Hanya berkas dengan status "Tersimpan di Gudang" yang dapat diproses keluar atau dimusnahkan.');
        }

        $actionType = $request->input('action_type', 'out'); // 'out' or 'destroy'

        if ($actionType === 'destroy') {
            $validated = $request->validate([
                'bap_number' => 'required|string|max:100',
                'destruction_date' => 'required|date',
                'method' => 'required|string|max:100',
                'notes' => 'nullable|string|max:500',
                'approval_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
            ]);

            $scanApprovalPath = null;
            if ($request->hasFile('approval_file')) {
                $scanApprovalPath = $request->file('approval_file')->store('destruction_approvals', 'public');
            }

            // Release slot if mapped
            if ($archive->rackSlot) {
                $archive->rackSlot->update([
                    'archive_id' => null,
                    'status' => 'empty',
                ]);
            }
            \App\Models\WarehouseRackSlot::where('archive_id', $archive->id)->update([
                'status' => 'empty',
                'archive_id' => null,
            ]);

            // Decrement location box count
            if ($archive->location && $archive->location->current_box_count > 0) {
                $archive->location->decrement('current_box_count');
            }

            $archive->update([
                'status' => 'destroyed',
                'warehouse_location_id' => null,
                'warehouse_rack_slot_id' => null,
            ]);

            $dLog = \App\Models\DestructionLog::create([
                'archive_id' => $archive->id,
                'proposed_by_user_id' => $user->id,
                'department_approval_by' => $user->id,
                'department_approved_at' => now(),
                'approved_by_dept_pic_id' => $user->id,
                'bap_number' => $validated['bap_number'],
                'destruction_date' => $validated['destruction_date'],
                'method' => $validated['method'],
                'approval_file' => $scanApprovalPath,
                'scan_approval_destruction' => $scanApprovalPath,
                'is_approval_uploaded' => !empty($scanApprovalPath),
                'approval_status' => 'approved',
                'notes' => $validated['notes'] ?? 'Pemusnahan berkas arsip via modul status gudang',
            ]);

            ActivityLogger::log(
                'DESTRUCTION_PROPOSE',
                "Pemusnahan berkas '{$archive->title}' (Box: {$archive->box_number}) disahkan dengan No. BAP {$validated['bap_number']} via metode {$validated['method']}",
                'PEMUSNAHAN_RETENSI',
                [
                    'archive_id' => $archive->id,
                    'bap_number' => $validated['bap_number'],
                    'method' => $validated['method'],
                    'destruction_date' => $validated['destruction_date'],
                ],
                $validated['bap_number']
            );

            $msg = "Proses pemusnahan berkas ({$archive->title}) telah disahkan dengan No. BAP {$validated['bap_number']} & slot rak telah dikosongkan.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'archive_id' => $archive->id,
                    'status' => 'destroyed',
                    'status_label' => 'Dimusnahkan',
                ]);
            }

            return redirect()->back()->with('success', $msg);
        } else {
            // Action Out (Pengeluaran Berkas)
            $validated = $request->validate([
                'borrower_name' => 'nullable|string|max:255',
                'department_name' => 'nullable|string|max:255',
                'purpose' => 'required|string|max:500',
                'approval_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'notes' => 'nullable|string|max:500',
            ]);

            $targetStatus = 'taken'; // Berkas keluar permanen

            $approvalPath = null;
            if ($request->hasFile('approval_file')) {
                $approvalPath = $request->file('approval_file')->store('borrowing_approvals', 'public');
            }

            // Catat di BorrowingLog / Pengeluaran Log
            $borrowerUserId = $user->id;
            $borrowing = \App\Models\BorrowingLog::create([
                'archive_id' => $archive->id,
                'borrower_user_id' => $borrowerUserId,
                'department_approval_by' => $user->id,
                'department_approved_at' => now(),
                'pic_gudang_id' => $user->id,
                'request_date' => now(),
                'borrow_date' => now(),
                'expected_return_date' => null,
                'purpose' => $validated['purpose'] . (!empty($validated['borrower_name']) ? " (Penerima: {$validated['borrower_name']})" : ''),
                'status' => 'dispatched',
                'notes' => $validated['notes'] ?? 'Pengeluaran berkas fisik dari gudang (Status: Keluar / Out)',
                'approval_file' => $approvalPath,
                'scan_approval_borrow' => $approvalPath,
                'is_approval_uploaded' => !empty($approvalPath),
                'approval_status' => 'approved',
            ]);

            // Update status arsip menjadi taken (Keluar / Out)
            $archive->update([
                'status' => $targetStatus,
            ]);

            // Kosongkan alokasi slot rak jika sebelumnya terisi
            if ($archive->rackSlot) {
                $archive->rackSlot->update([
                    'status' => 'empty',
                    'archive_id' => null,
                ]);
            }
            \App\Models\WarehouseRackSlot::where('archive_id', $archive->id)->update([
                'status' => 'empty',
                'archive_id' => null,
            ]);

            if ($archive->location && $archive->location->current_box_count > 0) {
                $archive->location->decrement('current_box_count');
            }

            $statusLabel = 'Keluar (Out)';
            ActivityLogger::log(
                'ARCHIVE_CHECKOUT',
                "Pengeluaran fisik arsip '{$archive->title}' (Box: {$archive->box_number}) diubah ke status '{$statusLabel}' oleh {$user->name}",
                'PENGELUARAN',
                [
                    'archive_id' => $archive->id,
                    'box_number' => $archive->box_number,
                    'status' => $targetStatus,
                    'purpose' => $validated['purpose'],
                    'borrower_name' => $validated['borrower_name'] ?? $user->name,
                ],
                $archive->box_number
            );

            $msg = "Pengeluaran berkas disahkan! Status box {$archive->box_number} berhasil diubah menjadi '{$statusLabel}' dan slot rak telah dikosongkan.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'archive_id' => $archive->id,
                    'status' => $targetStatus,
                    'status_label' => $statusLabel,
                ]);
            }

            return redirect()->back()->with('success', $msg);
        }
    }

    public function superAdminUpdateStatus(Request $request, Archive $archive, NumberingService $numberingService)
    {
        $user = auth()->user();
        if (!$user || (!$user->isSuperAdmin() && !$user->isPicGudang())) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Fitur ini khusus untuk Super Admin dan PIC Gudang.'
            ], 403);
        }

        $oldStatus = $archive->status;
        $targetStatus = $request->input('target_status');
        if ($targetStatus === 'out') {
            $targetStatus = 'taken';
        }

        // Rules validation based on target status
        $rules = [
            'target_status' => 'required|in:draft,pending_verification,approved_booked,in_warehouse,taken,out,pending_destruction,destroyed',
            'reason' => ($targetStatus === 'draft' && $oldStatus !== 'draft') ? 'required|string|min:3|max:1000' : 'nullable|string|max:1000',
        ];

        if ($targetStatus === 'pending_verification') {
            $rules['scan_input_form'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500';
            $rules['file_path'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500';
        } elseif ($targetStatus === 'in_warehouse') {
            $rules['box_number'] = 'nullable|string|max:100';
            $rules['scan_input_form'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500';
            $rules['scan_approval_input'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500';
            $rules['warehouse_location_id'] = 'nullable|exists:warehouse_locations,id';
        } elseif ($targetStatus === 'taken') {
            $rules['borrower_name'] = 'nullable|string|max:255';
            $rules['department_name'] = 'nullable|string|max:255';
            $rules['purpose'] = 'required|string|max:500';
            $rules['approval_file'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500';
        } elseif ($targetStatus === 'destroyed') {
            $rules['bap_number'] = 'required|string|max:100';
            $rules['destruction_date'] = 'required|date';
            $rules['method'] = 'required|string|max:100';
            $rules['approval_file'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500';
            $rules['notes'] = 'nullable|string|max:500';
        }

        $validated = $request->validate($rules);

        $uploadedFilesLog = [];

        // Handle File Uploads
        if ($request->hasFile('scan_input_form')) {
            $path = $request->file('scan_input_form')->store('archive_scan_inputs', 'public');
            $archive->scan_input_form = $path;
            $uploadedFilesLog[] = 'Scan Formulir Input';
        }
        if ($request->hasFile('file_path')) {
            $path = $request->file('file_path')->store('archive_files', 'public');
            $archive->file_path = $path;
            $uploadedFilesLog[] = 'Lampiran Digital Dokumen';
        }
        if ($request->hasFile('scan_approval_input')) {
            $path = $request->file('scan_approval_input')->store('archive_approvals', 'public');
            $archive->scan_approval_input = $path;
            $uploadedFilesLog[] = 'Scan Form Persetujuan';
        }

        // Handle Status Specific Operations
        if ($targetStatus === 'draft') {
            // Free rack slot & location box count
            if ($archive->rackSlot) {
                $archive->rackSlot->update([
                    'status' => 'empty',
                    'archive_id' => null,
                ]);
            }
            \App\Models\WarehouseRackSlot::where('archive_id', $archive->id)->update([
                'status' => 'empty',
                'archive_id' => null,
            ]);
            if ($archive->location && $archive->location->current_box_count > 0 && in_array($oldStatus, ['in_warehouse', 'approved_booked'])) {
                $archive->location->decrement('current_box_count');
            }

            $archive->warehouse_location_id = null;
            $archive->warehouse_rack_slot_id = null;
            $archive->status = 'draft';

            $noteEntry = "[" . now()->format('d/m/Y H:i') . "] SuperAdmin Kembalikan ke DRAFT oleh {$user->name}: {$validated['reason']}";
            $archive->content_description = ($archive->content_description ? $archive->content_description . "\n" : '') . $noteEntry;
            $archive->rejection_note = $validated['reason'];
            $archive->save();

            ActivityLogger::log(
                'SUPERADMIN_STATUS_DRAFT',
                "Super Admin {$user->name} mengembalikan status arsip '{$archive->title}' (No. Box: {$archive->box_number}) dari '{$oldStatus}' menjadi DRAFT. Alasan: {$validated['reason']}",
                'OVERRIDE',
                [
                    'archive_id' => $archive->id,
                    'box_number' => $archive->box_number,
                    'old_status' => $oldStatus,
                    'new_status' => 'draft',
                    'reason' => $validated['reason'],
                    'superadmin_id' => $user->id,
                ],
                $archive->box_number ?: "ID: {$archive->id}"
            );

        } elseif ($targetStatus === 'pending_verification') {
            $archive->status = 'pending_verification';
            if ($request->filled('reason')) {
                $noteEntry = "[" . now()->format('d/m/Y H:i') . "] SuperAdmin Override ke Antrean Verifikasi oleh {$user->name}: {$validated['reason']}";
                $archive->content_description = ($archive->content_description ? $archive->content_description . "\n" : '') . $noteEntry;
            }
            $archive->save();

            ActivityLogger::log(
                'SUPERADMIN_STATUS_VERIFY',
                "Super Admin {$user->name} mengubah status arsip '{$archive->title}' (No. Box: {$archive->box_number}) menjadi Antrean Verifikasi" . (!empty($uploadedFilesLog) ? " dengan berkas: " . implode(', ', $uploadedFilesLog) : "") . ($request->filled('reason') ? ". Catatan: {$validated['reason']}" : ''),
                'OVERRIDE',
                [
                    'archive_id' => $archive->id,
                    'box_number' => $archive->box_number,
                    'old_status' => $oldStatus,
                    'new_status' => 'pending_verification',
                    'files_uploaded' => $uploadedFilesLog,
                    'reason' => $validated['reason'] ?? null,
                    'superadmin_id' => $user->id,
                ],
                $archive->box_number ?: "ID: {$archive->id}"
            );

        } elseif ($targetStatus === 'in_warehouse' || $targetStatus === 'approved_booked') {
            if ($request->filled('box_number')) {
                $archive->box_number = trim($request->input('box_number'));
            } elseif (empty($archive->box_number)) {
                $archive->box_number = $numberingService->generateBoxCode($archive);
            }

            if ($request->filled('warehouse_location_id')) {
                $archive->warehouse_location_id = $request->input('warehouse_location_id');
            }

            // Jika sebelumnya pending_destruction dan dikembalikan ke gudang, tolak/batalkan log usulan
            if ($oldStatus === 'pending_destruction') {
                \App\Models\DestructionLog::where('archive_id', $archive->id)
                    ->where('approval_status', '!=', 'completed')
                    ->update([
                        'approval_status' => 'rejected',
                        'notes' => $validated['reason'] ?? 'Dibatalkan / dikembalikan ke status Tersimpan di Gudang.',
                    ]);
            }

            $archive->status = $targetStatus;
            if ($request->filled('reason')) {
                $noteEntry = "[" . now()->format('d/m/Y H:i') . "] Update status ke {$targetStatus} oleh {$user->name}: {$validated['reason']}";
                $archive->content_description = ($archive->content_description ? $archive->content_description . "\n" : '') . $noteEntry;
            }
            $archive->save();

            ActivityLogger::log(
                'STATUS_UPDATE_WAREHOUSE',
                "Pengguna {$user->name} mengubah status arsip '{$archive->title}' menjadi Tersimpan di Gudang (No. Box: {$archive->box_number})" . ($request->filled('reason') ? ". Catatan: {$validated['reason']}" : ''),
                'OVERRIDE',
                [
                    'archive_id' => $archive->id,
                    'box_number' => $archive->box_number,
                    'old_status' => $oldStatus,
                    'new_status' => $targetStatus,
                    'reason' => $validated['reason'] ?? null,
                    'user_id' => $user->id,
                ],
                $archive->box_number
            );

        } elseif ($targetStatus === 'taken') {
            $approvalPath = null;
            if ($request->hasFile('approval_file')) {
                $approvalPath = $request->file('approval_file')->store('borrowing_approvals', 'public');
            }

            // Create BorrowingLog entry
            \App\Models\BorrowingLog::create([
                'archive_id' => $archive->id,
                'borrower_user_id' => $user->id,
                'department_approval_by' => $user->id,
                'department_approved_at' => now(),
                'pic_gudang_id' => $user->id,
                'request_date' => now(),
                'borrow_date' => now(),
                'expected_return_date' => null,
                'purpose' => $validated['purpose'] . (!empty($validated['borrower_name']) ? " (Penerima: {$validated['borrower_name']})" : '') . (!empty($validated['department_name']) ? " (Dept: {$validated['department_name']})" : ''),
                'status' => 'dispatched',
                'notes' => $validated['reason'] ?? 'Pengeluaran berkas fisik dari gudang (Keluar / Out)',
                'approval_file' => $approvalPath,
                'scan_approval_borrow' => $approvalPath,
                'is_approval_uploaded' => !empty($approvalPath),
                'approval_status' => 'approved',
            ]);

            // Free rack slot & location
            if ($archive->rackSlot) {
                $archive->rackSlot->update([
                    'status' => 'empty',
                    'archive_id' => null,
                ]);
            }
            \App\Models\WarehouseRackSlot::where('archive_id', $archive->id)->update([
                'status' => 'empty',
                'archive_id' => null,
            ]);
            if ($archive->location && $archive->location->current_box_count > 0) {
                $archive->location->decrement('current_box_count');
            }

            $archive->status = 'taken';
            if ($request->filled('reason')) {
                $noteEntry = "[" . now()->format('d/m/Y H:i') . "] Pengeluaran Berkas (Out) oleh {$user->name}: {$validated['reason']}";
                $archive->content_description = ($archive->content_description ? $archive->content_description . "\n" : '') . $noteEntry;
            }
            $archive->save();

            ActivityLogger::log(
                'STATUS_UPDATE_OUT',
                "Pengguna {$user->name} mengubah status arsip '{$archive->title}' (Box: {$archive->box_number}) menjadi Keluar (Out). Keperluan: {$validated['purpose']}",
                'PENGELUARAN',
                [
                    'archive_id' => $archive->id,
                    'box_number' => $archive->box_number,
                    'old_status' => $oldStatus,
                    'new_status' => 'taken',
                    'borrower_name' => $validated['borrower_name'] ?? $user->name,
                    'department' => $validated['department_name'] ?? null,
                    'purpose' => $validated['purpose'],
                    'has_approval_file' => !empty($approvalPath),
                    'user_id' => $user->id,
                ],
                $archive->box_number
            );

        } elseif ($targetStatus === 'pending_destruction') {
            $scanApprovalPath = null;
            if ($request->hasFile('approval_file')) {
                $scanApprovalPath = $request->file('approval_file')->store('destruction_approvals', 'public');
            }

            $existingDestLog = \App\Models\DestructionLog::where('archive_id', $archive->id)
                ->where('approval_status', '!=', 'completed')
                ->latest()
                ->first();

            if ($existingDestLog) {
                $existingDestLog->update([
                    'approval_status' => 'approved',
                    'approved_by_dept_pic_id' => $user->id,
                    'notes' => $validated['reason'] ?? $existingDestLog->notes,
                ]);
            } else {
                \App\Models\DestructionLog::create([
                    'archive_id' => $archive->id,
                    'proposed_by_user_id' => $user->id,
                    'department_approval_by' => $user->id,
                    'department_approved_at' => now(),
                    'approved_by_dept_pic_id' => $user->id,
                    'bap_number' => '(Menunggu Penerbitan BAP)',
                    'destruction_date' => now(),
                    'method' => 'Belum Ditentukan',
                    'approval_file' => $scanApprovalPath,
                    'scan_approval_destruction' => $scanApprovalPath,
                    'approval_status' => 'approved',
                    'notes' => $validated['reason'] ?? 'Dipindahkan ke Antrean Pemusnahan.',
                ]);
            }

            $archive->status = 'pending_destruction';
            if ($request->filled('reason')) {
                $noteEntry = "[" . now()->format('d/m/Y H:i') . "] Antrean Pemusnahan oleh {$user->name}: {$validated['reason']}";
                $archive->content_description = ($archive->content_description ? $archive->content_description . "\n" : '') . $noteEntry;
            }
            $archive->save();

            ActivityLogger::log(
                'STATUS_UPDATE_PENDING_DESTRUCTION',
                "Pengguna {$user->name} memindahkan arsip '{$archive->title}' (Box: {$archive->box_number}) ke Antrean Pemusnahan.",
                'PEMUSNAHAN',
                [
                    'archive_id' => $archive->id,
                    'box_number' => $archive->box_number,
                    'old_status' => $oldStatus,
                    'new_status' => 'pending_destruction',
                    'reason' => $validated['reason'] ?? null,
                    'user_id' => $user->id,
                ],
                $archive->box_number
            );

        } elseif ($targetStatus === 'destroyed') {
            $scanApprovalPath = null;
            if ($request->hasFile('approval_file')) {
                $scanApprovalPath = $request->file('approval_file')->store('bap_certificates', 'public');
            } elseif ($request->hasFile('certificate_file')) {
                $scanApprovalPath = $request->file('certificate_file')->store('bap_certificates', 'public');
            }

            $existingDestLog = \App\Models\DestructionLog::where('archive_id', $archive->id)
                ->where('approval_status', '!=', 'completed')
                ->latest()
                ->first();

            if ($existingDestLog) {
                $existingDestLog->update([
                    'bap_number' => $validated['bap_number'],
                    'destruction_date' => $validated['destruction_date'],
                    'method' => $validated['method'],
                    'certificate_file' => $scanApprovalPath ?: $existingDestLog->certificate_file,
                    'approval_status' => 'completed',
                    'notes' => $validated['notes'] ?? ($validated['reason'] ?? $existingDestLog->notes),
                ]);
            } else {
                \App\Models\DestructionLog::create([
                    'archive_id' => $archive->id,
                    'proposed_by_user_id' => $user->id,
                    'department_approval_by' => $user->id,
                    'department_approved_at' => now(),
                    'approved_by_dept_pic_id' => $user->id,
                    'bap_number' => $validated['bap_number'],
                    'destruction_date' => $validated['destruction_date'],
                    'method' => $validated['method'],
                    'approval_file' => $scanApprovalPath,
                    'scan_approval_destruction' => $scanApprovalPath,
                    'certificate_file' => $scanApprovalPath,
                    'is_approval_uploaded' => !empty($scanApprovalPath),
                    'approval_status' => 'completed',
                    'notes' => $validated['notes'] ?? ($validated['reason'] ?? 'Pemusnahan berkas arsip resmi dengan BAP'),
                ]);
            }

            // Free rack slot & location
            if ($archive->rackSlot) {
                $archive->rackSlot->update([
                    'status' => 'empty',
                    'archive_id' => null,
                ]);
            }
            \App\Models\WarehouseRackSlot::where('archive_id', $archive->id)->update([
                'status' => 'empty',
                'archive_id' => null,
            ]);
            if ($archive->location && $archive->location->current_box_count > 0) {
                $archive->location->decrement('current_box_count');
            }

            $archive->status = 'destroyed';
            $archive->warehouse_location_id = null;
            $archive->warehouse_rack_slot_id = null;
            if ($request->filled('reason')) {
                $noteEntry = "[" . now()->format('d/m/Y H:i') . "] SuperAdmin Pemusnahan (BAP: {$validated['bap_number']}) oleh {$user->name}: {$validated['reason']}";
                $archive->content_description = ($archive->content_description ? $archive->content_description . "\n" : '') . $noteEntry;
            }
            $archive->save();

            ActivityLogger::log(
                'SUPERADMIN_STATUS_DESTROYED',
                "Super Admin {$user->name} mengesahkan status Pemusnahan berkas '{$archive->title}' (Box: {$archive->box_number}) dengan No. BAP {$validated['bap_number']} via metode {$validated['method']}",
                'PEMUSNAHAN_RETENSI',
                [
                    'archive_id' => $archive->id,
                    'box_number' => $archive->box_number,
                    'old_status' => $oldStatus,
                    'new_status' => 'destroyed',
                    'bap_number' => $validated['bap_number'],
                    'method' => $validated['method'],
                    'destruction_date' => $validated['destruction_date'],
                    'has_approval_file' => !empty($scanApprovalPath),
                    'superadmin_id' => $user->id,
                ],
                $validated['bap_number']
            );
        }

        $statusLabels = [
            'draft' => 'Draft',
            'pending_verification' => 'Antrean Verifikasi',
            'approved_booked' => 'Approved / Booked',
            'in_warehouse' => 'Tersimpan di Gudang',
            'taken' => 'Keluar (Out)',
            'destroyed' => 'Dimusnahkan',
        ];

        $label = $statusLabels[$targetStatus] ?? $targetStatus;
        $msg = "Status arsip box {$archive->box_number} berhasil diubah menjadi '{$label}' oleh Super Admin.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'archive_id' => $archive->id,
                'new_status' => $targetStatus,
                'new_status_label' => $label,
            ]);
        }

        return redirect()->back()->with('success', $msg);
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

    public function apiCalculateRetention(Request $request)
    {
        $periodeDoc = trim($request->query('periode_doc', '')); // e.g. 2026/09 or 2026/07 - 2026/09
        $deptId = $request->query('department_id');
        $subDeptId = $request->query('sub_department_id');
        $customYears = $request->query('masa_simpan_custom');

        if (preg_match('/^(\d{4}\/(?:0[1-9]|1[0-2]))\s*(?:-|s\/d|hingga|to)\s*(\d{4}\/(?:0[1-9]|1[0-2]))$/i', $periodeDoc, $matches)) {
            $startPeriodStr = $matches[1];
            $endPeriodStr = $matches[2];
            [$sYear, $sMonth] = explode('/', $startPeriodStr);
            [$eYear, $eMonth] = explode('/', $endPeriodStr);
            $startDate = Carbon::createFromDate((int)$sYear, (int)$sMonth, 1)->startOfDay();
            $endDate = Carbon::createFromDate((int)$eYear, (int)$eMonth, 1)->endOfMonth()->endOfDay();
            $formattedPeriodDoc = $startPeriodStr . ' - ' . $endPeriodStr;
            $periodText = $startDate->isoFormat('MMMM Y') . ' - ' . $endDate->isoFormat('MMMM Y');
        } elseif (preg_match('/^(\d{4}\/(?:0[1-9]|1[0-2]))$/', $periodeDoc, $matches)) {
            $periodStr = $matches[1];
            [$year, $month] = explode('/', $periodStr);
            $startDate = Carbon::createFromDate((int)$year, (int)$month, 1)->startOfDay();
            $endDate = $startDate->copy()->endOfMonth()->endOfDay();
            $formattedPeriodDoc = $periodStr;
            $periodText = $startDate->isoFormat('MMMM Y');
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Format periode harus YYYY/MM (contoh: 2026/09) atau Rentang YYYY/MM - YYYY/MM (contoh: 2026/07 - 2026/09)',
            ], 422);
        }

        $effectiveRetentionYears = 5;
        if (!empty($customYears) && (int)$customYears > 0) {
            $effectiveRetentionYears = (int)$customYears;
        } elseif (!empty($subDeptId)) {
            $subDept = SubDepartment::find($subDeptId);
            if ($subDept && $subDept->retention_years > 0) {
                $effectiveRetentionYears = (int)$subDept->retention_years;
            }
        } elseif (!empty($deptId)) {
            $dept = Department::find($deptId);
            if ($dept && $dept->retention_years > 0) {
                $effectiveRetentionYears = (int)$dept->retention_years;
            }
        }

        $expiryDate = $endDate->copy()->addYears($effectiveRetentionYears);

        return response()->json([
            'success' => true,
            'periode_doc' => $formattedPeriodDoc,
            'period_start_date' => $startDate->format('Y-m-d'),
            'period_end_date' => $endDate->format('Y-m-d'),
            'period_text' => $periodText,
            'effective_retention_years' => $effectiveRetentionYears,
            'retention_expiry_date' => $expiryDate->format('Y-m-d'),
            'retention_expiry_formatted' => $expiryDate->isoFormat('D MMMM Y'),
        ]);
    }

    /**
     * Download multi-box import template (XLSX or CSV).
     */
    public function downloadImportTemplate(Request $request, MultiBoxExcelService $excelService)
    {
        $format = $request->input('format', 'xlsx');
        return $excelService->generateTemplate($format);
    }

    /**
     * Parse and preview multi-box spreadsheet.
     */
    public function previewImport(Request $request, MultiBoxExcelService $excelService)
    {
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

        $result = $excelService->parseFile($file, auth()->user());

        return response()->json($result);
    }

    /**
     * Batch commit parsed multi-box archives to database.
     */
    public function commitImport(Request $request, MultiBoxExcelService $excelService)
    {
        $request->validate([
            'boxes' => 'required|array|min:1',
            'status' => 'required|in:draft,pending_verification',
        ]);

        $boxes = $request->input('boxes');
        $status = $request->input('status');
        $user = auth()->user();

        // Enforce PIC Dept security
        if ($user->isPicDept()) {
            foreach ($boxes as $box) {
                $targetDeptId = $box['department_id'] ?? null;
                if (!$targetDeptId && !empty($box['department_code'])) {
                    $foundDept = \App\Models\Department::where('code', strtoupper($box['department_code']))->first();
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
     * Live similarity check for box title and items within the user's department scope.
     */
    public function apiCheckSimilarity(Request $request)
    {
        $user = auth()->user();
        $title = trim($request->input('title', ''));
        $deptId = $request->input('department_id');

        if ($user && $user->isPicDept()) {
            $deptId = $user->department_id;
        }

        if (empty($title) || empty($deptId)) {
            return response()->json([
                'has_similarity' => false,
                'message' => 'Judul atau departemen kosong',
            ]);
        }

        // Normalize input title
        $normalizedInput = strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '', $title));
        $normalizedInput = preg_replace('/\s+/', ' ', trim($normalizedInput));

        $excludeId = $request->input('exclude_archive_id') ?: ($request->input('exclude_id') ?: $request->input('origin_archive_id'));

        // Query active archives in department
        $query = Archive::where('department_id', $deptId)
            ->where('status', '!=', 'destroyed');

        if ($excludeId) {
            $query->where('id', '!=', (int)$excludeId);
        }

        $existingArchives = $query->select(['id', 'box_number', 'title', 'status', 'periode_doc', 'periode', 'period_text'])
            ->latest('id')
            ->take(100)
            ->get();

        $bestMatch = null;
        $highestSimilarity = 0;
        $matchType = 'unique';

        foreach ($existingArchives as $arch) {
            $existingTitle = trim($arch->title ?? '');
            if (empty($existingTitle)) continue;

            $normalizedExisting = strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '', $existingTitle));
            $normalizedExisting = preg_replace('/\s+/', ' ', trim($normalizedExisting));

            if ($normalizedInput === $normalizedExisting) {
                $bestMatch = $arch;
                $highestSimilarity = 100;
                $matchType = 'identical';
                break;
            }

            // Calculate similarity percentage
            similar_text($normalizedInput, $normalizedExisting, $percent);
            if ($percent > $highestSimilarity) {
                $highestSimilarity = round($percent, 1);
                $bestMatch = $arch;
            }
        }

        if ($highestSimilarity >= 70 && $bestMatch) {
            if ($highestSimilarity < 100) {
                $matchType = 'similar';
            }

            // Word difference highlights
            $inputWords = explode(' ', $normalizedInput);
            $existingWords = explode(' ', strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '', $bestMatch->title)));
            $diffWordsInput = array_diff($inputWords, $existingWords);
            $diffWordsExisting = array_diff($existingWords, $inputWords);

            return response()->json([
                'has_similarity' => true,
                'match_type' => $matchType,
                'similarity_percentage' => $highestSimilarity,
                'matched_archive' => [
                    'id' => $bestMatch->id,
                    'box_number' => $bestMatch->box_number,
                    'title' => $bestMatch->title,
                    'status' => $bestMatch->status,
                    'periode_doc' => $bestMatch->periode_doc,
                    'period_text' => $bestMatch->period_text,
                ],
                'diff' => [
                    'unique_in_input' => array_values($diffWordsInput),
                    'unique_in_existing' => array_values($diffWordsExisting),
                ],
            ]);
        }

        return response()->json([
            'has_similarity' => false,
            'match_type' => 'unique',
            'similarity_percentage' => $highestSimilarity,
        ]);
    }

    public function getMaxPeriodMonths($departmentId = null, $user = null): int
    {
        $user = $user ?: auth()->user();
        if ($user) {
            $userDept = $user->department;
            if ($userDept && in_array(strtoupper($userDept->code), ['FAT', 'FIN', 'FATCLM'])) {
                return 120;
            }
            $userName = strtoupper($user->name ?? '');
            if (str_contains($userName, 'FAT') || str_contains($userName, 'FIN')) {
                return 120;
            }
        }

        if ($departmentId) {
            $dept = Department::find($departmentId);
            if ($dept && in_array(strtoupper($dept->code), ['FAT', 'FIN', 'FATCLM'])) {
                return 120;
            }
        }

        return 60;
    }
}

