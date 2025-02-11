<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CooperativeWallet extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wallets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'cooperative_id',
        'reference',
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
            $builder->whereNotNull('cooperative_id');
        });

        static::creating(function (CooperativeWallet $wallet) {
            $reference = config('settings.trx_prefix', 'AGB-') . Str::random(12);
            if (! $wallet->reference) {
                $wallet->reference = $reference;
            }
        });
    }

    public function topup($source, $amount, $detail = null): self
    {
        $reference = config('settings.trx_prefix', 'TRX-') . Str::random(12);

        return $this->create([
            'cooperative_id' => $this->cooperative_id,
            'reference' => $reference,
            'user_id' => $this->user_id,
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
