<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileStreamController extends Controller
{
    /**
     * Stream stored file directly to browser with inline preview header.
     * Solves Windows / Laragon symlink issues where public/storage is inaccessible.
     */
    /**
     * Stream stored file directly to browser with inline preview header.
     * Solves Windows / Laragon symlink issues where public/storage is inaccessible.
     */
    public function stream(Request $request, $path)
    {
        $cleanPath = $this->sanitizeFilePath($path);

        // Security check: prevent directory traversal attacks
        if (str_contains($cleanPath, '..')) {
            abort(403, 'Akses ke direktori tidak diizinkan.');
        }

        $candidates = $this->resolveCandidates($cleanPath);

        foreach ($candidates as $filePath) {
            if (!empty($filePath) && file_exists($filePath) && is_file($filePath)) {
                $mimeType = $this->detectMimeType($filePath);
                $fileName = basename($filePath);

                return response()->file($filePath, [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => 'inline; filename="' . $fileName . '"',
                    'Cache-Control' => 'public, max-age=86400',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        // Graceful fallback for missing/dummy demo files:
        $ext = strtolower(pathinfo($cleanPath, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400">'
                . '<rect width="600" height="400" fill="#f8fafc" stroke="#cbd5e1" stroke-width="2"/>'
                . '<circle cx="300" cy="160" r="45" fill="#fef3c7" stroke="#f59e0b" stroke-width="3"/>'
                . '<path d="M285 160l10 10 20-20" fill="none" stroke="#d97706" stroke-width="4" stroke-linecap="round"/>'
                . '<text x="300" y="240" font-family="sans-serif" font-size="16" font-weight="bold" fill="#1e293b" text-anchor="middle">Pratinjau Berkas Belum Tersedia di Disk</text>'
                . '<text x="300" y="270" font-family="monospace" font-size="12" fill="#64748b" text-anchor="middle">' . htmlspecialchars(basename($cleanPath)) . '</text>'
                . '<text x="300" y="300" font-family="sans-serif" font-size="11" fill="#94a3b8" text-anchor="middle">Silakan unggah ulang berkas fisik melalui form edit/perbarui arsip</text>'
                . '</svg>';

            return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
        }

        // If sample PDF exists, use as friendly demonstration fallback
        $samplePdf = public_path('storage/archive_scans/DROyzZvBon46BrUTLA37Gi1KPZYcWI2CxkvFbMLY.pdf');
        if (file_exists($samplePdf)) {
            return response()->file($samplePdf, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($cleanPath) . '"',
            ]);
        }

        abort(404, 'File berkas arsip fisik tidak ditemukan di server penyimpanan (' . htmlspecialchars($cleanPath) . ').');
    }

    /**
     * Anti-IDM Stream Endpoint for Live In-Browser Previews.
     * Serves file binary as application/octet-stream with generic non-pdf disposition
     * so download managers (like IDM) cannot intercept the fetch request.
     */
    public function previewStream(Request $request)
    {
        $token = $request->query('token') ?: $request->query('path');
        if (empty($token)) {
            abort(400, 'Parameter token atau path diperlukan.');
        }

        $cleanPath = $this->sanitizeFilePath($token);

        if (str_contains($cleanPath, '..')) {
            abort(403, 'Akses ke direktori tidak diizinkan.');
        }

        $candidates = $this->resolveCandidates($cleanPath);

        foreach ($candidates as $filePath) {
            if (!empty($filePath) && file_exists($filePath) && is_file($filePath)) {
                return response()->file($filePath, [
                    'Content-Type' => 'application/octet-stream',
                    'Content-Disposition' => 'inline; filename="preview_stream.dat"',
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        // Fallback demo sample PDF if available
        $samplePdf = public_path('storage/archive_scans/DROyzZvBon46BrUTLA37Gi1KPZYcWI2CxkvFbMLY.pdf');
        if (file_exists($samplePdf)) {
            return response()->file($samplePdf, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="preview_stream.dat"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        abort(404, 'File pratinjau fisik tidak ditemukan di server penyimpanan (' . htmlspecialchars(basename($cleanPath)) . ').');
    }

    /**
     * Sanitize and extract clean relative file path from various inputs
     * (URL, base64 token, prefix with storage/, files/stream/, etc.)
     */
    protected function sanitizeFilePath($path)
    {
        if (empty($path)) {
            return '';
        }

        // Check base64
        $decoded = @base64_decode($path, true);
        if ($decoded !== false && preg_match('/^[a-zA-Z0-9_\-\.\/:\?=&]+$/', $decoded)) {
            $path = $decoded;
        }

        $path = urldecode($path);
        // Strip query string if any
        $path = explode('?', $path)[0];
        // Strip http/https domain prefix
        $path = preg_replace('#^https?://[^/]+/#i', '', $path);
        $path = ltrim($path, '/');

        // Strip repeating prefixes: public/storage/, storage/, public/, files/stream/, files/preview-stream/
        $patterns = [
            '#^(public/storage/)#i',
            '#^(storage/)#i',
            '#^(public/)#i',
            '#^(files/stream/)#i',
            '#^(files/preview-stream/)#i',
        ];
        
        do {
            $prev = $path;
            foreach ($patterns as $pattern) {
                $path = preg_replace($pattern, '', $path);
                $path = ltrim($path, '/');
            }
        } while ($path !== $prev);

        return $path;
    }

    /**
     * Resolve all possible physical storage candidates on disk.
     */
    protected function resolveCandidates($cleanPath)
    {
        return [
            public_path('storage/' . $cleanPath),
            public_path($cleanPath),
            storage_path('app/public/' . $cleanPath),
            Storage::disk('public')->path($cleanPath),
            Storage::disk('local')->path($cleanPath),
            storage_path('app/' . $cleanPath),
        ];
    }

    /**
     * Detect MIME type with robust fallbacks.
     */
    protected function detectMimeType($filePath)
    {
        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($filePath);
            if ($mime) return $mime;
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $map = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'zip'  => 'application/zip',
            'txt'  => 'text/plain',
            'csv'  => 'text/csv',
        ];

        return $map[$extension] ?? 'application/octet-stream';
    }
}

