<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FoodBag extends Model
{
    use HasFactory;

    protected $fillable = [
        'fees',
        'title',
        'description',
        'plan_id',
    ];

    protected $casts = [
        'fees' => 'float',
    ];

    protected $attributes = [
        'fees' => 0.00,
    ];

    /**
     * Get the foods that belong to the foodbag
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function foods(): BelongsToMany
    {
        return $this->belongsToMany(Food::class, 'food_bag_items', 'food_bag_id', 'food_id')
            ->withPivot('quantity', 'is_active')
            ->withTimestamps();
    }

    /**
     * Merge all food images into one image array
     */
    public function image(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->foods->first()->image_url ?? '',
        );
    }

    /**
     * Get the plan that owns the foodbag
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the total price of the foodbag
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function price(): Attribute
    {
        return Attribute::make(
            get: fn () => (float)$this->foods->sum(fn ($food) => $food->price * $food->pivot->quantity),
        );
    }

    /**
     * Get the total weight of the foodbag
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function weight(): Attribute
    {
        return Attribute::make(
            get: fn () => (float)$this->foods->sum(fn ($food) => $food->weight * $food->pivot->quantity),
        );
    }

    /**
     * Get the total weight_unit of the foodbag
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function weightUnit(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->foods->first()->unit ?? 'kg',
        );
    }
}