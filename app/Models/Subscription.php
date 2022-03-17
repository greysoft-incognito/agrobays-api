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
    ];

    /**
     * Get all of the savings for the Subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function savings(): HasMany
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
        return $this->hasOne(FoodBag::class);
    }

    public function paidDays(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->savings()->count('id')
        );
    }

    public function daysLeft(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->plan->duration - $this->savings()->count('id')
        );
    }

    public function totalSaved(): Attribute
    {
        return Attribute::make(
            get: fn() => number_format($this->savings()->sum('amount'), 2)
        );
    }

    public function totalLeft(): Attribute
    {
        return Attribute::make(
            get: fn() => number_format($this->plan->amount - $this->savings()->sum('amount'), 2)
        );
    }
}