<?php

namespace App\Http\Requests;

use App\Services\FileStorage;
use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use ZipArchive;

/**
 * Upload of the multi-box spreadsheet (Laravel 7 ArchiveImportController: max 5 MB, .xlsx /
 * .xlsm / .csv). Added: the content must match the extension (a workbook zip, or plain text for
 * CSV). Commit also takes the target status and, for "Verifikasi Gudang", the scan form.
 * Errors come back as the Laravel 7 JSON `{success: false, errors: [...]}`.
 */
class ArchiveImportRequest extends FormRequest
{
    private const FORMAT_MESSAGE = 'Format file tidak didukung. Harap unggah berkas berekstensi .xlsx atau .csv.';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isCommit = $this->routeIs('archives.import.commit');
        $allowedStatuses = $this->user()?->isAdmin() ? 'in:draft,pending_verification,in_warehouse' : 'in:draft,pending_verification';

        return [
            'file' => ['required', 'file', 'max:5120', 'extensions:xlsx,xlsm,csv', $this->readable(...)],
            'status' => $isCommit ? ['required', $allowedStatuses] : ['exclude'],
            'scan_input_form' => $isCommit ? FileStorage::rules() : ['exclude'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['file.extensions' => self::FORMAT_MESSAGE];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422));
    }

    private function readable(string $attribute, mixed $file, Closure $fail): void
    {
        $extension = $file instanceof UploadedFile ? strtolower($file->getClientOriginalExtension()) : '';
        if (! in_array($extension, ['xlsx', 'xlsm', 'csv'], true)) {
            return; // reported by the `extensions` rule
        }
        $path = (string) $file->getRealPath();
        if ($extension === 'csv') {
            $ok = in_array($file->getMimeType(), ['text/plain', 'text/csv', 'application/csv', 'text/x-csv'], true);
        } else {
            $zip = new ZipArchive;
            $ok = $zip->open($path, ZipArchive::RDONLY) === true && $zip->locateName('xl/workbook.xml') !== false;
            if ($ok) {
                $zip->close();
            }
        }
        if (! $ok) {
            $fail(self::FORMAT_MESSAGE);
        }
    }
}
