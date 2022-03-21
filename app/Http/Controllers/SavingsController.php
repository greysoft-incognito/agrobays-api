<?php

namespace App\Http\Controllers;

use App\Models\FoodBag;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function plans()
    {
        $plans = Plan::all();

        return $this->buildResponse([
            'message' => $plans->isEmpty() ? 'There are no saving plans for now.' : 'OK',
            'status' => $plans->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'plans' => $plans,
        ]);
    }

    /**
     * Get a particular fruit bay plan by it's {id} or {slug}
     *
     * @param Request $request
     * @param string|integer $plan
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function getPlan(Request $request, $plan = 'user')
    {
        if ($plan === 'user')
        {
            $plan = Auth::user()->subscription->plan;
        }
        else
        {
            $plan = Plan::whereId($plan)->orWhere(['slug' => $plan])->first();
        }

        return $this->buildResponse([
            'message' => !$plan ? 'The requested plan is no longer available' : 'OK',
            'status' =>  !$plan ? 'error' : 'success',
            'response_code' => !$plan ? 404 : 200,
            'plan' => $plan,
        ]);
    }

    /**
     * Get the food bags for the selected plan
     * Pass the food_bag id to get a particular food bag
     *
     * @param Request $request
     * @param string|integer $plan
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function getBags(Request $request, $plan = 'user', $id = null)
    {
        if ($plan === 'user')
        {
            $plan = Auth::user()->subscription->plan;
        }
        else
        {
            $plan = Plan::whereId($plan)->orWhere(['slug' => $plan])->first();
        }

        if (!$plan)
        {
            return $this->buildResponse([
                'message' => 'The requested plan no longer exists.',
                'status' => 'error',
                'response_code' => 404,
            ]);
        }
        elseif ($id && !($bag = $plan->bags()->find($id)))
        {
            return $this->buildResponse([
                'message' => 'The requested food bag no longer exists.',
                'status' => 'info',
                'response_code' => 404,
            ]);
        }

        return $this->buildResponse([
            'message' => 'OK',
            'status' => 'success',
            'response_code' => 200,
            $id ? 'bag' : 'bags' => $id ? $bag : $plan->bags,
        ]);
    }

    /**
     * Subscribe to a new saving's plan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $id = null)
    {
        $plan = Plan::find($id);

        if (!$plan)
        {
            return $this->buildResponse([
                'message' => 'The requested plan no longer exists.',
                'status' => 'error',
                'response_code' => 404,
            ]);
        }
        // elseif (($usub = Auth::user()->subscription->days_left??0) < $plan->duration && $usub !== $plan->duration && $usub !== 0)
        // {
        //     return $this->buildResponse([
        //         'message' => "You have a savings pattern on your current plan, you can only switch after you complete the {$plan->duration} day savings for the plan.",
        //         'status' => 'info',
        //         'response_code' => 406,
        //     ]);
        // }
        elseif (Auth::user()->subscriptions()->where('status', 'pending')->exists())
        {
            return $this->buildResponse([
                'message' => 'You need to make at least one savings on all your existing subscriptions before you can subscribe to another plan.',
                'status' => 'info',
                'response_code' => 406,
            ]);
        }
        elseif (Auth::user()->subscriptions()->where([['plan_id', '=', $id], ['status', '=', 'active']])->exists())
        {
            return $this->buildResponse([
                'message' => 'You are already active on this plan, but you can subscribe to another plan.',
                'status' => 'info',
                'response_code' => 406,
            ]);
        }

        // Delete user's current subscription
        Subscription::where('user_id', Auth::id())->where('status', 'pending')->delete();

        // Create the new plan
        $userPlan = new Subscription;
        $userPlan->user_id = Auth::id();
        $userPlan->plan_id = $plan->id;
        $userPlan->food_bag_id = $plan->bags()->first()->id;
        $userPlan->save();

        return $this->buildResponse([
            'message' => "You have successfully activated the {$plan->title} plan",
            'status' => 'success',
            'response_code' => 201,
            'data' => $userPlan,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Plan  $plan
     * @return \Illuminate\Http\Response
     */
    public function edit(Plan $plan)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Plan  $plan
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Plan $plan)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Plan  $plan
     * @return \Illuminate\Http\Response
     */
    public function destroy(Plan $plan)
    {
        //
    }
}
