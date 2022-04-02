<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
     * Get the URL to the fruit bay category's photo.
     *
     * @return string
     */
    protected function imageUrl(): Attribute
    {
        $image = $this->image
            ? img($this->image, 'banner', 'medium')
            : 'https://loremflickr.com/320/320/'.urlencode($this->name??'fruit').'?random='.rand();

        return Attribute::make(
            get: fn () => $image,
        );
    }
}
