<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * Get the plan that owns the foodbag
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function getPlan(): BelongsTo
    {
        return $this->belong(Plan::class);
    }

    public function foods(): Attribute
    {
        return Attribute::make(
            get: fn()=> $this->getFoods()->get()
        );
    }
}
