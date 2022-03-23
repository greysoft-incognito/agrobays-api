<?php

namespace App\Http\Controllers;

use App\Models\Saving;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    /**
     * Display a listing of the user's transactions.
     *
     * @param \Illuminate\Support\Facades\Auth $auth
     * @return \Illuminate\Http\Response
     */
    public function index(Auth $auth)
    {
        $model = Transaction::where('user_id', Auth::id());

        return app('datatables')->eloquent($model)
            ->editColumn('created_at', function(Transaction $item) {
                return $item->created_at->format('Y-m-d H:i');
            })
            ->addColumn('type', function(Transaction $item) {
                return Str::replace('App\\Models\\', '', $item->transactable_type);
            })
            ->addColumn('action', function (Transaction $item) {
                return '<a href="transactions/invoice/'.$item->id.'" class="btn btn-xs btn-primary"><i class="ri-file-list-2-fill ri-xl"></i></a>';
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
    public function transactions($limit = 1, $status = null)
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

        $transactions = $trans->get();

        $msg = !$transactions->isNotEmpty() ? 'You have not made any transactions.' : 'OK';

        return $this->buildResponse([
            'message' => $msg,
            'status' =>  !$transactions ? 'info' : 'success',
            'response_code' => 200,
            'transactions' => $transactions??[],
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

        $msg = !$transaction ? 'This transaction was does not exist' : 'OK';

        return $this->buildResponse([
            'message' => $msg,
            'status' =>  !$transaction ? 'info' : 'success',
            'response_code' => 200,
            'transaction' => $transaction??[],
        ]);
    }
}