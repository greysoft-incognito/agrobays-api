<?php

namespace App\Http\Controllers\v2\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionCollection;
use App\Http\Resources\SubscriptionResource;
use App\Models\Cooperative;
use App\Models\Subscription;
use App\Notifications\SubStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $cooperative = null;

        // Set default period
        $period_placeholder = Carbon::now()->subDays(30)->format('Y/m/d') . '-' . Carbon::now()->format('Y/m/d');

        // Get period
        $period = explode('-', urldecode($request->get('period', $period_placeholder)));

        if ($request->cooperative_id) {
            $cooperative = Cooperative::whereId($request->cooperative_id)
                ->orWhere('slug', $request->cooperative_id)
                ->firstOrFail();
            $query = $cooperative->subscriptions()->orderBy('id', 'DESC');
        } else {
            $query = Auth::user()->subscriptions()->orderBy('id', 'DESC');
        }

        // Filter by status
        $query->when(
            $request->status && in_array($request->status, ['active', 'pending', 'complete', 'withdraw', 'closed']),
            function ($query) use ($request) {
                $query->where('status', $request->get('status'));
            }
        );

        // Filter by period
        $query->whereBetween('created_at', [new Carbon($period[0]), new Carbon($period[1])]);

        $subscriptions = $query->paginate($request->get('limit', 15));

        $msg = $subscriptions->isEmpty()
            ? __(':0 not have an active subscription', [
                $cooperative ? $cooperative->name . ' does' : 'You do',
            ])
            : HttpStatus::message(HttpStatus::OK);

        return (new SubscriptionCollection($subscriptions))->additional([
            'message' => $msg,
            'status' => $subscriptions->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'period' => implode(' to ', $period),
            'date_range' => $period,
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        if ($request->cooperative_id) {
            $subscription = Cooperative::whereId($request->cooperative_id)
                ->orWhere('slug', $request->cooperative_id)
                ->firstOrFail()
                ->subscriptions()
                ->findOrfail($id);
        } else {
            $subscription = Auth::user()->subscriptions()->findOrfail($id);
        }

        return (new SubscriptionResource($subscription))
            ->additional([
                'message' => HttpStatus::message(HttpStatus::OK),
                'status' => 'success',
                'response_code' => HttpStatus::OK,
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $subscription = Subscription::find($id);

        if (! $subscription) {
            return $this->responseBuilder([
                'message' => 'You are not subscribed to this plan.',
                'status' => 'error',
                'response_code' => HttpStatus::NOT_FOUND,
            ]);
        }

        if ($subscription->cooperative || $subscription->status === 'closed1') {
            $msg = $subscription->cooperative
                ? 'You cannot terminate an active subscription to a cooperative plan.'
                : 'You already terminated this subscription.';

            return $this->responseBuilder([
                'message' => $msg,
                'status' => 'error',
                'response_code' => HttpStatus::UNPROCESSABLE_ENTITY,
            ]);
        } else {
            $this->authorize('be-owner', [$subscription->user_id]);
        }

        // Check if this is a withdrawal request
        if ($subscription->paid_days > 0) {
            if ($subscription->status === 'closed') {
                return $this->responseBuilder([
                    'message' => 'You already closed this subscription.',
                    'status' => 'error',
                    'response_code' => HttpStatus::UNPROCESSABLE_ENTITY,
                ]);
            } elseif (config('settings.withdraw_to') === 'wallet') {
                $subscription->status = 'closed';
                $subscription->save();
                if ($subscription->saved_amount > 0) {
                    $subscription->user->wallet()->firstOrNew()->topup(
                        'Refunds',
                        $subscription->saved_amount,
                        __('Refunds for :0.', [$subscription->plan->title])
                    );
                    $subscription->user->notify(new SubStatus($subscription, 'closed'));
                }
                $message = __('Your subscription to the :0 has been terminated, :1.', [
                    $subscription->plan->title,
                    'your savings will be withdrawn to your wallet.',
                ]);
            } else {
                $subscription->status = 'withdraw';
                $subscription->save();
                $message = __('You have successfully terminated your saving for the :0, your withdrawal request has been logged and will be proccessed along with the next batch.', [$subscription->plan->title]);
            }
        } else {
            $subscription->delete();
            $message = __('Your subscription to the :0 has been terminated.', [$subscription->plan->title]);
        }

        return (new SubscriptionResource($subscription))
            ->additional([
                'message' => $message,
                'status' => 'success',
                'response_code' => HttpStatus::ACCEPTED,
            ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }
}