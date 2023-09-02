<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Food extends Model
{
    use HasFactory;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'image_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'weight' => 'float',
        'price' => 'float',
    ];

    /**
     * Get the URL to the fruit bay category's photo.
     *
     * @return string
     */
    protected function imageUrl(): Attribute
    {
        $image = $this->image
            ? img($this->image, 'banner', 'medium-square')
            : 'https://loremflickr.com/320/320/'.urlencode($this->name ?? 'fruit').'?random='.rand();

        return Attribute::make(
            get: fn () => $image,
        );
    }

    /**
     * Get the foodbags containing the food
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function foodbags(): BelongsToMany
    {
        return $this->belongsToMany(FoodBag::class, 'food_bag_items', 'food_id', 'food_bag_id');
    }
}
