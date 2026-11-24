<?php

namespace App\Support;

use App\Models\Archive;

/**
 * Files shown in "Dokumentasi Scan & Lampiran Digital" of the archive detail page, in Laravel 7
 * order (archives/show.blade.php $archiveFiles): the four archive columns, then the first
 * penarikan approval scan that is not already listed (needs borrowingLogs loaded).
 */
final class ArchiveFileList
{
    /** Archive column => [name, description] (Laravel 7 texts). */
    private const ARCHIVE_FILES = [
        'scan_input_form' => ['Scan Formulir Input', 'Form pendaftaran fisik'],
        'scan_approval_input' => ['Scan Approval Input', 'Bukti persetujuan PIC'],
        'scan_extension_form' => ['Scan Form Perpanjangan', 'Perpanjangan masa simpan'],
        'file_path' => ['Lampiran Digital', 'Softcopy arsip'],
    ];

    /**
     * @return list<array{name: string, note: string, type: string, data: string, download: string}>
     */
    public static function for(Archive $archive): array
    {
        $files = [];
        $seen = [];
        foreach (self::ARCHIVE_FILES as $field => [$name, $note]) {
            $path = $archive->getAttribute($field);
            if (filled($path)) {
                $seen[] = $path;
                $files[] = self::entry($name, $note, (string) $path, ['archive', $archive->id, $field]);
            }
        }

        foreach ($archive->borrowingLogs as $log) {
            $field = filled($log->approval_file) ? 'approval_file' : 'scan_approval_borrow';
            $path = $log->getAttribute($field);
            if (filled($path) && ! in_array($path, $seen, true)) {
                $files[] = self::entry('Scan Formulir Approval Penarikan', 'Persetujuan penarikan', (string) $path, ['borrowing', $log->id, $field]);
                break;
            }
        }

        return $files;
    }

    /**
     * @param  array{0: string, 1: int, 2: string}  $route  source, id, field
     * @return array{name: string, note: string, type: string, data: string, download: string}
     */
    private static function entry(string $name, string $note, string $path, array $route): array
    {
        $params = ['source' => $route[0], 'id' => $route[1], 'field' => $route[2]];
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return [
            'name' => $name,
            'note' => $note.' ('.strtoupper($extension).')',
            'type' => $extension === 'pdf' ? 'pdf' : 'image',
            'data' => route('files.preview_data', $params),
            'download' => route('files.download', $params),
        ];
    }
}
