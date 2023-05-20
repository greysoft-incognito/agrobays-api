<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MealPlanController as ControllersMealPlanController;
use App\Http\Resources\MealPlanResource;
use App\Models\MealPlan;
use Illuminate\Http\Request;

class MealPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return (new ControllersMealPlanController)->index($request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string',
            'category' => 'required|string',
            'description' => 'required|string',
            'calories' => 'required|integer',
            'protein' => 'required|integer',
            'fat' => 'required|integer',
            'carbohydrates' => 'required|integer',
            'image' => 'sometimes|image',
        ]);

        $meal_plan = new MealPlan();
        $meal_plan->name = $request->name;
        $meal_plan->category = $request->category;
        $meal_plan->description = $request->description;
        $meal_plan->calories = $request->calories;
        $meal_plan->protein = $request->protein;
        $meal_plan->fat = $request->fat;
        $meal_plan->carbohydrates = $request->carbohydrates;
        $meal_plan->save();

        return (new MealPlanResource($meal_plan))->additional([
            'message' => 'Meal plan created successfully',
            'status' => 'success',
            'response_code' => 201,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MealPlan  $meal_plan
     * @return \Illuminate\Http\Response
     */
    public function show(MealPlan $meal_plan)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MealPlan  $meal_plan
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MealPlan $meal_plan)
    {
        $this->validate($request, [
            'name' => 'required|string',
            'category' => 'required|string',
            'description' => 'required|string',
            'calories' => 'required|integer',
            'protein' => 'required|integer',
            'fat' => 'required|integer',
            'carbohydrates' => 'required|integer',
            'image' => 'sometimes|image',
        ]);

        $meal_plan->name = $request->name;
        $meal_plan->category = $request->category;
        $meal_plan->description = $request->description;
        $meal_plan->calories = $request->calories;
        $meal_plan->protein = $request->protein;
        $meal_plan->fat = $request->fat;
        $meal_plan->carbohydrates = $request->carbohydrates;
        $meal_plan->save();

        return (new MealPlanResource($meal_plan))->additional([
            'message' => __('Meal plan (:0) updated successfully', [$meal_plan->name]),
            'status' => 'success',
            'response_code' => 201,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|int  $plan
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $plan)
    {
        \Gate::authorize('usable', 'mealplans.manage');

        $count = false;

        if ($request->items) {
            $count = collect($request->items)->map(function ($id) use ($request) {
                $item = MealPlan::find($id);
                if ($item) {
                    $item->favorites()->delete();
                    $item->timetables()->delete();
                    $item->delete();
                    return $item->name;
                }

                return false;
            })->filter(fn ($i) => $i !== false);
            $count = $count->count() > 1 ? $count->count() : $count->first();
        } else {
            $item = MealPlan::find($plan);
            if ($item) {
                $item->favorites()->delete();
                $item->timetables()->delete();
                $item->delete();
                $count = $item->name;
            }
        }

        if ($count) {
            return $this->buildResponse([
                'message' => __(":0 been deleted.", [is_numeric($count) ? "{$count} items have" : "{$count} has"]),
                'status' => 'success',
                'response_code' => 200,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested item(s) no longer exists.',
            'status' => 'error',
            'response_code' => 404,
        ]);
    }
}