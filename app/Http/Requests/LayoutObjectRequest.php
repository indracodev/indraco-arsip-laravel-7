<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Tambah / Edit object of the Layout Gudang 2D (rules of Laravel 7 storeLocation and updateLocation).
 * Store needs type and geometry; update sends what the modal / canvas changed.
 * `expected_updated_at` (edit modal) turns on the optimistic lock.
 */
class LayoutObjectRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $store = $this->routeIs('api.warehouse.locations.store');
        $need = $store ? 'required' : 'nullable';
        $minSize = $store ? 'min:20' : 'min:4'; // production racks are 16 px thick (Laravel 7 update had no minimum)

        return [
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'location_type' => [$need, 'string', 'in:room,rack'],
            'rack_code' => ['required', 'string', 'max:100'],
            'room_sector' => ['nullable', 'string', 'max:50'],
            'shelf_code' => ['nullable', 'string', 'max:50'],
            'box_capacity' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'total_sap' => ['nullable', 'integer', 'min:1', 'max:10'],
            'boxes_per_sap' => ['nullable', 'integer', 'min:1', 'max:50'],
            'box_type' => ['nullable', 'string', 'max:50'],
            'custom_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{3,8}$/'],
            'is_fat_locked' => ['nullable', 'boolean'],
            'canvas_x' => [$need, 'integer', 'min:0', 'max:10000'],
            'canvas_y' => [$need, 'integer', 'min:0', 'max:10000'],
            'canvas_width' => [$need, 'integer', $minSize, 'max:4000'],
            'canvas_height' => [$need, 'integer', $minSize, 'max:4000'],
            'orientation' => ['nullable', 'string', 'in:horizontal,vertical'],
            'rotation_angle' => ['nullable', 'integer', 'min:0', 'max:359'],
            'is_locked' => ['nullable', 'boolean'],
            'assigned_department_ids' => ['nullable', 'array'],
            'assigned_department_ids.*' => ['integer', 'exists:departments,id'],
            'expected_updated_at' => ['nullable', 'string', 'max:30'],
        ];
    }
}
