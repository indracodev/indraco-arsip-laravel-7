<?php

namespace App\Http\Requests;

use App\Services\FileStorage;
use Illuminate\Foundation\Http\FormRequest;

/**
 * "Formulir Permintaan Penarikan Berkas Arsip [Form 2]" (Laravel 7 BorrowingController@store).
 * Box ids come as archive_ids[] (form) or archive_id (single, Laravel 7 fallback). Every
 * penarikan is permanent (decisions ASK 2): `is_permanent` / `expected_return_date` are ignored.
 * Which boxes may be withdrawn is checked by the SubmitBorrowing action.
 */
class BorrowingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $ids = $this->input('archive_ids');
        if (! is_array($ids)) {
            $ids = $this->filled('archive_id') ? [$this->input('archive_id')] : [];
        }
        $this->merge(['archive_ids' => array_values(array_unique(array_filter(array_map('intval', $ids))))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $file = FileStorage::rules();
        $file[0] = 'required';

        return [
            'archive_ids' => ['required', 'array'],
            'archive_ids.*' => ['integer'],
            'purpose' => ['required', 'string', 'max:500'],
            'approval_file' => $file,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['archive_ids.required' => 'Minimal satu berkas arsip wajib dipilih untuk pengajuan penarikan.'];
    }
}
