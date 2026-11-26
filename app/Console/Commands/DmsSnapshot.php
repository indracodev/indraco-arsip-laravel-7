<?php

namespace App\Console\Commands;

use App\Services\DatabaseSnapshot;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/** Snapshot before a load test (UJI-BEBAN-1-SNAPSHOT.bat); safe while the server runs. */
#[Signature('dms:snapshot')]
#[Description('Snapshot database (VACUUM INTO) + daftar file upload & sesi, untuk dipulihkan sesudah uji beban')]
class DmsSnapshot extends Command
{
    public function handle(DatabaseSnapshot $snapshots): int
    {
        $m = $snapshots->snapshot();
        $this->components->info('Snapshot dibuat: '.$m['directory']);
        $this->components->twoColumnDetail('Database', number_format($m['database_bytes'] / 1048576, 2).' MB, SHA-256 '.substr($m['sha256'], 0, 16).'...');
        $this->components->twoColumnDetail('File upload / sesi tercatat', count($m['files']).' / '.count($m['sessions']));
        foreach (['archives', 'archive_items', 'borrowing_logs', 'destruction_logs', 'archive_extension_logs', 'activity_logs'] as $table) {
            $this->components->twoColumnDetail("Baris {$table}", (string) ($m['tables'][$table] ?? '-'));
        }

        return self::SUCCESS;
    }
}
