<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Subscription extends Model
{
    use HasFactory;

    protected $appends = [
        'paid_days',
        'days_left',
        'total_saved',
        'total_left',
        'items',
    ];

    /**
     * Get the foodbag's dispatch.
     */
    public function dispatch()
    {
        return $this->morphOne(Dispatch::class, 'dispatchable');
    }

    /**
     * Get all of the complete savings for the Subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function savings(): HasMany
    {
        return $this->hasMany(Saving::class)->where('status', 'complete');
    }

    /**
     * Get all of the savings for the Subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function allSavings(): HasMany
    {
        return $this->hasMany(Saving::class);
    }

    /**
     * Get the user that owns the Subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plan that owns the Subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the foodBag associated with the Subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function bag(): HasOne
    {
        return $this->hasOne(FoodBag::class, 'id', 'food_bag_id');
    }

    /**
     * Get the items in the foodBag associated with the Subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function items(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->bag->foods
        );
    }

    public function paidDays(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->savings()->sum('days')
        );
    }

    public function daysLeft(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->plan->duration - $this->savings()->sum('days')
        );
    }

    public function totalSaved(): Attribute
    {
        $total = $this->savings()->get()->map(function ($value) {
            return $value->total ?? 0;
        })->sum();

        return Attribute::make(
            get: fn () => number_format($total)
        );
    }

    public function totalLeft(): Attribute
    {
        $total = $this->savings()->get()->map(function ($value) {
            return $value->total;
        })->sum();

        return Attribute::make(
            get: fn () => number_format($this->plan->amount - $total, 2)
        );
    }
}
