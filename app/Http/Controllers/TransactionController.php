<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable as Carbon;
use Nette\Utils\Html;

class TransactionController extends Controller
{
    /**
     * Display a listing of the user's transactions for datatables.
     *
     * @param \Illuminate\Support\Facades\Auth $auth
     * @return \Illuminate\Http\Response
     */
    public function index(Auth $auth)
    {
        $model = Transaction::where('user_id', Auth::id());

        return app('datatables')->eloquent($model)
            ->rawColumns(['action'])
            ->editColumn('created_at', function(Transaction $item) {
                return $item->created_at->format('Y-m-d H:i');
            })
            ->addColumn('type', function(Transaction $item) {
                return Str::replace('App\\Models\\', '', $item->transactable_type);
            })
            ->editColumn('amount', function (Transaction $item) {
                return money(num_reformat($item->amount));
            })
            ->addColumn('action', function (Transaction $item) {
                return implode([
                    Html::el('a', ["onclick"=>"hotLink('/transactions/invoice/".$item->id."')", "href"=>"javascript:void(0)"])->title(__('View Invoice'))->setHtml(Html::el('i')->class('ri-file-list-2-fill ri-2x text-primary'))
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
     * @param \Illuminate\Support\Facades\Auth $auth
     * @return \Illuminate\Http\Response
     */
    public function transactions(Request $request, $limit = 1, $status = null)
    {
        $trans = Auth::user()->transactions()->orderBy('id', 'DESC');

        if (is_numeric($limit) && $limit > 0)
        {
            $trans->limit($limit);
        }

        if ($status !== null && in_array($status, ['rejected', 'pending', 'complete']))
        {
            $trans->where('status', $status);
        }

        if ($p = $request->query('period'))
        {
            $period = explode('-', $p);
            $from = new Carbon($period[0]);
            $to = new Carbon($period[1]);
            $trans->whereBetween('created_at', [$from, $to]);
        }

        $transactions = $trans->get();

        if ($transactions->isNotEmpty()) {
            $transactions->each(function($tr) {
                $tr->type = Str::replace('App\\Models\\', '', $tr->transactable_type );
                $tr->date = $tr->created_at->format('Y-m-d H:i');
            });
        }

        $msg = $transactions->isEmpty() ? 'You have not made any transactions.' : 'OK';
        $_period = $transactions->isNotEmpty()
            ? ($transactions->last()->created_at->format('Y/m/d') . '-' . $transactions->first()->created_at->format('Y/m/d'))
            : "";

        return $this->buildResponse([
            'message' => $msg,
            'status' =>  $transactions->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'transactions' => $transactions??[],
            'period' => $p ? urldecode($p) : $_period
        ]);
    }

    /**
     * Display an invoice of the user's transactions.
     *
     * @param \Illuminate\Support\Facades\Auth $auth
     * @return \Illuminate\Http\Response
     */
    public function invoice($transaction_id = null)
    {
        $transaction = Auth::user()->transactions()->find($transaction_id);

        $msg = !$transaction ? 'This transaction does not exist' : 'OK';

        $transaction->user;
        return $this->buildResponse([
            'message' => $msg,
            'status' =>  !$transaction ? 'info' : 'success',
            'response_code' => 200,
            'transaction' => $transaction??[],
            'items' => $transaction->transactable->items??[($transaction->transactable??null)],
        ]);
    }
}
