<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Plan extends Model
{
    use HasFactory;

    public $appends = [
        'food_bag'
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function($plan) {
            $slug = Str::of($plan->title)->slug();
            $plan->slug = (string) Plan::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });
    }

    /**
     * Get the foodbag associated with the Plan
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function bag(): HasMany
    {
        return $this->hasMany(FoodBag::class);
    }

    public function getFoodBagAttribute()
    {
        return $this->bag()->get();
    }
}