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
 *   Laravel 7 relative path (`archive_scans/<name>.pdf`); files are served only by
 *   ArchiveFileController (login + ArchivePolicy) through locate().
 */
final class FileStorage
{
    public const DISK = 'local';

    public const MAX_KB = 500;

    /** Detected MIME type => extension used for the stored name. */
    private const TYPES = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];

    /**
     * Upload folders a stored path may point into (Laravel 7 controllers + archive_digital of
     * the L13 form). Anything else in storage/app (backups, quick_notes.json, bench) is never served.
     */
    public const FOLDERS = [
        'archive_scans', 'archive_scan_inputs', 'archive_digital', 'archive_files', 'archive_approvals',
        'archive_extensions', 'borrowing_approvals', 'destruction_approvals', 'bap_certificates',
    ];

    /** Servable extension => Content-Type (the upload whitelist). */
    public const MIME = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];

    /**
     * Where a stored path is looked up, in this order => X-Accel prefix under storage/app
     * (Caddy `handle_response` root). First hit wins; there is no fallback to another file.
     */
    public const SEARCH_DISKS = [
        self::DISK => '/private',      // L13 uploads: storage/app/private
        'public' => '/public',         // Laravel 7 disk "public": storage/app/public
        'legacy_public' => '/legacy-public', // Laravel 7 real folder public/storage (findings B11)
    ];

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
     * Find a stored relative path (`archive_scans/<name>.pdf`, as kept in the database) on disk.
     * Null when the path is not `<allowed folder>/<plain file name>.<pdf|jpg|jpeg|png>` or the
     * file exists on no disk.
     *
     * @return array{absolute: string, accel: string, extension: string}|null
     */
    public function locate(?string $stored): ?array
    {
        $folders = implode('|', self::FOLDERS);
        if ($stored === null || ! preg_match('#\A('.$folders.')/([A-Za-z0-9][A-Za-z0-9._-]{0,199})\z#', $stored, $m)) {
            return null;
        }
        $extension = strtolower(pathinfo($m[2], PATHINFO_EXTENSION));
        if (! isset(self::MIME[$extension])) {
            return null;
        }

        foreach (self::SEARCH_DISKS as $disk => $prefix) {
            $root = realpath(Storage::disk($disk)->path(''));
            $absolute = $root === false ? false : realpath($root.DIRECTORY_SEPARATOR.$m[1].DIRECTORY_SEPARATOR.$m[2]);
            // realpath + prefix check: a link inside the folder cannot point outside the disk.
            if ($absolute !== false && is_file($absolute) && str_starts_with($absolute, $root.DIRECTORY_SEPARATOR)) {
                return ['absolute' => $absolute, 'accel' => $prefix.'/'.$m[1].'/'.rawurlencode($m[2]), 'extension' => $extension];
            }
        }

        return null;
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
