<?php

namespace App\Providers;

require_once base_path('vendor/matomo/device-detector/autoload.php');

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

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
        config(['auth.providers.users.model' => \App\Models\v2\User::class]);

        // Load Custom Helpers
        array_filter(File::files(app_path('Helpers')), function ($file) {
            if ($file->getExtension() === 'php' && stripos($file->getFileName(), 'helper') !== false) {
                require_once app_path('Helpers/' . $file->getFileName());
            }
        });

        Str::macro('isBool', function (string $value) {
            return preg_match('/^[0-1]{1}+$|^(?:true|false|on|off)+$/', $value) || is_bool($value);
        });

        Stringable::macro('isBool', function () {
            return new Stringable(Str::isBool($this->value));
        });
    }
}
