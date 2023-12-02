<?php

namespace App\Http\Controllers\v2\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CooperativeResource;
use App\Http\Resources\UserBasicDataResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\WalletCollection;
use App\Models\Cooperative;
use App\Models\User;
use App\Rules\Identity;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * Display a listing of the wallet resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = $user->wallet()->orderByDesc('id');

        // Set default period
        $period_placeholder = Carbon::now()->subDays(30)->format('Y/m/d') . '-' . Carbon::now()->addDay()->format('Y/m/d');

        // Get period
        $period = $request->period == '0' ? [] : explode('-', urldecode($request->get('period', $period_placeholder)));

        $query->when(isset($period[0]), function ($query) use ($period) {
            // Filter by period
            $query->whereBetween('created_at', [new Carbon($period[0]), (new Carbon($period[1]))->addDay()]);
        });

        /** @var \App\Models\Wallet $transactions */
        $wallet_transactions = $query->paginate($request->get('limit', 15));

        return (new WalletCollection($wallet_transactions))->additional([
            'balance' => $user->wallet_balance,
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Send money to another user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function send(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'recipient_type' => 'bail|required|in:user,cooperative',
            'recipient_id' => ['required', new Identity($request->recipient_type)],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        /** @var \App\Models\User|\App\Models\Cooperative $recipient */
        $recipient = $request->recipient_type === 'user'
            ? User::whereUsername($request->recipient_id)->orWhere('id', $request->recipient_id)->first()
            : Cooperative::whereSlug($request->recipient_id)->orWhere('id', $request->recipient_id)->first();

        if (
            $user->wallet_balance < $request->amount ||
            ($request->recipient_type === 'user' && $recipient->id === $user->id)
        ) {
            return $this->responseBuilder([
                'message' => $recipient->id === $user->id
                    ? __('You cannot transfer funds to yourself')
                    : __('Insufficient funds.'),
                'status' => 'error',
                'response_code' => HttpStatus::UNPROCESSABLE_ENTITY,
            ]);
        }

        $recipient_name = $recipient->title ??
            $recipient->name ??
            $recipient->fullname ??
            $recipient->username ??
            $recipient->slug;

        $user->wallet()->create([
            'type' => 'debit',
            'amount' => $request->amount,
            'source' => 'Funds Transfer',
            'detail' => __('Completed funds transfer to :0', [$recipient_name]),
        ]);

        $recipient->wallet()->create([
            'type' => 'credit',
            'amount' => $request->amount,
            'source' => 'Funds Transfer',
            'detail' => __(':0 transfered funds to your wallet.', [$user->fullname ?? $user->username]),
            'sender_id' => $user->id,
        ]);

        return (new UserResource($user->fresh()))->additional([
            'recipient' => $request->recipient_type === 'user'
                ? new UserBasicDataResource($recipient)
                : new CooperativeResource($recipient),
            'message' => __('Fund transfer to :0 completed successfully.', [$recipient_name]),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ]);
    }
}
