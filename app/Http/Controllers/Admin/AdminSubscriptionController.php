<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Notifications\SubStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminSubscriptionController extends Controller
{
    public function index(Request $request, $limit = '15')
    {
        \Gate::authorize('usable', 'subscriptions');
        $query = Subscription::query()->with('user');

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('status', 'like', "%$request->search%");
            });
        }

        // Reorder Columns
        if ($request->order && is_array($request->order)) {
            foreach ($request->order as $key => $dir) {
                if ($dir === 'desc') {
                    $query->orderByDesc($key ?? 'id');
                } else {
                    $query->orderBy($key ?? 'id');
                }
            }
        }

        if ($request->status && in_array($request->status, [
            'active', 'pending', 'complete', 'withdraw', 'closed'
        ])) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', '!=', 'closed');
        }

        $items = ($limit <= 0 || $limit === 'all') ? $query->get() : $query->paginate($limit);

        return $this->buildResponse([
            'message' => 'OK',
            'status' =>  $items->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'items' => $items ?? [],
        ]);
    }

    public function getItem(Request $request, $item)
    {
        \Gate::authorize('usable', 'subscriptions');
        $subscription = Subscription::whereId($item)->first();

        return $this->buildResponse([
            'message' => ! $subscription ? 'The requested subscription no longer exists' : 'OK',
            'status' =>  ! $subscription ? 'info' : 'success',
            'response_code' => ! $subscription ? 404 : 200,
            'subscription' => $subscription,
        ]);
    }

    /**
     * Update the subscription status
     *
     * @param  Request  $request
     * @param  int  $item
     * @return void
     */
    public function store(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'subscriptions');
        $subscription = Subscription::find($item);
        if (! $subscription) {
            return $this->buildResponse([
                'message' => 'The requested subscription no longer exists',
                'status' => 'error',
                'response_code' => 404,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,active,complete,withdraw,closed',
        ]);

        if ($request->status === 'closed') {
            $subscription->user->notify(new SubStatus($subscription, $request->status));
        }

        if ($validator->fails()) {
            return $this->buildResponse([
                'message' => $validator->errors()->first(),
                'status' => 'error',
                'response_code' => 422,
                'errors' => $validator->errors(),
            ]);
        }

        $subscription->status = $request->status;
        $subscription->save();

        return $this->buildResponse([
            'message' => 'Subscription status updated.',
            'status' =>  'success',
            'response_code' => 200,
            'plan' => $subscription,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'subscriptions');
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) {
                $subscription = Subscription::whereId($item)->first();
                if ($subscription) {
                    return $subscription->delete();
                }

                return false;
            })->filter(fn ($i) =>$i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} subscriptions bags have been deleted.",
                'status' =>  'success',
                'response_code' => 200,
            ]);
        } else {
            $subscription = Subscription::whereId($item)->first();
        }

        if ($subscription) {
            $food->delete();

            return $this->buildResponse([
                'message' => 'Subscription has been deleted.',
                'status' =>  'success',
                'response_code' => 200,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested subscription no longer exists.',
            'status' => 'error',
            'response_code' => 404,
        ]);
    }
}