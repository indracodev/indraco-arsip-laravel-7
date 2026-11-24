<?php

namespace App\Services;

use App\Models\Archive;
use Illuminate\Http\UploadedFile;

/**
 * The two files of a box submission (Laravel 7 folders): `scan_input_form` → archive_scans/,
 * `file` (photo / digital attachment) → archive_digital/. One upload is shared by every box of
 * the submission, so a file is deleted only when no archive row points to it any more.
 */
final class ArchiveAttachments
{
    public function __construct(private FileStorage $files) {}

    /**
     * Store new uploads (before the transaction) and work out the final paths.
     *
     * @return array{scan_input_form: string|null, file_path: string|null, new: list<string>, replaced: list<string>}
     */
    public function prepare(?Archive $archive, ?UploadedFile $scan, ?UploadedFile $file, bool $removeScan = false, bool $removeFile = false): array
    {
        $result = ['scan_input_form' => $archive?->scan_input_form, 'file_path' => $archive?->file_path, 'new' => [], 'replaced' => []];
        $slots = [
            'scan_input_form' => [$scan, $removeScan, 'archive_scans'],
            'file_path' => [$file, $removeFile, 'archive_digital'],
        ];

        try {
            foreach ($slots as $column => [$upload, $remove, $folder]) {
                $old = $result[$column];
                if ($upload) {
                    $result[$column] = $this->files->store($upload, $folder);
                    $result['new'][] = $result[$column];
                } elseif ($remove) {
                    $result[$column] = null;
                }
                if ($old && $old !== $result[$column]) {
                    $result['replaced'][] = $old;
                }
            }
        } catch (\Throwable $e) {
            $this->files->delete($result['new']);
            throw $e;
        }

        return $result;
    }

    /**
     * Remove files of the failed attempt.
     *
     * @param  list<string>  $paths
     */
    public function discard(array $paths): void
    {
        $this->files->delete($paths);
    }

    /**
     * Delete files no archive row references any more (call after the transaction committed).
     *
     * @param  list<string|null>  $paths
     */
    public function deleteUnreferenced(array $paths): void
    {
        $paths = array_values(array_unique(array_filter($paths)));
        if ($paths === []) {
            return;
        }
        $used = Archive::query()->whereIn('scan_input_form', $paths)->pluck('scan_input_form')
            ->merge(Archive::query()->whereIn('file_path', $paths)->pluck('file_path'))->all();
        $this->files->delete(array_diff($paths, $used));
    }
}
