<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'reference',
        'status',
        'method',
        'amount',
        'fees',
        'due',
    ];

    /**
     * The attributes to be appended
     *
     * @var array
     */
    protected $appends = [
        'content',
        'type',
    ];

    /**
     * Get the transaction's transactable model (probably a fruit bay item).
     */
    public function transactable()
    {
        return $this->morphTo();
    }

    public function content(): Attribute
    {
        return new Attribute(
            get: fn () => $this->transactable()->get(),
        );
    }

    public function type(): Attribute
    {
        return new Attribute(
            get: fn () => Str::replace('App\\Models\\', '', $this->transactable_type),
        );
    }

    /**
     * Get the user that owns the Transaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}