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

    protected $fillable = [
        'delivery_method',
        'interval',
        'next_date',
    ];

    protected $appends = [
        'paid_days',
        'days_left',
        'total_left',
        'total_saved',
        'saved_amount',
        'left_amount',
        'fees_split',
        'items',
    ];

    protected $casts = [
        'fees_paid' => 'float',
        'next_date' => 'datetime',
    ];

    protected $attributes = [
        'fees_paid' => 0.00,
        'delivery_method' => 'delivery',
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

    public function lastSaving()
    {
        return $this->hasOne(Saving::class)->latest();
    }

    /**
     * Get the next amount for the Subscription
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function nextAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->interval === 'yearly') {
                    $days = 365;
                } elseif ($this->interval === 'monthly') {
                    $days = 30;
                } elseif ($this->interval === 'weekly') {
                    $days = 7;
                } else {
                    $days = 1;
                }

                $amount = $this->plan->amount;
                $duration = $this->plan->duration;
                $nextAmount = ($amount / $duration) * $days;

                // Check if the next amount would make the subscription exceed the plan amount
                if ($this->left_amount + $nextAmount > $this->plan->amount && $this->paid_days > 0) {
                    $nextAmount = $this->plan->amount - $this->left_amount;
                }

                return $nextAmount;
            },
        );
    }

    public function setDateByInterval(\Illuminate\Support\Carbon $date)
    {
        if ($this->interval === 'daily') {
            return $date->addDays(1);
        } elseif ($this->interval === 'weekly') {
            return $date->addWeeks(1);
        } elseif ($this->interval === 'monthly') {
            return $date->addMonths(1);
        } elseif ($this->interval === 'yearly') {
            return $date->addYears(1);
        } else {
            return $date;
        }
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
            get: fn () => (int) $this->savings()->sum('days')
        );
    }

    public function daysLeft(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) $this->plan->duration - $this->savings()->sum('days')
        );
    }

    public function leftAmount(): Attribute
    {
        $total = $this->savings()->get()->map(function ($value) {
            return $value->total;
        })->sum();

        return Attribute::make(
            get: fn () => ($this->plan->amount - $total)
        );
    }

    public function savedAmount(): Attribute
    {
        $total = $this->savings()->get()->map(function ($value) {
            return $value->total ?? 0;
        })->sum();

        return Attribute::make(
            get: fn () => $total
        );
    }

    public function feesSplit(): Attribute
    {
        // Divide the fees by the number of days left
        return Attribute::make(
            get: fn () => $this->bag->fees && $this->paid_days > 0 ? ($this->bag->fees / $this->paid_days) : 0
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