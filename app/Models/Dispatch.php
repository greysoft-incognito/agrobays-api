<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Notifications\Notifiable;

class Dispatch extends Model
{
    use HasFactory;
    use Notifiable;

    protected $statusLabels = [
        'shipped' => "Order Shipped.",
        'confirmed' => "Order confirmed",
        'dispatched' => "Order dispatched.",
        'delivered' => 'Order Delivered.',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'code',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'item_type',
        'type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'placed_at' => 'datetime',
        'extra_data' => 'collection',
        'last_location' => 'collection',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'extra_data' => '{"logs": []}',
        'last_location' => '{"lon": "", "lat": "", "address": ""}',
    ];

    public static function boot(): void
    {
        parent::boot();
        static::creating(function (Dispatch $model) {
            $model->placed_at = $model->dispatchable->created_at ?? now();
        });
    }

    /**
     * Get the dispatch's dispatchable model (probably an order or a food bag).
     */
    public function dispatchable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Interact with the dispatch's type name.
     *
     * @return  \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function itemType(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->dispatchable instanceof Order) {
                    return 'Fruit/Food Order';
                } elseif ($this->dispatchable instanceof Subscription) {
                    return 'Food Bag';
                }

                return 'Package';
            }
        );
    }

    /**
     * Interact with the dispatch's type.
     *
     * @return  \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function type(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->dispatchable instanceof Order) {
                    return 'order';
                } elseif ($this->dispatchable instanceof Subscription) {
                    return 'foodbag';
                }

                return 'package';
            }
        );
    }

    /**
     * Route notifications for the mail channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array|string
     */
    public function routeNotificationForMail($notification)
    {
        // Return email address and name...
        if ($notification->status === 'assigned') {
            return $this->user ? [$this->user->email => $this->user->firstname] : null;
        } else {
            return [$this->dispatchable->user->email => $this->dispatchable->user->firstname];
        }
    }

    /**
     * Route notifications for the twillio channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array|string
     */
    public function routeNotificationForTwilio($notification)
    {
        // Return phone number...
        if ($notification->status === 'assigned') {
            return $this->user ? $this->user->phone : null;
        } else {
            return $this->dispatchable->user->phone;
        }
    }

    /**
     * Get the user that owns the Dispatch (Handler/Rider)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the vendor that owns the Dispatch (Vendor)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function log(string $log, $uid, $save = false)
    {
        $log = str($log)->wordCount() > 1 ? $log : $this->statusLabels[$log] ?? null;

        if (!$log) {
            return $this->extra_data;
        }

        $entry = [
            'log' => $log,
            'date' => now()->toIso8601ZuluString(),
            'user_id' => $uid,
        ];

        if (isset($this->last_location['lat'], $this->last_location['lng'])) {
            $entry['pos'] = [
                'lat' => $this->last_location['lat'],
                'lng' => $this->last_location['lng']
            ];
        }

        $extra_data = $this->extra_data->merge([
            'logs' => [ ...$this->extra_data['logs'] ?? [], $entry ]
        ]);

        if ($save) {
            $this->extra_data = $extra_data;
            $this->save();
        }
        return $extra_data;
    }
}
