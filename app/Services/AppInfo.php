<?php

namespace App\Services;

class AppInfo
{
    public static function basic()
    {
        return [
            'name' => env('APP_NAME', 'Laravel OP'),
            'version' => env('APP_VERSION', config('app.api.version.code', '1.0.0')),
            'author' => 'Toneflix Code.',
            'updated' => env('LAST_UPDATE', '2022-11-02 00:27:53'),
        ];
    }

    public static function api()
    {
        return [
            'api' => self::basic(),
        ];
    }
}
