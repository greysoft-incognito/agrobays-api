<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FruitBayCategory extends Model
{
    use HasFactory;

    public static function boot()
    {
        parent::boot();
        static::creating(function($category) {
            $slug = Str::of($category->title)->slug();
            $category->slug = (string) FruitBayCategory::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });
    }

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
