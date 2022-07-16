<?php

namespace App\Http\Controllers;

use App\Models\FoodBag;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\CarbonImmutable as Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavingsController extends Controller
{
    /**
     * Display a listing of the user's savings
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Support\Facades\Auth  $auth
     * @param  int  $sub_id
     * @param  int  $limit
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Auth $auth, $sub_id = null, $limit = 1, $status = null)
    {
        $save = $auth::user()->savings()->orderBy('id', 'DESC');

        if (is_numeric($limit) && $limit > 0) {
            $save->limit($limit);
        }

        if ($status !== null && in_array($status, ['rejected', 'pending', 'complete'])) {
            $save->where('status', $status);
        }

        if (is_numeric($sub_id)) {
            $save->where('subscription_id', $sub_id);
        }

        if ($p = $request->query('period')) {
            $period = explode('-', $p);
            $from = new Carbon($period[0]);
            $to = new Carbon($period[1]);
            $save->whereBetween('created_at', [$from, $to]);
        }

        $savings = $save->get();

        if ($savings->isNotEmpty()) {
            $savings->each(function ($tr) {
                $tr->date = $tr->created_at->format('Y-m-d H:i');
                $tr->title = $tr->subscription->plan->title;
            });
        }

        $msg = $savings->isEmpty() ? 'You have not made any savings.' : 'OK';
        $_period = $savings->isNotEmpty()
            ? ($savings->last()->created_at->format('Y/m/d').'-'.$savings->first()->created_at->format('Y/m/d'))
            : '';

        return $this->buildResponse([
            'message' => $msg,
            'status' =>  $savings->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'savings' => $savings ?? [],
            'period' => $p ? urldecode($p) : $_period,
        ]);
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
     * @param  Request  $request
     * @param  string|int  $plan
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function getPlan(Request $request, $plan = 'user')
    {
        if ($plan === 'user') {
            $plan = Auth::user()->subscription->plan;
        } else {
            $plan = Plan::whereId($plan)->orWhere(['slug' => $plan])->first();
        }

        return $this->buildResponse([
            'message' => ! $plan ? 'The requested plan is no longer available' : 'OK',
            'status' =>  ! $plan ? 'error' : 'success',
            'response_code' => ! $plan ? 404 : 200,
            'plan' => $plan,
        ]);
    }

    /**
     * Get the food bags for the selected plan
     * Pass the food_bag id to get a particular food bag
     *
     * @param  Request  $request
     * @param  string|int  $plan
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function getBags(Request $request, $plan = 'user', $id = null)
    {
        if ($plan === 'user') {
            $plan = Auth::user()->subscription->plan;
        } else {
            $plan = Plan::whereId($plan)->orWhere(['slug' => $plan])->first();
        }

        if (! $plan) {
            return $this->buildResponse([
                'message' => 'The requested plan no longer exists.',
                'status' => 'error',
                'response_code' => 404,
            ]);
        } elseif ($id && ! ($bag = $plan->bags()->find($id))) {
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

        $planActiveNoSavings = Subscription::where('user_id', Auth::id())->whereDoesntHave('allSavings', function (Builder $query) {
            $query->where('status', 'complete');
        })->exists();

        if (! $plan) {
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
        // }
        //     ]);
        elseif ($planActiveNoSavings) {
            return $this->buildResponse([
                'message' => 'You need to make at least one savings on all your existing subscriptions before you can subscribe to another plan.',
                'status' => 'info',
                'response_code' => 406,
            ]);
        } elseif (Auth::user()->subscriptions()->where([['plan_id', '=', $id], ['status', '=', 'active']])->exists()) {
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
        $userPlan->food_bag_id = $plan->bags()->first()->id ?? FoodBag::first()->id;
        $userPlan->save();

        return $this->buildResponse([
            'message' => "You have successfully activated the {$plan->title}",
            'status' => 'success',
            'response_code' => 201,
            'data' => $userPlan,
        ]);
    }

    /**
     * Subscribe to a new saving's plan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function terminate(Request $request, $id = null)
    {
        $userPlan = Subscription::find($request->plan_id);

        if (! $userPlan) {
            return $this->buildResponse([
                'message' => 'You do not have an active subscription.',
                'status' => 'error',
                'response_code' => 404,
            ]);
        }

        $userPlan->status = 'withdraw';
        $userPlan->save();

        return $this->buildResponse([
            'message' => "You have successfully terminated your saving for the {$userPlan->plan->title}, your withdrawal request has been logged and will be proccessed along with the next batch.",
            'status' => 'success',
            'response_code' => 201,
            'data' => $userPlan
        ]);
    }
}