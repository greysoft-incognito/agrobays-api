<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Plan extends Model
{
    use HasFactory;

    public $appends = [
        // 'food_bag',
        // 'image_url',
    ];

    protected $casts = [
        'customizable' => 'boolean',
    ];

    /**
     * The model's attributes.
     *
     * @var array<string, any>
     */
    protected $attributes = [
        'customizable' => true,
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($plan) {
            $slug = Str::of($plan->title)->slug();
            $plan->slug = (string) Plan::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });

        static::deleting(function (Saving $org) {
            $org->bags()->delete();
        });
    }

    /**
     * Get the foodbag associated with the Plan
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function bags(): HasMany
    {
        return $this->hasMany(FoodBag::class);
    }

    public function foodBag(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->bags()->get()
        );
    }

    /**
     * Get the URL to the fruit bay item's photo.
     *
     * @return string
     */
    protected function imageUrl(): Attribute
    {
        $image = $this->image
            ? img($this->image, 'banner', 'large')
            : 'https://loremflickr.com/320/320/' . urlencode($this->title ?? 'fruit');

        return Attribute::make(
            get: fn () => $image,
        );
    }
}
