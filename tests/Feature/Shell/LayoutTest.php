<?php

namespace Tests\Feature\Shell;

use App\Models\User;
use App\Support\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Shell (layouts/app): Laravel 7 menu per role, central confirm modal, local versioned assets.
 */
class LayoutTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_MENU = [
        'Dashboard Overview', 'Master Departemen', 'Master Gudang &amp; Rak', 'Layout Gudang 2D',
        'Format Penomoran Box', 'Kelola User &amp; Hak Akses', 'Katalog Arsip', 'Penarikan Berkas',
        'Pemusnahan &amp; Retensi', 'Log History', 'Report', 'Live Monitor LAN &amp; User',
    ];

    private const GUDANG_MENU = [
        'Dashboard Overview', 'Master Gudang &amp; Rak', 'Layout Gudang 2D', 'Katalog Arsip',
        'Penarikan Berkas', 'Pemusnahan &amp; Retensi',
    ];

    private function user(string $role, ?string $deptCode = null): User
    {
        $deptId = $deptCode === null ? null
            : DB::table('departments')->insertGetId(['code' => $deptCode, 'name' => $deptCode, 'retention_years' => 5, 'is_active' => 1]);

        return User::factory()->create(['role' => $role, 'department_id' => $deptId]);
    }

    /** @return list<string> */
    private function menuLabels(string $html): array
    {
        preg_match('/<nav class="sidebar".*?<\/nav>/s', $html, $nav);
        preg_match_all('/class="nav__link[^"]*"[^>]*>([^<]+)<\/a>/', $nav[0] ?? '', $m);

        return $m[1];
    }

    public function test_pic_dept_gets_the_laravel7_pic_forms_and_the_new_box_button(): void
    {
        $response = $this->actingAs($this->user('pic_dept', 'HCS'))->get('/archives')->assertOk();

        $this->assertSame([
            '[Form 1] Katalog Arsip HCS', '[Form 2] Penarikan Berkas', '[Form 3] Pengajuan Perpanjangan &amp; Pemusnahan',
        ], $this->menuLabels($response->getContent()));
        $response->assertSee('Baru (F2)')->assertSee('data-shortcut="F2"', false);
    }

    public function test_admin_gets_the_full_laravel7_desktop_menu_in_order(): void
    {
        $response = $this->actingAs($this->user('admin'))->get('/')->assertOk()->assertSee('Dashboard Overview');

        $this->assertSame(self::ADMIN_MENU, $this->menuLabels($response->getContent()));
        $response->assertDontSee('Baru (F2)');
    }

    public function test_pic_gudang_gets_the_desktop_menu_without_admin_items(): void
    {
        $response = $this->actingAs($this->user('pic_gudang'))->get('/')->assertOk();

        $this->assertSame(self::GUDANG_MENU, $this->menuLabels($response->getContent()));
    }

    public function test_top_menu_groups_keep_the_laravel7_labels_and_order(): void
    {
        $admin = $this->user('admin');
        $groups = Menu::grouped($admin);

        $this->assertSame(array_column(Menu::for($admin), 'label'), array_merge(...array_map(fn ($g) => array_column($g['items'], 'label'), $groups)));
        $this->assertSame([null, 'Master', 'Arsip', 'Laporan & Sistem'], array_column($groups, 'label'));
        $this->assertSame([null, null, null], array_column(Menu::grouped($this->user('pic_dept', 'HCS')), 'label'));

        $html = $this->actingAs($admin)->get('/')->assertOk()->getContent();
        $this->assertSame(3, substr_count($html, 'data-nav-group'));
        $this->assertStringContainsString('aria-expanded="false" aria-controls="nav-group-1"', $html);
        // Layout preference is applied in <head> before the stylesheets render.
        $this->assertMatchesRegularExpression('/<head>.*js\/prefs\.js\?v=[0-9a-f]{10}"><\/script>.*<\/head>/s', $html);
        $this->assertStringContainsString('Tata Letak Menu', $html);
    }

    public function test_pic_dept_home_opens_the_catalog(): void
    {
        $this->actingAs($this->user('pic_dept', 'HCS'))->get('/')->assertRedirect(route('archives.index'));
    }

    public function test_menu_pages_keep_the_laravel7_role_checks(): void
    {
        $pic = $this->user('pic_dept', 'HCS');
        $gudang = $this->user('pic_gudang');
        $admin = $this->user('admin');

        foreach (['/borrowings', '/destructions'] as $url) {
            $this->actingAs($pic)->get($url)->assertOk()->assertSee('Sedang dibangun ulang');
        }
        foreach (['/master/departments', '/master/numbering', '/master/users', '/logs', '/reports', '/diagnostics'] as $url) {
            $this->actingAs($gudang)->get($url)->assertForbidden();
            $this->actingAs($admin)->get($url)->assertOk();
        }
        foreach (['/master/warehouses', '/master/warehouses/layout'] as $url) {
            $this->actingAs($pic)->get($url)->assertForbidden();
            $this->actingAs($gudang)->get($url)->assertOk();
        }
        $this->actingAs($gudang)->get('/archives/create')->assertForbidden();
    }

    public function test_layout_has_the_central_confirm_modal_and_only_local_versioned_assets(): void
    {
        $html = $this->actingAs($this->user('admin'))->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('id="confirm-modal"', $html);
        $this->assertStringContainsString('data-confirm-ok', $html);
        $this->assertSame(4, preg_match_all('/<link rel="stylesheet" href="[^"]+\/css\/[a-z-]+\.css\?v=[0-9a-f]{10}"/', $html));
        $this->assertStringNotContainsString('/build/', $html);
        $this->assertDoesNotMatchRegularExpression('/(src|href)="(https?:)?\/\/(?!localhost)/', $html);
        $this->assertStringNotContainsString('confirm(', $html);
    }

    public function test_login_and_change_password_pages_use_the_design_system_with_laravel7_labels(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('NAMA PENGGUNA / EMAIL')->assertSee('KATA SANDI / PASSWORD')
            ->assertSee('css/components.css?v=', false)->assertSee('id="confirm-modal"', false);

        $this->actingAs($this->user('pic_dept', 'HCS'))->get('/profile/password')->assertOk()
            ->assertSee('PASSWORD SAAT INI')->assertSee('PASSWORD BARU')->assertSee('(Min. 6 Karakter)')->assertSee('ULANGI PASSWORD BARU');
    }

    public function test_no_view_uses_vite(): void
    {
        $views = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS));
        foreach ($views as $file) {
            $this->assertStringNotContainsString('@vite', (string) file_get_contents($file->getPathname()), $file->getPathname());
        }
        $this->assertFileDoesNotExist(base_path('vite.config.js'));
        $this->assertFileDoesNotExist(base_path('package.json'));
    }
}
