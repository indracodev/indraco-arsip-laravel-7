<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use App\Services\NumberingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WarehouseLayoutController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::all();
        $departments = Department::with(['subDepartments' => function ($q) {
            $q->where('is_active', true);
        }])->where('is_active', true)->get();
        return view('master.warehouse_layout', compact('warehouses', 'departments'));
    }

    public function apiLayoutData(Request $request)
    {
        $warehouseId = $request->query('warehouse_id');

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

        $locations = $query->get()->map(function ($loc) {
            if ($loc->location_type === 'rack' && $loc->slots->count() === 0) {
                $loc->generateStandardSlots();
                $loc->load(['slots.archive.department', 'slots.archive.subDepartment', 'slots.archive.items']);
            }

            $isFatLocked = (bool) ($loc->is_fat_locked || ($loc->warehouse && $loc->warehouse->is_fat_locked));

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
                'assigned_department_id' => $loc->assigned_department_id,
                'assigned_department' => $loc->assignedDepartment ? [
                    'id' => $loc->assignedDepartment->id,
                    'code' => $loc->assignedDepartment->code,
                    'name' => $loc->assignedDepartment->name,
                ] : null,
                'is_booked' => $loc->is_booked,
                'booked_by_user' => $loc->bookedBy ? $loc->bookedBy->name : null,
                'booking_notes' => $loc->booking_notes,
                'slots' => $loc->slots->map(function ($slot) {
                    $isExpired = false;
                    if ($slot->archive) {
                        $isExpired = (bool) $slot->archive->is_expired;
                    }
                    $status = $slot->status;
                    if ($slot->archive && $isExpired) {
                        $status = 'expired';
                    }

                    return [
                        'id' => $slot->id,
                        'sap_level' => (int) $slot->sap_level,
                        'layer' => $slot->layer,
                        'layer_label' => $slot->layer_label,
                        'slot_number' => (int) $slot->slot_number,
                        'slot_code' => $slot->slot_code,
                        'status' => $status,
                        'status_badge' => $slot->status_badge,
                        'archive' => $slot->archive ? [
                            'id' => $slot->archive->id,
                            'box_number' => $slot->archive->box_number,
                            'title' => $slot->archive->effective_title ?? $slot->archive->title,
                            'periode_doc' => $slot->archive->periode_doc ?? $slot->archive->period_text ?? '-',
                            'retention_expiry_date' => $slot->archive->retention_expiry_date ? $slot->archive->retention_expiry_date->format('d M Y') : '-',
                            'department' => $slot->archive->department ? $slot->archive->department->code : 'Dept',
                            'department_name' => $slot->archive->department ? $slot->archive->department->name : '',
                            'sub_department' => $slot->archive->subDepartment ? $slot->archive->subDepartment->code : null,
                            'sub_department_name' => $slot->archive->subDepartment ? $slot->archive->subDepartment->name : null,
                            'is_expired' => $isExpired,
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
                        'retention_expiry_date' => $arc->retention_expiry_date ? $arc->retention_expiry_date->format('d M Y') : '-',
                        'is_expired' => $arc->is_expired,
                        'scan_input_form' => $arc->scan_input_form ? asset('storage/' . $arc->scan_input_form) : null,
                        'scan_approval_input' => $arc->scan_approval_input ? asset('storage/' . $arc->scan_approval_input) : null,
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

        $unassignedArchives = Archive::with('department')
            ->where(function ($q) {
                $q->whereNull('warehouse_rack_slot_id')
                  ->orWhere('status', 'draft')
                  ->orWhere('status', 'approved_booked');
            })
            ->whereNotIn('status', ['destroyed', 'in_warehouse'])
            ->get()
            ->map(function ($arc) {
                return [
                    'id' => $arc->id,
                    'box_number' => $arc->box_number,
                    'title' => $arc->title,
                    'department_id' => $arc->department_id,
                    'department_code' => $arc->department ? $arc->department->code : 'Dept',
                    'periode_doc' => $arc->periode_doc ?? $arc->period_text,
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

        // Business Rule: Room Locking for FAT
        $isLocationFat = $location->is_fat_locked || ($location->warehouse && $location->warehouse->is_fat_locked);
        $department = Department::find($validated['department_id']);
        $isDeptFat = ($department && strtoupper($department->code) === 'FIN');

        if ($isLocationFat && !$isDeptFat) {
            $deptName = $department ? $department->name : 'Departemen';
            return response()->json([
                'success' => false,
                'message' => "Akses Ditolak: Lokasi rak '{$location->rack_code}' berada di Ruangan Khusus FAT (Finance, Accounting & Tax). Departemen {$deptName} tidak diizinkan mem-booking lokasi ini.",
            ], 403);
        }

        $location->update([
            'is_booked' => true,
            'assigned_department_id' => $validated['department_id'],
            'booked_by_user_id' => auth()->id(),
            'booking_notes' => $validated['booking_notes'],
        ]);

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
            'canvas_x' => 'nullable|integer',
            'canvas_y' => 'nullable|integer',
            'canvas_width' => 'nullable|integer',
            'canvas_height' => 'nullable|integer',
            'orientation' => 'nullable|string|in:horizontal,vertical',
            'rotation_angle' => 'nullable|integer',
            'is_locked' => 'nullable|boolean',
        ]);

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

        return response()->json([
            'success' => true,
            'message' => "Data object '{$location->rack_code}' berhasil diperbarui.",
        ]);
    }

    public function destroyLocation(WarehouseLocation $location)
    {
        $name = $location->rack_code;

        if ($location->location_type === 'room') {
            Warehouse::where('code', $name)->delete();
        }

        $location->delete();

        return response()->json([
            'success' => true,
            'message' => "Object '{$name}' berhasil dihapus dari canvas.",
        ]);
    }

    public function assignSlotArchive(Request $request, WarehouseLocation $location)
    {
        $validated = $request->validate([
            'sap_level' => 'required|integer|min:1|max:10',
            'layer' => 'required|string|in:top,bottom',
            'slot_number' => 'required|integer|min:1|max:20',
            'slot_code' => 'nullable|string|max:50',
            'mode' => 'required|string|in:create_new,existing_archive',
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
            'archive_id' => 'required_if:mode,existing_archive|nullable|exists:archives,id',
        ]);

        // Room Locking for FAT validation
        $isLocationFat = $location->is_fat_locked || ($location->warehouse && $location->warehouse->is_fat_locked);

        $departmentId = $validated['department_id'] ?? null;
        if ($validated['mode'] === 'existing_archive') {
            $existingArc = Archive::findOrFail($validated['archive_id']);
            $departmentId = $existingArc->department_id;
        }

        if ($departmentId) {
            $department = Department::find($departmentId);
            $isDeptFat = ($department && strtoupper($department->code) === 'FIN');
            if ($isLocationFat && !$isDeptFat) {
                $deptName = $department ? $department->name : 'Departemen';
                return response()->json([
                    'success' => false,
                    'message' => "Akses Ditolak: Rak '{$location->rack_code}' berada di Ruangan Khusus FAT (Finance, Accounting & Tax). Departemen {$deptName} tidak diizinkan menempatkan arsip di rak ini.",
                ], 403);
            }
        }

        // Get or Create Slot in DB
        $slotCode = $validated['slot_code'] ?? ("SAP-{$validated['sap_level']}-" . ($validated['layer'] === 'top' ? 'T' : 'B') . str_pad($validated['slot_number'], 2, '0', STR_PAD_LEFT));

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
            ]
        );

        $archive = null;

        if ($validated['mode'] === 'create_new') {
            $dept = Department::findOrFail($validated['department_id']);
            $subDept = !empty($validated['sub_department_id']) ? SubDepartment::find($validated['sub_department_id']) : null;

            $retentionYears = (int) ($validated['retention_years'] ?? ($subDept && $subDept->retention_years ? $subDept->retention_years : ($dept->retention_years ?? 5)));
            if ($retentionYears <= 0) $retentionYears = 5;

            $expiryDate = Carbon::now()->addYears($retentionYears)->endOfYear();

            $boxNum = $validated['box_number'] ?? null;

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
            $archive = Archive::findOrFail($validated['archive_id']);
            $archive->update([
                'warehouse_location_id' => $location->id,
                'warehouse_rack_slot_id' => $slot->id,
                'status' => 'in_warehouse',
            ]);
        }

        // Link slot to archive
        $slot->update([
            'archive_id' => $archive->id,
            'status' => 'filled',
        ]);

        // Recalculate filled box count for rack
        $filledCount = $location->slots()->where('status', '!=', 'empty')->count();
        $location->update(['current_box_count' => $filledCount]);

        return response()->json([
            'success' => true,
            'message' => "Kardus arsip '{$archive->box_number}' berhasil ditempatkan di slot {$slot->slot_code}.",
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
                    'retention_expiry_date' => $archive->retention_expiry_date ? $archive->retention_expiry_date->format('d M Y') : '-',
                    'department' => $archive->department ? $archive->department->code : 'Dept',
                    'department_name' => $archive->department ? $archive->department->name : '',
                    'sub_department' => $archive->subDepartment ? $archive->subDepartment->code : null,
                    'sub_department_name' => $archive->subDepartment ? $archive->subDepartment->name : null,
                    'is_expired' => (bool) $archive->is_expired,
                ],
            ],
        ]);
    }

    public function unassignSlotArchive(Request $request, WarehouseLocation $location)
    {
        $validated = $request->validate([
            'sap_level' => 'required|integer|min:1|max:10',
            'layer' => 'required|string|in:top,bottom',
            'slot_number' => 'required|integer|min:1|max:20',
        ]);

        $slot = WarehouseRackSlot::where('warehouse_location_id', $location->id)
            ->where('sap_level', $validated['sap_level'])
            ->where('layer', $validated['layer'])
            ->where('slot_number', $validated['slot_number'])
            ->first();

        if ($slot && $slot->archive_id) {
            $archive = Archive::find($slot->archive_id);
            if ($archive) {
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

        return response()->json([
            'success' => true,
            'message' => "Slot berhasil dikosongkan.",
        ]);
    }
}

