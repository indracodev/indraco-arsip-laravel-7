<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\WarehouseEntryLog;
use App\Models\WarehouseLocation;
use App\Services\NumberingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Archive::with(['department', 'subDepartment', 'location.warehouse', 'creator']);

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
            $query->where('status', $request->status);
        }

        if ($request->filled('expiry_filter')) {
            if ($request->expiry_filter === 'expiring_soon') {
                $query->whereNotNull('retention_expiry_date')
                      ->where('status', '!=', 'destroyed')
                      ->whereDate('retention_expiry_date', '<=', Carbon::now()->addDays(90));
            } elseif ($request->expiry_filter === 'expired') {
                $query->whereNotNull('retention_expiry_date')
                      ->where('status', '!=', 'destroyed')
                      ->whereDate('retention_expiry_date', '<', Carbon::now());
            }
        }

        // Sorting
        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = strtolower($request->get('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['box_number', 'title', 'created_at', 'retention_expiry_date', 'status'];

        if (in_array($sortColumn, $allowedSorts)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->latest();
        }

        $archives = $query->paginate(10)->withQueryString();
        $departments = Department::with('subDepartments')->get();

        return view('archives.index', compact('archives', 'departments'));
    }

    public function create()
    {
        $user = auth()->user();
        $departments = Department::with(['subDepartments' => function ($q) {
            $q->where('is_active', true);
        }])->where('is_active', true)->get();

        return view('archives.create', compact('user', 'departments'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'sub_department_id' => 'nullable|exists:sub_departments,id',
            'company_name' => 'nullable|string|max:150',
            'document_type' => 'nullable|string|max:100',
            'is_custom_doc_name' => 'nullable|boolean',
            'custom_doc_name' => 'required_if:is_custom_doc_name,1,true|nullable|string|max:255',
            'title' => 'required_without:custom_doc_name|nullable|string|max:255',
            'periode_doc' => ['required', 'string', 'regex:/^\d{4}\/(0[1-9]|1[0-2])$/'], // Format YYYY/MM
            'tgl_penyerahan' => 'required|date',
            'period_text' => 'nullable|string|max:100',
            'content_description' => 'required|string',
            'retention_years' => 'nullable|integer|min:1|max:30',
            'masa_simpan_custom' => 'nullable|integer|min:1|max:30',
            'physical_condition' => 'required|string|max:100',
            'file' => 'nullable|file|mimes:pdf,jpg,png,doc,docx,zip|max:10240',
            'scan_input_form' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'scan_approval_input' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($user->isPicDept()) {
            $validated['department_id'] = $user->department_id;
        }

        // 1. Business Rule: 1 Box 1 Periode Dokumen (YYYY/MM)
        [$year, $month] = explode('/', $validated['periode_doc']);
        $startDate = Carbon::createFromDate((int)$year, (int)$month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();

        $periodText = $validated['period_text'] ?? ($startDate->isoFormat('MMMM Y'));
        $periodYyMm = $validated['periode_doc'];

        // 2. Title & Custom Doc Name Handling
        $isCustomDocName = !empty($validated['is_custom_doc_name']);
        $customDocName = $isCustomDocName ? $validated['custom_doc_name'] : null;
        $finalTitle = $isCustomDocName ? $customDocName : ($validated['title'] ?? 'Dokumen Periode ' . $validated['periode_doc']);

        // 3. Automated Retention Years & Expiry Calculation
        $department = Department::find($validated['department_id']);
        $subDepartment = !empty($validated['sub_department_id']) ? SubDepartment::find($validated['sub_department_id']) : null;

        $effectiveRetentionYears = 5;
        if (!empty($validated['masa_simpan_custom']) && (int)$validated['masa_simpan_custom'] > 0) {
            $effectiveRetentionYears = (int)$validated['masa_simpan_custom'];
        } elseif ($subDepartment && $subDepartment->retention_years > 0) {
            $effectiveRetentionYears = (int)$subDepartment->retention_years;
        } elseif ($department && $department->retention_years > 0) {
            $effectiveRetentionYears = (int)$department->retention_years;
        } elseif (!empty($validated['retention_years']) && (int)$validated['retention_years'] > 0) {
            $effectiveRetentionYears = (int)$validated['retention_years'];
        }

        $retentionExpiryDate = $endDate->copy()->addYears($effectiveRetentionYears)->format('Y-m-d');

        // 4. File uploads
        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('archive_digital', 'public');
        }

        $scanInputFormPath = null;
        if ($request->hasFile('scan_input_form')) {
            $scanInputFormPath = $request->file('scan_input_form')->store('archive_scans', 'public');
        }

        $scanApprovalInputPath = null;
        if ($request->hasFile('scan_approval_input')) {
            $scanApprovalInputPath = $request->file('scan_approval_input')->store('archive_scans', 'public');
        }

        Archive::create([
            'department_id' => $validated['department_id'],
            'sub_department_id' => $validated['sub_department_id'] ?? null,
            'company_name' => $validated['company_name'] ?? 'PT Indraco',
            'document_type' => $validated['document_type'] ?? 'UMUM',
            'created_by_user_id' => $user->id,
            'title' => $finalTitle,
            'is_custom_doc_name' => $isCustomDocName,
            'custom_doc_name' => $customDocName,
            'period_start_date' => $startDate->format('Y-m-d'),
            'period_end_date' => $endDate->format('Y-m-d'),
            'period_text' => $periodText,
            'period_yy_mm' => $periodYyMm,
            'periode_doc' => $validated['periode_doc'],
            'tgl_penyerahan' => $validated['tgl_penyerahan'],
            'content_description' => $validated['content_description'],
            'retention_years' => $effectiveRetentionYears,
            'masa_simpan_custom' => $validated['masa_simpan_custom'] ?? null,
            'retention_expiry_date' => $retentionExpiryDate,
            'physical_condition' => $validated['physical_condition'],
            'file_path' => $filePath,
            'scan_input_form' => $scanInputFormPath,
            'scan_approval_input' => $scanApprovalInputPath,
            'status' => 'pending_verification',
        ]);

        return redirect()->route('archives.index')
            ->with('success', 'Pengajuan booking arsip dokumen (Periode ' . $validated['periode_doc'] . ') berhasil disubmit untuk diverifikasi PIC Gudang.');
    }

    public function show(Archive $archive)
    {
        $archive->load([
            'department',
            'subDepartment',
            'creator',
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
        if ($user->isPicDept() && $archive->department_id !== $user->department_id) {
            abort(403, 'Anda tidak memiliki akses ke label arsip departemen lain.');
        }

        $archive->load(['department', 'subDepartment', 'location.warehouse', 'rackSlot', 'creator']);
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

        $query = Archive::with(['department', 'subDepartment', 'location.warehouse', 'rackSlot', 'creator']);

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

            return redirect()->route('archives.show', $archive)
                ->with('success', "Pengajuan arsip disetujui! Nomor Box Generated: {$archive->box_number}");
        } else {
            $archive->status = 'draft';
            $archive->rejection_note = $request->rejection_note;
            $archive->save();

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
        $archive->update([
            'warehouse_location_id' => $location->id,
            'status' => 'in_warehouse',
        ]);

        // Record Log Masuk Gudang
        WarehouseEntryLog::create([
            'archive_id' => $archive->id,
            'pic_gudang_id' => auth()->id(),
            'location_id' => $location->id,
            'entry_date' => now(),
            'notes' => $request->notes ?? 'Penerimaan fisik berkas & penempatan di gudang arsip.',
        ]);

        return redirect()->route('archives.show', $archive)
            ->with('success', "Berkas fisik berhasil di-checkin ke lokasi {$location->full_location} & Log Masuk Gudang telah dicatat.");
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
        $periodeDoc = $request->query('periode_doc'); // e.g. 2026/09
        $deptId = $request->query('department_id');
        $subDeptId = $request->query('sub_department_id');
        $customYears = $request->query('masa_simpan_custom');

        if (!$periodeDoc || !preg_match('/^\d{4}\/(0[1-9]|1[0-2])$/', $periodeDoc)) {
            return response()->json([
                'success' => false,
                'message' => 'Format periode harus YYYY/MM (contoh: 2026/09)',
            ], 422);
        }

        [$year, $month] = explode('/', $periodeDoc);
        $startDate = Carbon::createFromDate((int)$year, (int)$month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();

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
            'periode_doc' => $periodeDoc,
            'period_start_date' => $startDate->format('Y-m-d'),
            'period_end_date' => $endDate->format('Y-m-d'),
            'period_text' => $startDate->isoFormat('MMMM Y'),
            'effective_retention_years' => $effectiveRetentionYears,
            'retention_expiry_date' => $expiryDate->format('Y-m-d'),
            'retention_expiry_formatted' => $expiryDate->isoFormat('D MMMM Y'),
        ]);
    }
}
