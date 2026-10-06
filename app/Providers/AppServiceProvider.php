<?php

namespace App\Providers;

use App\Models\AppSetting;
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
