<?php

namespace App\Http\Controllers;

use App\Models\Saving;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            ->editColumn('type', function(Transaction $item) {
                if ($item->transactable() instanceof Saving) {
                    return $item->transactable()->subscription->plan->title;
                }
                elseif ($item->transactable() instanceof Saving) {
                    return $item->transactable()->subscription->plan->title;
                }
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
}