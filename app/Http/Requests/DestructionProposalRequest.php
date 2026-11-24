<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\FileStorage;
use Illuminate\Foundation\Http\FormRequest;

/**
 * "Formulir Pengajuan Usulan Pemusnahan Box Arsip" (Laravel 7 DestructionController@propose,
 * rules and messages kept). Box ids come as archive_ids[] (form) or archive_id (Laravel 7
 * fallback). The approval scan may come as `approval_file` or `scan_approval_destruction`.
 * bap_number is read only for admin (PIC: always "(Menunggu Penerbitan BAP)").
 * Which boxes may be proposed is checked by ProposeDestruction (B2).
 */
class DestructionProposalRequest extends FormRequest
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
        /** @var User $user */
        $user = $this->user();

        return [
            'archive_ids' => ['required', 'array'],
            'archive_ids.*' => ['integer'],
            'destruction_date' => ['required', 'date'],
            'method' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'approval_file' => ['required_without:scan_approval_destruction', ...$file],
            'scan_approval_destruction' => ['required_without:approval_file', ...$file],
            'certificate_file' => $file,
        ] + ($user->role === User::ROLE_PIC_DEPT ? [] : ['bap_number' => ['nullable', 'string', 'max:100']]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'archive_ids.required' => 'Pilih minimal satu berkas arsip yang akan dimusnahkan terlebih dahulu.',
            'destruction_date.required' => 'Tanggal pelaksanaan pemusnahan wajib diisi.',
            'method.required' => 'Metode fisik pemusnahan wajib dipilih.',
            'approval_file.required_without' => 'Wajib mengunggah berkas surat persetujuan (approval) pemusnahan.',
            'approval_file.max' => 'Ukuran file surat persetujuan maksimal 500 KB.',
            'scan_approval_destruction.max' => 'Ukuran file persetujuan maksimal 500 KB.',
            'certificate_file.max' => 'Ukuran file sertifikat BAP maksimal 500 KB.',
        ];
    }
}
