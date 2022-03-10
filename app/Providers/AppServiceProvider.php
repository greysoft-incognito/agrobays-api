<?php

namespace App\Providers;

require_once base_path('vendor/matomo/device-detector/autoload.php');

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

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
        // Load Custom Helpers
        array_filter(File::files(app_path('Helpers')), function ($file) {
            if ($file->getExtension() === 'php' && stripos($file->getFileName(), 'helper') !== false) {
                require_once app_path('Helpers/' . $file->getFileName());
            }
        });
    }
}
