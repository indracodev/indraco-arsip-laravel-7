<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Tambah / Edit Slot Rak (Laravel 7 WarehouseController@storeLocation / @updateLocation).
 */
class WarehouseLocationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'rack_code' => ['required', 'string', 'max:50'],
            'shelf_code' => ['required', 'string', 'max:50'],
            'box_capacity' => ['required', 'integer', 'min:1', 'max:5000'],
        ];
    }
}
