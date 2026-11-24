<?php

namespace App\Http\Requests;

use App\Models\SubDepartment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tambah / Edit Sub-Departemen (Laravel 7 DepartmentController: storeSubDepartment,
 * updateSubDepartment). The code is unique within its department (Laravel 7 message).
 */
class SubDepartmentRequest extends FormRequest
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
        $sub = $this->route('subDepartment');

        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'sidar_id' => ['nullable', 'string', 'max:50'],
            'code' => ['required', 'string', 'max:20', Rule::unique('sub_departments', 'code')
                ->where('department_id', (int) $this->input('department_id'))
                ->ignore($sub instanceof SubDepartment ? $sub->id : null)],
            'name' => ['required', 'string', 'max:100'],
            'retention_years' => ['nullable', 'integer', 'min:1', 'max:100'],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['code.unique' => 'Kode sub-departemen '.$this->input('code').' sudah digunakan pada departemen ini.'];
    }
}
