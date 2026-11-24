<?php

namespace Tests\Feature\System;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use PDO;
use Tests\Feature\Borrowing\BorrowingFixtures;
use Tests\TestCase;

/**
 * Pengaturan Sistem: VACUUM INTO backup, hapus massal (confirm + automatic backup first), git pull,
 * logo upload. DatabaseMigrations (not RefreshDatabase): VACUUM cannot run inside a transaction.
 * storage/ and public/uploads/logo point to a temp folder that is removed afterwards.
 */
class MaintenanceTest extends TestCase
{
    use BorrowingFixtures;
    use DatabaseMigrations;

    private string $temp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->temp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'dms-system-test-'.uniqid();
        File::ensureDirectoryExists($this->temp.'/storage/logs');
        $this->app->useStoragePath($this->temp.'/storage');
        $this->app->usePublicPath($this->temp.'/public');
        $this->setUpBorrowingFixtures();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->temp);
        parent::tearDown();
    }

    /** @return list<string> */
    private function keptBackups(): array
    {
        return glob($this->temp.'/storage/app/backups/*.sqlite') ?: [];
    }

    private function assertSqliteWithArchives(string $path, int $archives): void
    {
        $this->assertStringStartsWith("SQLite format 3\0", (string) file_get_contents($path, false, null, 0, 16));
        $pdo = new PDO('sqlite:'.$path);
        $this->assertSame('ok', $pdo->query('PRAGMA integrity_check')->fetchColumn());
        $this->assertSame($archives, (int) $pdo->query('SELECT COUNT(*) FROM archives')->fetchColumn());
        $pdo = null;
    }

    public function test_backup_downloads_a_valid_sqlite_snapshot_and_removes_the_temp_file(): void
    {
        $this->storedBox($this->hcs, 'BOX-1');

        $response = $this->actingAs($this->admin)->post('/master/settings/backup-database')->assertOk();
        $file = $response->baseResponse->getFile()->getPathname();

        $this->assertStringContainsString('backup_indraco_dms_', (string) $response->headers->get('Content-Disposition'));
        $this->assertSqliteWithArchives($file, 1);
        $this->assertSame(1, DB::table('activity_logs')->where('action', 'SYSTEM_DATABASE_BACKUP')->count());
        $response->baseResponse->sendContent(); // deleteFileAfterSend
        $this->assertFileDoesNotExist($file);
    }

    public function test_clear_requires_the_confirmation(): void
    {
        $this->storedBox($this->hcs, 'BOX-1');

        $this->actingAs($this->admin)->post('/master/settings/clear-logs')->assertSessionHasErrors('confirmed');
        $this->actingAs($this->admin)->post('/master/settings/clear-archives', ['confirmed' => '1'])->assertSessionHasErrors('confirmation_text');
        $this->actingAs($this->admin)->post('/master/settings/clear-archives', ['confirmed' => '1', 'confirmation_text' => 'reset'])->assertSessionHasErrors('confirmation_text');

        $this->assertSame(1, DB::table('archives')->count());
        $this->assertSame([], $this->keptBackups());
    }

    public function test_clear_archives_makes_a_backup_first_then_deletes(): void
    {
        $this->storedBox($this->hcs, 'BOX-1');
        $this->storedBox($this->fat, 'BOX-2');

        $this->actingAs($this->admin)->post('/master/settings/clear-archives', ['confirmed' => '1', 'confirmation_text' => 'RESET'])
            ->assertSessionHas('success', fn (string $m): bool => str_contains($m, '(2 arsip)') && str_contains($m, 'storage/app/backups/backup_indraco_'));

        $this->assertSame(0, DB::table('archives')->count());
        $this->assertSame(0, DB::table('archive_items')->count());
        $this->assertSame(0, DB::table('warehouse_rack_slots')->whereNotNull('archive_id')->count());
        $this->assertCount(1, $this->keptBackups());
        $this->assertSqliteWithArchives($this->keptBackups()[0], 2);
        $log = DB::table('activity_logs')->where('action', 'SYSTEM_ARCHIVES_CLEARED')->first();
        $this->assertStringContainsString('backup_file', (string) $log?->properties);
    }

    public function test_clear_logs_and_box_allocations(): void
    {
        $box = $this->storedBox($this->hcs, 'BOX-1');
        DB::table('activity_logs')->insert(['action' => 'LOGIN', 'module' => 'AUTH', 'description' => 'lama']);

        $this->actingAs($this->admin)->post('/master/settings/clear-logs', ['confirmed' => '1'])->assertSessionHas('success');
        $this->assertSame(['SYSTEM_LOGS_CLEARED'], DB::table('activity_logs')->pluck('action')->all());

        $this->actingAs($this->admin)->post('/master/settings/clear-box-allocations', ['confirmed' => '1'])->assertSessionHas('success');
        $archive = DB::table('archives')->find($box);
        $this->assertSame('approved_booked', $archive->status);
        $this->assertNull($archive->warehouse_rack_slot_id);
        $this->assertCount(2, $this->keptBackups());
    }

    public function test_git_pull_keeps_a_backup_logs_and_starts_the_update_script(): void
    {
        Process::fake();
        $this->actingAs($this->admin)->post('/master/settings/git-pull-restart')->assertSessionHas('success');

        Process::assertRan(fn ($process): bool => str_contains((string) $process->command, 'UPDATE-AND-RESTART.bat') && str_starts_with((string) $process->command, 'start "" /min'));
        $this->assertSame(1, DB::table('activity_logs')->where('action', 'SYSTEM_GIT_PULL_RESTART')->count());
        $this->assertCount(1, $this->keptBackups());
    }

    public function test_logo_accepts_png_and_rejects_svg(): void
    {
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><circle r="5"/></svg>');
        $this->actingAs($this->admin)->post('/settings/update', ['logo' => $svg])->assertSessionHasErrors('logo');
        $this->assertNull(DB::table('app_settings')->where('key', 'app_logo')->value('value'));

        $png = UploadedFile::fake()->createWithContent('logo.png', (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
        $this->actingAs($this->admin)->post('/settings/update', ['logo' => $png, 'app_name' => 'DMS Uji', 'ping_alert_enabled' => '1', 'ping_alert_threshold_ms' => 1000])
            ->assertRedirect(route('settings.index'));
        $logo = (string) DB::table('app_settings')->where('key', 'app_logo')->value('value');
        $this->assertMatchesRegularExpression('#^uploads/logo/logo_\d+_[a-z0-9]{6}\.png$#', $logo);
        $this->assertFileExists($this->temp.'/public/'.$logo);
        $this->assertSame(['1', '1000', 'DMS Uji'], [
            DB::table('app_settings')->where('key', 'ping_alert_enabled')->value('value'),
            DB::table('app_settings')->where('key', 'ping_alert_threshold_ms')->value('value'),
            DB::table('app_settings')->where('key', 'app_name')->value('value'),
        ]);

        $this->actingAs($this->admin)->post('/settings/reset-logo')->assertRedirect(route('settings.index'));
        $this->assertNull(DB::table('app_settings')->where('key', 'app_logo')->value('value'));
    }
}
