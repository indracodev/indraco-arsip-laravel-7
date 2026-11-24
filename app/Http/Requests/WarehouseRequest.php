<?php

namespace App\Http\Requests;

use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tambah / Edit Gudang (Laravel 7 WarehouseController@storeWarehouse / @updateWarehouse).
 */
class WarehouseRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $warehouse = $this->route('warehouse');

        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('warehouses', 'code')->ignore($warehouse instanceof Warehouse ? $warehouse->id : null)],
            'name' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
        ];
    }
}
