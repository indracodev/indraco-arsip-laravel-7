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

    /**
     * "Perbesar teks": everything that should grow with the per-user font size is in rem/em.
     * px is only allowed for hairlines (<= 4px), borders, outlines, shadows, radii and the 14px root
     * size; breakpoints are rem classes from public/js/prefs.js, never width media queries
     * (a px/em media query ignores the user's font size).
     */
    public function test_css_sizes_scale_with_the_font_size(): void
    {
        foreach (array_merge(Asset::STYLESHEETS, array_map(fn (string $f): string => 'css/components/'.basename($f), glob(public_path('css/components/*.css')) ?: [])) as $path) {
            $css = (string) preg_replace('#/\*.*?\*/#s', '', (string) file_get_contents(public_path($path)));
            $this->assertDoesNotMatchRegularExpression('/@media[^{]*width/', $css, "{$path}: use the .bp-* classes instead of a width media query");
            preg_match_all('/([a-z-]+)\s*:\s*([^;{}]*\d+(?:\.\d+)?px[^;{}]*)/', $css, $declarations, PREG_SET_ORDER);
            foreach ($declarations as [$declaration, $property, $value]) {
                if ($declaration === 'font-size: 14px' || preg_match('/^(border|outline|box-shadow|--radius|--shadow)/', $property)) {
                    continue;
                }
                preg_match_all('/(\d+(?:\.\d+)?)px/', $value, $px);
                $this->assertLessThanOrEqual(4, max(array_map('floatval', $px[1])), "{$path}: `{$declaration}` should be rem/em");
                $this->assertNotSame('font-size', $property, "{$path}: `{$declaration}` should be rem/em");
            }
        }
    }
}
