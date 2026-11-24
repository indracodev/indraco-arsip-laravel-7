<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\ArchiveExtensionLog;
use App\Models\BorrowingLog;
use App\Models\DestructionLog;
use App\Services\FileStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Preview / download of an uploaded file (replaces Laravel 7 `files/stream/{path}` and
 * `files/preview-stream`, which needed no login and took a raw path: findings #2, #3, B11).
 *
 * The URL names a record and one of its file columns, never a path. Access = ArchivePolicy@view
 * of the archive the record belongs to. The stored path is checked against the upload folders
 * by FileStorage::locate(); a missing file is a 404 (no substitute file). With
 * `filesystems.x_accel` Caddy streams the file (X-Accel-Redirect), so no PHP worker is held.
 */
class ArchiveFileController extends Controller
{
    /** Route {source} => [model, file columns]. */
    public const SOURCES = [
        'archive' => [Archive::class, ['scan_input_form', 'file_path', 'scan_approval_input', 'scan_extension_form']],
        'borrowing' => [BorrowingLog::class, ['approval_file', 'scan_approval_borrow']],
        'destruction' => [DestructionLog::class, ['approval_file', 'scan_approval_destruction', 'certificate_file', 'scan_extension_form']],
        'extension' => [ArchiveExtensionLog::class, ['scan_extension_form']],
    ];

    public function __invoke(Request $request, FileStorage $files, string $source, int $id, string $field): Response
    {
        [$model, $fields] = self::SOURCES[$source];
        abort_unless(in_array($field, $fields, true), 404);

        /** @var Model $record */
        $record = $model::query()->findOrFail($id);
        $archive = $record instanceof Archive ? $record : $record->getRelationValue('archive');
        abort_unless($archive instanceof Archive, 404);
        Gate::authorize('view', $archive);

        $stored = $record->getAttribute($field);
        abort_if(blank($stored), 404, 'Berkas tidak dilampirkan pada arsip ini.');
        $file = $files->locate((string) $stored);
        abort_if($file === null, 404, 'Berkas tidak ditemukan di server penyimpanan. Unggah ulang berkas melalui form edit arsip.');

        $name = Str::slug(str_replace('_', ' ', $field)).'-'.$source.'-'.$id.'.'.$file['extension'];
        $headers = [
            'Content-Type' => FileStorage::MIME[$file['extension']],
            'Content-Disposition' => HeaderUtils::makeDisposition($request->routeIs('files.download') ? 'attachment' : 'inline', $name),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-cache',
        ];

        if (config('filesystems.x_accel')) {
            return response('', 200, $headers + ['X-Accel-Redirect' => $file['accel']]);
        }

        return response()->file($file['absolute'], $headers);
    }
}
