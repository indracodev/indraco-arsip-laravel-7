<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HandlesWarehouseLayoutSlots;
use App\Models\Archive;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use App\Services\ActivityLogger;
use App\Services\NumberingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WarehouseLayoutController extends Controller
{
    use HandlesWarehouseLayoutSlots;

    public function index()

    {
        $isSuperAdmin = auth()->check() && auth()->user()->isSuperAdmin();
        $warehouses = $isSuperAdmin 
            ? Warehouse::all() 
            : Warehouse::whereNotIn('code', ['GUDANG GA', 'GUDANG IT', 'GA', 'IT'])->get();
        $departments = Department::with(['subDepartments' => function ($q) {
            $q->where('is_active', true);
        }])->where('is_active', true)->get();
        return view('master.warehouse_layout', compact('warehouses', 'departments'));
    }

    public function apiLayoutData(Request $request)
    {
        $warehouseId = $request->query('warehouse_id');
        $isSuperAdmin = auth()->check() && auth()->user()->isSuperAdmin();

        $query = WarehouseLocation::with([
            'warehouse',
            'assignedDepartment',
            'bookedBy',
            'archives.department',
            'archives.subDepartment',
            'archives.creator',
            'archives.items',
            'slots.archive.department',
            'slots.archive.subDepartment',
            'slots.archive.items',
        ]);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if (!$isSuperAdmin) {
            $query->whereNotIn('rack_code', ['GUDANG GA', 'GUDANG IT', 'GA', 'IT'])
                  ->where(function($q) {
                      $q->whereNull('room_sector')
                        ->orWhereNotIn('room_sector', ['GUDANG GA', 'GUDANG IT', 'GA', 'IT']);
                  });
        }

        $locations = $query->get()->map(function ($loc) {
            if ($loc->location_type === 'rack' && $loc->slots->count() === 0) {
                $loc->generateStandardSlots();
                $loc->load(['slots.archive.department', 'slots.archive.subDepartment', 'slots.archive.items']);
            }

            $roomSector = strtoupper(trim($loc->room_sector ?? ''));
            $isFatLocked = (bool) ($loc->is_fat_locked 
                || ($loc->warehouse && $loc->warehouse->is_fat_locked)
                || in_array($roomSector, ['R1', 'R2', 'RUANG 1', 'RUANG 2', 'SEKTOR R1', 'SEKTOR R2'])
                || preg_match('/^R[12]$/i', $roomSector));

            return [
                'id' => $loc->id,
                'warehouse_id' => $loc->warehouse_id,
                'location_type' => $loc->location_type ?? 'rack',
                'room_sector' => $loc->room_sector ?? 'Umum',
                'rack_code' => $loc->rack_code,
                'shelf_code' => $loc->shelf_code,
                'box_capacity' => $loc->box_capacity ?? 100,
                'total_sap' => $loc->total_sap ?? 5,
                'boxes_per_sap' => $loc->boxes_per_sap ?? 20,
                'box_type' => $loc->box_type ?? 'TB 30g',
                'current_box_count' => $loc->current_box_count,
                'capacity_percentage' => $loc->capacity_percentage,
                'custom_color' => $loc->custom_color,
                'status_color' => $loc->status_color,
                'canvas_x' => $loc->canvas_x,
                'canvas_y' => $loc->canvas_y,
                'canvas_width' => $loc->canvas_width,
                'canvas_height' => $loc->canvas_height,
                'orientation' => $loc->orientation,
                'rotation_angle' => $loc->rotation_angle ?? 0,
                'is_locked' => (bool) ($loc->is_locked ?? true),
                'is_fat_locked' => $isFatLocked,
                'is_active' => (bool) ($loc->is_active ?? true),
                'assigned_department_id' => $loc->assigned_department_id,
                'assigned_department_ids' => $loc->allowed_department_ids,
                'assigned_department' => $loc->assignedDepartment ? [
                    'id' => $loc->assignedDepartment->id,
                    'code' => $loc->assignedDepartment->code,
                    'name' => $loc->assignedDepartment->name,
                ] : null,
                'is_booked' => $loc->is_booked,
                'booked_by_user' => $loc->bookedBy ? $loc->bookedBy->name : null,
                'booking_notes' => $loc->booking_notes,
                'slots' => $loc->slots->map(function ($slot) {
                    $hasActiveArchive = $slot->archive && in_array($slot->archive->status, ['in_warehouse', 'approved_booked', 'pending_verification']);
                    $isExpired = false;
                    $isNearExp = false;
                    if ($hasActiveArchive) {
                        $isExpired = (bool) $slot->archive->is_expired;
                        $isNearExp = (bool) $slot->archive->is_near_exp;
                    }
                    $status = $slot->status;
                    if (!$hasActiveArchive) {
                        $status = 'empty';
                    } elseif ($isExpired) {
                        $status = 'expired';
                    } elseif ($isNearExp) {
                        $status = 'near_exp';
                    }
                    if ($slot->is_active === false) {
                        $status = 'inactive';
                    }

                    return [
                        'id' => $slot->id,
                        'sap_level' => (int) $slot->sap_level,
                        'layer' => $slot->layer,
                        'layer_label' => $slot->layer_label,
                        'slot_number' => (int) $slot->slot_number,
                        'slot_code' => $slot->slot_code,
                        'status' => $status,
                        'is_expired' => $isExpired,
                        'is_near_exp' => $isNearExp,
                        'status_badge' => $status === 'filled' ? 'bg-amber-400 text-slate-900' : ($status === 'expired' ? 'bg-red-500 text-white' : 'bg-emerald-500 text-white'),
                        'archive' => $hasActiveArchive ? [
                            'id' => $slot->archive->id,
                            'box_number' => $slot->archive->box_number,
                            'title' => $slot->archive->effective_title ?? $slot->archive->title,
                            'periode_doc' => $slot->archive->periode_doc ?? $slot->archive->period_text ?? '-',
                            'retention_expiry_date' => $slot->archive->retention_expiry_date ? $slot->archive->retention_expiry_date->format('M Y') : '-',
                            'retention_display' => $slot->archive->retention_display,
                            'retention_duration_label' => $slot->archive->retention_duration_label,
                            'department' => $slot->archive->department ? $slot->archive->department->code : 'Dept',
                            'department_name' => $slot->archive->department ? $slot->archive->department->name : '',
                            'sub_department' => $slot->archive->subDepartment ? $slot->archive->subDepartment->code : null,
                            'sub_department_name' => $slot->archive->subDepartment ? $slot->archive->subDepartment->name : null,
                            'is_expired' => $isExpired,
                            'is_near_exp' => $isNearExp,
                            'scan_input_form' => $slot->archive->scan_input_form ? app_storage_url($slot->archive->scan_input_form) : null,
                            'scan_input_form_stream' => $slot->archive->scan_input_form ? app_preview_stream_url($slot->archive->scan_input_form) : null,
                            'scan_input_form_raw' => $slot->archive->scan_input_form,
                            'scan_approval_input' => $slot->archive->scan_approval_input ? app_storage_url($slot->archive->scan_approval_input) : null,
                            'scan_approval_input_stream' => $slot->archive->scan_approval_input ? app_preview_stream_url($slot->archive->scan_approval_input) : null,
                            'scan_approval_input_raw' => $slot->archive->scan_approval_input,
                            'items_count' => $slot->archive->items ? $slot->archive->items->count() : 0,
                            'items' => $slot->archive->items ? $slot->archive->items->map(function ($it) {
                                return [
                                    'item_number' => $it->item_number,
                                    'document_name' => $it->document_name,
                                    'period_text' => $it->period_text,
                                    'notes' => $it->notes,
                                ];
                            }) : [],
                        ] : null,
                    ];
                }),
                'archives' => $loc->archives->map(function ($arc) {
                    return [
                        'id' => $arc->id,
                        'box_number' => $arc->box_number,
                        'title' => $arc->effective_title ?? $arc->title,
                        'company_name' => $arc->company_name ?? 'PT Indraco Jaya Perkasa',
                        'document_type' => $arc->document_type ?? 'UMUM',
                        'department' => $arc->department ? $arc->department->code : 'Dept',
                        'sub_department' => $arc->subDepartment ? $arc->subDepartment->code : null,
                        'period_yy_mm' => $arc->periode_doc ?? $arc->period_yy_mm ?? $arc->period_text,
                        'periode_doc' => $arc->periode_doc,
                        'retention_years' => $arc->retention_years,
                        'retention_display' => $arc->retention_display,
                        'retention_duration_label' => $arc->retention_duration_label,
                        'retention_expiry_date' => $arc->retention_expiry_date ? $arc->retention_expiry_date->format('M Y') : '-',
                        'is_expired' => $arc->is_expired,
                        'scan_input_form' => $arc->scan_input_form ? app_storage_url($arc->scan_input_form) : null,
                        'scan_input_form_stream' => $arc->scan_input_form ? app_preview_stream_url($arc->scan_input_form) : null,
                        'scan_input_form_raw' => $arc->scan_input_form,
                        'scan_approval_input' => $arc->scan_approval_input ? app_storage_url($arc->scan_approval_input) : null,
                        'scan_approval_input_stream' => $arc->scan_approval_input ? app_preview_stream_url($arc->scan_approval_input) : null,
                        'scan_approval_input_raw' => $arc->scan_approval_input,
                        'items_count' => $arc->items ? $arc->items->count() : 0,
                        'items' => $arc->items ? $arc->items->map(function ($it) {
                            return [
                                'item_number' => $it->item_number,
                                'document_name' => $it->document_name,
                                'period_text' => $it->period_text,
                                'notes' => $it->notes,
                            ];
                        }) : [],
                    ];
                }),
            ];
        });

        $departments = Department::with(['subDepartments' => function ($q) {
            $q->where('is_active', true);
        }])->where('is_active', true)->get();

        $unassignedQuery = Archive::with(['department', 'creator'])
            ->whereIn('status', ['pending_verification', 'approved_booked', 'approved', 'in_warehouse'])
            ->whereNull('warehouse_rack_slot_id')
            ->orderBy('id', 'desc');

        if ($warehouseId) {
            $targetWh = Warehouse::find($warehouseId);
            if ($targetWh) {
                $whCode = strtoupper(trim($targetWh->code ?? ''));
                if (in_array($whCode, ['GUDANG GA', 'GA'])) {
                    $unassignedQuery->whereHas('department', function($q) {
                        $q->where('code', 'GA');
                    });
                } elseif (in_array($whCode, ['GUDANG IT', 'IT'])) {
                    $unassignedQuery->whereHas('department', function($q) {
                        $q->whereIn('code', ['IT', 'MIS', 'EDP']);
                    });
                } else {
                    $unassignedQuery->whereHas('department', function($q) {
                        $q->whereNotIn('code', ['GA', 'IT']);
                    });
                }
            }
        } elseif (!$isSuperAdmin) {
            $unassignedQuery->whereHas('department', function($q) {
                $q->whereNotIn('code', ['GA', 'IT']);
            });
        }

        $unassignedArchives = $unassignedQuery->get()
            ->map(function ($arc) {
                $archiveFiles = [];
                if ($arc->scan_input_form) {
                    $archiveFiles[] = [
                        'name' => 'Scan Formulir Input',
                        'category' => 'Formulir Pendaftaran Fisik',
                        'url' => app_storage_url($arc->scan_input_form),
                        'stream_url' => app_preview_stream_url($arc->scan_input_form),
                        'raw_path' => $arc->scan_input_form,
                        'filename' => basename($arc->scan_input_form),
                        'ext' => strtolower(pathinfo($arc->scan_input_form, PATHINFO_EXTENSION)),
                    ];
                }
                if ($arc->file_path) {
                    $archiveFiles[] = [
                        'name' => 'Lampiran Digital Dokumen',
                        'category' => 'Softcopy Dokumen',
                        'url' => app_storage_url($arc->file_path),
                        'stream_url' => app_preview_stream_url($arc->file_path),
                        'raw_path' => $arc->file_path,
                        'filename' => basename($arc->file_path),
                        'ext' => strtolower(pathinfo($arc->file_path, PATHINFO_EXTENSION)),
                    ];
                }
                if ($arc->scan_approval_input) {
                    $archiveFiles[] = [
                        'name' => 'Scan Approval Input',
                        'category' => 'Bukti Persetujuan PIC',
                        'url' => app_storage_url($arc->scan_approval_input),
                        'stream_url' => app_preview_stream_url($arc->scan_approval_input),
                        'raw_path' => $arc->scan_approval_input,
                        'filename' => basename($arc->scan_approval_input),
                        'ext' => strtolower(pathinfo($arc->scan_approval_input, PATHINFO_EXTENSION)),
                    ];
                }

                return [
                    'id' => $arc->id,
                    'box_number' => $arc->box_number,
                    'title' => $arc->title,
                    'department_id' => $arc->department_id,
                    'department_code' => $arc->department ? $arc->department->code : 'Dept',
                    'department_name' => $arc->department ? $arc->department->name : '',
                    'department' => $arc->department ? $arc->department->name : '',
                    'creator' => $arc->creator ? $arc->creator->name : ($arc->created_by_user_id ? 'PIC / Staf' : '-'),
                    'periode' => $arc->effective_periode ?? $arc->periode_doc ?? $arc->period_text,
                    'periode_doc' => $arc->periode_doc ?? $arc->period_text,
                    'physical_condition' => $arc->physical_condition ?? 'Baik',
                    'files' => $archiveFiles,
                    'has_files' => count($archiveFiles) > 0,
                    'status' => $arc->status,
                    'is_approved' => in_array($arc->status, ['approved_booked', 'approved', 'in_warehouse']),
                ];
            });

        return response()->json([
            'success' => true,
            'locations' => $locations,
            'departments' => $departments,
            'unassigned_archives' => $unassignedArchives,
        ]);
    }

    public function storeLocation(Request $request)
    {
        $warehouse = Warehouse::first();

        $validated = $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'location_type' => 'required|string|in:room,rack',
            'rack_code' => 'required|string|max:100',
            'room_sector' => 'nullable|string|max:50',
            'shelf_code' => 'nullable|string|max:50',
            'box_capacity' => 'nullable|integer|min:0',
            'total_sap' => 'nullable|integer|min:1|max:10',
            'boxes_per_sap' => 'nullable|integer|min:1|max:50',
            'box_type' => 'nullable|string|max:50',
            'custom_color' => 'nullable|string|max:30',
            'is_fat_locked' => 'nullable|boolean',
            'canvas_x' => 'required|integer',
            'canvas_y' => 'required|integer',
            'canvas_width' => 'required|integer|min:20',
            'canvas_height' => 'required|integer|min:20',
            'orientation' => 'nullable|string|in:horizontal,vertical',
            'rotation_angle' => 'nullable|integer',
            'is_locked' => 'nullable|boolean',
        ]);

        $validated['warehouse_id'] = $validated['warehouse_id'] ?? ($warehouse ? $warehouse->id : 1);
        $validated['shelf_code'] = $validated['shelf_code'] ?? 'S1';
        $validated['box_capacity'] = $validated['box_capacity'] ?? 100;
        $validated['total_sap'] = $validated['total_sap'] ?? 5;
        $validated['boxes_per_sap'] = $validated['boxes_per_sap'] ?? 20;
        $validated['box_type'] = $validated['box_type'] ?? 'TB 30g';
        $validated['is_locked'] = $validated['is_locked'] ?? true;
        $validated['is_fat_locked'] = $validated['is_fat_locked'] ?? false;
        $validated['current_box_count'] = 0;

        $location = WarehouseLocation::create($validated);

        if ($location->location_type === 'rack') {
            $location->generateStandardSlots();
        }

        if ($location->location_type === 'room') {
            Warehouse::firstOrCreate(
                ['code' => $location->rack_code],
                [
                    'name' => 'Gudang ' . $location->rack_code,
                    'address' => 'Kawasan Industri Indraco - Sektor ' . ($location->room_sector ?? $location->rack_code),
                    'is_fat_locked' => $location->is_fat_locked,
                ]
            );
        }

        ActivityLogger::log(
            'LAYOUT_OBJECT_CREATE',
            "Menambahkan objek " . ($location->location_type === 'room' ? 'Gudang' : 'Rak') . " '{$location->rack_code}' pada denah layout 2D (Ukuran: {$location->canvas_width}x{$location->canvas_height}px).",
            'LAYOUT_GUDANG',
            [
                'location_id' => $location->id,
                'location_type' => $location->location_type,
                'rack_code' => $location->rack_code,
                'room_sector' => $location->room_sector,
            ],
            $location->rack_code
        );

        return response()->json([
            'success' => true,
            'message' => "Object '{$location->rack_code}' berhasil ditambahkan ke canvas.",
            'location' => $location,
        ]);
    }

    public function bookLocation(Request $request, WarehouseLocation $location)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'booking_notes' => 'required|string|max:500',
        ]);

        // Validasi Pembatasan Departemen yang Diizinkan (Fitur Baru)
        $parentRoom = null;
        if ($location->location_type !== 'room' && !empty($location->room_sector)) {
            $parentRoom = WarehouseLocation::where('location_type', 'room')
                ->where('room_sector', $location->room_sector)
                ->first();
        }

        $department = Department::find($validated['department_id']);
        $allowedDeptIds = $location->allowed_department_ids;
        if (empty($allowedDeptIds) && $parentRoom) {
            $allowedDeptIds = $parentRoom->allowed_department_ids;
        }

        if (!empty($allowedDeptIds) && !in_array((int)$validated['department_id'], $allowedDeptIds, true)) {
            $deptNames = Department::whereIn('id', $allowedDeptIds)->pluck('code')->implode(', ');
            $reqDeptName = $department ? "{$department->code} ({$department->name})" : "Departemen Anda";
            return response()->json([
                'success' => false,
                'message' => "Akses Ditolak: Lokasi ini dikhususkan untuk [{$deptNames}]. {$reqDeptName} tidak diizinkan mem-booking lokasi ini.",
            ], 403);
        }

        $location->update([
            'is_booked' => true,
            'assigned_department_id' => $validated['department_id'],
            'booked_by_user_id' => auth()->id(),
            'booking_notes' => $validated['booking_notes'],
        ]);

        ActivityLogger::log(
            'LAYOUT_SLOT_BOOK',
            "Booking slot rak '{$location->rack_code}' untuk alokasi departemen {$department->code} ({$department->name}). Catatan: {$validated['booking_notes']}",
            'LAYOUT_GUDANG',
            [
                'location_id' => $location->id,
                'rack_code' => $location->rack_code,
                'department_id' => $department->id,
                'booking_notes' => $validated['booking_notes'],
            ],
            $location->rack_code
        );

        return response()->json([
            'success' => true,
            'message' => "Slot rak '{$location->rack_code}' berhasil di-booking untuk departemen {$department->code}.",
            'location' => $location->fresh(['assignedDepartment', 'bookedBy']),
        ]);
    }

    public function unbookLocation(WarehouseLocation $location)
    {
        $location->update([
            'is_booked' => false,
            'booked_by_user_id' => null,
            'booking_notes' => null,
        ]);

        ActivityLogger::log(
            'LAYOUT_SLOT_UNBOOK',
            "Melepas status booking pada slot rak '{$location->rack_code}'.",
            'LAYOUT_GUDANG',
            ['location_id' => $location->id, 'rack_code' => $location->rack_code],
            $location->rack_code
        );

        return response()->json([
            'success' => true,
            'message' => "Status booking slot rak '{$location->rack_code}' berhasil dilepas.",
        ]);
    }

    public function updateLocation(Request $request, WarehouseLocation $location)
    {
        $oldCode = $location->rack_code;

        $validated = $request->validate([
            'rack_code' => 'required|string|max:100',
            'location_type' => 'nullable|string|in:room,rack',
            'room_sector' => 'nullable|string|max:50',
            'shelf_code' => 'nullable|string|max:50',
            'box_capacity' => 'nullable|integer|min:0|max:5000',
            'total_sap' => 'nullable|integer|min:1|max:10',
            'boxes_per_sap' => 'nullable|integer|min:1|max:50',
            'box_type' => 'nullable|string|max:50',
            'custom_color' => 'nullable|string|max:30',
            'is_fat_locked' => 'nullable|boolean',
            'assigned_department_id' => 'nullable|exists:departments,id',
            'assigned_department_ids' => 'nullable|array',
            'assigned_department_ids.*' => 'exists:departments,id',
            'canvas_x' => 'nullable|integer',
            'canvas_y' => 'nullable|integer',
            'canvas_width' => 'nullable|integer',
            'canvas_height' => 'nullable|integer',
            'orientation' => 'nullable|string|in:horizontal,vertical',
            'rotation_angle' => 'nullable|integer',
            'is_locked' => 'nullable|boolean',
        ]);

        // Security check: Hanya superadmin yang dapat mengatur pembatasan departemen
        if (!auth()->check() || !auth()->user()->isSuperAdmin()) {
            unset($validated['assigned_department_id'], $validated['assigned_department_ids']);
        } else {
            if (isset($validated['assigned_department_ids'])) {
                $ids = array_values(array_filter(array_map('intval', $validated['assigned_department_ids'])));
                $validated['assigned_department_ids'] = $ids;
                $validated['assigned_department_id'] = !empty($ids) ? $ids[0] : null;
            } elseif (array_key_exists('assigned_department_id', $validated)) {
                $validated['assigned_department_ids'] = $validated['assigned_department_id'] ? [(int)$validated['assigned_department_id']] : [];
            }
        }

        $location->update($validated);

        if ($location->location_type === 'rack') {
            $location->generateStandardSlots();
        }

        if ($location->location_type === 'room' && isset($validated['rack_code'])) {
            $wh = Warehouse::where('code', $oldCode)->first();
            $whData = [
                'code' => $validated['rack_code'],
                'name' => 'Gudang ' . $validated['rack_code'],
                'address' => 'Kawasan Industri Indraco - Sektor ' . ($validated['room_sector'] ?? $validated['rack_code']),
            ];
            if (isset($validated['is_fat_locked'])) {
                $whData['is_fat_locked'] = $validated['is_fat_locked'];
            }

            if ($wh) {
                $wh->update($whData);
            } else {
                Warehouse::firstOrCreate(['code' => $validated['rack_code']], $whData);
            }
        }

        ActivityLogger::log(
            'LAYOUT_OBJECT_UPDATE',
            "Memperbarui data / geometri objek '{$location->rack_code}' pada denah (W: {$location->canvas_width}px, H: {$location->canvas_height}px, Lock: " . ($location->is_locked ? 'Locked' : 'Unlocked') . ").",
            'LAYOUT_GUDANG',
            [
                'location_id' => $location->id,
                'rack_code' => $location->rack_code,
                'canvas_x' => $location->canvas_x,
                'canvas_y' => $location->canvas_y,
                'canvas_width' => $location->canvas_width,
                'canvas_height' => $location->canvas_height,
                'is_locked' => $location->is_locked,
            ],
            $location->rack_code
        );

        return response()->json([
            'success' => true,
            'message' => "Data object '{$location->rack_code}' berhasil diperbarui.",
            'location' => [
                'id' => $location->id,
                'rack_code' => $location->rack_code,
                'assigned_department_id' => $location->assigned_department_id,
                'assigned_department_ids' => $location->allowed_department_ids,
            ]
        ]);
    }

    public function destroyLocation(WarehouseLocation $location)
    {
        $name = $location->rack_code;

        if ($location->location_type === 'room') {
            Warehouse::where('code', $name)->delete();
        }

        $location->delete();

        ActivityLogger::log(
            'LAYOUT_OBJECT_DELETE',
            "Menghapus objek '{$name}' dari canvas layout gudang.",
            'LAYOUT_GUDANG',
            ['rack_code' => $name],
            $name
        );

        return response()->json([
            'success' => true,
            'message' => "Object '{$name}' berhasil dihapus dari canvas.",
        ]);
    }
}


