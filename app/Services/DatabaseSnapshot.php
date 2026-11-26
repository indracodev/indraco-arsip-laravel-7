<?php

namespace App\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Snapshot / restore of the SQLite database + upload and session files, for the server load test
 * (runbook-uji-beban-server.md): take a snapshot, let k6 write test data, restore the snapshot.
 *
 * - snapshot(): online-safe `VACUUM INTO` (DatabaseBackup) into storage/app/snapshots/<time>/
 *   plus manifest.json (size, SHA-256, row count per table, every upload file and session file).
 * - restore(): only while the server is stopped. A safety snapshot of the current state is taken
 *   first (storage/app/snapshots-sebelum-restore/<time>), the database file is replaced by rename
 *   (-wal/-shm removed first), upload files that are not in the manifest are MOVED into the safety
 *   folder (never deleted, only inside the FileStorage upload folders of storage/app), session
 *   files created after the snapshot are deleted, then SHA-256 and row counts are verified.
 */
class DatabaseSnapshot
{
    public const DIR = 'app/snapshots';

    public const SAFETY_DIR = 'app/snapshots-sebelum-restore';

    public function __construct(private DatabaseBackup $backup) {}

    /** @return array<string, mixed> the manifest written next to the copy */
    public function snapshot(?string $directory = null): array
    {
        $directory ??= storage_path(self::DIR.'/'.date('Y-m-d_His'));
        if (is_dir($directory)) {
            throw new DomainException("Folder snapshot sudah ada: {$directory}");
        }
        $copy = $directory.'/database.sqlite';
        $this->backup->snapshot($copy);
        $manifest = [
            'created_at' => now()->toDateTimeString(),
            'source_database' => $this->databasePath(),
            'database_bytes' => filesize($copy),
            'sha256' => hash_file('sha256', $copy),
            'tables' => $this->rowCounts($copy),
            'files' => $this->uploadFiles(),
            'sessions' => $this->sessionFiles(),
        ];
        File::put($directory.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $manifest + ['directory' => $directory];
    }

    /** Newest snapshot folder (safety copies live elsewhere, so they are never picked by default). */
    public function latest(): ?string
    {
        $dirs = array_filter(File::directories(storage_path(self::DIR)), fn (string $d): bool => is_file($d.'/manifest.json'));
        sort($dirs);

        return $dirs === [] ? null : end($dirs);
    }

    /**
     * Why the database must not be replaced now (null = safe). Port check always; launcher
     * processes of this folder only matter when they use this database (the default file).
     */
    public function serverRunning(int $port): ?string
    {
        $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.5);
        if ($socket !== false) {
            fclose($socket);

            return "port {$port} masih dipakai (server masih berjalan)";
        }
        if (PHP_OS_FAMILY !== 'Windows' || realpath($this->databasePath()) !== realpath(database_path('database.sqlite'))) {
            return null;
        }
        $env = str_replace("'", "''", base_path('evironment'));
        $script = "@(Get-CimInstance Win32_Process | Where-Object { (\$_.Name -in 'php-cgi.exe','caddy.exe' -and \$_.ExecutablePath -like '{$env}\\*') -or (\$_.Name -eq 'cmd.exe' -and \$_.CommandLine -like '*START-DMS-INDRACO.bat*') }).Count";
        $count = (int) trim(Process::run(['powershell', '-NoProfile', '-Command', $script])->output());

        return $count > 0 ? "{$count} proses server DMS (Caddy/php-cgi/launcher) masih berjalan" : null;
    }

    /**
     * @return array{safety: string, moved: list<string>, sessions_removed: int, tables: array<string, int>}
     */
    public function restore(string $directory, int $port): array
    {
        $manifest = json_decode((string) File::get($directory.'/manifest.json'), true);
        $copy = $directory.'/database.sqlite';
        if (! is_array($manifest) || ! is_file($copy) || hash_file('sha256', $copy) !== $manifest['sha256']) {
            throw new DomainException("Snapshot {$directory} rusak atau tidak lengkap (SHA-256 tidak cocok). Restore dibatalkan.");
        }
        if ($reason = $this->serverRunning($port)) {
            throw new DomainException("Restore ditolak: {$reason}. Jalankan STOP-DMS-INDRACO.bat dulu.");
        }

        $safety = $this->snapshot(storage_path(self::SAFETY_DIR.'/'.date('Y-m-d_His')))['directory'];
        $this->replaceDatabase($copy, $manifest['sha256']);
        $moved = $this->moveNewUploads($manifest['files'], $safety.'/removed-files');
        $sessions = $this->deleteNewSessions($manifest['sessions']);

        $tables = $this->rowCounts($this->databasePath());
        if ($tables !== $manifest['tables']) {
            throw new RuntimeException('Jumlah baris sesudah restore tidak sama dengan manifest. Salinan pengaman: '.$safety);
        }

        return ['safety' => $safety, 'moved' => $moved, 'sessions_removed' => $sessions, 'tables' => $tables];
    }

    public function databasePath(): string
    {
        return (string) config('database.connections.sqlite.database');
    }

    /** Swap in the copy: close our connection, drop -wal/-shm of the old file, rename over it. */
    private function replaceDatabase(string $copy, string $sha256): void
    {
        $db = $this->databasePath();
        DB::purge('sqlite');
        gc_collect_cycles();
        $tmp = $db.'.restore-tmp';
        if (! copy($copy, $tmp) || hash_file('sha256', $tmp) !== $sha256) {
            @unlink($tmp);
            throw new RuntimeException('Gagal menyalin snapshot ke folder database. Database lama tidak diubah.');
        }
        foreach (['-wal', '-shm'] as $suffix) {
            if (is_file($db.$suffix) && ! unlink($db.$suffix)) {
                throw new RuntimeException("Tidak bisa menghapus {$db}{$suffix} (masih dipakai proses lain?). Database lama tidak diubah.");
            }
        }
        if (! rename($tmp, $db) || hash_file('sha256', $db) !== $sha256) {
            throw new RuntimeException('Gagal mengganti file database. Salinan pengaman ada di storage/'.self::SAFETY_DIR.'.');
        }
    }

    /**
     * @param  list<string>  $keep  paths relative to storage/app from the manifest
     * @return list<string> moved files (relative to storage/app)
     */
    private function moveNewUploads(array $keep, string $target): array
    {
        $moved = [];
        foreach (array_diff($this->uploadFiles(), $keep) as $relative) {
            File::ensureDirectoryExists(dirname($target.'/'.$relative));
            if (rename(storage_path('app/'.$relative), $target.'/'.$relative)) {
                $moved[] = $relative;
            }
        }

        return $moved;
    }

    /** @param list<string> $keep */
    private function deleteNewSessions(array $keep): int
    {
        $dir = (string) config('session.files');
        $removed = 0;
        foreach (array_diff($this->sessionFiles(), $keep) as $name) {
            $removed += (int) @unlink($dir.'/'.$name);
        }

        return $removed;
    }

    /**
     * Every file in the FileStorage upload folders of every search disk, relative to storage/app.
     * A disk root outside storage/app is skipped, so nothing else can ever be touched.
     *
     * @return list<string>
     */
    private function uploadFiles(): array
    {
        $base = realpath(storage_path('app'));
        $files = [];
        foreach (array_keys(FileStorage::SEARCH_DISKS) as $disk) {
            foreach (FileStorage::FOLDERS as $folder) {
                $dir = realpath(config("filesystems.disks.{$disk}.root").'/'.$folder);
                if ($dir === false || $base === false || ! str_starts_with($dir, $base.DIRECTORY_SEPARATOR)) {
                    continue;
                }
                foreach (File::allFiles($dir) as $file) {
                    $files[] = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
                }
            }
        }
        sort($files);

        return $files;
    }

    /** @return list<string> */
    private function sessionFiles(): array
    {
        $dir = (string) config('session.files');
        $names = is_dir($dir) ? array_map(fn ($f): string => $f->getFilename(), File::files($dir)) : [];

        return array_values(array_filter($names, fn (string $n): bool => $n !== '.gitignore'));
    }

    /** @return array<string, int> row count per table of a database file (opened read-only) */
    private function rowCounts(string $path): array
    {
        $pdo = new \PDO('sqlite:'.$path, null, null, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $counts = [];
        foreach ($pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(\PDO::FETCH_COLUMN) as $table) {
            $counts[$table] = (int) $pdo->query('SELECT COUNT(*) FROM "'.$table.'"')->fetchColumn();
        }
        $pdo = null;

        return $counts;
    }
}
