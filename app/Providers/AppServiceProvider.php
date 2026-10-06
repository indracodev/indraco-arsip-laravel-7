<?php

namespace App\Providers;

use App\Models\AppSetting;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // 1. Optimize SQLite for multi-client LAN concurrency (WAL mode + busy timeout)
        Event::listen(ConnectionEstablished::class, function ($event) {
            if ($event->connection->getDriverName() === 'sqlite') {
                try {
                    $event->connection->statement('PRAGMA journal_mode = WAL;');
                    $event->connection->statement('PRAGMA busy_timeout = 5000;');
                    $event->connection->statement('PRAGMA synchronous = NORMAL;');
                    $event->connection->statement('PRAGMA temp_store = MEMORY;');
                    $event->connection->statement('PRAGMA cache_size = -32000;');
                    $event->connection->statement('PRAGMA mmap_size = 67108864;');
                } catch (\Exception $e) {
                    // Ignore if memory database or unsupported pragma
                }
            }
        });

        // 2. Cached View Composer for Global Layout Settings
        static $cachedGlobalSettings = null;

        View::composer('*', function ($view) use (&$cachedGlobalSettings) {
            if ($cachedGlobalSettings === null) {
                $appLogo = 'logo-indraco-est.png';
                $appFontSize = '14px';
                $appName = 'DMS PT INDRACO';

                if (Schema::hasTable('app_settings')) {
                    try {
                        $appLogo = AppSetting::get('app_logo', 'logo-indraco-est.png');
                        $appFontSize = AppSetting::get('app_font_size', '14px');
                        $appName = AppSetting::get('app_name', 'DMS PT INDRACO');
                    } catch (\Exception $e) {
                        // Fallback to default
                    }
                }

                $cachedGlobalSettings = [
                    'globalAppLogo' => asset($appLogo),
                    'globalAppLogoRaw' => $appLogo,
                    'globalAppFontSize' => $appFontSize,
                    'globalAppName' => $appName,
                ];
            }

            $view->with($cachedGlobalSettings);
        });
    }
}
