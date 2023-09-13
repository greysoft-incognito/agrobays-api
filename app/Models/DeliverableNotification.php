<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliverableNotification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'draft',
        'user_id',
        'subject',
        'message',
        'count_sent',
        'count_failed',
        'count_pending',
        'recipient_ids',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'draft' => 'boolean',
        'count_sent' => 'integer',
        'count_failed' => 'integer',
        'count_pending' => 'integer',
        'recipient_ids' => 'collection',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'type' => 'mail',
        'draft' => false,
        'count_sent' => 0,
        'count_failed' => 0,
        'count_pending' => 0,
        'recipient_ids' => "['all']",
    ];

    /**
     * Determine if all recipients have recieved.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function sent(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->count_sent >= $this->recipients->count()
        );
    }

    /**
     * Get all the recipient models
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function recipients(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->recipient_ids) {
                    return collect([]);
                }

                $users = collect([]);
                if ($this->recipient_ids->contains('all')) {
                    $users = $users->concat(User::query()->get());
                }

                if ($this->recipient_ids->contains('savers')) {
                    $users = $users->concat(User::whereHas('savings')->get());
                }

                if ($this->recipient_ids->contains('!savers')) {
                    $users = $users->concat(User::whereDoesntHave('savings')->get());
                }

                if ($this->recipient_ids->contains('buyers')) {
                    $users = $users->concat(User::whereHas('orders')->get());
                }

                if ($this->recipient_ids->contains('!buyers')) {
                    $users = $users->concat(User::whereDoesntHave('orders')->get());
                }

                return $users->concat(User::whereIn('id', $this->recipient_ids)->get())->unique('id');
            }
        );
    }

    /**
     * Get the user that created the DeliverableNotification
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
