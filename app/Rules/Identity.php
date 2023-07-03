<?php

namespace App\Rules;

use App\Models\Cooperative;
use App\Models\User;
use Illuminate\Contracts\Validation\Rule;

class Identity implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($type = 'user')
    {
        $this->type = $type;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($this->type === 'user') {
            return User::whereUsername($value)->orWhere('id', $value)->exists();
        }

        if ($this->type === 'cooperative') {
            return Cooperative::whereSlug($value)->orWhere('id', $value)->exists();
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('This :type does not exist.', ['type' => $this->type ?? 'user']);
    }
}
