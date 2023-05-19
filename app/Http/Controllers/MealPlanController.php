<?php

namespace App\Http\Controllers;

use App\Http\Resources\AutoTimetableCollection;
use App\Http\Resources\MealPlanCollection;
use App\Http\Resources\MealPlanResource;
use App\Models\MealPlan;
use Carbon\Carbon;
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
        $query = MealPlan::query();

        if ($request->has('search')) {
            $query->where(function ($query) use ($request) {
                $search = $request->search;
                $query->where('name', 'like', "%$search%")
                    ->orWhere('category', 'like', "%$search%")
                    ->orWhereFullText('description', $search);
            });
        }

        if ($request->has('category') && $request->category !== 'all') {
            if ($request->category === 'favorites') {
                $query->whereHas('favorites', function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id);
                });
            } else {
                $query->where('category', $request->category);
            }
        }

        if ($request->has('sort')) {
            $query->orderBy($request->sort, 'asc');
        }

        if ($request->has('calories')) {
            $query->where('calories', '<=', $request->calories);
        }

        if ($request->has('protein')) {
            $query->where('protein', '<=', $request->protein);
        }

        if ($request->has('carbohydrates')) {
            $query->where('carbohydrates', '<=', $request->carbohydrates);
        }

        if ($request->has('fat')) {
            $query->where('fat', '<=', $request->fat);
        }

        $mealPlans = $query->paginate($request->get('limit', 30))->withQueryString();

        return (new MealPlanCollection($mealPlans))
            ->additional([
                'message' => 'OK',
                'status' => '200',
                'response_code' => '200',
            ])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MealPlan  $mealPlan
     * @return \Illuminate\Http\Response
     */
    public function show(MealPlan $mealPlan)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MealPlan  $mealPlan
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MealPlan $mealPlan)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MealPlan  $plan
     * @return \Illuminate\Http\Response
     */
    public function toggleFavorite(Request $request, MealPlan $plan)
    {
        $user = $request->user();

        $user->toggleFavorite($plan);

        $message = $user->hasFavorited($plan)
            ? 'Meal plan added to your favorites'
            : 'Meal plan removed from your favorites';

        return (new MealPlanResource($plan))
            ->additional([
                'message' => $message,
                'status' =>  'success',
                'response_code' => '202',
            ])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MealPlan  $mealPlan
     * @return \Illuminate\Http\Response
     */
    public function destroy(MealPlan $mealPlan)
    {
        //
    }
}
