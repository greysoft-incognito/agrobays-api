<?php

namespace App\Http\Controllers\v2;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlanCollection;
use App\Http\Resources\PlanResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\FoodBag;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $plans = Plan::all();

        return (new PlanCollection($plans))->additional([
            'message' => $plans->isEmpty() ? 'There are no saving plans for now.' : HttpStatus::message(HttpStatus::OK),
            'status' => $plans->isEmpty() ? 'info' : 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Subscribe to a new saving's plan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'cooperative_id' => 'nullable|exists:cooperatives,id',
            'plan_id' => 'required|exists:plans,id',
        ], [
            'cooperative_id.exists' => 'The selected cooperative does not exist.',
            'plan_id.exists' => 'The selected plan does not exist.',
        ]);

        /** @var \App\Models\User */
        $user = $request->user();

        /** @var \App\Models\Plan */
        $plan = Plan::find($request->plan_id);

        if ($request->cooperative_id) {
            /** @var \App\Models\Cooperative */
            $cooperative = Cooperative::find($request->cooperative_id);

            $checkQuery = $cooperative->subscriptions()->whereDoesntHave('allSavings', function (Builder $query) {
                $query->where('status', 'complete');
            });

            $this->authorize('manage', [$cooperative, 'manage_plans']);

            /** @var \App\Models\Subscription */
            $query = $cooperative->subscriptions();
        } else {
            $checkQuery = $user->subscriptions()->whereDoesntHave('allSavings', function (Builder $query) {
                $query->where('status', 'complete');
            });
            /** @var \App\Models\Subscription */
            $query = $user->subscriptions();
        }

        $planActiveNoSavings = $checkQuery->exists();

        if ($planActiveNoSavings) {
            return $this->responseBuilder([
                'message' => 'You need to make at least one savings on all your existing subscriptions before you can subscribe to another plan.',
                'status' => 'info',
                'response_code' => HttpStatus::UNPROCESSABLE_ENTITY,
            ]);
        } elseif ($query->where([['plan_id', '=', $plan->id], ['status', '=', 'active']])->exists()) {
            return $this->responseBuilder([
                'message' => 'You are already active on this plan, but you can subscribe to another plan.',
                'status' => 'info',
                'response_code' => HttpStatus::UNPROCESSABLE_ENTITY,
            ]);
        }

        // Since there can only be one pending plan at a time, we need to delete the current pending plan
        if ($request->cooperative_id) {
            $cooperative->subscriptions()->where('status', 'pending')->delete();
        } else {
            $user->subscriptions()->where('status', 'pending')->delete();
        }

        // Create the new plan
        $subscription = new Subscription();
        $subscription->user_id = $user->id;
        $subscription->plan_id = $plan->id;
        $subscription->food_bag_id = $plan->bags()->first()->id ?? FoodBag::first()->id;

        if ($request->cooperative_id) {
            $subscription->cooperative_id = $request->cooperative_id;
        }

        $subscription->save();

        return  (new SubscriptionResource($subscription))
            ->additional([
                'message' => __('You have successfully subscribed to the :0:1', [
                    $plan->title,
                    $request->cooperative_id ? ' for ' . $subscription->cooperative->name . '.' : null,
                ]),
                'status' => 'success',
                'response_code' => HttpStatus::CREATED,
            ])->response()->setStatusCode(HttpStatus::CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Plan $plan)
    {
        return (new PlanResource($plan))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }
}
