<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VendorCatalogItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quantity',
        'vendor_id',
        'catalogable_id',
        'catalogable_type',
    ];

    /**
     * Get catalogable
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function catalogable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the vendor that owns the catalog item
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
