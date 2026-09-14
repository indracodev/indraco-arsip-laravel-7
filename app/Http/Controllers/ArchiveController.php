<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\Department;
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

        $query = Archive::with(['department', 'location.warehouse', 'creator']);

        if ($user->isPicDept()) {
            $query->where('department_id', $user->department_id);
        }

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('box_number', 'like', "%{$search}%")
                  ->orWhere('period_text', 'like', "%{$search}%")
                  ->orWhere('content_description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
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
        $departments = Department::all();

        return view('archives.index', compact('archives', 'departments'));
    }

    public function create()
    {
        $user = auth()->user();
        $departments = Department::all();
        return view('archives.create', compact('user', 'departments'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'company_name' => 'nullable|string|max:150',
            'document_type' => 'nullable|string|max:100',
            'title' => 'required|string|max:255',
            'period_start_date' => 'required|date',
            'period_end_date' => 'required|date|after_or_equal:period_start_date',
            'period_yy_mm' => 'nullable|string|max:7',
            'period_text' => 'nullable|string|max:100',
            'content_description' => 'required|string',
            'retention_years' => 'required|integer|min:1|max:5', // Strictly max 5 years per revisi 1
            'physical_condition' => 'required|string|max:100',
            'file' => 'nullable|file|mimes:pdf,jpg,png,doc,docx,zip|max:10240',
            'scan_input_form' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'scan_approval_input' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($user->isPicDept()) {
            $validated['department_id'] = $user->department_id;
        }

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

        // Calculate retention expiry date
        $endDate = Carbon::parse($validated['period_end_date']);
        $retentionExpiryDate = $endDate->copy()->addYears((int)$validated['retention_years']);

        // Generate period text if empty
        $periodText = $validated['period_text'];
        if (empty($periodText)) {
            $periodText = Carbon::parse($validated['period_start_date'])->isoFormat('MMMM Y') . ' - ' . $endDate->isoFormat('MMMM Y');
        }

        // Generate period YY-MM if empty (e.g., "26-03")
        $periodYyMm = $validated['period_yy_mm'] ?? null;
        if (empty($periodYyMm)) {
            $periodYyMm = $endDate->format('y-m');
        }

        Archive::create([
            'department_id' => $validated['department_id'],
            'company_name' => $validated['company_name'] ?? 'PT Indraco',
            'document_type' => $validated['document_type'] ?? 'UMUM',
            'created_by_user_id' => $user->id,
            'title' => $validated['title'],
            'period_start_date' => $validated['period_start_date'],
            'period_end_date' => $validated['period_end_date'],
            'period_text' => $periodText,
            'period_yy_mm' => $periodYyMm,
            'content_description' => $validated['content_description'],
            'retention_years' => $validated['retention_years'],
            'retention_expiry_date' => $retentionExpiryDate,
            'physical_condition' => $validated['physical_condition'],
            'file_path' => $filePath,
            'scan_input_form' => $scanInputFormPath,
            'scan_approval_input' => $scanApprovalInputPath,
            'status' => 'pending_verification', // Submit directly to PIC Gudang queue
        ]);

        return redirect()->route('archives.index')
            ->with('success', 'Pengajuan booking arsip dokumen berhasil disubmit untuk diverifikasi oleh PIC Gudang.');
    }

    public function show(Archive $archive)
    {
        $archive->load([
            'department',
            'creator',
            'location.warehouse',
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

        $archive->load(['department', 'location.warehouse', 'creator']);
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

        $query = Archive::with(['department', 'location.warehouse', 'creator']);

        // Scope to user's department if PIC Dept
        if ($user->isPicDept()) {
            $query->where('department_id', $user->department_id);
        }

        if (!empty($ids) && is_array($ids)) {
            $query->whereIn('id', $ids);
        } else {
            // Apply search & department filter if no specific IDs passed
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('box_number', 'like', "%{$search}%")
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
            // Generate Custom Box Code if not already set
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

        $location = WarehouseLocation::findOrFail($request->warehouse_location_id);

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
}
