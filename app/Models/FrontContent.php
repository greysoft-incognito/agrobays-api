<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FrontContent extends Model
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
        static::creating(function($front_content) {
            $slug = Str::of($front_content->title)->slug();
            $front_content->slug = (string) FrontContent::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
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
            ? img($this->image, 'avatar', 'medium-square')
            : asset('media/default_avatar.png');

        return Attribute::make(
            get: fn () => $image,
        );
    }
}

