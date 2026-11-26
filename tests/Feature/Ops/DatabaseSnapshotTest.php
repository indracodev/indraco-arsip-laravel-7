<?php

namespace Tests\Feature\Ops;

use App\Services\DatabaseBackup;
use App\Services\DatabaseSnapshot;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mockery;
use Tests\TestCase;

/**
 * dms:snapshot / dms:restore-snapshot on a temp database file and a temp storage folder: rows and
 * upload / session files created after the snapshot disappear, older files and files outside the
 * upload folders stay, and a running server blocks the restore (serverRunning mocked).
 */
class DatabaseSnapshotTest extends TestCase
{
    private string $temp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->temp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'dms-snapshot-test-'.uniqid();
        $storage = $this->temp.'/storage';
        $this->app->useStoragePath($storage);
        config([
            'database.connections.sqlite.database' => $this->temp.'/database.sqlite',
            'filesystems.disks.local.root' => $storage.'/app/private',
            'filesystems.disks.public.root' => $storage.'/app/public',
            'filesystems.disks.legacy_public.root' => $storage.'/app/legacy-public',
            'session.files' => $storage.'/framework/sessions',
        ]);
        File::ensureDirectoryExists($this->temp);
        File::put($this->temp.'/database.sqlite', '');
        DB::purge('sqlite');
        DB::statement('CREATE TABLE archives (id INTEGER PRIMARY KEY, title TEXT)');
        DB::table('archives')->insert(['title' => 'lama']);
        $this->storageFile('app/private/archive_scans/lama.pdf');
        $this->storageFile('framework/sessions/sesi-lama');
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        File::deleteDirectory($this->temp);
        parent::tearDown();
    }

    public function test_restore_removes_rows_files_and_sessions_created_after_the_snapshot(): void
    {
        $this->serverRunning(null);
        $this->artisan('dms:snapshot')->assertSuccessful();

        DB::table('archives')->insert(['title' => 'LOADTEST-x']);
        $this->storageFile('app/private/archive_scans/baru.pdf');
        $this->storageFile('app/private/bap_certificates/bap.pdf');
        $this->storageFile('app/public/borrowing_approvals/lama-l7.pdf');
        $this->storageFile('app/private/bench/bukan-upload.txt');
        $this->storageFile('app/backups/backup.sqlite');
        $this->storageFile('framework/sessions/sesi-uji');

        $this->artisan('dms:restore-snapshot', ['--yes' => true])->assertSuccessful();

        $this->assertSame(['lama'], DB::table('archives')->pluck('title')->all());
        foreach (['app/private/archive_scans/baru.pdf', 'app/private/bap_certificates/bap.pdf', 'app/public/borrowing_approvals/lama-l7.pdf', 'framework/sessions/sesi-uji'] as $gone) {
            $this->assertFileDoesNotExist(storage_path($gone));
        }
        foreach (['app/private/archive_scans/lama.pdf', 'app/private/bench/bukan-upload.txt', 'app/backups/backup.sqlite', 'framework/sessions/sesi-lama'] as $kept) {
            $this->assertFileExists(storage_path($kept));
        }
        $safety = File::directories(storage_path(DatabaseSnapshot::SAFETY_DIR))[0];
        $this->assertFileExists($safety.'/removed-files/private/archive_scans/baru.pdf');
        $this->assertSame(2, (int) (new \PDO('sqlite:'.$safety.'/database.sqlite'))->query('SELECT COUNT(*) FROM archives')->fetchColumn());
    }

    public function test_restore_is_refused_while_the_server_runs(): void
    {
        $snapshots = $this->serverRunning('port 8000 masih dipakai (server masih berjalan)');
        $directory = $snapshots->snapshot()['directory'];
        DB::table('archives')->insert(['title' => 'LOADTEST-x']);
        $this->storageFile('app/private/archive_scans/baru.pdf');

        $this->artisan('dms:restore-snapshot', ['--yes' => true])->assertFailed();
        $this->expectException(DomainException::class);
        try {
            $snapshots->restore($directory, 8000);
        } finally {
            $this->assertSame(2, DB::table('archives')->count());
            $this->assertFileExists(storage_path('app/private/archive_scans/baru.pdf'));
            $this->assertDirectoryDoesNotExist(storage_path(DatabaseSnapshot::SAFETY_DIR));
        }
    }

    public function test_a_tampered_snapshot_is_refused(): void
    {
        $snapshots = $this->serverRunning(null);
        $directory = $snapshots->snapshot()['directory'];
        File::append($directory.'/database.sqlite', 'x');

        $this->expectExceptionMessage('SHA-256 tidak cocok');
        $snapshots->restore($directory, 8000);
    }

    private function serverRunning(?string $reason): DatabaseSnapshot
    {
        $mock = Mockery::mock(DatabaseSnapshot::class, [app(DatabaseBackup::class)])->makePartial();
        $mock->shouldReceive('serverRunning')->andReturn($reason);
        $this->app->instance(DatabaseSnapshot::class, $mock);

        return $mock;
    }

    private function storageFile(string $relative): void
    {
        File::ensureDirectoryExists(dirname(storage_path($relative)));
        File::put(storage_path($relative), 'x');
    }
}
