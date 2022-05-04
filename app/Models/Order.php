<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $casts = [
        'items' => 'array'
    ];

    protected $fillable = [
        'due',
        'items',
        'status',
        'amount',
        'user_id',
        'payment',
        'reference',
        'delivery_method',
    ];

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
}
