<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
            $reference = config('settings.trx_prefix', 'AGB-').Str::random(12);
            if (! $wallet->reference) {
                $wallet->reference = $reference;
            }
        });
    }

    public function topup($source, $amount, $detail = null): self
    {
        $reference = config('settings.trx_prefix', 'TRX-').Str::random(12);

        return $this->create([
            'user_id' => $this->user_id,
            'reference' => $reference,
            'amount' => $amount,
            'source' => $source,
            'detail' => $detail,
            'type' => 'credit',
        ]);
    }
}
