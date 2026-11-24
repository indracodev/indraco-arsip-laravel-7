<?php

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tambah / Edit Departemen (Laravel 7 DepartmentController@store / @update). The code is
 * upper-cased before the unique check (Laravel 7 checked the raw input, so "fin" next to
 * "FIN" slipped through). Empty retention = 5 years (DepartmentController).
 */
class DepartmentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $department = $this->route('department');

        return [
            'sidar_id' => ['nullable', 'string', 'max:50'],
            'code' => ['required', 'string', 'max:10', Rule::unique('departments', 'code')->ignore($department instanceof Department ? $department->id : null)],
            'name' => ['required', 'string', 'max:100'],
            'retention_years' => ['nullable', 'integer', 'min:1', 'max:100'],
            'description' => ['nullable', 'string'],
        ];
    }
}
