<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomFoodbag extends Model
{
    use HasFactory;

    protected $appends = [
        'title',
        'is_custom',
    ];

    protected $fillable = [
        'items',
        'active',
        'user_id',
    ];

    protected $casts = [
        'items' => 'collection',
        'active' => 'boolean',
    ];

    protected $attributes = [
        'items' => '[]',
        'active' => true,
    ];

    public function __get($key)
    {
        if ($key === 'foods') {
            return $this->foods()->get()->map(function ($food) {
                $food->quantity = (int) $this->items->first(fn($v) => $v['id'] == $food->id)['qty'] ?? 1;
                return $food;
            });
        }

        return $this->getAttribute($key);
    }

    /**
     * Get the foods that belong to the foodbag
     *
     */
    public function foods()
    {
        return Food::whereIn('id', $this->items->map(fn ($item) => $item['id']));
    }

    /**
     * Indicate that this is a custom foodbag
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isCustom(): Attribute
    {
        return Attribute::make(fn () => true);
    }

    /**
     * Get the fees for the foodbag
     */
    public function fees(): Attribute
    {
        return Attribute::make(
            get: fn () => config('settings.custom_foodbag_shipping_fee') +
                ($this->foods->count() * config('settings.custom_foodbag_item_shipping_fee')),
        );
    }

    /**
     * Get the foodbag image url from the first food item image
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
        return $this->subscription->plan();
    }

    /**
     * Get the plan Id of the foodbag
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function planId(): Attribute
    {
        return Attribute::make(fn () => $this->plan->id);
    }

    /**
     * Get the subscription that owns the foodbag
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Set the title of the foodbag
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function title(): Attribute
    {
        return Attribute::make(fn () => 'Custom Foodbag');
    }

    /**
     * Get the total price of the foodbag
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function price(): Attribute
    {
        return Attribute::make(
            get: fn () => round((float) $this->foods->sum(fn ($food) => $food->price * $food->quantity), 2),
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
            get: fn () => $this->foods->sum(fn ($food) => $food->weight * $food->quantity),
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