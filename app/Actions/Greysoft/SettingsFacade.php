<?php

namespace App\Actions\Greysoft;

use Illuminate\Support\Facades\Facade;

class SettingsFacade extends Facade
{ 
    protected static function getFacadeAccessor()
    {
        return 'settings';
    }

    /**
     * Resolve a new instance for the facade
     *
     * @return mixed
     */
    public static function fresh()
    {
        static::clearResolvedInstance(static::getFacadeAccessor());

        return static::getFacadeRoot();
    }
}
