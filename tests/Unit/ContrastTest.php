<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * WCAG AA for every colour pair the UI uses (public/css/tokens.css):
 * 4.5:1 for normal text, 3:1 for UI parts (control borders, focus ring, active indicator).
 * "ambient" = the darkest point of the --ambient glows over --color-bg (content area background).
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
        ['on-accent', 'accent', 4.5], ['on-accent', 'accent-hover', 4.5], ['on-accent', 'danger', 4.5],
        ['nav-text', 'nav-bg', 4.5], ['nav-text', 'nav-hover', 4.5], ['nav-text', 'nav-active', 4.5], ['nav-text-muted', 'nav-bg', 4.5],
        ['success', 'success-soft', 4.5], ['warning', 'warning-soft', 4.5], ['danger', 'danger-soft', 4.5], ['info', 'info-soft', 4.5],
        ['success', 'surface', 4.5], ['warning', 'surface', 4.5], ['danger', 'surface', 4.5], ['info', 'surface', 4.5],
        ['border-strong', 'surface', 3.0], ['border-strong', 'surface-alt', 3.0],
        ['focus', 'surface', 3.0], ['focus', 'bg', 3.0], ['focus', 'nav-bg', 3.0],
        ['brand', 'nav-bg', 3.0], ['brand', 'nav-active', 3.0], ['brand', 'surface', 3.0],
    ];

    public function test_token_pairs_meet_wcag_aa(): void
    {
        $tokens = self::tokens();
        foreach (self::PAIRS as [$fg, $bg, $min]) {
            $ratio = self::ratio($tokens[$fg], $tokens[$bg]);
            $this->assertGreaterThanOrEqual($min, $ratio, sprintf('--color-%s on --color-%s = %.2f:1 (needs %.1f:1)', $fg, $bg, $ratio, $min));
        }
    }

    /** @return array<string, string> token name (without --color-) => #rrggbb, plus "ambient" */
    public static function tokens(): array
    {
        $css = (string) file_get_contents(__DIR__.'/../../public/css/tokens.css');
        preg_match_all('/--color-([a-z-]+):\s*(#[0-9a-f]{6})\s*;/i', $css, $m);
        $tokens = array_combine($m[1], $m[2]);
        $tokens['ambient'] = self::darkestAmbient($css, $tokens['bg']);

        return $tokens;
    }

    /**
     * Each glow centre (its strongest rgba stop) blended over --color-bg; the darkest wins.
     * The glows do not overlap (see the --ambient comment), so they are not stacked.
     */
    public static function darkestAmbient(string $css, string $bg): string
    {
        preg_match('/--ambient:(.*?);/s', $css, $ambient);
        preg_match_all('/rgba\((\d+),\s*(\d+),\s*(\d+),\s*([\d.]+)\)/', $ambient[1] ?? '', $stops, PREG_SET_ORDER);
        $base = array_map('hexdec', str_split(ltrim($bg, '#'), 2));
        $darkest = $bg;
        foreach ($stops as $stop) {
            $alpha = (float) $stop[4];
            $hex = '#';
            foreach ($base as $i => $channel) {
                $hex .= sprintf('%02x', (int) round($channel * (1 - $alpha) + (int) $stop[$i + 1] * $alpha));
            }
            if (self::luminance($hex) < self::luminance($darkest)) {
                $darkest = $hex;
            }
        }

        return $darkest;
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
