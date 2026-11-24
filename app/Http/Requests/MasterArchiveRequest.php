<?php

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Master Berkas (type berkas) of a department, single form or batch (Laravel 7
 * MasterArchiveController@store / @update / @storeBatch). The sub-department must belong to the
 * department in the URL (Laravel 7 accepted any sub-department id).
 */
class MasterArchiveRequest extends FormRequest
{
    public const DOCUMENT_TYPES = [
        'FAKTUR_PAJAK' => 'FAKTUR PAJAK & KEUANGAN',
        'KONTRAK_KERJA' => 'KONTRAK KERJA / SDM',
        'MOU_SPONSOR' => 'MOU / KERJASAMA / SALES',
        'SURAT_JALAN' => 'SURAT JALAN & LOGISTIK',
        'PRODUKSI_QC' => 'PRODUKSI & QUALITY CONTROL',
        'MAINTENANCE' => 'MAINTENANCE & FASILITAS',
        'UMUM' => 'UMUM / LAIN-LAIN',
    ];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $department = $this->route('department');
        $rules = [
            'sub_department_id' => ['nullable', 'integer', Rule::exists('sub_departments', 'id')->where('department_id', $department instanceof Department ? $department->id : 0)],
            'document_type' => ['nullable', 'string', 'max:50'],
            'retention_years' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];

        if ($this->routeIs('master.departments.master_archives.batch')) {
            return $rules + [
                'raw_text' => ['nullable', 'string'],
                'items' => ['nullable', 'array'],
                'items.*' => ['nullable', 'string', 'max:255'],
            ];
        }

        return $rules + [
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Batch: names from items[] or one per line of raw_text, trimmed, empty lines dropped.
     *
     * @return list<string>
     */
    public function batchNames(): array
    {
        $items = $this->input('items');
        $names = is_array($items) && $items !== [] ? $items : preg_split('/[\r\n]+/', (string) $this->input('raw_text'));

        return array_values(array_filter(array_map(fn ($name): string => trim((string) $name), $names ?: []), fn (string $name): bool => $name !== ''));
    }
}
