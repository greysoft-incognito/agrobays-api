<?php

namespace App\Http\Controllers\v2\Admin\Users;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\SavingResource;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\TransactionCollection;
use App\Http\Resources\TransactionResource;
use App\Models\Order;
use App\Models\Saving;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, User $user)
    {
        $this->authorize('usable', 'users');

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $user->transactions();

        // Filter by status
        $query->when(
            $request->status && in_array($request->status, ['rejected', 'pending', 'complete']),
            function ($query) use ($request) {
                $query->where('status', $request->get('status'));
            }
        );

        // Set default period
        $period_placeholder = Carbon::now()->subDays(30)->format('Y/m/d') . '-' . Carbon::now()->addDay()->format('Y/m/d');

        // Get period
        $period = $request->period == '0' ? [] : explode('-', urldecode($request->get('period', $period_placeholder)));

        $query->when(isset($period[0]), function ($query) use ($period) {
            // Filter by period
            $query->whereBetween('created_at', [new Carbon($period[0]), (new Carbon($period[1]))->addDay()]);
        });

        /** @var \App\Models\Transaction $transactions */
        $transactions = $query->paginate($request->get('limit', 15));

        // Return response
        return (new TransactionCollection($transactions))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
            'period' => implode(' to ', $period),
            'date_range' => $period,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, User $user, $id)
    {
        $this->authorize('usable', 'users');

        /** @var \App\Models\Transaction $transaction */
        $transaction = $user->transactions()->find($id);

        // Check if transaction exists
        if (! $transaction) {
            abort(HttpStatus::NOT_FOUND, 'This transaction does not exist');
        }

        // Set additional data
        $additional = [
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ];

        // Return invoice
        if ($request->boolean('invoice')) {
            if ($transaction?->transactable instanceof Order) {
                return (new OrderResource($transaction->transactable))
                ->additional($additional)
                ->response()
                ->setStatusCode($additional['response_code']);
            } elseif ($transaction?->transactable instanceof Saving) {
                return ($request->subscription
                ? new SubscriptionResource($transaction->transactable->subscription)
                : new SavingResource($transaction->transactable))
                    ->additional($additional)
                    ->response()
                    ->setStatusCode($additional['response_code']);
            } else {
                $transaction->items = $transaction->transactable;
            }
        }

        // Return response
        return (new TransactionResource($transaction))
            ->additional($additional)
            ->response()
            ->setStatusCode(HttpStatus::OK);
    }
}