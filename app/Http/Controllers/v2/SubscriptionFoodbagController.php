<?php

namespace App\Http\Controllers\v2;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\FoodCollection;
use App\Http\Resources\SubscriptionResource;
use App\Models\Cooperative;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubscriptionFoodbagController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, string $subscription_id)
    {
        $exists = false;

        $this->validate($request, [
            'foodbag_id' => ['required_without:items', Rule::exists('food_bags', 'id')],
            'cooperative_id' => ['nullable', Rule::exists('cooperatives', 'id')],
            'items' => 'required_without:foodbag_id|array',
            'items.*.id' => ['required', Rule::exists('food', 'id')->where('available', true)],
            'items.*.qty' => 'required|integer|min:1',
        ], [
            'items.*.id' => '[#:position] This food item is no longer available.',
            'items.*.qty' => '[#:position] Quantity needs to be at least 1.',
        ]);

        Validator::validate(['subscription_id' => $subscription_id], [
            'subscription_id' => ['required', Rule::exists('subscriptions', 'id')],
        ], [
            'subscription_id.exists' => 'Subscription not found',
        ]);

        /** @var \App\Models\User|\App\Models\Cooperative $user */
        $user = $request->cooperative_id
            ? Cooperative::find($request->cooperative_id)
            : $request->user();

        abort_if($request->cooperative_id && !$user, 404, 'Cooperative not found.');

        /** @var \App\Models\Subscription $subscription */
        $subscription = $user->subscriptions()->find($subscription_id);

        abort_if(!$subscription, 404, 'Subscription not found.');

        // Update the subscription
        $subscription->custom_foodbag = $request->has('items');
        $subscription->delivery_method = $request->delivery_method ?? 'delivery';

        if ($request->foodbag_id) {
            // Assign the user the selected foodbag
                /** @var \App\Models\Foodbag */
            $bag = $subscription->plan->bags()->find($request->foodbag_id);

            if ($user instanceof Cooperative) {
                $b = $user->foodbags()->updateOrCreate([
                    'user_id' => $request->user()->id,
                    'subscription_id' => $subscription->id,
                ], [
                    'food_bag_id' => $bag->id,
                    'approved' => (bool) $user->settings['auto_approve_foodbags'] ?? false,
                ]);
                $exists = !$b->wasRecentlyCreated;
            } else {
                $subscription->food_bag_id = $bag->id;
            }
            $message = __('You have successfully activated the :0 food bag.', [$bag->title]);
        } else {
            // Save the items to the custom foodbag
            $bag = $subscription->bag()->firstOrNew([], ['user_id' => $user->id]);
            $bag->items = $request->items;
            $exists = $bag->exists;
            $bag->save();
            $message = __('Your custom foodbag :0.', [!$exists ? 'is now ready' : 'has been updated']);
        }

        // Save the updated subscription
        $subscription->save();


        return (new FoodCollection($bag->foods))->additional([
            'message' => $message,
            'status' => 'success',
            'subscription' => new SubscriptionResource($subscription),
            'response_code' => $exists ? HttpStatus::ACCEPTED : HttpStatus::CREATED,
        ])->response()->setStatusCode($exists ? HttpStatus::ACCEPTED : HttpStatus::CREATED);
    }
}
