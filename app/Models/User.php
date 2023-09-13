<?php

namespace App\Models;

use App\Events\ActionComplete;
use App\Notifications\SendCode;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Overtrue\LaravelFavorite\Traits\Favoriter;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use Favoriter;
    use Fileable;

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
        'address',
        'has_pending_updates',
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
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('id', $value)
            ->orWhere('username', $value)
            ->firstOrFail();
    }

    public function registerFileable()
    {
        $this->fileableLoader('image', 'avatar', 'default', true, true);
    }

    public static function registerEvents()
    {
        static::creating(function ($user) {
            if (! $user->username) {
                $u = str($user->email
                    ? str($user->email)->explode('@')->first()
                    : str($user->name ?? $user->firstname)->slug())->replace('.', '_')->toString();

                $user->username = User::where('username', $u)->exists() ? $u.rand(100, 999) : $u;
            }
        });

        static::deleting(function (User $model) {
            $model->subscriptions()->delete();
            $model->transactions()->delete();
            $model->cooperatives()->delete();
            $model->dispatches()->delete();
            $model->feedbacks()->delete();
            $model->savings()->delete();
            $model->orders()->delete();
            $model->wallet()->delete();
        });
    }

    /**
     * Get the URL to the fruit bay category's photo.
     *
     * @return string
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->media_file,
        );
    }

    /**
     * Get the URL to the user's photo.
     *
     * @return string
     */
    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->media_file,
        );
    }

    public function fullname(): Attribute
    {
        $name = isset($this->firstname) ? ucfirst($this->firstname) : '';
        $name .= isset($this->lastname) ? ' '.ucfirst($this->lastname) : '';
        $name .= ! isset($this->lastname) && ! isset($this->firstname) && isset($this->username) ? ucfirst($this->username) : '';

        return new Attribute(
            get: fn () => $name,
        );
    }

    /**
     * Get the user's affiliates (users they referred).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function affiliates(): HasMany
    {
        return $this->hasMany(User::class, 'referrer_id');
    }

    /**
     * Get the user's referrer
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Get all of the cooperatives the User is a member of
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cooperatives(): HasMany
    {
        return $this->hasMany(Cooperative::class);
    }

    public function hasRequestedToJoin($model)
    {
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
     * Get the user's meals timetable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function mealTimetable(): BelongsToMany
    {
        return $this->belongsToMany(MealPlan::class, 'meal_timetables', 'user_id', 'meal_plan_id')
            ->using(MealTimetable::class)
            ->withPivot('date')
            ->withTimestamps();
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
        return $this->hasMany(Subscription::class)->whereDoesntHave('cooperative');
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
        // Sum wallet credit transactions and subtract wallet debit transactions
        return new Attribute(
            get: fn () => (float) $this->wallet()
                ->selectRaw('sum(case when type = "credit" then amount else -amount end) as balance')
                ->value('balance'),
        );
    }

    public function refreshUi($updates = [
        'user' => true,
        'savings' => true,
        'subscriptions' => true,
        'transactions' => true,
        'settings' => true,
        'charts' => true,
        'wallet' => true,
        'orders' => true,
        'auth' => true,
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
