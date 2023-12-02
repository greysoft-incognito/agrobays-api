<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class Vendor extends Model
{
    use HasFactory;
    use Fileable;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'blocked' => 'boolean',
        'verified' => 'boolean',
        'verification_data' => 'collection',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'id_type' => 'national_passport',
        'blocked' => false,
        'verified' => false,
        'verification_data' => '{"data": {}}',
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
        $this->fileableLoader([
            'image' => 'avatar',
            'id_image' => 'private.docs',
            'reg_image' => 'private.docs',
        ], true);
    }

    /**
     * Get the user that owns the Subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get vendor's catalog
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function catalog(): HasMany
    {
        return $this->hasMany(VendorCatalogItem::class);
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
     * Return the vendor's verification level
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function verificationLevel(): Attribute
    {
        return new Attribute(
            get: fn () => $this->verified
                ? 3 : ($this->image && $this->id_image
                    ? 2 : ($this->image
                        ? 1
                        : 0
                    )
                ),
        );
    }
}
