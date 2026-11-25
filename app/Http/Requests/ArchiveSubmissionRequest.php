<?php

namespace App\Http\Requests;

use App\Models\Archive;
use App\Models\User;
use App\Services\FileStorage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Box submission form (create + edit), Laravel 7 HandlesMultiBoxCreation/Update rules:
 * "Simpan Sementara (Draft)" (`submit_action=draft`) requires nothing, "Ajukan" requires every
 * starred field. PIC Departemen never sends department_id (their own department is used);
 * sub-departments must belong to the department. Authorization: ArchivePolicy (controller).
 */
class ArchiveSubmissionRequest extends FormRequest
{
    public function isDraft(): bool
    {
        return $this->input('submit_action') === 'draft';
    }

    /** Target department: PIC = own department; admin = chosen one (validated). */
    public function departmentId(): ?int
    {
        /** @var User $user */
        $user = $this->user();
        $chosen = $user->role === User::ROLE_PIC_DEPT ? null : $this->validated('department_id');

        /** @var Archive|null $archive */
        $archive = $this->route('archive');

        return (int) ($chosen ?: ($archive->department_id ?? $user->department_id)) ?: null;
    }

    protected function prepareForValidation(): void
    {
        // "Inti Departemen" = no sub-department (Laravel 7 ArchiveController@store/update).
        $boxes = $this->input('boxes');
        if (is_array($boxes)) {
            foreach ($boxes as $key => $box) {
                if (is_array($box) && ($box['sub_department_id'] ?? null) === 'inti') {
                    $boxes[$key]['sub_department_id'] = null;
                }
            }
            $this->merge(['boxes' => $boxes]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();
        $isPic = $user->role === User::ROLE_PIC_DEPT;
        $required = $this->isDraft() ? 'nullable' : 'required';
        $deptForSubs = $isPic ? $user->department_id : $this->input('department_id', $this->route('archive')?->department_id);

        return [
            'department_id' => $isPic ? ['exclude'] : [$required, 'integer', 'exists:departments,id'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'tgl_penyerahan' => $this->isDraft() ? ['nullable', 'date'] : ['required', 'date', 'before_or_equal:today'],
            'scan_input_form' => FileStorage::rules(),
            'file' => FileStorage::rules(),
            'remove_scan_input_form' => ['nullable', 'boolean'],
            'remove_file' => ['nullable', 'boolean'],
            'original_updated_at' => [$this->route('archive') ? 'required' : 'exclude', 'string'],
            'boxes' => $this->isDraft() ? ['nullable', 'array'] : ['required', 'array', 'min:1'],
            'boxes.*.title' => [$required, 'string', 'max:255'],
            'boxes.*.sub_department_id' => ['nullable', 'integer', Rule::exists('sub_departments', 'id')->where('department_id', (int) $deptForSubs)],
            'boxes.*.document_type' => ['nullable', 'string', 'max:100'],
            'boxes.*.periode' => ['nullable', 'string', 'max:100'],
            'boxes.*.physical_condition' => [$required, 'string', 'max:100'],
            'boxes.*.retention_years' => ['nullable', 'integer', 'min:1', 'max:30'],
            'boxes.*.origin_borrowing_log_id' => ['nullable', 'integer', 'exists:borrowing_logs,id'],
            'boxes.*.items' => $this->isDraft() ? ['nullable', 'array'] : ['required', 'array', 'min:1'],
            'boxes.*.items.*.document_name' => [$required, 'string', 'max:255'],
            'boxes.*.items.*.period_start' => [$required, 'string', 'max:50'],
            'boxes.*.items.*.period_end' => [$required, 'string', 'max:50'],
            'boxes.*.items.*.notes' => [$required, 'string', 'max:255'],
        ];
    }

    /**
     * Laravel 7 checks after the field rules: identical document lines in one box, and the
     * scan form required to submit.
     *
     * @return list<\Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $duplicate = $this->duplicateItemMessage((array) $this->input('boxes', []));
            if ($duplicate !== null) {
                $validator->errors()->add('duplicate_item', $duplicate);
            }
            /** @var Archive|null $archive */
            $archive = $this->route('archive');
            $keepsScan = $archive?->scan_input_form && ! $this->boolean('remove_scan_input_form');
            if (! $this->isDraft() && ! $this->attributes->get('skip_scan_check') && ! $this->hasFile('scan_input_form') && ! $keepsScan) {
                $validator->errors()->add('scan_input_form', 'Scan Formulir Input wajib diunggah sebelum mengajukan verifikasi box arsip.');
            }
        }];
    }

    /** @param array<int, mixed> $boxes */
    private function duplicateItemMessage(array $boxes): ?string
    {
        foreach (array_values($boxes) as $boxIndex => $box) {
            $seen = [];
            foreach (array_values((array) ($box['items'] ?? [])) as $row => $item) {
                $name = strtolower(trim((string) ($item['document_name'] ?? '')));
                $notes = strtolower(trim((string) ($item['notes'] ?? '')));
                if ($name === '' && $notes === '') {
                    continue;
                }
                $signature = $name.'||'.trim((string) ($item['period_start'] ?? '')).'||'.trim((string) ($item['period_end'] ?? '')).'||'.$notes;
                if (isset($seen[$signature])) {
                    return 'Box #'.($boxIndex + 1).' memiliki data butir berkas kembar identik: Baris #'.($row + 1).' sama persis dengan Baris #'.($seen[$signature] + 1).' (Nama Berkas, Periode, dan Keterangan sama persis). Data duplikat tidak diperbolehkan.';
                }
                $seen[$signature] = $row;
            }
        }

        return null;
    }
}
