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
        'days',
        'tax',
        'status',
        'user_id',
        'payment_ref',
    ];

    protected $appends = [
        'total',
    ];

    protected $casts = [
        'due' => 'float',
        'tax' => 'float',
        'amount' => 'float',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (Saving $org) {
            $org->transaction()->delete();
        });
    }

    /**
     * Get the saving's subscription.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class); //->where('status', '!=', 'complete');
    }

    /**
     * Get all of the saving's subscription.
     */
    public function gsubscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function getSubscription(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->subscription
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
            get: fn () => $this->amount
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
