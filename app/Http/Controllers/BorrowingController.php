<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\BorrowingLog;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class BorrowingController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = BorrowingLog::with(['archive.department', 'borrower', 'picGudang', 'departmentApprovedBy']);

        if ($user->isPicDept()) {
            $query->where('borrower_user_id', $user->id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('purpose', 'like', "%{$search}%")
                  ->orWhereHas('archive', function ($q2) use ($search) {
                      $q2->where('title', 'like', "%{$search}%")
                         ->orWhere('box_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('borrower', function ($q3) use ($search) {
                      $q3->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortColumn = $request->get('sort', 'created_at');
        if ($sortColumn === 'borrowed_at') {
            $sortColumn = 'borrow_date';
        }
        $sortDirection = strtolower($request->get('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['created_at', 'borrow_date', 'request_date', 'expected_return_date', 'status'];

        if (in_array($sortColumn, $allowedSorts)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->latest();
        }

        $borrowings = $query->paginate(10)->withQueryString();

        return view('borrowings.index', compact('borrowings'));
    }

    public function create(Request $request)
    {
        $user = auth()->user();

        // Get archives available for borrowing (status in_warehouse)
        $archivesQuery = Archive::with(['department', 'subDepartment', 'location.warehouse', 'rackSlot'])->where('status', 'in_warehouse');

        if ($user->isPicDept()) {
            $archivesQuery->where('department_id', $user->department_id);
        }

        $archives = $archivesQuery->get();
        $selectedArchiveId = $request->query('archive_id');

        return view('borrowings.create', compact('archives', 'selectedArchiveId'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'archive_id' => 'required|exists:archives,id',
            'purpose' => 'required|string|max:500',
            'is_permanent' => 'nullable|boolean',
            'expected_return_date' => 'nullable|required_unless:is_permanent,1,true|date|after_or_equal:today',
            'approval_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $archive = Archive::findOrFail($validated['archive_id']);

        if ($archive->status !== 'in_warehouse') {
            return back()->with('error', 'Arsip dokumen saat ini tidak tersedia di gudang untuk dipinjam.');
        }

        $approvalPath = $request->file('approval_file')->store('borrowing_approvals', 'public');

        $isPermanent = $request->boolean('is_permanent') || empty($request->expected_return_date);
        $expectedReturnDate = $isPermanent ? null : ($validated['expected_return_date'] ?? null);

        $borrowing = BorrowingLog::create([
            'archive_id' => $archive->id,
            'borrower_user_id' => $user->id,
            'request_date' => now(),
            'expected_return_date' => $expectedReturnDate,
            'purpose' => $validated['purpose'],
            'status' => 'requested',
            'approval_file' => $approvalPath,
            'scan_approval_borrow' => $approvalPath,
            'is_approval_uploaded' => true,
            'approval_status' => 'pending',
        ]);

        ActivityLogger::log(
            'BORROW_CREATE',
            "Pengajuan peminjaman arsip '{$archive->title}' (Box: {$archive->box_number}) oleh {$user->name} untuk keperluan: {$validated['purpose']}",
            'PEMINJAMAN',
            [
                'borrowing_id' => $borrowing->id,
                'archive_id' => $archive->id,
                'box_number' => $archive->box_number,
                'purpose' => $validated['purpose'],
                'expected_return_date' => $validated['expected_return_date'],
            ],
            $archive->box_number
        );

        return redirect()->route('borrowings.index')
            ->with('success', 'Permintaan peminjaman berkas arsip dengan lampiran approval berhasil diajukan ke PIC Gudang.');
    }

    public function deptApprove(BorrowingLog $borrowing, Request $request)
    {
        $user = auth()->user();

        if (!$user->isSuperAdmin() && (!$user->isPicDept() || (int)$user->department_id !== (int)$borrowing->archive->department_id)) {
            abort(403, 'Hanya PIC Departemen pemilik berkas atau Admin yang dapat menyetujui peminjaman ini.');
        }

        $request->validate([
            'approval_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'scan_approval_borrow' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $scanPath = $borrowing->approval_file ?? $borrowing->scan_approval_borrow;
        if ($request->hasFile('approval_file')) {
            $scanPath = $request->file('approval_file')->store('borrowing_approvals', 'public');
        } elseif ($request->hasFile('scan_approval_borrow')) {
            $scanPath = $request->file('scan_approval_borrow')->store('borrowing_approvals', 'public');
        }

        $borrowing->update([
            'status' => 'dept_approved',
            'department_approval_by' => $user->id,
            'department_approved_at' => now(),
            'approval_file' => $scanPath,
            'scan_approval_borrow' => $scanPath,
            'is_approval_uploaded' => !empty($scanPath),
            'approval_status' => 'approved',
        ]);

        ActivityLogger::log(
            'BORROW_DEPT_APPROVE',
            "Persetujuan Departemen atas peminjaman arsip '{$borrowing->archive->title}' (Box: {$borrowing->archive->box_number}) oleh {$user->name}",
            'PEMINJAMAN',
            [
                'borrowing_id' => $borrowing->id,
                'box_number' => $borrowing->archive->box_number,
            ],
            $borrowing->archive->box_number
        );

        return redirect()->route('borrowings.index')
            ->with('success', 'Persetujuan Departemen berhasil disahkan. Pengajuan kini siap dikirimkan ke PIC Gudang.');
    }

    public function approve(BorrowingLog $borrowing)
    {
        if (!auth()->user()->isPicGudang() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $borrowing->update([
            'status' => 'approved',
            'pic_gudang_id' => auth()->id(),
            'approval_status' => 'approved',
        ]);

        ActivityLogger::log(
            'BORROW_APPROVE',
            "Persetujuan Gudang atas peminjaman arsip '{$borrowing->archive->title}' (Box: {$borrowing->archive->box_number}) oleh PIC Gudang " . auth()->user()->name,
            'PEMINJAMAN',
            [
                'borrowing_id' => $borrowing->id,
                'box_number' => $borrowing->archive->box_number,
            ],
            $borrowing->archive->box_number
        );

        return redirect()->route('borrowings.index')
            ->with('success', 'Permintaan peminjaman disetujui Gudang. Silakan persiapkan berkas fisik untuk diserahkan.');
    }

    public function dispatch(BorrowingLog $borrowing, Request $request)
    {
        if (!auth()->user()->isPicGudang() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $request->validate([
            'approval_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'scan_approval_borrow' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $scanPath = $borrowing->approval_file ?? $borrowing->scan_approval_borrow;
        if ($request->hasFile('approval_file')) {
            $scanPath = $request->file('approval_file')->store('borrowing_approvals', 'public');
        } elseif ($request->hasFile('scan_approval_borrow')) {
            $scanPath = $request->file('scan_approval_borrow')->store('borrowing_approvals', 'public');
        }

        $borrowing->update([
            'status' => 'dispatched',
            'borrow_date' => now(),
            'pic_gudang_id' => auth()->id(),
            'approval_file' => $scanPath,
            'scan_approval_borrow' => $scanPath,
        ]);

        $archive = $borrowing->archive;
        $isPermanent = empty($borrowing->expected_return_date);
        $archiveStatus = $isPermanent ? 'taken' : 'borrowed';

        $archive->update([
            'status' => $archiveStatus,
        ]);

        // Vacate rack slot when document is issued / dispatched
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

        $successMsg = $isPermanent 
            ? 'Pengeluaran berkas disahkan! Status arsip diubah menjadi "Diambil (Permanen)" dan slot rak telah dikosongkan.'
            : 'Pengeluaran berkas disahkan! Status arsip diubah menjadi "Sedang Dipinjam" dan slot rak telah dikosongkan.';

        return redirect()->back()
            ->with('success', $successMsg);
    }

    public function returnArchive(BorrowingLog $borrowing, Request $request)
    {
        if (!auth()->user()->isPicGudang() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $borrowing->update([
            'status' => 'returned',
            'actual_return_date' => now(),
            'notes' => $request->notes ?? 'Berkas fisik dikembalikan dalam kondisi baik.',
        ]);

        $archive = $borrowing->archive;
        $archive->update([
            'status' => 'in_warehouse',
        ]);

        // Re-occupy rack slot upon return if assigned slot exists
        if ($archive->warehouse_rack_slot_id) {
            $slot = \App\Models\WarehouseRackSlot::find($archive->warehouse_rack_slot_id);
            if ($slot && $slot->status === 'empty') {
                $slot->update([
                    'status' => 'filled',
                    'archive_id' => $archive->id,
                ]);
            }
        }

        if ($archive->location) {
            $archive->location->increment('current_box_count');
        }

        return redirect()->route('borrowings.index')
            ->with('success', 'Pengembalian berkas dikonfirmasi! Status arsip kembali "Tersimpan di Gudang".');
    }
}
