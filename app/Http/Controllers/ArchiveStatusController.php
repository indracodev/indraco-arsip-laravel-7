<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\WarehouseRackSlot;
use App\Models\DestructionLog;
use App\Models\BorrowingLog;
use App\Services\ActivityLogger;
use App\Services\NumberingService;
use Illuminate\Http\Request;

class ArchiveStatusController extends Controller
{
    /**
     * Override archive status directly by Super Admin / PIC Gudang.
     */
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
            WarehouseRackSlot::where('archive_id', $archive->id)->update([
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
                DestructionLog::where('archive_id', $archive->id)
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
            BorrowingLog::create([
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
            WarehouseRackSlot::where('archive_id', $archive->id)->update([
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

            $existingDestLog = DestructionLog::where('archive_id', $archive->id)
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
                DestructionLog::create([
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

            $existingDestLog = DestructionLog::where('archive_id', $archive->id)
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
            WarehouseRackSlot::where('archive_id', $archive->id)->update([
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
}
