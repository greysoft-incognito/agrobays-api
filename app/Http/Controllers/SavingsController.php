<?php

namespace App\Http\Controllers;

use App\Models\FoodBag;
use App\Models\Plan;
use App\Models\Saving;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
    public function getPlan(Request $request, $plan)
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
    public function getBags(Request $request, $plan, $id = null)
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
     * Store a newly created resource in storage.
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
        elseif (($usub = Auth::user()->subscription->days_left??0) < $plan->duration && $usub > 0)
        {
            return $this->buildResponse([
                'message' => "You have a savings pattern on your current plan, you can only switch after you complete the {$plan->duration} day savings for the plan.",
                'status' => 'info',
                'response_code' => 406,
                'test_data' => [($usub = Auth::user()->subscription->days_left??0), $usub]
            ]);
        }
        elseif ((Auth::user()->subscription->plan->id??null) === $plan->id)
        {
            return $this->buildResponse([
                'message' => 'You are already active on this plan, but you can subscribe to another plan.',
                'status' => 'info',
                'response_code' => 406,
            ]);
        }

        // Delete user's current subscription
        Subscription::where('user_id', Auth::id())->where('status', 'pending')->delete();
        Subscription::where('user_id', Auth::id())->where('status', 'active')->update(['status' => 'completed']);
        // Auth::user()->subscription()->delete();

        // Create the new plan
        $userPlan = new Subscription;
        $userPlan->user_id = Auth::id();
        $userPlan->plan_id = $plan->id;
        $userPlan->food_bag_id = $plan->bags()->first()->id;
        $userPlan->save();

        return $this->buildResponse([
            'message' => "You have successfully activated the {$userPlan->plan->title} plan",
            'status' => 'success',
            'response_code' => 201,
            'data' => $userPlan,
        ]);
    }

    /**
     * Update the user's foodbag
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateBag(Request $request, $id = null)
    {
        $bag = FoodBag::find($id);
        $ids = Auth::user()->subscription->plan->bags()->get('id')->values()->toArray();

        if (!$bag || !in_array($bag->id, Collect($ids[0]??[])->filter(fn($k)=>!empty($k))->values()->toArray()))
        {
            return $this->buildResponse([
                'message' => 'The requested food bag no longer exists.',
                'status' => 'error',
                'response_code' => 404,
            ]);
        }

        // Update the user's current subscription's food bag
        $plan = Auth::user()->subscription;
        $plan->food_bag_id = $bag->id;
        $plan->save();

        return $this->buildResponse([
            'message' => "You have successfully activated the {$bag->title} food bag",
            'status' => 'success',
            'response_code' => 201,
            'data' => $bag,
        ]);
    }

    /**
     * List all the authenticated user's subscriptions.
     *
     * @param  \Illuminate\Http\Client\Request  $request
     * @param  String $action
     * @return \Illuminate\Http\Response
     */
    public function subscription(Request $request, $action = null)
    {
        $subscription = Auth::user()->subscription;

        $key = 'subscription';

        if ($action === 'deposit')
        {
            $validator = Validator::make($request->all(), [
                'days' => ['required', 'numeric', 'min:1', 'max:'.$subscription->plan->duration],
            ], [
                'days.min' => 'You have to save for at least 1 day.',
                'days.max' => "You cannot save for more than {$subscription->plan->duration} days."
            ]);

            if ($validator->fails()) {
                return $this->validatorFails($validator);
            }

            if (!$subscription)
            {
                $msg = 'You do not have an active subscription';
            }
            elseif ($subscription->days_left <= 1)
            {
                $msg = 'You have completed the saving circle for this subscription, you would be notified when your food bag is ready for pickup or delivery.';
            }
            else
            {
                $save = new Saving([
                    'user_id' => Auth::id(),
                    'days' => $request->days,
                    'amount' => $subscription->plan->amount / $subscription->plan->duration,
                    'due' => $subscription->plan->amount / $subscription->plan->duration,
                ]);

                $savings = $subscription->savings()->save($save);
                $trans = $savings->transaction();
                $trans->create([
                    'user_id' => Auth::id(),
                    'reference' => Str::random(12),
                    'method' => 'direct',
                    'amount' => $subscription->plan->amount * $request->days,
                    'due' => $subscription->plan->amount * $request->days,
                ]);

                $subscription->status = $subscription->days_left >= 1 ? 'active' : 'complete';
                $subscription->save();

                $key = 'deposit';
                $_amount = money($savings->amount*$request->days);
                $_left = $subscription->days_left;
                $msg = !$subscription
                    ? 'You do not have an active subscription'
                    : "You have successfully made a {$savings->days} day savings of {$_amount} for the {$subscription->plan->title} plan, you now have only {$_left} days left to save up.";
            }
        }
        else
        {
            $subscription->plan??null;
            $msg = !$subscription ? 'You do not have an active subscription' : 'OK';
        }

        return $this->buildResponse([
            'message' => $msg,
            'status' =>  !$subscription ? 'info' : 'success',
            'response_code' => 200,
            $key => $subscription??[],
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