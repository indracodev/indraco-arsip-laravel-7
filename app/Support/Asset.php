<?php

namespace App\Support;

/**
 * Versioned URLs for static files in public/ (`?v=<content hash>`), so browsers and Caddy can
 * cache them for a year and still load a new version right after an update.
 *
 * Hashes come from bootstrap/cache/assets.php (written by `php artisan assets:cache`, which
 * `php artisan optimize` runs); OPcache keeps that file in memory, so a lookup costs nothing.
 * Without the manifest (development) the file is hashed once per request.
 */
final class Asset
{
    /** The only stylesheets a page loads (rules/50-frontend-blade.md: at most 4 <link>). */
    public const STYLESHEETS = ['css/tokens.css', 'css/base.css', 'css/layout.css', 'css/components.css'];

    /** @var array<string, string>|null */
    private static ?array $manifest = null;

    public static function url(string $path): string
    {
        return asset($path).'?v='.self::version($path);
    }

    public static function version(string $path): string
    {
        self::$manifest ??= is_file(self::manifestPath()) ? (array) require self::manifestPath() : [];

        return self::$manifest[$path] ??= self::hash(public_path($path));
    }

    public static function hash(string $file): string
    {
        return substr((string) @md5_file($file), 0, 10);
    }

    public static function manifestPath(): string
    {
        $configured = config('app.assets_cache');

        return is_string($configured) && $configured !== '' ? base_path($configured) : app()->bootstrapPath('cache/assets.php');
    }

    /** Forget the loaded manifest (after assets:cache / assets:clear, and in tests). */
    public static function flush(): void
    {
        self::$manifest = null;
    }
}
