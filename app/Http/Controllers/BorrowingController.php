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

        $query = BorrowingLog::with([
            'archive.department',
            'archive.subDepartment',
            'archive.items',
            'archive.location.warehouse',
            'archive.rackSlot',
            'archive.borrowingLogs' => function ($q) {
                $q->where('status', 'rejected')->orderBy('id', 'desc');
            },
            'borrower',
            'picGudang',
            'departmentApprovedBy'
        ]);

        // Pastikan tetap 1 data per box arsip (ambil record penarikan terkini, log lama yang sudah diajukan ulang masuk ke riwayat detail)
        $query->where(function ($mainQ) {
            $mainQ->whereNull('archive_id')
                  ->orWhereNotExists(function ($sub) {
                      $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                          ->from('borrowing_logs as bl2')
                          ->whereColumn('bl2.archive_id', 'borrowing_logs.archive_id')
                          ->whereColumn('bl2.id', '>', 'borrowing_logs.id');
                  });
        });

        if ($user->isPicDept()) {
            $query->where(function ($q) use ($user) {
                $q->where('borrower_user_id', $user->id)
                  ->orWhereHas('archive', function ($q2) use ($user) {
                      $q2->where('department_id', $user->department_id);
                  });
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search, $user) {
                if ($user && $user->isPicGudang()) {
                    $q->where('purpose', 'like', "%{$search}%")
                      ->orWhereHas('archive', function ($q2) use ($search) {
                          $q2->where('title', 'like', "%{$search}%")
                             ->orWhere('box_number', 'like', "%{$search}%")
                             ->orWhereHas('location', function ($lq) use ($search) {
                                 $lq->where('rack_code', 'like', "%{$search}%");
                             });
                      })
                      ->orWhereHas('borrower', function ($q3) use ($search) {
                          $q3->where('name', 'like', "%{$search}%");
                      });
                } else {
                    $q->where('purpose', 'like', "%{$search}%")
                      ->orWhereHas('archive', function ($q2) use ($search) {
                          $q2->where('title', 'like', "%{$search}%")
                             ->orWhere('box_number', 'like', "%{$search}%")
                             ->orWhere('content_description', 'like', "%{$search}%")
                             ->orWhereHas('location', function ($lq) use ($search) {
                                 $lq->where('rack_code', 'like', "%{$search}%");
                             });
                      })
                      ->orWhereHas('borrower', function ($q3) use ($search) {
                          $q3->where('name', 'like', "%{$search}%");
                      });
                }
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sorting: Yang bisa ditarik selalu berada di paling atas
        $query->orderByRaw("CASE WHEN status IN ('requested', 'dept_approved', 'approved') THEN 0 ELSE 1 END ASC");

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
        if ($user && $user->isPicGudang()) {
            abort(403, 'Akses Ditolak: PIC Gudang tidak memiliki hak untuk membuat pengajuan penarikan. Wewenang gudang hanya untuk pengeluaran (dispatch) dan approval.');
        }

        // Get archives available for borrowing (status in_warehouse)
        $archivesQuery = Archive::select(
            'id', 'archive_code', 'title', 'department_id', 'sub_department_id',
            'warehouse_location_id', 'warehouse_rack_slot_id', 'box_number',
            'retention_expiry_date', 'status', 'period_start_date', 'period_end_date',
            'period_text', 'periode_doc', 'periode', 'physical_condition', 'content_description'
        )->with([
            'department:id,name,code',
            'subDepartment:id,name',
            'location:id,warehouse_id,room_sector,rack_code,shelf_code',
            'location.warehouse:id,name,code',
            'rackSlot:id,warehouse_location_id,sap_level,layer,slot_number,slot_code',
            'items:id,archive_id,item_number,document_name,period_start,period_end,period_text,notes',
            'borrowingLogs' => function ($q) {
                $q->orderBy('id', 'desc');
            }
        ])->where('status', 'in_warehouse');

        if ($user->isPicDept()) {
            $archivesQuery->where('department_id', $user->department_id);
        }

        $archives = $archivesQuery->get()->map(function ($arc) {
            $activeLog = $arc->borrowingLogs->whereIn('status', ['requested', 'dept_approved', 'approved'])->first();
            $rejectedLog = !$activeLog ? $arc->borrowingLogs->where('status', 'rejected')->first() : null;
            $arc->has_active_borrowing = !empty($activeLog);
            $arc->has_rejected_borrowing = !empty($rejectedLog);
            $arc->rejected_note = $rejectedLog ? $rejectedLog->approval_notes : null;
            return $arc;
        });

        // Support both single archive_id and multiple archive_ids (e.g. from catalog checkboxes)
        $selectedArchiveIds = [];
        if ($request->filled('archive_ids')) {
            $raw = $request->query('archive_ids');
            $selectedArchiveIds = is_array($raw) ? $raw : explode(',', (string)$raw);
        } elseif ($request->filled('archive_id')) {
            $selectedArchiveIds = [$request->query('archive_id')];
        }
        $selectedArchiveIds = array_filter(array_map('intval', (array)$selectedArchiveIds));
        $selectedArchiveId = !empty($selectedArchiveIds) ? $selectedArchiveIds[0] : null;

        $from = $request->query('from') ?: (strpos(url()->previous(), 'archives') !== false ? 'archives' : 'borrowings');

        return view('borrowings.create', compact('archives', 'selectedArchiveId', 'selectedArchiveIds', 'from'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user && $user->isPicGudang()) {
            abort(403, 'Akses Ditolak: PIC Gudang tidak memiliki hak untuk membuat pengajuan penarikan. Wewenang gudang hanya untuk pengeluaran (dispatch) dan approval.');
        }

        // Support both single archive_id and multiple archive_ids (Multi-Box Penarikan)
        $rawArchiveIds = [];
        if ($request->has('archive_ids') && is_array($request->input('archive_ids'))) {
            $rawArchiveIds = array_filter(array_map('intval', $request->input('archive_ids')));
        } elseif ($request->has('boxes') && is_array($request->input('boxes'))) {
            foreach ($request->input('boxes') as $b) {
                if (!empty($b['archive_id'])) {
                    $rawArchiveIds[] = (int)$b['archive_id'];
                }
            }
        } elseif ($request->filled('archive_id')) {
            $rawArchiveIds = [(int)$request->input('archive_id')];
        }

        $archiveIds = array_values(array_unique($rawArchiveIds));

        if (empty($archiveIds)) {
            return back()->withInput()->withErrors([
                'archive_ids' => 'Minimal satu berkas arsip wajib dipilih untuk pengajuan penarikan.',
                'archive_id' => 'Berkas arsip wajib dipilih.',
            ]);
        }

        $validated = $request->validate([
            'purpose' => 'required|string|max:500',
            'expected_return_date' => 'nullable|date',
            'approval_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:500',
        ]);

        $archives = Archive::whereIn('id', $archiveIds)->get();

        if ($archives->count() !== count($archiveIds)) {
            return back()->withInput()->with('error', 'Satu atau lebih berkas arsip yang dipilih tidak ditemukan.');
        }

        // Validasi ketersediaan di gudang (in_warehouse)
        $unavailableArchives = $archives->where('status', '!==', 'in_warehouse');
        if ($unavailableArchives->isNotEmpty()) {
            $unavailLabels = $unavailableArchives->map(function ($a) {
                return ($a->box_number ? "[{$a->box_number}] " : '') . $a->title;
            })->implode(', ');
            return back()->withInput()->with('error', "Berkas arsip berikut tidak tersedia di gudang untuk ditarik: {$unavailLabels}");
        }

        // Validasi jika arsip sedang dalam pengajuan penarikan aktif (Poin 6)
        $busyBorrowings = \App\Models\BorrowingLog::whereIn('archive_id', $archiveIds)
            ->whereIn('status', ['requested', 'dept_approved', 'approved'])
            ->with('archive')
            ->get();

        if ($busyBorrowings->isNotEmpty()) {
            $busyLabels = $busyBorrowings->map(function ($b) {
                return ($b->archive && $b->archive->box_number ? "[{$b->archive->box_number}] " : '') . ($b->archive ? $b->archive->title : "ID #{$b->archive_id}");
            })->unique()->implode(', ');
            return back()->withInput()->with('error', "Gagal mengajukan penarikan: Box arsip berikut sedang dalam proses penarikan aktif: {$busyLabels}");
        }

        // Proteksi departemen untuk PIC Dept
        if ($user->isPicDept()) {
            $unauthorized = $archives->where('department_id', '!==', (int)$user->department_id);
            if ($unauthorized->isNotEmpty()) {
                return back()->withInput()->with('error', 'Anda hanya berhak mengajukan penarikan untuk berkas milik departemen Anda.');
            }
        }

        $approvalPath = $request->file('approval_file')->store('borrowing_approvals', 'public');

        $isPermanent = $request->has('is_permanent') || empty($validated['expected_return_date']);
        $expectedReturnDate = $isPermanent ? null : $validated['expected_return_date'];

        $createdLogs = [];
        $boxNumbers = [];
        $titles = [];

        try {
            db_immediate_transaction(function () use ($archives, $archiveIds, $user, $expectedReturnDate, $validated, $approvalPath, &$createdLogs, &$boxNumbers, &$titles) {
                // Concurrency guard: pastikan tidak ada log aktif yang baru saja dibuat oleh sesi paralel
                $alreadyActive = \App\Models\BorrowingLog::whereIn('archive_id', $archiveIds)
                    ->whereIn('status', ['requested', 'dept_approved', 'approved'])
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyActive) {
                    throw new \DomainException('Satu atau lebih berkas arsip yang dipilih telah diajukan penarikan oleh pengguna lain.');
                }

                foreach ($archives as $archive) {
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

                    // Refresh archive updated_at so it jumps to the top of catalog sorting
                    $archive->touch();

                    $createdLogs[] = $borrowing;
                    if ($archive->box_number) {
                        $boxNumbers[] = $archive->box_number;
                    }
                    $titles[] = $archive->title;
                }
            });
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $boxCount = count($archives);
        $boxListStr = !empty($boxNumbers) ? implode(', ', $boxNumbers) : implode(', ', array_slice($titles, 0, 3));

        ActivityLogger::log(
            'BORROW_CREATE',
            "Pengajuan penarikan {$boxCount} box berkas ('{$boxListStr}') oleh {$user->name} untuk keperluan: {$validated['purpose']}",
            'PENARIKAN_BERKAS',
            [
                'box_count' => $boxCount,
                'archive_ids' => $archiveIds,
                'box_numbers' => $boxNumbers,
                'purpose' => $validated['purpose'],
                'expected_return_date' => $expectedReturnDate,
            ],
            !empty($boxNumbers) ? implode(', ', array_slice($boxNumbers, 0, 2)) : "Batch {$boxCount} Box"
        );

        $successMessage = $boxCount > 1
            ? "Permintaan penarikan {$boxCount} box berkas berhasil diajukan dengan lampiran form persetujuan. Menunggu verifikasi Departemen & pengeluaran fisik Gudang."
            : "Permintaan penarikan berkas berhasil diajukan dengan lampiran form persetujuan. Menunggu verifikasi Departemen & pengeluaran fisik Gudang.";

        $from = $request->input('from', 'borrowings');
        $redirectParams = $request->has('embed') ? ['embed' => 1] : [];
        $targetRoute = ($from === 'archives') ? 'archives.index' : 'borrowings.index';

        return redirect()->route($targetRoute, $redirectParams)
            ->with('success', $successMessage);
    }

    public function deptApprove(BorrowingLog $borrowing, Request $request)
    {
        $user = auth()->user();

        if (!$user->isSuperAdmin() && (!$user->isPicDept() || (int)$user->department_id !== (int)$borrowing->archive->department_id)) {
            abort(403, 'Hanya PIC Departemen pemilik berkas atau Admin yang dapat menyetujui peminjaman ini.');
        }

        $request->validate([
            'approval_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
            'scan_approval_borrow' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
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

    public function reject(BorrowingLog $borrowing, Request $request)
    {
        $user = auth()->user();
        if (!$user->isPicGudang() && !$user->isSuperAdmin() && !$user->isPicDept()) {
            abort(403);
        }

        if ($user->isPicDept() && $borrowing->archive && (int)$borrowing->archive->department_id !== (int)$user->department_id) {
            abort(403);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $reason = $request->input('reason');

        $borrowing->update([
            'status' => 'rejected',
            'approval_status' => 'rejected',
            'approval_notes' => $reason,
            'pic_gudang_id' => $user->isPicGudang() ? $user->id : $borrowing->pic_gudang_id,
        ]);

        ActivityLogger::log(
            'BORROW_REJECT',
            "Penolakan pengajuan penarikan arsip '{$borrowing->archive->title}' (Box: {$borrowing->archive->box_number}) oleh {$user->name}. Alasan: {$reason}",
            'PENARIKAN_BERKAS',
            [
                'borrowing_id' => $borrowing->id,
                'archive_id' => $borrowing->archive_id,
                'box_number' => $borrowing->archive ? $borrowing->archive->box_number : null,
                'reason' => $reason,
                'rejected_by' => $user->id,
            ],
            $borrowing->archive ? $borrowing->archive->box_number : 'Pengajuan Penarikan'
        );

        return redirect()->back()
            ->with('success', "Pengajuan penarikan berkas arsip (Box: " . ($borrowing->archive->box_number ?? '-') . ") berhasil ditolak.");
    }

    public function dispatch(BorrowingLog $borrowing, Request $request)
    {
        if (!auth()->user()->isPicGudang() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $request->validate([
            'approval_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
            'scan_approval_borrow' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
        ]);

        $scanPath = $borrowing->approval_file ?? $borrowing->scan_approval_borrow;
        if ($request->hasFile('approval_file')) {
            $scanPath = $request->file('approval_file')->store('borrowing_approvals', 'public');
        } elseif ($request->hasFile('scan_approval_borrow')) {
            $scanPath = $request->file('scan_approval_borrow')->store('borrowing_approvals', 'public');
        }

        $archive = $borrowing->archive;
        $items = ($archive && $archive->items) ? $archive->items->map(function ($it) {
            return [
                'number' => $it->item_number,
                'name' => $it->document_name,
                'period' => $it->period_text,
                'notes' => $it->notes
            ];
        })->toArray() : [];

        $borrowing->update([
            'status' => 'dispatched',
            'borrow_date' => now(),
            'pic_gudang_id' => auth()->id(),
            'approval_file' => $scanPath,
            'scan_approval_borrow' => $scanPath,
            'approval_status' => 'approved',
            'department_approved_at' => $borrowing->department_approved_at ?: now(),
            'department_approval_by' => $borrowing->department_approval_by ?: auth()->id(),
            'snapshot_box_number' => $archive ? $archive->box_number : null,
            'snapshot_title' => $archive ? $archive->title : null,
            'snapshot_department' => ($archive && $archive->department) ? $archive->department->code : 'DEPT',
            'snapshot_items' => json_encode($items),
        ]);
        $isPermanent = empty($borrowing->expected_return_date);
        $archiveStatus = $isPermanent ? 'taken' : 'borrowed';

        $archive->update([
            'status' => $archiveStatus,
            'warehouse_location_id' => null,
            'warehouse_rack_slot_id' => null,
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

        ActivityLogger::log(
            'BORROW_DISPATCH',
            "Pengeluaran berkas fisik arsip '{$archive->title}' (Box: {$archive->box_number}) oleh PIC Gudang " . auth()->user()->name,
            'PENARIKAN_BERKAS',
            [
                'borrowing_id' => $borrowing->id,
                'archive_id' => $archive->id,
                'box_number' => $archive->box_number,
                'status' => $borrowing->status,
            ],
            $archive->box_number
        );

        $successMsg = 'Pengeluaran berkas disahkan! Status penarikan berubah menjadi "Sudah Ditarik" dan slot rak telah dikosongkan.';

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

    public function update(BorrowingLog $borrowing, Request $request)
    {
        $user = auth()->user();

        // Check permission: superadmin/admin, or borrower / pic dept
        if (!$user->isSuperAdmin() && !$user->isAdmin() && (int)$borrowing->borrower_user_id !== (int)$user->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah pengajuan penarikan ini.');
        }

        $validated = $request->validate([
            'purpose' => 'required|string|max:500',
            'expected_return_date' => 'nullable|date',
            'approval_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
        ]);

        $isPermanent = $request->has('is_permanent') || empty($validated['expected_return_date']);
        $expectedReturnDate = $isPermanent ? null : $validated['expected_return_date'];

        $updateData = [
            'purpose' => $validated['purpose'],
            'expected_return_date' => $expectedReturnDate,
        ];

        if ($request->hasFile('approval_file')) {
            $approvalPath = $request->file('approval_file')->store('borrowing_approvals', 'public');
            $updateData['approval_file'] = $approvalPath;
            $updateData['scan_approval_borrow'] = $approvalPath;
            $updateData['is_approval_uploaded'] = true;
        }

        $borrowing->update($updateData);

        ActivityLogger::log(
            'BORROW_UPDATE',
            "Pembaruan data penarikan berkas '{$borrowing->archive->title}' (Box: {$borrowing->archive->box_number}) oleh {$user->name}",
            'PENARIKAN_BERKAS',
            [
                'borrowing_id' => $borrowing->id,
                'box_number' => $borrowing->archive->box_number,
                'purpose' => $validated['purpose'],
            ],
            $borrowing->archive->box_number
        );

        return redirect()->back()
            ->with('success', 'Data pengajuan penarikan berkas dan kelengkapan dokumen berhasil diperbarui.');
    }
}
