<?php

namespace App\Http\Controllers\v2\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\SavingResource;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\TransactionCollection;
use App\Http\Resources\TransactionResource;
use App\Models\Order;
use App\Models\Saving;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Set default period
        $period_placeholder = Carbon::now()->subDays(30)->format('Y/m/d').'-'.Carbon::now()->format('Y/m/d');

        // Get period
        $period = explode('-', urldecode($request->get('period', $period_placeholder)));

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $user->transactions();

        // Filter by status
        $query->when(
            $request->status && in_array($request->status, ['rejected', 'pending', 'complete']),
            function ($query) use ($request) {
                $query->where('status', $request->get('status'));
            }
        );

        // Filter by period
        $query->whereBetween('created_at', [new Carbon($period[0]), new Carbon($period[1])]);

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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

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
