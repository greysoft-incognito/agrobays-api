<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Saving extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'due',
        'user_id',
        'days',
        'status',
    ];

    protected $appends = [
        'total'
    ];

    /**
     * Get the saving's plan.
     */
    public function plan(): HasOneThrough
    {
        return $this->hasOneThrough(Subscription::class, Plan::class);
    }

    /**
     * Get the user that owns the Saving
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTotalAttribute()
    {
        return $this->amount * $this->days;
    }

    /**
     * Get the fruit bay item's transaction.
     */
    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'transactable');
    }
}