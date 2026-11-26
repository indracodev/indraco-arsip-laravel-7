<?php

namespace App\Console\Commands;

use App\Services\DatabaseSnapshot;
use DomainException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Restore after a load test (UJI-BEBAN-3-RESTORE.bat): server must be stopped. Without --yes the
 * command asks itself; the .bat asks "YA" first and passes --yes.
 */
#[Signature('dms:restore-snapshot {--snapshot= : Folder snapshot (default: terbaru di storage/app/snapshots)} {--port=8000 : Port server yang harus mati} {--yes : Tanpa pertanyaan konfirmasi}')]
#[Description('Kembalikan database + file upload + sesi ke kondisi snapshot (server harus berhenti)')]
class DmsRestoreSnapshot extends Command
{
    public function handle(DatabaseSnapshot $snapshots): int
    {
        $option = $this->option('snapshot');
        $directory = is_string($option) && $option !== '' ? (is_dir($option) ? $option : storage_path(DatabaseSnapshot::DIR.'/'.$option)) : $snapshots->latest();
        if ($directory === null || ! is_file($directory.'/manifest.json')) {
            $this->components->error('Snapshot tidak ditemukan. Jalankan dms:snapshot (UJI-BEBAN-1-SNAPSHOT.bat) sebelum uji beban.');

            return self::FAILURE;
        }
        $this->components->warn("Database {$snapshots->databasePath()} akan dikembalikan ke snapshot {$directory}. Semua data yang dibuat sesudah snapshot hilang.");
        if (! $this->option('yes') && ! $this->confirm('Lanjutkan restore?')) {
            return self::FAILURE;
        }

        try {
            $result = $snapshots->restore($directory, (int) $this->option('port'));
        } catch (DomainException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Restore selesai, jumlah baris semua tabel sama dengan snapshot.');
        $this->components->twoColumnDetail('File upload baru dipindahkan', count($result['moved']).' file');
        $this->components->twoColumnDetail('Sesi login baru dihapus', (string) $result['sessions_removed']);
        $this->components->twoColumnDetail('Baris archives / archive_items', ($result['tables']['archives'] ?? 0).' / '.($result['tables']['archive_items'] ?? 0));
        $this->components->twoColumnDetail('Salinan pengaman (data sebelum restore + file yang dipindah)', $result['safety']);

        return self::SUCCESS;
    }
}
