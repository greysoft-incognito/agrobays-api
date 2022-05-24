<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Notifications\SendCode;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Propaganistas\LaravelPhone\PhoneNumber;
use Illuminate\Support\Facades\Http;

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
        'last_attempt' => 'datetime',
        'address' => 'array',
        'country' => 'array',
        'state' => 'array',
        'city' => 'array',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
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
                        "shipping" => "",
                        "home" => "",
                    ];
                }
                return $val;
            },
            set: fn($value) => ["address" => json_encode([
                "shipping" => $value->shipping??$value['shipping']??'',
                "home" => $value->home??$value['home']??'',
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
                        "name" => "",
                        "iso2" => "",
                        "emoji" => "",
                    ];
                }
                return $val;
            },
            set: fn($value) => ["country" => json_encode([
                "name" => $value->name??$value['name']??'',
                "iso2" => $value->iso2??$value['iso2']??'',
                "emoji" => $value->emoji??$value['emoji']??'',
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
                        "name" => "",
                        "iso2" => "",
                    ];
                }
                return $val;
            },
            set: fn($value) => ["state" => json_encode([
                "name" => $value->name??$value['name']??'',
                "iso2" => $value->iso2??$value['iso2']??'',
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
                        "name" => "",
                    ];
                }
                return $val;
            },
            set: fn($value) => ["city" => json_encode([
                "name" => $value->name??$value['name']??$value??'',
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
        $cIso2 = "NG";
        if (($ipInpfo = \Illuminate\Support\Facades\Http::get('ipinfo.io/'.request()->ip().'?token='.config('settings.ipinfo_access_token')))->status() === 200) {
            $cIso2 = $ipInpfo->json('country') ?? $cIso2;
        }
        return Attribute::make(
            get: function ($value) use ($cIso2) {
                if (!empty($this->country->iso2??$this->country['iso2'])) {
                    return (string) PhoneNumber::make($value, $this->country->iso2??$this->country['iso2'])->formatE164();
                }
                return $value ? (string) PhoneNumber::make($value, $cIso2)->formatE164() : $value;
            },
            set: function ($value) use ($cIso2) {
                $value = str_ireplace('-', '', $value);
                if (!empty($this->country->iso2??$this->country['iso2'])) {
                    return ['phone' => (string) PhoneNumber::make($value, $this->country->iso2??$this->country['iso2'])->formatE164()];
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
     * Get the subscription associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', '!=', 'complete');
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
}