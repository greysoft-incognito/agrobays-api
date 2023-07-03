<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class Cooperative extends Model
{
    use HasFactory;
    use Fileable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'lga',
        'name',
        'email',
        'phone',
        'about',
        'state',
        'user_id',
        'website',
        'address',
        'classification',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'collection',
        'meta' => 'array',
        'verified' => 'boolean',
        'is_active' => 'boolean',
        'publishing' => 'collection',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'settings' => '{"public":true, "require_join_approval": true, "auto_approve_foodbags": false}',
        'meta' => '{}',
        'classification' => 'agriculture',
        'verified' => false,
        'is_active' => false,
        'publishing' => '{"restricted": false, "hidden": false}',
    ];

    /**
     * All of the allowed classifications
     *
     * @var array<string>
     */
    public static $classifications = [
        'agriculture',
        'marketing',
        'consumer',
        'construction',
        'education',
        'energy',
        'financial',
        'healthcare',
        'hospitality',
        'information',
        'legal',
        'manufacturing',
        'media',
        'non_profit',
        'professional',
        'real_estate',
        'transportation',
        'other',
    ];

    public $permissions = [
        'manage_members',
        'manage_admins',
        'manage_plans',
        'manage_settings',
        'update_profile',
        'delete_cooperative',
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
            ->orWhere('slug', $value)
            ->firstOrFail();
    }

    public function registerFileable()
    {
        $this->fileableLoader([
            'image' => 'avatar',
            'cover' => 'banner',
        ], 'default', true);
    }

    public static function registerEvents()
    {
        static::creating(function (Cooperative $org) {
            $slug = str($org->name)->slug();
            $org->slug = (string) Cooperative::whereSlug($slug)->exists() ? $slug->append(rand(1000, 9000)) : $slug;
        });

        static::deleting(function (Cooperative $org) {
            $org->members()->delete();
            $org->foodbags()->delete();
            $org->subscriptions()->delete();
        });
    }

    /**
     * Approve a member's request to join the organization
     */
    public function approveRequest(User $user, bool $approve = true)
    {
        $query = $this->members()->where('user_id', $user->id);

        if ($approve) {
            return $query->update(['accepted' => true, 'requesting' => false]);
        }

        return $query->delete();
    }

    /**
     * Get all of the foodbags for the Cooperative
     */
    public function foodbags(): HasMany
    {
        return $this->hasMany(CooperativeMembersFoodbag::class, 'cooperative_id', 'id');
    }

    public function hasMember($userId)
    {
        return $this->isFollowedBy($userId);
    }

    public function isAvailable(): Attribute
    {
        return new Attribute(
            get: function () {
                if (config('settings.require_org_approval', false) === false) {
                    $this->is_active = true;
                }

                $role = auth()->user()?->role;
                $user_id = auth()->id();

                if (! $this->is_active && $role !== 'admin' && $role !== 'manager' && $this->user_id != $user_id) {
                    return false;
                }

                return true;
            },
        );
    }

    /**
     * Get the organization's members.
     */
    public function members(): MorphMany
    {
        return $this->morphMany(ModelMember::class, 'model');
    }

    /**
     * Get the abilities of the authenticated member on the organization.
     *
     * @return Attribute
     */
    public function abilities(): Attribute
    {
        return new Attribute(
            get: function () {
                $manager = $this->members()->isAccepted(true)->forUser(auth('sanctum')->user())->first();

                if ($manager) {
                    return $manager->abilities?->contains('all') ? $this->permissions : $manager->abilities;
                }

                return [];
            },
        );
    }

    /**
     * Determine wether joining organizations should require admin approvals
     *
     * @return bool
     */
    public function needsToApproveFollowRequests(): bool
    {
        return (bool) isset($this->settings['require_join_approval']) && $this->settings['require_join_approval'] === true;
    }

    /**
     * Scope a query to only include active feeds.
     *
     * Inactive organization will only be visible to it's owners.
     */
    public function scopeActive($query, $active = true, User $user = null)
    {
        if (config('settings.require_org_approval', false) === false) {
            if ($user) {
                return $query->where(function ($query) use ($user, $active) {
                    $query->whereIsActive($active);
                    $query->orWhere('user_id', $user->id);
                });
            } else {
                $query->whereIsActive($active);
            }
        }
    }

    /**
     * Scope a query to only include restricted organization.
     *
     * Restricted organization will only be visible to admins.
     */
    public function scopeRestricted($query, $restricted = true)
    {
        $query->whereJsonContains('publishing->restricted', $restricted);
        if (! $restricted) {
            $query->orWhereNull('publishing->restricted');
        }
    }

    public function scopeFiltered($query, $filters)
    {
        if (! is_array($filters)) {
            return;
        }

        $query->whereRaw('1 = 1');

        foreach ($filters as $filter) {
            if (in_array($filter, ['old'])) {
                $query->where('created_at', '<', now()->subWeek());
            }

            if (in_array($filter, ['new'])) {
                $query->where('created_at', '>=', now()->subWeek());
            }

            if (in_array($filter, ['active', 'inactive'])) {
                $query->active($filter == 'active');
            }

            if (in_array($filter, ['verified', 'unverified'])) {
                $query->whereVerified($filter == 'verified');
            }

            if (in_array($filter, ['hidden', 'unhidden'])) {
                $query->hidden($filter == 'hidden');
            }

            if (in_array($filter, ['restricted', 'unrestricted'])) {
                $query->restricted($filter == 'restricted');
            }

            if (in_array($filter, ['public', 'private', 'protected'])) {
                $query->whereJsonContains('privacy', $filter);
            }

            if (str($filter)->contains('user-')) {
                $filter = str($filter)->replace('user-', '');

                if (in_array($filter, ['old'])) {
                    $query->where('created_at', '<', now()->subWeek());
                }

                if (in_array($filter, ['new'])) {
                    $query->where('created_at', '>=', now()->subWeek());
                }

                if (in_array($filter, ['user', 'admin'])) {
                    $query->whereHas('user', function ($q) use ($filter) {
                        if ($filter == 'admin') {
                            $q->whereIn('role', ['admin', 'manager']);
                        } else {
                            $q->whereNotIn('role', ['admin', 'manager']);
                        }
                    });
                }

                if (in_array($filter, ['verified', 'unverified'])) {
                    $query->where('verified', $filter === 'verified');
                }
            }
        }
    }

    /**
     * Scope a query to only include hidden organization.
     *
     * When an organization is hidden it will only be visible to admins and it's owners.
     */
    public function scopeHidden($query, $hidden = true, User $user = null)
    {
        if ($user && $user->is_admin) {
            return;
        }

        if ($user) {
            $query->where(function ($query) use ($user, $hidden) {
                $query->whereJsonContains('publishing->hidden', $hidden);
                $query->orWhere('user_id', $user->id);
                if (! $hidden) {
                    $query->orWhereNull('publishing->hidden');
                }
            });
        } else {
            $query->whereJsonContains('publishing->hidden', $hidden);
            if (! $hidden) {
                $query->orWhereNull('publishing->hidden');
            }
        }
    }

    /**
     * Get the cooperative's most recent subscription
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
     * Get all of the subscriptions for the cooperative.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'cooperative_id', 'id');
    }

    /**
     * Get the user that created the cooperative.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all of the wallet transactions for the Cooperative
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function wallet(): HasMany
    {
        return $this->hasMany(CooperativeWallet::class);
    }

    public function walletBalance(): Attribute
    {
        // Sum wallet credit transactions and subtract wallet debit transactions
        return new Attribute(
            get: fn () => $this->wallet()
                ->selectRaw('sum(case when type = "credit" then amount else -amount end) as balance')
                ->value('balance'),
        );
    }
}
