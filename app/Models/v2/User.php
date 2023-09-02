<?php

namespace App\Models\v2;

use App\Models\User as ModelsUser;
use App\Traits\Extendable;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;

class User extends ModelsUser
{
    use Extendable;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_refreshed' => 'datetime',
        'last_attempt' => 'datetime',
        'last_seen' => 'datetime',
        'address' => 'collection',
        'country' => 'collection',
        'state' => 'collection',
        'city' => 'collection',
        'bank' => 'collection',
        'data' => 'collection',
        // 'phone' => E164PhoneNumberCast::class . ':NG',
        // 'nextofkin_phone' => E164PhoneNumberCast::class . ':NG',
    ];
}
