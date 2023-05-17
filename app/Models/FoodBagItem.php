<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodBagItem extends Model
{
    use HasFactory;

    /**
     * Get the foodbag that owns the food
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function foodbag(): BelongsTo
    {
        return $this->belongsTo(FoodBag::class, 'food_bag_id');
    }

    /**
     * Get the food that owns the foodbag
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class, 'food_id');
    }
}
