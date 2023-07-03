<?php

namespace App\Models;

use App\Notifications\MemberInvitation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'model_id',
        'model_type',
        'abilities',
        'accepted',
        'requesting',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'abilities' => 'collection',
        'accepted' => 'boolean',
        'requesting' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'pending',
        'requesting',
    ];

    public function hasPermission(User $user, $model, $permission = 'all')
    {
        // Check if the user has the permission to perform the action on the model
        $model_permission = $this->isAccepted()
                               ->where('model_id', $model->id)
                                ->where('model_type', get_class($model))
                                ->where('user_id', $user->id)->first();
        if ($model_permission) {
            return $model_permission->abilities->contains($permission);
        }

        return false;
    }

    public function model(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the notification associated with the ModelManager
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function notification(): Attribute
    {
        return Attribute::make(
            get: fn () => auth()->user()->notifications()
                ->whereType(MemberInvitation::class)
                ->where('data->request->id', $this->id)->first(),
        );
    }

    /**
     * Get the pending attribute
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function pending(): Attribute
    {
        return new Attribute(get: fn () => ! $this->accepted);
    }

    public function setPermission($abilities)
    {
        $this->abilities = $abilities;
        $this->save();
    }

    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        return $query->where('user_id', $user->id ?? $user);
    }

    public function scopeIsAccepted(Builder $query, $accepted = true): Builder
    {
        return $query->where('accepted', $accepted);
    }

    public function scopeIsRequesting(Builder $query, $requesting = true): Builder
    {
        return $query->where('requesting', $requesting);
    }

    public function scopeHasAbility(Builder $query, $ability = 'all'): Builder
    {
        $query->whereJsonContains('abilities', $ability);

        return $query;
    }

    public function scopeIsCooperativeAdmin(Builder $query): Builder
    {
        $query->whereModelType(Cooperative::class);
        $query->whereJsonLength('abilities', '>', 0);

        return $query;
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
