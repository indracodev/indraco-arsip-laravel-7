<?php

namespace Tests\Feature\Shell;

use App\Console\Commands\AssetsCache;
use App\Support\Asset;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Versioned asset URLs, the committed CSS bundle and the CSS rules for old browsers/low-spec PCs.
 */
class AssetsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        @unlink(Asset::manifestPath());
        Asset::flush();
    }

    protected function tearDown(): void
    {
        @unlink(Asset::manifestPath());
        Asset::flush();
        parent::tearDown();
    }

    public function test_asset_url_carries_the_content_hash_and_the_manifest_is_used_when_cached(): void
    {
        $expected = substr((string) md5_file(public_path('css/tokens.css')), 0, 10);
        $this->assertStringEndsWith('/css/tokens.css?v='.$expected, Asset::url('css/tokens.css'));

        Artisan::call('assets:cache');
        $this->assertFileExists(Asset::manifestPath());
        $manifest = require Asset::manifestPath();
        $this->assertSame($expected, $manifest['css/tokens.css']);
        $this->assertArrayHasKey('js/vendor/alpine.min.js', $manifest);

        // Proves the cached value is read (no hashing per request).
        file_put_contents(Asset::manifestPath(), "<?php return ['css/tokens.css' => 'cached1234'];");
        Asset::flush();
        $this->assertStringEndsWith('?v=cached1234', Asset::url('css/tokens.css'));
    }

    public function test_committed_component_bundle_is_up_to_date(): void
    {
        $committed = str_replace("\r\n", "\n", (string) file_get_contents(public_path('css/components.css')));

        $this->assertSame(AssetsCache::bundle(), $committed, 'Run `php artisan assets:cache` after editing public/css/components/*.css');
    }

    public function test_css_avoids_features_old_browsers_lack_and_stays_within_budget(): void
    {
        $total = 0;
        foreach (Asset::STYLESHEETS as $path) {
            $css = (string) file_get_contents(public_path($path));
            $total += strlen($css);
            foreach ([':has(', '@container', 'color-mix(', '@layer', '@property', 'oklch(', 'lab(', 'backdrop-filter', '@import', 'blur('] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, $css, "{$path} uses {$forbidden}");
            }
            $code = (string) preg_replace('#/\*.*?\*/#s', '', $css); // comments may contain "&"
            $this->assertDoesNotMatchRegularExpression('/&[\s.:]/', $code, "{$path} uses CSS nesting");
            $this->assertDoesNotMatchRegularExpression('/https?:\/\//', preg_replace('/xmlns=\'http:\/\/www\.w3\.org\/2000\/svg\'/', '', $css), "{$path} loads a remote URL");
        }

        $this->assertLessThan(60 * 1024, $total, 'CSS budget (raw) exceeded');
    }
}
