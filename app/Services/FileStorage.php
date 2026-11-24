<?php

namespace App\Services;

use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The one place uploads are written (rules/60-security.md, skill file-upload-storage).
 *
 * - Laravel 7 limits: pdf/jpg/jpeg/png, max 500 KB (`mimes:pdf,jpg,jpeg,png|max:500`).
 * - Type checked from the file content (finfo) and the client extension; the stored name is
 *   random with the extension of the detected type, so `x.php.pdf` can never land as `.php`.
 * - Private disk `local` (storage/app/private), outside public/. The database keeps the
 *   Laravel 7 relative path (`archive_scans/<name>.pdf`); files are served by a controller
 *   that checks access (FILE module).
 */
final class FileStorage
{
    public const DISK = 'local';

    public const MAX_KB = 500;

    /** Detected MIME type => extension used for the stored name. */
    private const TYPES = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];

    /**
     * Validation rules for one optional upload (same limits and messages as Laravel 7).
     *
     * @return list<string>
     */
    public static function rules(): array
    {
        return ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:'.self::MAX_KB];
    }

    /**
     * Store an upload under $folder with a server-generated name. Call it before the database
     * transaction and delete the returned path if the transaction fails.
     */
    public function store(UploadedFile $file, string $folder): string
    {
        $extension = self::TYPES[(string) $file->getMimeType()] ?? null;
        $clientExtension = strtolower($file->getClientOriginalExtension());
        if ($extension === null || ! in_array($clientExtension, ['pdf', 'jpg', 'jpeg', 'png'], true) || $file->getSize() > self::MAX_KB * 1024) {
            throw new DomainException('Berkas ditolak: hanya PDF/JPG/PNG maksimal 500 KB.');
        }

        $path = $file->storeAs($folder, Str::random(40).'.'.$extension, self::DISK);
        if ($path === false) {
            throw new RuntimeException('Gagal menyimpan berkas unggahan.');
        }

        return $path;
    }

    /**
     * @param  iterable<string|null>  $paths
     */
    public function delete(iterable $paths): void
    {
        foreach ($paths as $path) {
            if ($path !== null && $path !== '' && ! str_contains($path, '..')) {
                Storage::disk(self::DISK)->delete($path);
            }
        }
    }
}
