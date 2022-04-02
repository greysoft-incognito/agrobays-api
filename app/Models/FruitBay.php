<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FruitBay extends Model
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
        static::creating(function($item) {
            $slug = Str::of($item->name)->slug();
            $item->slug = (string) FruitBay::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });
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
            : 'https://loremflickr.com/320/320/'.urlencode($this->name??'fruit').'?random='.rand();

        return Attribute::make(
            get: fn () => $image,
        );
    }

    /**
     * Get the fruit bay item's transaction.
     */
    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'transactable');
    }
}
