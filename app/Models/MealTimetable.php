<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class MealTimetable extends Pivot
{
    use HasFactory;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $table = 'meal_timetables';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date:Y-m-d',
        'meal_plan_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Get the plan that owns the MealTimetable
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class, 'meal_plan_id');
    }

    /**
     * Get the user that owns the MealTimetable
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope a query to only include meal plans for a given user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopeForUser($query, $user)
    {
        return $query->where('user_id', $user->id);
    }
}
