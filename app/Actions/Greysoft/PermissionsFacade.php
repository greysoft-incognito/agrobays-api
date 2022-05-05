<?php

namespace App\Actions\Greysoft;

use Illuminate\Support\Facades\Facade;

class PermissionsFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Permissions::class;
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
