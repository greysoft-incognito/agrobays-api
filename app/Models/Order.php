<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $casts = [
        'tax' => 'float',
        'due' => 'float',
        'fees' => 'float',
        'items' => 'collection',
        'amount' => 'float',
        'express_delivery' => 'boolean',
    ];

    protected $fillable = [
        'due',
        'fees',
        'items',
        'status',
        'amount',
        'user_id',
        'payment',
        'reference',
        'delivery_method',
        'express_delivery',
    ];

    protected $attributes = [
        'tax' => 0.00,
        'due' => 0.00,
        'fees' => 0.00,
        'amount' => 0.00,
        'delivery_method' => 'delivery',
        'express_delivery' => false,
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (Order $org) {
            $org->transaction()->delete();
            $org->dispatch()->delete();
        });
    }

    /**
     * Get the order's transaction.
     */
    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'transactable');
    }

    /**
     * Get the order's dispatch.
     */
    public function dispatch()
    {
        return $this->morphOne(Dispatch::class, 'dispatchable');
    }

    /**
     * Get the user that owns the Order
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scoped the order query by status
     */
    public function scopeHasStatus(Builder $query, $status = 'pending')
    {
        $query->where('status', $status);
    }

    /**
     * Scoped the order query by payment status
     */
    public function scopePaymentStatus(Builder $query, $status = 'complete')
    {
        $query->where('payment', $status);
    }
}