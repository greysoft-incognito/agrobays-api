<?php

namespace App\Models;

use App\Http\Resources\MealPlanResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Overtrue\LaravelFavorite\Traits\Favoriteable;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class MealPlan extends Model
{
    use HasFactory, Favoriteable, Fileable;

    public static function registerEvents()
    {
        parent::boot();
        static::creating(function (MealPlan $plan) {
            $slug = str($plan->title)->slug();
            $plan->slug = (string) MealPlan::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });
    }

    public function registerFileable()
    {
        $this->fileableLoader('image', 'plans', true);
    }

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

    /**
     * Generate a recommendation for a user on a given date.
     *
     */
    public function generateRecommendation($date = null)
    {
        $mealTypes = ['breakfast', 'lunch', 'dinner'];
        $recommendation = [];

        foreach ($mealTypes as $mealType) {
            $meal = $this->where('category', $mealType)
                ->inRandomOrder()
                ->first();

            if ($meal) {
                $recommendation[$mealType] = [new MealPlanResource($meal)];
            }
        }

        return $recommendation;
    }

    /**
     * Get the user's meals timetable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function timetables(): HasMany
    {
        return $this->hasMany(MealTimetable::class);
    }
}