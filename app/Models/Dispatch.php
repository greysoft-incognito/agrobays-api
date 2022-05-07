<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class Dispatch extends Model
{
    use HasFactory, Notifiable;

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
        'last_location',
        'item_type',
        'type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_location' => 'array',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'last_location' => '{"lon": "", "lat": ""}',
    ];

    /**
     * Get the dispatch's dispatchable model (probably an order or a food bag).
     */
    public function dispatchable()
    {
        return $this->morphTo();
    }

    /**
     * Interact with the dispatch's last location.
     *
     * @return  \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function lastLocation(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (!$value || !is_string($value) || is_null($val = json_decode($value))) {
                    return [
                        "lon" => "",
                        "lat" => "",
                    ];
                }
                return $val;
            },
            set: fn($value) => ["last_location" => json_encode([
                "lon" => $value->lon??$value['lon']??'',
                "lat" => $value->lat??$value['lat']??$value??'',
            ])]
        );
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
     * Get the user that owns the Dispatch (Handler/Rider)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
