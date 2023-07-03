<?php

namespace App\Models;

use App\Http\Resources\MealPlanResource;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Overtrue\LaravelFavorite\Traits\Favoriteable;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class MealPlan extends Model
{
    use HasFactory;
    use Favoriteable;
    use Fileable;

    /**
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'calories' => 'float',
        'protein' => 'float',
        'carbohydrates' => 'float',
        'fat' => 'float',
    ];

    public static function registerEvents()
    {
        parent::boot();
        static::creating(function (MealPlan $plan) {
            $slug = str($plan->title)->slug();
            $plan->slug = (string) MealPlan::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });

        static::deleting(function (MealPlan $org) {
            $org->timetable()->delete();
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
     * Get all the user's meals timetable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'meal_timetables')
            ->using(MealTimetable::class)
            ->withPivot('date')
            ->withTimestamps();
    }

    /**
     * Get all of timetable days for the meal plan for a auth user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function timetable(): HasMany
    {
        return $this->hasMany(MealTimetable::class);
    }

    /**
     * Check if the user has saved the meal plan.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isSaved(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->users()->where('user_id', auth()->id())->exists()
        );
    }

    /**
     * Get the last time the user saved the meal plan.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function date(): Attribute
    {
        $user = auth()->user();

        return Attribute::make(
            get: fn () => $this->users()->where('user_id', $user->id)->first()?->pivot->date->format('Y-m-d') ?? null
        );
    }
}
