<?php

namespace App\Http\Requests;

use App\Actions\Warehouse\ChangeArchiveStatus;
use App\Models\Archive;
use App\Services\FileStorage;
use App\Services\NumberingService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * "Super Admin: Ubah Status Workflow" (Laravel 7 superAdminUpdateStatus rules). The manual box
 * number is checked against active boxes with the condition of the partial UNIQUE index
 * `idx_archives_box_number_active` (PROJECT_RULES_SQLITE §3.5.c); the action checks it again
 * inside the transaction.
 */
class ArchiveStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('processWarehouse', Archive::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Archive $archive */
        $archive = $this->route('archive');
        $target = (string) $this->input('target_status');

        return [
            'target_status' => ['required', 'in:'.implode(',', array_keys(ChangeArchiveStatus::TARGETS))],
            'reason' => $target === 'draft' && $archive->status !== 'draft' ? ['required', 'string', 'min:3', 'max:1000'] : ['nullable', 'string', 'max:1000'],
            'box_number' => ['nullable', 'string', 'max:100', function (string $attribute, mixed $value, Closure $fail) use ($archive): void {
                $holder = NumberingService::holder(trim((string) $value), $archive->id);
                if ($holder) {
                    $fail("Nomor Box '".trim((string) $value)."' sudah digunakan oleh arsip aktif lain (#{$holder->id} - {$holder->title}). Harap gunakan nomor box lain.");
                }
            }],
            'scan_input_form' => FileStorage::rules(),
            'file_path' => FileStorage::rules(),
            'scan_approval_input' => FileStorage::rules(),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'target_status.in' => 'Status tujuan ini tidak diubah dari sini: Keluar (Out) lewat Penarikan Berkas, Antrean Pemusnahan / Dimusnahkan lewat menu Pemusnahan.',
            'reason.required' => 'Mohon isi alasan / keterangan pengembalian status ke Draft.',
        ];
    }

    /**
     * Only the fields of the chosen target (Laravel 7 sent nothing else).
     *
     * @return array<string, UploadedFile|null>
     */
    public function uploads(): array
    {
        $fields = match ($this->input('target_status')) {
            'pending_verification' => ['scan_input_form', 'file_path'],
            'approved_booked', 'in_warehouse' => ['scan_input_form', 'scan_approval_input'],
            default => [],
        };
        $uploads = [];
        foreach ($fields as $field) {
            $file = $this->file($field);
            $uploads[$field] = $file instanceof UploadedFile ? $file : null;
        }

        return $uploads;
    }
}
