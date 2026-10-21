<?php

namespace App\Http\Controllers\Traits;

use App\Models\Archive;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use App\Services\ActivityLogger;
use App\Services\NumberingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

trait HandlesWarehouseLayoutSlots
{
    public function assignSlotArchive(Request $request, WarehouseLocation $location)
    {
        $user = auth()->user();
        if (!$user || (!$user->isPicGudang() && !$user->isSuperAdmin())) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Hanya Super Admin dan PIC Gudang yang berhak mengalokasikan slot rak gudang.',
            ], 403);
        }

        $validated = $request->validate([
            'sap_level' => 'required|integer|min:1|max:10',
            'layer' => 'required|string|in:top,bottom',
            'slot_number' => 'required|integer|min:1|max:100',
            'slot_code' => 'nullable|string|max:50',
            'mode' => 'required|string|in:create_new,existing_archive,assign_existing',
            // fields for create_new
            'department_id' => 'required_if:mode,create_new|nullable|exists:departments,id',
            'sub_department_id' => 'nullable|exists:sub_departments,id',
            'title' => 'required_if:mode,create_new|nullable|string|max:255',
            'box_number' => 'nullable|string|max:100',
            'periode_doc' => 'nullable|string|max:50',
            'document_type' => 'nullable|string|max:50',
            'retention_years' => 'nullable|integer|min:1|max:50',
            'content_description' => 'nullable|string',
            // field for existing_archive
            'archive_id' => 'required_if:mode,existing_archive,assign_existing|nullable|exists:archives,id',
        ]);

        if ($user->isPicGudang() && $validated['mode'] === 'create_new') {
            return response()->json([
                'success' => false,
                'message' => 'Akses Ditolak: PIC Gudang tidak memiliki hak untuk membuat pengajuan berkas baru. Wewenang gudang hanya untuk verifikasi fisik dan approval.',
            ], 403);
        }

        // Validasi Pembatasan Departemen yang Diizinkan (Fitur Baru)
        $parentRoom = null;
        if ($location->location_type !== 'room' && !empty($location->room_sector)) {
            $parentRoom = WarehouseLocation::where('location_type', 'room')
                ->where('room_sector', $location->room_sector)
                ->first();
        }

        $departmentId = $validated['department_id'] ?? null;
        $wasPending = false;
        if (in_array($validated['mode'], ['existing_archive', 'assign_existing'])) {
            $existingArc = Archive::findOrFail($validated['archive_id']);
            if (!in_array($existingArc->status, ['approved_booked', 'approved', 'pending_verification', 'in_warehouse'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Akses Ditolak: Status arsip tidak valid untuk penempatan ke rak (Status: " . ($existingArc->status_label ?? $existingArc->status) . ").",
                ], 422);
            }
            if ($existingArc->status === 'pending_verification') {
                $wasPending = true;
            }
            $departmentId = $existingArc->department_id;
        }

        // Validasi Proteksi Ruang FAT (R1 & R2)
        $sector = strtoupper(trim($location->room_sector ?? ($parentRoom ? $parentRoom->room_sector : '')));
        $isFatSector = in_array($sector, ['R1', 'R2', 'RUANG 1', 'RUANG 2', 'RUANG R1', 'RUANG R2', 'SEKTOR R1', 'SEKTOR R2']) || preg_match('/^R[12]$/i', $sector);
        if ($isFatSector) {
            $docDept = Department::find($departmentId);
            $isFatDept = $docDept && (in_array(strtoupper(trim($docDept->code)), ['FAT', 'FIN']) || stripos($docDept->name, 'Finance') !== false || stripos($docDept->name, 'Accounting') !== false);
            if (!$isFatDept) {
                return response()->json([
                    'success' => false,
                    'message' => "Akses Ditolak: Ruang/Sektor {$sector} dikhususkan hanya untuk dokumen Departemen FAT (Finance & Accounting). Dokumen dari departemen " . ($docDept ? $docDept->code : 'Lain') . " tidak diizinkan ditempatkan di sini.",
                ], 403);
            }
        }

        $allowedDeptIds = $location->allowed_department_ids;
        if (empty($allowedDeptIds) && $parentRoom) {
            $allowedDeptIds = $parentRoom->allowed_department_ids;
        }

        if (!empty($allowedDeptIds) && $departmentId && !in_array((int)$departmentId, $allowedDeptIds, true)) {
            $deptNames = Department::whereIn('id', $allowedDeptIds)->pluck('code')->implode(', ');
            $docDept = Department::find($departmentId);
            $docDeptName = $docDept ? "{$docDept->code} ({$docDept->name})" : "Departemen Terkait";

            return response()->json([
                'success' => false,
                'message' => "Akses Ditolak: Lokasi/Gudang '{$location->rack_code}' dikhususkan untuk [{$deptNames}]. Box dari {$docDeptName} tidak diizinkan ditempatkan di lokasi ini.",
            ], 403);
        }

        // Get or Create Slot in DB
        $rackId = $location->rack_identifier;
        $slotCode = $validated['slot_code'] ?? "{$rackId}{$validated['slot_number']}";

        $slot = WarehouseRackSlot::firstOrCreate(
            [
                'warehouse_location_id' => $location->id,
                'sap_level' => $validated['sap_level'],
                'layer' => $validated['layer'],
                'slot_number' => $validated['slot_number'],
            ],
            [
                'slot_code' => $slotCode,
                'status' => 'empty',
                'is_active' => true,
            ]
        );

        if ($slot->is_active === false) {
            return response()->json([
                'success' => false,
                'message' => "Akses Ditolak: Slot '{$slot->slot_code}' saat ini dalam status NON-AKTIF (Inactive). Silakan aktifkan slot terlebih dahulu untuk menempatkan arsip.",
            ], 403);
        }

        $archive = null;

        if ($validated['mode'] === 'create_new') {
            $dept = Department::findOrFail($validated['department_id']);
            $subDept = !empty($validated['sub_department_id']) ? SubDepartment::find($validated['sub_department_id']) : null;

            $retentionYears = (int) ($validated['retention_years'] ?? ($subDept && $subDept->retention_years ? $subDept->retention_years : ($dept->retention_years ?? 5)));
            if ($retentionYears <= 0) $retentionYears = 5;

            $expiryDate = Carbon::now()->addYears($retentionYears)->endOfYear();

            $boxNum = !empty($validated['box_number']) ? trim($validated['box_number']) : null;
            if ($boxNum) {
                // Validasi unique box_number (abaikan arsip berstatus taken/destroyed/borrowed)
                $boxConflict = Archive::where('box_number', $boxNum)
                    ->whereNotIn('status', ['taken', 'destroyed', 'borrowed'])
                    ->first();
                if ($boxConflict) {
                    return response()->json([
                        'success' => false,
                        'message' => "Nomor Box '{$boxNum}' sudah digunakan oleh arsip aktif lain (#{$boxConflict->id} - {$boxConflict->title}). Harap gunakan nomor box lain.",
                    ], 422);
                }
            }

            $periodText = $validated['periode_doc'] ?? date('Y/m');
            $periodStart = Carbon::now()->startOfMonth();
            $periodEnd = Carbon::now()->endOfMonth();

            if (preg_match('/^(\d{4})[\/-](\d{1,2})$/', $periodText, $matches)) {
                $y = (int) $matches[1];
                $m = (int) $matches[2];
                $periodStart = Carbon::createFromDate($y, $m, 1)->startOfMonth();
                $periodEnd = Carbon::createFromDate($y, $m, 1)->endOfMonth();
            } elseif (preg_match('/^(\d{4})$/', $periodText, $matches)) {
                $y = (int) $matches[1];
                $periodStart = Carbon::createFromDate($y, 1, 1)->startOfYear();
                $periodEnd = Carbon::createFromDate($y, 12, 31)->endOfYear();
            }

            $archive = new Archive([
                'department_id' => $dept->id,
                'sub_department_id' => $subDept ? $subDept->id : null,
                'company_name' => 'PT Indraco',
                'document_type' => $validated['document_type'] ?? 'UMUM',
                'created_by_user_id' => auth()->id() ?? 1,
                'title' => $validated['title'],
                'periode_doc' => $periodText,
                'period_text' => $periodText,
                'period_start_date' => $periodStart,
                'period_end_date' => $periodEnd,
                'tgl_penyerahan' => Carbon::now(),
                'content_description' => !empty($validated['content_description']) ? $validated['content_description'] : ($validated['title'] ?? 'Dokumen Arsip'),
                'retention_years' => $retentionYears,
                'retention_expiry_date' => $expiryDate,
                'physical_condition' => 'Baik',
                'warehouse_location_id' => $location->id,
                'warehouse_rack_slot_id' => $slot->id,
                'status' => 'in_warehouse',
            ]);

            if (!$boxNum) {
                $numberingService = new NumberingService();
                $archive->box_number = $numberingService->generateBoxCode($archive);
            } else {
                $archive->box_number = $boxNum;
            }

            $archive->save();
        } else {
            $archive = Archive::with('department')->findOrFail($validated['archive_id']);
            $numberingService = new NumberingService();
            if (empty($archive->box_number)) {
                $archive->box_number = $numberingService->generateBoxCode($archive);
            }

            $updateData = [
                'warehouse_location_id' => $location->id,
                'warehouse_rack_slot_id' => $slot->id,
                'status' => 'in_warehouse',
                'box_number' => $archive->box_number,
                'rejection_note' => null,
            ];

            if (!empty($validated['box_number'])) {
                $manualBox = trim($validated['box_number']);
                // Validasi unique box_number (abaikan arsip berstatus taken/destroyed/borrowed dan arsip itu sendiri)
                $boxConflict = Archive::where('box_number', $manualBox)
                    ->where('id', '!=', $archive->id)
                    ->whereNotIn('status', ['taken', 'destroyed', 'borrowed'])
                    ->first();
                if ($boxConflict) {
                    return response()->json([
                        'success' => false,
                        'message' => "Nomor Box '{$manualBox}' sudah digunakan oleh arsip aktif lain (#{$boxConflict->id} - {$boxConflict->title}). Harap gunakan nomor box lain.",
                    ], 422);
                }
                $archive->box_number = $manualBox;
                $updateData['box_number'] = $archive->box_number;
            }

            $archive->update($updateData);
            $archive->refresh();

            if ($wasPending) {
                ActivityLogger::log(
                    'ARCHIVE_VERIFY',
                    "Verifikasi persetujuan (Approve) & penempatan box '{$archive->box_number}' di slot {$slot->slot_code} pada rak '{$location->rack_code}'.",
                    'DOKUMEN_ARSIP',
                    [
                        'archive_id' => $archive->id,
                        'box_number' => $archive->box_number,
                        'status' => 'in_warehouse',
                        'slot_code' => $slot->slot_code,
                    ],
                    $archive->box_number
                );
            }
        }

        // Link slot to archive
        $slot->update([
            'archive_id' => $archive->id,
            'status' => 'filled',
        ]);

        // Recalculate filled box count for rack
        $filledCount = $location->slots()->where('status', '!=', 'empty')->count();
        $location->update(['current_box_count' => $filledCount]);

        ActivityLogger::log(
            'SLOT_ASSIGN',
            "Penempatan box '{$archive->box_number}' di slot {$slot->slot_code} pada rak '{$location->rack_code}'.",
            'LAYOUT_GUDANG',
            [
                'location_id' => $location->id,
                'rack_code' => $location->rack_code,
                'slot_code' => $slot->slot_code,
                'archive_id' => $archive->id,
                'box_number' => $archive->box_number,
                'title' => $archive->title,
            ],
            $archive->box_number
        );

        $successMsg = $wasPending
            ? "Box arsip '{$archive->box_number}' berhasil disetujui (ACC) dan ditempatkan di slot {$slot->slot_code}."
            : "Box arsip '{$archive->box_number}' berhasil ditempatkan di slot {$slot->slot_code}.";

        return response()->json([
            'success' => true,
            'message' => $successMsg,
            'display_location' => $archive->display_location,
            'location_full' => $archive->display_location,
            'slot' => [
                'id' => $slot->id,
                'slot_code' => $slot->slot_code,
                'sap_level' => (int) $slot->sap_level,
                'layer' => $slot->layer,
                'layer_label' => $slot->layer_label,
                'slot_number' => (int) $slot->slot_number,
                'status' => 'filled',
                'archive' => [
                    'id' => $archive->id,
                    'box_number' => $archive->box_number,
                    'title' => $archive->title,
                    'periode_doc' => $archive->periode_doc,
                    'retention_duration_label' => $archive->retention_duration_label,
                    'retention_expiry_date' => $archive->retention_expiry_date ? $archive->retention_expiry_date->format('d M Y') : '-',
                    'department' => $archive->department ? $archive->department->code : 'Dept',
                    'department_name' => $archive->department ? $archive->department->name : '',
                    'sub_department' => $archive->subDepartment ? $archive->subDepartment->code : null,
                    'sub_department_name' => $archive->subDepartment ? $archive->subDepartment->name : null,
                    'is_expired' => (bool) $archive->is_expired,
                    'is_near_exp' => (bool) $archive->is_near_exp,
                ],
            ],
        ]);
    }

    public function unassignSlotArchive(Request $request, WarehouseLocation $location)
    {
        $user = auth()->user();
        if (!$user || (!$user->isPicGudang() && !$user->isSuperAdmin())) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Hanya Super Admin dan PIC Gudang yang berhak menghapus penempatan slot rak gudang.',
            ], 403);
        }

        $validated = $request->validate([
            'sap_level' => 'required|integer|min:1|max:10',
            'layer' => 'required|string|in:top,bottom',
            'slot_number' => 'required|integer|min:1|max:100',
        ]);

        $slot = WarehouseRackSlot::where('warehouse_location_id', $location->id)
            ->where('sap_level', $validated['sap_level'])
            ->where('layer', $validated['layer'])
            ->where('slot_number', $validated['slot_number'])
            ->first();

        $releasedBox = null;
        if ($slot && $slot->archive_id) {
            $archive = Archive::find($slot->archive_id);
            if ($archive) {
                $releasedBox = $archive->box_number;
                $archive->update([
                    'warehouse_rack_slot_id' => null,
                ]);
            }
            $slot->update([
                'archive_id' => null,
                'status' => 'empty',
            ]);
        }

        $filledCount = $location->slots()->where('status', '!=', 'empty')->count();
        $location->update(['current_box_count' => $filledCount]);

        ActivityLogger::log(
            'SLOT_UNASSIGN',
            "Pengosongan slot {$slot->slot_code} pada rak '{$location->rack_code}'" . ($releasedBox ? " (Box {$releasedBox} dilepas)" : "") . ".",
            'LAYOUT_GUDANG',
            [
                'location_id' => $location->id,
                'rack_code' => $location->rack_code,
                'slot_code' => $slot ? $slot->slot_code : null,
                'released_box' => $releasedBox,
            ],
            $location->rack_code
        );

        return response()->json([
            'success' => true,
            'message' => "Slot berhasil dikosongkan.",
        ]);
    }

    public function toggleSlotActive(Request $request, WarehouseLocation $location)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses Ditolak: Hanya Super Admin yang diizinkan mengubah status aktif/non-aktif slot rak.',
            ], 403);
        }

        $validated = $request->validate([
            'slot_id' => 'nullable|exists:warehouse_rack_slots,id',
            'sap_level' => 'required|integer|min:1|max:10',
            'layer' => 'required|string|in:top,bottom',
            'slot_number' => 'required|integer|min:1|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $slotCode = "SAP-{$validated['sap_level']}-" . ($validated['layer'] === 'top' ? 'T' : 'B') . str_pad($validated['slot_number'], 2, '0', STR_PAD_LEFT);

        $slot = WarehouseRackSlot::firstOrCreate(
            [
                'warehouse_location_id' => $location->id,
                'sap_level' => $validated['sap_level'],
                'layer' => $validated['layer'],
                'slot_number' => $validated['slot_number'],
            ],
            [
                'slot_code' => $slotCode,
                'status' => 'empty',
                'is_active' => true,
            ]
        );

        $newActiveState = isset($validated['is_active']) ? (bool) $validated['is_active'] : !((bool) ($slot->is_active ?? true));
        $slot->is_active = $newActiveState;

        if (!$newActiveState) {
            if (!$slot->archive_id) {
                $slot->status = 'inactive';
            }
        } else {
            $slot->status = $slot->archive_id ? 'filled' : 'empty';
        }
        $slot->save();

        $statusText = $slot->is_active ? 'AKTIF (Active)' : 'NON-AKTIF (Inactive)';

        ActivityLogger::log(
            'SLOT_STATUS_TOGGLE',
            "SuperAdmin mengubah status slot rak {$location->rack_code} [{$slot->slot_code}] (Sap {$slot->sap_level}, {$slot->layer_label}, Slot {$slot->slot_number}) menjadi {$statusText}.",
            'LAYOUT_GUDANG',
            [
                'location_id' => $location->id,
                'rack_code' => $location->rack_code,
                'slot_id' => $slot->id,
                'slot_code' => $slot->slot_code,
                'sap_level' => $slot->sap_level,
                'layer' => $slot->layer,
                'slot_number' => $slot->slot_number,
                'is_active' => (bool) $slot->is_active,
            ],
            $location->rack_code
        );

        return response()->json([
            'success' => true,
            'message' => "Status slot {$slot->slot_code} berhasil diubah menjadi {$statusText}.",
            'is_active' => (bool) $slot->is_active,
            'status' => $slot->status,
            'slot' => [
                'id' => $slot->id,
                'slot_code' => $slot->slot_code,
                'sap_level' => (int) $slot->sap_level,
                'layer' => $slot->layer,
                'layer_label' => $slot->layer_label,
                'slot_number' => (int) $slot->slot_number,
                'status' => $slot->status,
                'is_active' => (bool) $slot->is_active,
            ]
        ]);
    }
}
