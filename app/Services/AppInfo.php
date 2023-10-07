<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class AppInfo
{
    public static function basic()
    {
        return[
            'name' => env('APP_NAME', 'Agrobays API'),
            'version' => config('api.api_version'),
            'api_version' => config('api.api_version'),
            'app_version' => config('api.app_version'),
            'ios_version' => config('api.ios_version'),
            'author' => 'Greysoft Limited',
            'updated' => File::exists(base_path('.updated'))
                ? new Carbon(File::lastModified(base_path('.updated')))
                : now(),
        ];
    }

    public static function api()
    {
        return [
            'api' => self::basic(),
        ];
    }
}