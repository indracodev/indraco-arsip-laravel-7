<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;

class WarehouseLayoutController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::all();
        $departments = Department::all();
        return view('master.warehouse_layout', compact('warehouses', 'departments'));
    }

    public function apiLayoutData(Request $request)
    {
        $warehouseId = $request->query('warehouse_id');

        $query = WarehouseLocation::with([
            'assignedDepartment',
            'bookedBy',
            'archives.department',
            'archives.creator'
        ]);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $locations = $query->get()->map(function ($loc) {
            return [
                'id' => $loc->id,
                'warehouse_id' => $loc->warehouse_id,
                'location_type' => $loc->location_type ?? 'rack',
                'room_sector' => $loc->room_sector ?? 'Umum',
                'rack_code' => $loc->rack_code,
                'shelf_code' => $loc->shelf_code,
                'box_capacity' => $loc->box_capacity,
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
                'assigned_department_id' => $loc->assigned_department_id,
                'assigned_department' => $loc->assignedDepartment ? [
                    'id' => $loc->assignedDepartment->id,
                    'code' => $loc->assignedDepartment->code,
                    'name' => $loc->assignedDepartment->name,
                ] : null,
                'is_booked' => $loc->is_booked,
                'booked_by_user' => $loc->bookedBy ? $loc->bookedBy->name : null,
                'booking_notes' => $loc->booking_notes,
                'archives' => $loc->archives->map(function ($arc) {
                    return [
                        'id' => $arc->id,
                        'box_number' => $arc->box_number,
                        'title' => $arc->title,
                        'company_name' => $arc->company_name ?? 'PT Indraco',
                        'document_type' => $arc->document_type ?? 'UMUM',
                        'department' => $arc->department ? $arc->department->code : 'Dept',
                        'period_yy_mm' => $arc->period_yy_mm ?? $arc->period_text,
                        'retention_years' => $arc->retention_years,
                        'retention_expiry_date' => $arc->retention_expiry_date ? $arc->retention_expiry_date->format('d M Y') : '-',
                        'scan_input_form' => $arc->scan_input_form ? asset('storage/' . $arc->scan_input_form) : null,
                        'scan_approval_input' => $arc->scan_approval_input ? asset('storage/' . $arc->scan_approval_input) : null,
                    ];
                }),
            ];
        });

        $departments = Department::all(['id', 'code', 'name']);

        return response()->json([
            'success' => true,
            'locations' => $locations,
            'departments' => $departments,
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
            'custom_color' => 'nullable|string|max:30',
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
        $validated['is_locked'] = $validated['is_locked'] ?? true;
        $validated['current_box_count'] = 0;

        $location = WarehouseLocation::create($validated);

        if ($location->location_type === 'room') {
            Warehouse::firstOrCreate(
                ['code' => $location->rack_code],
                [
                    'name' => 'Gudang ' . $location->rack_code,
                    'address' => 'Kawasan Industri Indraco - Sektor ' . ($location->room_sector ?? $location->rack_code),
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

        $location->update([
            'is_booked' => true,
            'assigned_department_id' => $validated['department_id'],
            'booked_by_user_id' => auth()->id(),
            'booking_notes' => $validated['booking_notes'],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Slot rak '{$location->rack_code}' berhasil di-booking untuk departemen.",
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
            'custom_color' => 'nullable|string|max:30',
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

        if ($location->location_type === 'room' && isset($validated['rack_code'])) {
            $wh = Warehouse::where('code', $oldCode)->first();
            if ($wh) {
                $wh->update([
                    'code' => $validated['rack_code'],
                    'name' => 'Gudang ' . $validated['rack_code'],
                    'address' => 'Kawasan Industri Indraco - Sektor ' . ($validated['room_sector'] ?? $validated['rack_code']),
                ]);
            } else {
                Warehouse::firstOrCreate(
                    ['code' => $validated['rack_code']],
                    [
                        'name' => 'Gudang ' . $validated['rack_code'],
                        'address' => 'Kawasan Industri Indraco - Sektor ' . ($validated['room_sector'] ?? $validated['rack_code']),
                    ]
                );
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
}
