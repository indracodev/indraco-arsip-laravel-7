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
        View::composer('*', function ($view) {
            $appLogo = 'images/logo-indraco.png';
            $appFontSize = '19px';
            $appName = 'DMS PT INDRACO';

            if (Schema::hasTable('app_settings')) {
                try {
                    $appLogo = AppSetting::get('app_logo', 'images/logo-indraco.png');
                    $appFontSize = AppSetting::get('app_font_size', '19px');
                    $appName = AppSetting::get('app_name', 'DMS PT INDRACO');
                } catch (\Exception $e) {
                    // Fallback to default
                }
            }

            $view->with([
                'globalAppLogo' => asset($appLogo),
                'globalAppLogoRaw' => $appLogo,
                'globalAppFontSize' => $appFontSize,
                'globalAppName' => $appName,
            ]);
        });
    }
}
