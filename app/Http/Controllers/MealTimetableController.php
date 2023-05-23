<?php

namespace App\Http\Controllers;

use App\Http\Resources\AutoTimetableCollection;
use App\Http\Resources\MealPlanResource;
use App\Models\MealPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MealTimetableController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->boolean('auto') === true) {
            return $this->autoTimetable($request);
        }

        $user = $request->user();

        $query = $user->mealTimetable();

        if ($request->has('period')) {
            if ($request->period === 'today') {
                $query->where('date', Carbon::today());
            } elseif ($request->period === 'week') {
                $query->whereBetween('date', [
                    Carbon::today(),
                    Carbon::today()->addDays(7),
                ]);
            } elseif ($request->period === 'month') {
                $query->whereBetween('date', [
                    Carbon::today(),
                    Carbon::today()->addDays(30),
                ]);
            } elseif ($request->period === 'year') {
                $query->whereBetween('date', [
                    Carbon::today(),
                    Carbon::today()->addDays(365),
                ]);
            }
        }

        $prepared = $query->orderByPivot('date', 'asc')->get();

        $days = $prepared->groupBy(function ($item) {
            return Carbon::parse($item->pivot->date)->format('Y-m-d');
        });

        $timetable = [];
        foreach ($days as $date => $day) {
            $timetable[] = [
                'date' => $date,
                'recommendation' => $day->groupBy('category')->map(function ($item) {
                    return MealPlanResource::collection($item);
                }),
            ];
        }

        return (new AutoTimetableCollection($timetable))
            ->additional([
                'message' => 'OK',
                'status' => 'success',
                'response_code' => '200',
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:meal_plans,id',
            'date' => 'required|date',
        ]);

        $user = $request->user();
        $plan = MealPlan::findOrFail($request->plan_id);

        // Check if the user already has this meal plan for the given date
        $existingTimetable = $user->mealTimetable()
            ->where('meal_plan_id', $plan->id)
            ->whereDate('date', $request->date)
            ->exists();

        if ($existingTimetable) {
            return $this->buildResponse([
                'message' => 'You already have this meal plan for the given date',
                'status' => 'error',
                'response_code' => '422',
            ], 422);
        }

        $user->mealTimetable()->attach($plan, [
            'date' => Carbon::parse($request->date)->format('Y-m-d'),
        ]);

        $item = $user->mealTimetable()->whereMealPlanId($plan->id)->first();

        return (new MealPlanResource($item))
            ->additional([
                'message' => __("You have successfully added :0 to your meal timetable for :1", [$plan->item, $request->date]),
                'status' => 'success',
                'response_code' => '201',
            ]);
    }

    /**
     * Generate a 30 day meal plan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function autoTimetable(Request $request)
    {
        $user = $request->user();

        // Generate recommendations for each day of the month
        $mealRecommendations = [];

        $daysInMonth = Carbon::now()->daysInMonth;
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::createFromDate(null, null, $day)->format('Y-m-d');
            $recommendation = (new MealPlan())->generateRecommendation();
            $mealRecommendations[$day] = [
                'date' => $date,
                'recommendation' => $recommendation,
            ];
        }

        return (new AutoTimetableCollection($mealRecommendations))
            ->additional([
                'message' => 'OK',
                'status' => '200',
                'response_code' => '200',
            ])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:meal_plans,id',
            'date' => 'required|date',
        ]);

        $user = $request->user();
        $plan = $user->mealTimetable()->whereMealPlanId($request->plan_id)->firstOrFail();
        $user->mealTimetable()->wherePivot('date', $request->date)->detach($plan);

        return $this->buildResponse([
            'message' => __("You have successfully removed :0 from your meal timetable for :1", [$plan->name, $request->date]),
            'status' => 'success',
            'response_code' => '200',
        ]);
    }
}
