<?php

namespace App\Models\v2;

use App\Models\User as ModelsUser;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends ModelsUser
{
    /**
     * Interact with the user's country.
     *
     * @return  \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function country(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                return Country::find($value);
            },
        );
    }
}
