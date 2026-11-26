<?php

namespace Tests\Feature\Shell;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Display settings (per browser): font size, theme Terang / Gelap / Ikuti Sistem, notification sound.
 * Behaviour lives in public/js/{prefs,sound,app}.js; this checks the wiring the browser relies on.
 */
class DisplaySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_modal_offers_font_theme_and_sound(): void
    {
        $html = $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('x-data="displaySettings"', $html);
        $this->assertStringContainsString('Tema Tampilan', $html);
        $this->assertStringContainsString('type="radio" name="theme"', $html);
        $this->assertStringContainsString('Suara Notifikasi', $html);
        $this->assertStringContainsString('x-model="sound"', $html);
        $this->assertStringContainsString('Tes suara', $html);
        // prefs.js runs in <head> before paint (not deferred); sound.js is loaded before Alpine.
        $this->assertMatchesRegularExpression('#<script src="[^"]+/js/prefs\.js\?v=[0-9a-f]{10}"></script>#', $html);
        $this->assertLessThan(strpos($html, 'js/vendor/alpine.min.js'), strpos($html, 'js/sound.js'));
    }

    /** Owner request: default light, one-click toggle in the top bar (+ Alt+T like Laravel 7). */
    public function test_theme_defaults_to_light_and_the_top_bar_has_a_toggle(): void
    {
        $html = $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<button type="button" class="btn btn--ghost btn--icon theme-toggle" data-theme-toggle="data-theme-toggle" aria-label="Ganti ke mode gelap \(Alt\+T\)"/', $html);
        $this->assertStringContainsString('theme-toggle__moon', $html);
        $this->assertStringContainsString('theme-toggle__sun', $html);
        $this->assertStringContainsString("['system', 'Ikuti Sistem']", (string) file_get_contents(public_path('js/app.js')));

        $prefs = (string) file_get_contents(public_path('js/prefs.js'));
        $this->assertStringContainsString("theme: 'light',", $prefs);
        $this->assertStringContainsString("prefs.setTheme(stored.theme || 'light')", $prefs); // R26: nothing stored = light, never the OS theme
        $this->assertStringContainsString("localStorage.getItem('theme')", $prefs);
        $this->assertStringContainsString('e.altKey', $prefs);
        $this->assertStringContainsString("'dms-theme'", $prefs);
        $this->assertStringContainsString('data-img-src-dark', $prefs);
    }

    public function test_login_page_has_theme_toggle_adaptive_logo_and_background_image(): void
    {
        $loginHtml = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('data-theme-toggle="data-theme-toggle"', $loginHtml);
        $this->assertStringContainsString('guest__theme-toggle', $loginHtml);
        $this->assertMatchesRegularExpression('/data-img-src-light="[^"]*images\/logo-indraco\.png\?v=[0-9a-f]{10}"/', $loginHtml);
        $this->assertMatchesRegularExpression('/data-img-src-dark="[^"]*images\/logo-indraco-invert\.png\?v=[0-9a-f]{10}"/', $loginHtml);
        $this->assertMatchesRegularExpression('/--guest-bg-image:\s*url\(\'[^\']*images\/indraco-bg\.jpg\?v=[0-9a-f]{10}\'\)/', $loginHtml);
        $this->assertMatchesRegularExpression('/<section class="card">.*guest__brand--card.*data-img-src-light.*<\/section>/s', $loginHtml);
        $this->assertMatchesRegularExpression('/<\/section>\s*<footer class="guest__footer">Developed by Tim WEBDEV\. Copyright &copy; 2026 INDRACO\. All rights reserved<\/footer>/s', $loginHtml);

        $appHtml = $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/data-img-src-light="[^"]*images\/logo-indraco\.png\?v=[0-9a-f]{10}"/', $appHtml);
        $this->assertMatchesRegularExpression('/data-img-src-dark="[^"]*images\/logo-indraco-invert\.png\?v=[0-9a-f]{10}"/', $appHtml);
    }

    public function test_desktop_canvas_minimum_is_1024x720(): void
    {
        $tokens = (string) file_get_contents(public_path('css/tokens.css'));
        $layout = (string) file_get_contents(public_path('css/layout.css'));

        $this->assertStringContainsString('--canvas-min-w: 1024px;', $tokens);
        $this->assertStringContainsString('--canvas-min-h: 720px;', $tokens);
        $this->assertStringContainsString('min-width: var(--canvas-min-w);', $layout);
        $this->assertStringContainsString('min-height: max(100vh, var(--canvas-min-h));', $layout);
    }

    public function test_theme_tokens_cover_dark_mode_and_print_stays_light(): void
    {
        $tokens = (string) file_get_contents(public_path('css/tokens.css'));

        // Dark tokens only on screen, so printing always uses the light :root values.
        $this->assertMatchesRegularExpression('/@media screen\s*\{\s*:root\[data-theme="dark"\]/', $tokens);
        foreach (['bg', 'surface', 'text', 'accent', 'focus', 'nav-bg', 'success', 'warning', 'danger', 'info', 'logo-chip'] as $token) {
            $this->assertMatchesRegularExpression('/\[data-theme="dark"\][^}]*--color-'.$token.':/s', $tokens, "dark --color-{$token}");
        }
        $this->assertStringContainsString('dms_theme', (string) file_get_contents(public_path('js/prefs.js')));
        $this->assertStringContainsString('window.dmsSound', (string) file_get_contents(public_path('js/sound.js')));
        // Synthesised chime (Laravel 7 playChime), no audio file: tiny code.
        $this->assertLessThan(1024, strlen((string) preg_replace(['#/\*.*?\*/#s', '/\s+/'], ['', ' '], (string) file_get_contents(public_path('js/sound.js')))));
    }
}
