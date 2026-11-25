<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\ArchiveExtensionLog;
use App\Models\BorrowingLog;
use App\Models\DestructionLog;
use App\Services\FileStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
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
 *
 * `files.preview_data` returns the file as base64 JSON for the in-page viewer (finding R27):
 * Internet Download Manager grabs PDF responses in Chrome, even fetch() calls, and the page gets an
 * empty 204. A JSON body is not grabbed (Laravel 7 preview-stream did the same). Served by PHP,
 * capped at PREVIEW_MAX_BYTES (uploads are <= 500 KB).
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

    public const PREVIEW_MAX_BYTES = 5 * 1024 * 1024;

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
            'Cache-Control' => 'no-store, private', // R50; X-Accel body is empty so SetCacheHeaders skips it
        ];

        if ($request->routeIs('files.preview_data')) {
            return $this->previewData($file, $name);
        }

        if (config('filesystems.x_accel')) {
            return response('', 200, $headers + ['X-Accel-Redirect' => $file['accel']]);
        }

        return response()->file($file['absolute'], $headers);
    }

    /** @param  array{absolute: string, accel: string, extension: string}  $file */
    private function previewData(array $file, string $name): JsonResponse
    {
        $headers = ['Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff'];
        $size = (int) filesize($file['absolute']);
        if ($size > self::PREVIEW_MAX_BYTES) {
            return response()->json(['message' => 'Berkas terlalu besar untuk pratinjau ('.round($size / 1048576, 1).' MB). Gunakan tombol "Unduh".'], 413, $headers);
        }

        return response()->json([
            'name' => $name,
            'mime' => FileStorage::MIME[$file['extension']],
            'size' => $size,
            'data' => base64_encode((string) file_get_contents($file['absolute'])),
        ], 200, $headers);
    }
}
