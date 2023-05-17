<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Http\Resources\SavingResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Order;
use App\Models\Saving;
use App\Models\Transaction;
use Carbon\CarbonImmutable as Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Nette\Utils\Html;

class TransactionController extends Controller
{
    /**
     * Display a listing of the user's transactions for datatables.
     *
     * @param  \Illuminate\Support\Facades\Auth  $auth
     * @return \Illuminate\Http\Response
     */
    public function index(Auth $auth)
    {
        $model = Transaction::where('user_id', Auth::id());

        return app('datatables')->eloquent($model)
            ->rawColumns(['action'])
            ->editColumn('created_at', function (Transaction $item) {
                return $item->created_at->format('Y-m-d H:i');
            })
            ->addColumn('type', function (Transaction $item) {
                return Str::replace('App\\Models\\', '', $item->transactable_type);
            })
            ->editColumn('amount', function (Transaction $item) {
                return money(num_reformat($item->amount));
            })
            ->addColumn('action', function (Transaction $item) {
                return implode([
                    Html::el('a', ['onclick' => "hotLink('/transactions/invoice/".$item->id."')", 'href' => 'javascript:void(0)'])->title(__('View Invoice'))->setHtml(Html::el('i')->class('ri-file-list-2-fill ri-2x text-primary')),
                ]);
            })
            ->removeColumn('updated_at')->toJson();

        // return $this->buildResponse([
        //     'message' => 'OK',
        //     'status' => 'success',
        //     'response_code' => 200,
        //     'transactions' => $auth::user()->transactions()->paginate(15),
        // ]);
    }

    /**
     * Display a listing of the user's transactions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $limit
     * @param  string  $status
     * @return \Illuminate\Http\Response
     */
    public function transactions(Request $request, $limit = 1, $status = null)
    {
        $trans = Auth::user()->transactions()->orderBy('id', 'DESC');

        if (is_numeric($limit) && $limit > 0) {
            $trans->limit($limit);
        }

        if ($status !== null && in_array($status, ['rejected', 'pending', 'complete'])) {
            $trans->where('status', $status);
        }

        if ($p = $request->query('period')) {
            $period = explode('-', $p);
            $from = new Carbon($period[0]);
            $to = new Carbon($period[1]);
            $trans->whereBetween('created_at', [$from, $to]);
        }

        $transactions = $trans->get();

        if ($transactions->isNotEmpty()) {
            $transactions->each(function ($tr) {
                $tr->type = Str::replace('App\\Models\\', '', $tr->transactable_type);
                $tr->date = $tr->created_at->format('Y-m-d H:i');
            });
        }

        $msg = $transactions->isEmpty() ? 'You have not made any transactions.' : 'OK';
        $_period = $transactions->isNotEmpty()
            ? ($transactions->last()->created_at->format('Y/m/d').'-'.$transactions->first()->created_at->format('Y/m/d'))
            : '';

        return $this->buildResponse([
            'message' => $msg,
            'status' => $transactions->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'transactions' => $transactions ?? [],
            'period' => $p ? urldecode($p) : $_period,
        ]);
    }

    /**
     * Display an invoice of the user's transactions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Support\Facades\Auth  $auth
     * @return \Illuminate\Http\Response
     */
    public function invoice(Request $request, $transaction_id = null)
    {
        $transaction = Auth::user()->transactions()->with('user')->findOrFail($transaction_id);

        $msg = $transaction ? 'Ok' : 'This transaction does not exist';

        $additionalData = [
            'message' => $msg,
            'status' => $transaction ? 'success' : 'info',
            'response_code' => $transaction ? 200 : 404,
        ];

        if ($transaction->transactable instanceof Order) {
            return (new OrderResource($transaction->transactable))
                ->additional($additionalData)
                ->response()
                ->setStatusCode($additionalData['response_code']);
        } elseif ($transaction->transactable instanceof Saving) {
            return ($request->subscription
                ? new SubscriptionResource($transaction->transactable->subscription)
                : new SavingResource($transaction->transactable))
                ->additional($additionalData)
                ->response()
                ->setStatusCode($additionalData['response_code']);
        }

        return $this->buildResponse([
            'message' => $msg,
            'status' => $transaction ? 'success' : 'info',
            'response_code' => $transaction ? 200 : 404,
            'transaction' => $transaction ?? [],
            'items' => $transaction->transactable->items ?? [($transaction->transactable ?? null)],
        ]);
    }
}
