<?php

if (!function_exists('app_storage_url')) {
    /**
     * Generate reliable storage URL for stored files.
     * Prioritizes direct public asset access for zero-symlink reliability,
     * while seamlessly falling back to streaming when appropriate.
     *
     * @param string|null $path
     * @return string|null
     */
    function app_storage_url($path)
    {
        if (empty($path)) {
            return null;
        }

        // If it's already an absolute HTTP/HTTPS URL
        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        // Clean path by stripping leading slashes, storage/ or public/
        $cleanPath = ltrim($path, '/');
        $cleanPath = preg_replace('/^(storage\/|public\/)/', '', $cleanPath);

        // Direct public file check (no symlink required)
        if (file_exists(public_path('storage/' . $cleanPath))) {
            return asset('storage/' . $cleanPath);
        }

        // Fallback to controller streaming (e.g. if file is only in storage/app/public or storage/app)
        return url('/files/stream/' . $cleanPath);
    }
}

if (!function_exists('app_preview_stream_url')) {
    /**
     * Generate Anti-IDM Stream URL for live in-browser preview without download intercepts.
     * Uses base64 token so neither the path nor query string contains the .pdf keyword.
     *
     * @param string|null $path
     * @return string|null
     */
    function app_preview_stream_url($path)
    {
        if (empty($path)) {
            return null;
        }

        $cleanPath = ltrim($path, '/');
        $cleanPath = preg_replace('/^(storage\/|public\/)/', '', $cleanPath);

        $token = base64_encode($cleanPath);
        return url('/files/preview-stream?token=' . urlencode($token));
    }
}

if (!function_exists('db_immediate_transaction')) {
    /**
     * Run database transaction with immediate write lock for SQLite.
     * Prevents SQLITE_BUSY deadlocks when concurrent transactions read then write.
     * Automatically falls back to standard DB::transaction for MySQL/PostgreSQL.
     *
     * PERINGATAN / PENTING:
     * Dilarang keras memanggil `DB::transaction()` bertumpuk di dalam closure callback ini
     * pada SQLite, karena dapat memicu konflik status transaksi atau nesting error.
     *
     * @param \Closure $callback
     * @return mixed
     * @throws \Throwable
     */
    function db_immediate_transaction(\Closure $callback)
    {
        $connection = \Illuminate\Support\Facades\DB::connection();
        if ($connection->getDriverName() === 'sqlite') {
            if ($connection->getPdo()->inTransaction()) {
                return $callback();
            }

            $connection->statement('BEGIN IMMEDIATE;');
            try {
                $result = $callback();
                $connection->statement('COMMIT;');
                return $result;
            } catch (\Throwable $e) {
                try {
                    $connection->statement('ROLLBACK;');
                } catch (\Throwable $rollbackError) {
                    // Ignore if already rolled back
                }
                throw $e;
            }
        }

        return \Illuminate\Support\Facades\DB::transaction($callback);
    }
}
