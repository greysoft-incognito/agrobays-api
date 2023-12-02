<?php

namespace App\Http\Controllers\v2;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\FoodBagCollection;
use App\Http\Resources\FoodBagResource;
use App\Http\Resources\FoodCollection;
use App\Http\Resources\SubscriptionResource;
use App\Models\Cooperative;
use App\Models\FoodBag;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PlanFoodbagController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     * @param  \App\Models\Plan  $plan
     */
    public function index(Request $request, Plan $plan)
    {
        $query = $plan->bags()->getQuery();

        $bags = $query->paginate($request->get('limit', 30));

        return (new FoodBagCollection($bags))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Plan  $plan
     * @param  \App\Models\FoodBag  $foodbag
     * @return \Illuminate\Http\Response
     */
    public function show(Plan $plan, FoodBag $foodbag)
    {
        return (new FoodBagResource($foodbag))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }
}
