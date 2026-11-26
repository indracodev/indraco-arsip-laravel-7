<?php

namespace App\Support;

use App\Models\AppSetting;

/**
 * app_settings read once per request (PROJECT_RULES_SQLITE §3.3: global data for many views
 * is not queried in every partial). Fallbacks = Laravel 7 AppSetting::get() defaults.
 */
final class AppSettings
{
    private const DEFAULTS = [
        'app_name' => 'DMS PT INDRACO',
        'app_logo' => 'images/logo-indraco.png',
        'app_logo_dark' => 'images/logo-indraco-invert.png',
        'app_font_size' => '14px',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = once(fn (): array => AppSetting::query()->pluck('value', 'key')->all());

        return ($all[$key] ?? null) ?: ($default ?? self::DEFAULTS[$key] ?? null);
    }
}
