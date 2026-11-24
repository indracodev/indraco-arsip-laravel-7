<?php

namespace Tests\Feature\System;

use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use App\Services\ReportBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Borrowing\BorrowingFixtures;
use Tests\TestCase;

/**
 * Access per role of the SISTEM pages (findings #6, #10, #11) and the pages themselves.
 */
class SystemAccessTest extends TestCase
{
    use BorrowingFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBorrowingFixtures();
    }

    /** @return list<array{0: string, 1: string}> */
    private function adminOnly(): array
    {
        return [
            ['get', '/logs'], ['get', '/reports'], ['get', '/reports/print/users'], ['get', '/reports/export-csv/users'],
            ['get', '/settings'], ['post', '/settings/update'], ['post', '/settings/reset-logo'],
            ['post', '/master/settings/backup-database'], ['post', '/master/settings/clear-logs'], ['post', '/master/settings/clear-box-allocations'],
            ['post', '/master/settings/clear-archives'], ['post', '/master/settings/git-pull-restart'],
            ['get', '/diagnostics'], ['get', '/api/health/metrics'], ['get', '/api/health/probe-ip?target=127.0.0.1'], ['get', '/api/health/logs'], ['post', '/api/health/wal-checkpoint'],
        ];
    }

    public function test_pic_departemen_and_pic_gudang_get_403_on_admin_pages(): void
    {
        foreach ([$this->picHcs, $this->gudang] as $user) {
            foreach ($this->adminOnly() as [$method, $uri]) {
                $this->actingAs($user)->{$method}($uri)->assertForbidden();
            }
        }
        $this->assertSame(0, DB::table('activity_logs')->where('module', 'SETTINGS')->count());
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/settings')->assertRedirect('/login');
        $this->get('/reports/export-csv/users')->assertRedirect('/login');
    }

    public function test_admin_opens_every_system_page(): void
    {
        $this->storedBox($this->hcs, 'BOX-1');
        ActivityLogger::log('LOGIN', 'Login berhasil', 'AUTH', ['ip' => 'x'], null, $this->picHcs);

        $this->actingAs($this->admin)->get('/')->assertOk()->assertSee('Selamat Datang, '.$this->admin->name)->assertSee('IND/BOX-1')->assertSee('Workstation Super Admin');
        $this->actingAs($this->admin)->get('/logs')->assertOk()->assertSee('Login Masuk')->assertSee('PIC_HCS');
        foreach (['entry', 'borrowing', 'destruction'] as $tab) {
            $this->actingAs($this->admin)->get('/logs?tab='.$tab)->assertOk();
        }
        foreach (array_keys(ReportBuilder::TYPES) as $type) {
            $this->actingAs($this->admin)->get('/reports?type='.$type)->assertOk();
            $this->actingAs($this->admin)->get('/reports/print/'.$type)->assertOk()->assertDontSee('css/app.css');
        }
        $this->actingAs($this->admin)->get('/reports/print/nope')->assertNotFound();
        $this->actingAs($this->admin)->get('/settings')->assertOk()->assertSee('Pemeliharaan & Reset Database');
        $this->actingAs($this->admin)->get('/diagnostics')->assertOk()->assertSee('Live Monitor LAN & User');
        $metrics = $this->actingAs($this->admin)->getJson('/api/health/metrics')->assertOk();
        $this->assertSame('PIC_HCS', collect($metrics->json('connected_users.users'))->firstWhere('id', $this->picHcs->id)['name']);
        $this->assertArrayNotHasKey('diagnostic_key', $metrics->json());
        $this->actingAs($this->admin)->getJson('/api/health/probe-ip?target=not-an-ip')->assertStatus(422);
        $this->actingAs($this->admin)->getJson('/api/health/logs')->assertOk()->assertJsonStructure(['file_exists', 'lines']);
    }

    public function test_gudang_dashboard_and_pic_redirect(): void
    {
        $this->storedBox($this->fat, 'BOX-FAT');
        $this->actingAs($this->gudang)->get('/')->assertOk()->assertSee('Workstation Kurator Gudang')->assertSee('IND/BOX-FAT')->assertDontSee('Buat Draft Arsip');
        $this->actingAs($this->picHcs)->get('/')->assertRedirect(route('archives.index'));
    }

    public function test_log_history_module_filter_matches_the_stored_uppercase_modules(): void
    {
        ActivityLogger::log('LOGIN', 'Login A', 'AUTH', [], null, $this->picHcs);
        ActivityLogger::log('ARCHIVE_CREATE', 'Arsip B', 'DOKUMEN_ARSIP', [], null, $this->picHcs);

        $this->actingAs($this->admin)->get('/logs?module=auth')->assertOk()->assertSee('Login A')->assertDontSee('Arsip B');
        $this->actingAs($this->admin)->get('/logs?module=archive')->assertOk()->assertSee('Arsip B')->assertDontSee('Login A');
        $this->assertSame(2, ActivityLog::query()->count());
    }
}
