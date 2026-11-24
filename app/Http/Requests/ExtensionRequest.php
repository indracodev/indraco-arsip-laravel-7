<?php

namespace App\Http\Requests;

use App\Models\Archive;
use App\Models\Department;
use App\Models\User;
use App\Services\FileStorage;
use App\Services\RetentionCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * "Formulir Pengajuan Perpanjangan Masa Simpan Box Arsip" (Laravel 7
 * HandlesArchiveExtensions@extendStore, rules and messages kept). Maximum 120 months (10 years)
 * when the user's or a chosen box's department is FAT/FIN/FATCLM, else 60 (decision ASK 12:
 * department code only, the user-name rule is gone: B12). Months come from additional_months,
 * else additional_years × 12.
 */
class ExtensionRequest extends FormRequest
{
    private ?int $maxMonths = null;

    protected function prepareForValidation(): void
    {
        $ids = $this->input('archive_ids');
        if (! is_array($ids)) {
            $ids = $this->filled('archive_id') ? [$this->input('archive_id')] : [];
        }
        $this->merge(['archive_ids' => array_values(array_unique(array_filter(array_map('intval', $ids))))]);
    }

    public function maxMonths(): int
    {
        if ($this->maxMonths === null) {
            /** @var User $user */
            $user = $this->user();
            $departments = Department::query()->whereIn('id', Archive::query()->whereIn('id', (array) $this->input('archive_ids'))->select('department_id'))->get();
            $this->maxMonths = RetentionCalculator::maxExtensionMonths($user->department, ...$departments->all());
        }

        return $this->maxMonths;
    }

    /** Requested months (validated input), 0 when missing. */
    public function months(): int
    {
        return $this->filled('additional_months') ? (int) $this->input('additional_months') : (int) $this->input('additional_years') * 12;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $max = $this->maxMonths();

        return [
            'archive_ids' => ['required', 'array'],
            'archive_ids.*' => ['integer'],
            'additional_months' => ['nullable', 'integer', 'min:1', 'max:'.$max],
            'additional_years' => ['nullable', 'integer', 'min:1', 'max:'.intdiv($max, 12)],
            'extension_reason' => ['required', 'string', 'max:1000'],
            'scan_extension_form' => FileStorage::rules(),
        ];
    }

    /**
     * Laravel 7: months outside 1..max (e.g. both fields empty) after the field rules.
     *
     * @return list<\Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $months = $this->months();
            if (! $validator->errors()->has('additional_months') && ($months < 1 || $months > $this->maxMonths())) {
                $validator->errors()->add('additional_months', 'Tambahan masa simpan harus antara 1 sampai '.$this->maxMonths().' bulan.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $max = $this->maxMonths();
        $years = intdiv($max, 12);

        return [
            'archive_ids.required' => 'Minimal satu berkas arsip wajib dipilih untuk perpanjangan masa simpan.',
            'additional_months.max' => "Tambahan masa simpan maksimal {$max} bulan ({$years} tahun).",
            'additional_years.max' => "Tambahan masa simpan maksimal {$years} tahun ({$max} bulan).",
            'extension_reason.required' => 'Alasan perpanjangan masa simpan wajib diisi.',
            'scan_extension_form.max' => 'Ukuran file formulir perpanjangan maksimal 500 KB.',
            'scan_extension_form.mimes' => 'Format file formulir perpanjangan harus berupa PDF, JPG, JPEG, atau PNG.',
        ];
    }
}
