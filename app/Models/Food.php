<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class Food extends Model
{
    use HasFactory;
    use Fileable;

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
        'available' => 'boolean',
    ];

    /**
     * The model's attributes.
     *
     * @var array<string, any>
     */
    protected $attributes = [
        'available' => true,
    ];

    public function registerFileable()
    {
        $this->fileableLoader([
            'image' => 'products',
        ], 'default', true, true);
    }

    /**
     * Get the fruit bay item's image url.
     *
     * @return string
     */
    public function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->media_file,
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
