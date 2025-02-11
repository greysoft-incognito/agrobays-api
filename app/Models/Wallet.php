<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Wallet extends Model
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
        'sender_id',
        'amount',
        'source',
        'detail',
        'type',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('account', function (Builder $builder) {
            $builder->whereNull('cooperative_id');
        });

        static::creating(function (Wallet $wallet) {
            if (! $wallet->reference) {
                $reference = config('settings.trx_prefix', 'AGB-') . Str::random(12);
                $wallet->reference = $reference;
            }
        });
    }

    public function topup($source, $amount, $detail = null): self
    {
        $reference = config('settings.trx_prefix', 'TRX-') . Str::random(12);

        return $this->create([
            'user_id' => $this->user_id,
            'reference' => $reference,
            'amount' => $amount,
            'source' => $source,
            'detail' => $detail,
            'type' => 'credit',
        ]);
    }

    /**
     * Get the user that sent the funds if available
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
