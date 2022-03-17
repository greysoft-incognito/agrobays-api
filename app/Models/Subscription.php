<?php

namespace App\Models;

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

    public function getPaidDaysAttribute()
    {
        return $this->savings()->count('id');
    }

    public function getDaysLeftAttribute()
    {
        return $this->plan->duration - $this->savings()->count('id');
    }

    public function getTotalSavedAttribute()
    {
        return number_format($this->savings()->sum('amount'), 2);
    }

    public function getTotalLeftAttribute()
    {
        return number_format($this->plan->amount - $this->savings()->sum('amount'), 2);
    }
}
