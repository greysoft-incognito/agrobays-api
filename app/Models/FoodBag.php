<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FoodBag extends Model
{
    use HasFactory;

    public $appends = [
        'foods'
    ];

    /**
     * Get the foodbag associated with the Plan
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getFoods(): HasMany
    {
        return $this->hasMany(Food::class);
    }

    public function foods(): Attribute
    {
        return Attribute::make(
            get: fn()=> $this->getFoods()->get()
        );
    }
}
