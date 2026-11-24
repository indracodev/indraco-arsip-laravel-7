<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * WCAG AA for every colour pair the UI uses (public/css/tokens.css), light and dark theme:
 * 4.5:1 for normal text, 3:1 for UI parts (control borders, focus ring, active indicator).
 * "ambient" = every glow centre of --ambient over --color-bg (content area background).
 * Prints nothing; the pair list doubles as documentation (see ui-design-system.md).
 */
class ContrastTest extends TestCase
{
    /** @var list<array{0: string, 1: string, 2: float}> [foreground, background, minimum] */
    public const PAIRS = [
        ['text', 'bg', 4.5], ['text', 'surface', 4.5], ['text', 'surface-alt', 4.5], ['text', 'hover', 4.5],
        ['text-muted', 'bg', 4.5], ['text-muted', 'surface', 4.5], ['text-muted', 'surface-alt', 4.5], ['text-muted', 'hover', 4.5],
        ['text-subtle', 'surface', 4.5], ['text-subtle', 'surface-alt', 4.5], ['text-subtle', 'bg', 4.5],
        // Page text on the ambient glow. text-subtle is only placeholder (on a surface), a disabled
        // pager link (WCAG-exempt) or a decorative icon there, so 3:1 is its floor on the glow.
        ['text', 'ambient', 4.5], ['text-muted', 'ambient', 4.5], ['accent', 'ambient', 4.5], ['text-subtle', 'ambient', 3.0],
        ['focus', 'ambient', 3.0],
        ['accent', 'surface', 4.5], ['accent', 'bg', 4.5], ['accent', 'accent-soft', 4.5],
        ['on-accent', 'accent', 4.5], ['on-accent', 'accent-hover', 4.5], ['on-accent', 'danger', 4.5], ['on-accent', 'danger-hover', 4.5],
        ['nav-text', 'nav-bg', 4.5], ['nav-text', 'nav-hover', 4.5], ['nav-text', 'nav-active', 4.5], ['nav-text-muted', 'nav-bg', 4.5],
        ['success', 'success-soft', 4.5], ['warning', 'warning-soft', 4.5], ['danger', 'danger-soft', 4.5], ['info', 'info-soft', 4.5],
        ['success', 'surface', 4.5], ['warning', 'surface', 4.5], ['danger', 'surface', 4.5], ['info', 'surface', 4.5],
        ['border-strong', 'surface', 3.0], ['border-strong', 'surface-alt', 3.0],
        ['focus', 'surface', 3.0], ['focus', 'bg', 3.0], ['focus', 'nav-bg', 3.0],
        ['brand', 'nav-bg', 3.0], ['brand', 'nav-active', 3.0], ['brand', 'surface', 3.0],
        ['ink', 'paper', 4.5], ['ink', 'paper-head', 4.5],
    ];

    /** @return array<string, array{0: string}> */
    public static function themes(): array
    {
        return ['light' => ['light'], 'dark' => ['dark']];
    }

    #[DataProvider('themes')]
    public function test_token_pairs_meet_wcag_aa(string $theme): void
    {
        [$tokens, $ambient] = self::tokens($theme);
        foreach (self::PAIRS as [$fg, $bg, $min]) {
            foreach ($bg === 'ambient' ? $ambient : [$tokens[$bg]] as $background) {
                $ratio = self::ratio($tokens[$fg], $background);
                $this->assertGreaterThanOrEqual($min, $ratio, sprintf('%s: --color-%s on %s (%s) = %.2f:1 (needs %.1f:1)', $theme, $fg, $bg, $background, $ratio, $min));
            }
        }
    }

    /**
     * Light = the first :root block; dark = light overridden by :root[data-theme="dark"].
     *
     * @return array{0: array<string, string>, 1: list<string>} tokens (name without --color- => #rrggbb) and the ambient glow points
     */
    public static function tokens(string $theme): array
    {
        $css = (string) file_get_contents(__DIR__.'/../../public/css/tokens.css');
        preg_match('/^:root\s*\{(.*?)^\}/ms', $css, $light);
        $blocks = [$light[1] ?? ''];
        if ($theme === 'dark') {
            preg_match('/:root\[data-theme="dark"\]\s*\{(.*?)
    \}/s', $css, $dark);
            $blocks[] = $dark[1] ?? '';
        }
        $tokens = [];
        $ambient = '';
        foreach ($blocks as $block) {
            preg_match_all('/--color-([a-z-]+):\s*(#[0-9a-f]{6})\s*;/i', $block, $m);
            $tokens = array_merge($tokens, array_combine($m[1], $m[2]));
            $ambient = preg_match('/--ambient:(.*?);/s', $block, $a) ? $a[1] : $ambient;
        }

        return [$tokens, self::ambientPoints($ambient, $tokens['bg'])];
    }

    /**
     * Each glow centre (its strongest rgba stop) blended over --color-bg. Every point is checked,
     * so the darkest (light theme) and the lightest (dark theme) point are both covered.
     * The glows do not overlap (see the --ambient comment), so they are not stacked.
     *
     * @return list<string>
     */
    public static function ambientPoints(string $ambient, string $bg): array
    {
        preg_match_all('/rgba\((\d+),\s*(\d+),\s*(\d+),\s*([\d.]+)\)/', $ambient, $stops, PREG_SET_ORDER);
        $base = array_map('hexdec', str_split(ltrim($bg, '#'), 2));
        $points = [];
        foreach ($stops as $stop) {
            $alpha = (float) $stop[4];
            if ($alpha <= 0) {
                continue;
            }
            $hex = '#';
            foreach ($base as $i => $channel) {
                $hex .= sprintf('%02x', (int) round($channel * (1 - $alpha) + (int) $stop[$i + 1] * $alpha));
            }
            $points[] = $hex;
        }

        return $points;
    }

    public static function ratio(string $a, string $b): float
    {
        [$l1, $l2] = [self::luminance($a), self::luminance($b)];

        return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
    }

    private static function luminance(string $hex): float
    {
        $channels = array_map(function (string $c): float {
            $v = hexdec($c) / 255;

            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
