<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FruitBay extends Model
{
    use HasFactory;


    public static function boot()
    {
        parent::boot();
        static::creating(function($item) {
            $slug = Str::of($item->name)->slug();
            $item->slug = (string) FruitBayCategory::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });
    }

    /**
     * Get the fruit bay item's transaction.
     */
    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'transactable');
    }
}