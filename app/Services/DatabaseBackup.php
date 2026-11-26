<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Consistent SQLite snapshot with `VACUUM INTO` (PROJECT_RULES_SQLITE §3.5.g): one statement, readers
 * and writers keep working (WAL), never a table-by-table dump. Files stay in storage/app (never
 * served: FileStorage only serves its upload folders). Must not run inside a transaction.
 */
final class DatabaseBackup
{
    /** Download copy, removed after it was sent (`deleteFileAfterSend`). */
    public function temporary(): string
    {
        return $this->snapshot(storage_path('app/backup-tmp/backup_indraco_'.Str::random(16).'.sqlite'));
    }

    /** Kept copy in storage/app/backups (automatic backup before "hapus massal" / update, ASK 7). */
    public function keep(string $reason): string
    {
        return $this->snapshot(storage_path('app/backups/backup_indraco_'.date('Y_m_d_His').'_'.$reason.'_'.Str::random(4).'.sqlite'));
    }

    /** Consistent copy of the live database into a new file (also used by DatabaseSnapshot). */
    public function snapshot(string $path): string
    {
        File::ensureDirectoryExists(dirname($path));
        DB::statement('VACUUM INTO ?', [str_replace('\\', '/', $path)]);

        return $path;
    }
}
