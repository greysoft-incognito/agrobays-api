<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
     * Route notifications for the mail channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array|string
     */
    public function routeNotificationForMail($notification)
    {
        // Return email address and name...
        return [$this->dispatchable->user->email => $this->dispatchable->user->firstname];
    }
}