<?php

namespace App\Models;

use App\Events\ActionComplete;
use App\Notifications\SendCode;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use libphonenumber\NumberParseException as LibphonenumberNumberParseException;
use Propaganistas\LaravelPhone\Exceptions\NumberParseException;
use Propaganistas\LaravelPhone\PhoneNumber;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'phone',
        'username',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_refreshed' => 'datetime',
        'last_attempt' => 'datetime',
        'last_seen' => 'datetime',
        'address' => 'collection',
        'country' => 'collection',
        'state' => 'collection',
        'city' => 'collection',
        'bank' => 'collection',
        'data' => 'collection',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'wallet_balance',
        'subscription',
        'permissions',
        'image_url',
        'fullname',
        'address',
        'country',
        'city',
        'state',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'address' => '{"shipping": "", "home": ""}',
        'country' => '{"name": "", "iso2": "", "emoji": ""}',
        'state' => '{"name": "", "iso2": ""}',
        'city' => '{"name": ""}',
        'bank' => '{"bank": "", "nuban":"", "account_name":""}',
        'data' => '{"settings": {"notifications": {"email": true, "sms": true, "push": true}}}',
    ];

    /**
     * Interact with the user's address.
     *
     * @return  \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function address(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (!$value || !is_string($value) || is_null($val = json_decode($value))) {
                    return [
                        'shipping' => '',
                        'home' => '',
                    ];
                }

                return $val;
            },
            set: fn ($value) => ['address' => json_encode([
                'shipping' => $value->shipping ?? $value['shipping'] ?? '',
                'home' => $value->home ?? $value['home'] ?? '',
            ])]
        );
    }

    /**
     * Interact with the user's country.
     *
     * @return  \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function country(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (!$value || !is_string($value) || is_null($val = json_decode($value))) {
                    return [
                        'name' => '',
                        'iso2' => '',
                        'emoji' => '',
                    ];
                }

                return $val;
            },
            set: fn ($value) => ['country' => json_encode([
                'name' => $value->name ?? $value['name'] ?? '',
                'iso2' => $value->iso2 ?? $value['iso2'] ?? '',
                'emoji' => $value->emoji ?? $value['emoji'] ?? '',
            ])]
        );
    }

    /**
     * Interact with the user's state.
     *
     * @return  \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function state(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (!$value || !is_string($value) || is_null($val = json_decode($value))) {
                    return [
                        'name' => '',
                        'iso2' => '',
                    ];
                }

                return $val;
            },
            set: fn ($value) => ['state' => json_encode([
                'name' => $value->name ?? $value['name'] ?? '',
                'iso2' => $value->iso2 ?? $value['iso2'] ?? '',
            ])]
        );
    }

    /**
     * Interact with the user's city.
     *
     * @return  \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function city(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (!$value || !is_string($value) || is_null($val = json_decode($value))) {
                    return [
                        'name' => '',
                    ];
                }

                return $val;
            },
            set: fn ($value) => ['city' => json_encode([
                'name' => $value->name ?? $value['name'] ?? $value ?? '',
            ])]
        );
    }

    /**
     * Interact with the user's phone.
     *
     * @return  \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function phone(): Attribute
    {
        $cIso2 = 'NG';

        return Attribute::make(
            get: function ($value) use ($cIso2) {
                // return $value;
                try {
                    if (!empty($this->country->iso2 ?? $this->country['iso2']) && $value) {
                        return (string) PhoneNumber::make($value, $this->country->iso2 ?? $this->country['iso2'])->formatE164();
                    }

                    return $value ? (string) PhoneNumber::make($value, $cIso2)->formatE164() : $value;
                } catch (NumberParseException | LibphonenumberNumberParseException $th) {
                    return $value;
                }
            },
            set: function ($value) use ($cIso2) {
                if (($ipInpfo = \Illuminate\Support\Facades\Http::get('ipinfo.io/' . request()->ip() . '?token=' . config('settings.ipinfo_access_token')))->status() === 200) {
                    $cIso2 = $ipInpfo->json('country') ?? $cIso2;
                }
                $value = str_ireplace('-', '', $value);
                if (!empty($this->country->iso2 ?? $this->country['iso2']) && $value) {
                    return ['phone' => (string) PhoneNumber::make($value, $this->country->iso2 ?? $this->country['iso2'])->formatE164()];
                }

                return ['phone' => $value ? (string) PhoneNumber::make($value, $cIso2)->formatE164() : $value];
            }
        );
    }

    /**
     * Get the URL to the fruit bay category's photo.
     *
     * @return string
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => ($attributes['image']
                ? img($attributes['image'], 'avatar', 'medium-square')
                : asset('media/default_avatar.png')),
        );
    }

    public function fullname(): Attribute
    {
        $name = isset($this->firstname) ? ucfirst($this->firstname) : '';
        $name .= isset($this->lastname) ? ' ' . ucfirst($this->lastname) : '';
        $name .= !isset($this->lastname) && !isset($this->firstname) && isset($this->username) ? ucfirst($this->username) : '';

        return new Attribute(
            get: fn () => $name,
        );
    }

    /**
     * Interact with the user's permissions.
     *
     * @return  \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function permissions(): Attribute
    {
        return Attribute::make(
            get: fn () => \Permission::getPermissions($this),
        );
    }

    /**
     * Get all of the transactions for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get all of the dispatches for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dispatches(): HasMany
    {
        return $this->hasMany(Dispatch::class);
    }

    /**
     * Get all of the feedbacks for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    /**
     * Get all of the savings for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function savings(): HasMany
    {
        return $this->hasMany(Saving::class);
    }

    /**
     * Route notifications for the mail channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array|string
     */
    public function routeNotificationForMail()
    {
        // Return email address and name...
        return [$this->email => $this->firstname];
    }

    /**
     * Route notifications for the twillio channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array|string
     */
    public function routeNotificationForTwilio()
    {
        return $this->phone;
    }

    public function sendEmailVerificationNotification()
    {
        $this->last_attempt = now();
        $this->email_verify_code = mt_rand(100000, 999999);
        $this->save();

        $this->notify(new SendCode($this->email_verify_code, 'verify'));
    }

    public function sendPhoneVerificationNotification()
    {
        $this->last_attempt = now();
        $this->phone_verify_code = mt_rand(100000, 999999);
        $this->save();

        $this->notify(new SendCode($this->phone_verify_code, 'verify-phone'));
    }

    public function hasVerifiedPhone()
    {
        return $this->phone_verified_at !== null;
    }

    public function markEmailAsVerified()
    {
        $this->last_attempt = null;
        $this->email_verify_code = null;
        $this->email_verified_at = now();
        $this->save();

        if ($this->wasChanged('email_verified_at')) {
            return true;
        }

        return false;
    }

    public function markPhoneAsVerified()
    {
        $this->last_attempt = null;
        $this->phone_verify_code = null;
        $this->phone_verified_at = now();
        $this->save();

        if ($this->wasChanged('phone_verified_at')) {
            return true;
        }

        return false;
    }

    /**
     * Return the user's online status
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function onlinestatus(): Attribute
    {
        return new Attribute(
            get: fn () => ($this->last_seen ?? now()->subMinutes(6))->gt(now()->subMinutes(5)) ? 'online' : 'offline',
        );
    }

    public function scopeIsOnline($query, $is_online = true)
    {
        if ($is_online) {
            // Check if the user's last last_seen was less than 5 minutes ago
            $query->where('last_seen', '>=', now()->subMinutes(5));
        } else {
            // Check if the user's last last_seen was more than 5 minutes ago
            $query->where('last_seen', '<', now()->subMinutes(5));
        }
    }

    /**
     * Get the subscription associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    // public function subscription(): HasOne
    // {
    //     return $this->hasOne(Subscription::class)->where('status', '!=', 'complete');
    // }

    /**
     * Get the user's most recent subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function subscription(): Attribute
    {
        return new Attribute(
            get: fn () => $this->subscriptions()->where('status', '!=', 'complete')->latest()->first(),
        );
    }

    /**
     * Get all of the subscriptions for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get all of the orders for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all of the wallet transactions for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function wallet(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function walletBalance(): Attribute
    {
        $credit = Wallet::where([['type', 'credit'], ['user_id', auth()->id()]]);
        $debit = Wallet::where([['type', 'debit'], ['user_id', auth()->id()]]);

        return new Attribute(
            get: fn () => $credit->sum('amount') - $debit->sum('amount'),
        );
    }

    public function refresh($updates = [
        'user' => true,
        'savings' => true,
        'subscriptions' => true,
        'transactions' => true,
        'settings' => true,
        'charts' => true,
        'wallet' => true,
        'orders' => true,
        'auth' => true
    ]): void
    {
        broadcast(new ActionComplete([
            'type' => 'refresh',
            'mode' => 'automatic',
            'data' => $this,
            'updated' => $updates,
            'created_at' => now(),
        ], $this));
    }
}