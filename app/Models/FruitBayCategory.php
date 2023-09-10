<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class FruitBayCategory extends Model
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
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('id', $value)
            ->orWhere('slug', $value)
            ->firstOrFail();
    }

    public static function registerEvents()
    {
        static::creating(function ($category) {
            $slug = Str::of($category->title)->slug();
            $category->slug = (string) FruitBayCategory::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });

        static::deleting(function (FruitBayCategory $org) {
            $org->items()->delete();
        });
    }

    public function registerFileable()
    {
        $this->fileableLoader([
            'image' => 'products',
        ], 'default', true, true);
    }

    /**
     * Get the URL to the fruit bay category's photo.
     *
     * @return string
     */
    protected function imageUrl(): Attribute
    {
        if (request()->version > 1) {
            return Attribute::make(
                get: fn () => $this->media_file,
            );
        } else {
            $image = $this->image
                ? img($this->image, 'banner', 'large')
                : 'https://loremflickr.com/320/320/'.urlencode($this->title ?? 'fruit').'?random='.rand();

            return Attribute::make(
                get: fn () => $image,
            );
        }
    }

    /**
     * Get all of the items for the FruitBayCategory
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(FruitBay::class);
    }
}
