<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class FruitBay extends Model
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
     * @var array<string, string>
     */
    protected $casts = [
        'bag' => 'array',
        'fees' => 'double',
        'price' => 'double',
        'weight' => 'double',
        'no_fees' => 'boolean',
        'available' => 'boolean',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'bag' => '[]',
        'fees' => 0.00,
        'weight' => 0.00,
        'unit' => 'kg',
        'no_fees' => false,
        'available' => true,
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
        static::creating(function ($item) {
            $slug = Str::of($item->name)->slug();
            $item->slug = (string) FruitBay::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });

        static::deleting(function (FruitBay $org) {
            $org->transaction()->delete();
        });
    }

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
     * Get the fruit bay item's transaction.
     */
    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'transactable');
    }

    /**
     * Get the fruit bay item's category.
     */
    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FruitBayCategory::class, 'fruit_bay_category_id');
    }
}