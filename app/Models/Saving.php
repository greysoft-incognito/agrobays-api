<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'total',
        'get_subscription',
    ];

    /**
     * Get the saving's subscription.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class)->where('status', '!=', 'complete');
    }

    public function getSubscription(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->subscription
        );
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

    public function total(): Attribute
    {
        return Attribute::make(
            get: fn()=> $this->amount * $this->days
        );
    }

    /**
     * Get the fruit bay item's transaction.
     */
    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'transactable');
    }
}
