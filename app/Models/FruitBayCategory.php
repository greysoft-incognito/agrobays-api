<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FruitBayCategory extends Model
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

    public static function boot()
    {
        parent::boot();
        static::creating(function($category) {
            $slug = Str::of($category->title)->slug();
            $category->slug = (string) FruitBayCategory::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });
    }

    /**
     * Get the URL to the fruit bay category's photo.
     *
     * @return string
     */
    protected function imageUrl(): Attribute
    {
        $image = $this->image
            ? profile_photo($this)
            : 'https://loremflickr.com/320/320/fruit?random='.rand();

        return Attribute::make(
            get: fn () => $image,
        );
    }

    /**
     * Get all of the items for the FruitBayCategory
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(FruitBay::class, 'foreign_key', 'local_key');
    }
}
