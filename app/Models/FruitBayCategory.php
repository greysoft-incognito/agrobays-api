<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FruitBayCategory extends Model
{
    use HasFactory;

    /**
     * Get all of the items for the FruitBayCategory
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(FruitBay::class, 'foreign_key', 'local_key');
    }
}