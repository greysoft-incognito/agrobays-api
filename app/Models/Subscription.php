<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'interval',
        'next_date',
        'cooperative_id',
        'custom_foodbag',
        'delivery_method',
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
        'custom_foodbag' => 'boolean',
    ];

    protected $attributes = [
        'fees_paid' => 0.00,
        'custom_foodbag' => false,
        'delivery_method' => 'delivery',
    ];

    public function __get($key)
    {
        if ($key === 'plan') {
            $plan = $this->plan()->first();
            if ($this->custom_foodbag) {
                $plan->amount = $this->bag->price ?? 0;
            }
            return $plan;
        }

        return $this->getAttribute($key);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (Subscription $org) {
            $org->allSavings()->delete();
            $org->dispatch()->delete();
        });

        static::retrieved(function (Subscription $sub) {
            if ($sub->cooperative) {
                $participants = $sub->cooperative->foodbags()->isApproved()->whereSubscriptionId($sub->id)->count();
                $plan_amount = $participants > 0 ? $sub->plan->amount * $participants : $sub->plan->amount;
                $sub->plan->amount = $plan_amount;
            }
        });
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
     * Get the foodBag associated with the Subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function bag(): HasOne
    {
        if ($this->custom_foodbag) {
            return $this->hasOne(CustomFoodbag::class, 'subscription_id', 'id');
        }

        return $this->hasOne(FoodBag::class, 'id', 'food_bag_id');
    }

    /**
     * Get the cooperative if the plan is attached to one
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

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
        return Attribute::make(
            get: fn () => (float) ($this->plan->amount - $this->saved_amount)
        );
    }

    public function savedAmount(): Attribute
    {
        $total = (float) $this->savings()->sum('amount');

        return Attribute::make(
            get: fn () => $total
        );
    }

    public function feesSplit(): Attribute
    {
        // Divide the fees by the number of days left
        $fees = $this->bag->fees ?? 0;

        return Attribute::make(
            get: fn () => $fees && $this->paid_days > 0 ? ($fees / $this->paid_days) : 0
        );
    }

    public function totalSaved(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->saved_amount)
        );
    }

    public function totalLeft(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->plan->amount - $this->saved_amount, 2)
        );
    }

    public function scopeCurrentStatus(Builder $query, $status = 'active')
    {
        if (is_array($status)) {
            $query->where(function ($q) use ($status) {
                foreach ($status as $stat) {
                    if (in_array(str_ireplace('!', '', $stat), ['active', 'pending', 'complete', 'withdraw', 'closed'])) {
                        if (str($stat)->contains('!')) {
                            $stat = str_replace('!', '', $stat);
                            $q->where('status', '!=', $stat);
                        } else {
                            $q->orWhere('status', $stat);
                        }
                    }
                }
            });
        } else {
            if (in_array(str_ireplace('!', '', $status), ['active', 'pending', 'complete', 'withdraw', 'closed'])) {
                if (str($status)->contains('!')) {
                    $status = str_replace('!', '', $status);

                    return $query->where('status', '!=', $status);
                }

                return $query->where('status', $status);
            }
        }
    }
}
