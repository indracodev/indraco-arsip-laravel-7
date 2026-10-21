<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use App\Models\WarehouseEntryLog;
use App\Models\BorrowingLog;
use App\Models\DestructionLog;
use App\Models\Department;
use App\Services\ActivityLogger;
use App\Services\NumberingService;
use Illuminate\Http\Request;

class ArchiveVerificationController extends Controller
{
    /**
     * Verifikasi fisik box arsip oleh PIC Gudang / Superadmin.
     */
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

    /**
     * Check-in fisik berkas box arsip ke lokasi rak gudang.
     */
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
        if ($location->location_type === 'room') {
            return back()->with('error', 'Akses Ditolak: Lokasi yang dipilih adalah sektor/ruangan. Silakan pilih baris rak fisik yang tersedia.');
        }

        // Validasi Pembatasan Departemen yang Diizinkan
        $parentRoom = null;
        if ($location->location_type !== 'room' && !empty($location->room_sector)) {
            $parentRoom = WarehouseLocation::where('location_type', 'room')
                ->where('room_sector', $location->room_sector)
                ->first();
        }

        $allowedDeptIds = $location->allowed_department_ids;
        if (empty($allowedDeptIds) && $parentRoom) {
            $allowedDeptIds = $parentRoom->allowed_department_ids;
        }

        if (empty($allowedDeptIds) && ($location->is_fat_locked || ($location->warehouse && $location->warehouse->is_fat_locked))) {
            $allowedDeptIds = Department::whereIn('code', ['FIN', 'FAT', 'FATCLM'])->orWhere('name', 'like', '%FAT%')->pluck('id')->all();
        }

        if (!empty($allowedDeptIds) && $archive->department_id && !in_array((int)$archive->department_id, $allowedDeptIds, true)) {
            $deptNames = Department::whereIn('id', $allowedDeptIds)->pluck('code')->implode(', ');
            $arcDeptName = $archive->department ? "{$archive->department->code} ({$archive->department->name})" : "Departemen Terkait";
            return back()->with('error', "Akses Ditolak: Lokasi '{$location->rack_code}' dikhususkan untuk [{$deptNames}]. Box dari {$arcDeptName} tidak diizinkan dialokasikan ke lokasi ini.");
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

    /**
     * Checkout fisik (pengeluaran atau pemusnahan berkas).
     */
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
            WarehouseRackSlot::where('archive_id', $archive->id)->update([
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

            DestructionLog::create([
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
            BorrowingLog::create([
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
            WarehouseRackSlot::where('archive_id', $archive->id)->update([
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
                    'status' => 'taken',
                    'status_label' => $statusLabel,
                ]);
            }

            return redirect()->back()->with('success', $msg);
        }
    }
}
