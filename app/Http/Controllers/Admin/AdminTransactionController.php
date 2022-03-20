<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminTransactionController extends Controller
{
    public function index(Request $request)
    {
        $model = Transaction::query();
        return app('datatables')->eloquent($model)
            ->editColumn('created_at', function(Transaction $item) {
                return $item->created_at->format('Y-m-d H:i');
            })
            ->addColumn('action', function (Transaction $item) {
                return '<a href="#edit-'.$item->id.'" class="btn btn-xs btn-primary"><i class="fa fa-pen-alt"></i> Edit</a>';
            })
            ->removeColumn('updated_at')->toJson();

        // $transaction = Transaction::paginate(15);

        // return $this->buildResponse([
        //     'message' => $transaction->isEmpty() ? 'No food has been created' : '',
        //     'status' => $transaction->isEmpty() ? 'info' : 'success',
        //     'response_code' => 200,
        //     'transaction' => $transaction,
        // ]);
    }

    public function getItem(Request $request, $item)
    {
        $transaction = Transaction::whereId($item)->first();

        return $this->buildResponse([
            'message' => !$transaction ? 'The requested transaction no longer exists' : 'OK',
            'status' =>  !$transaction ? 'info' : 'success',
            'response_code' => !$transaction ? 404 : 200,
            'transaction' => $transaction,
        ]);
    }

    /**
     * Update the transaction status
     *
     * @param Request $request
     * @param integer $item
     * @return void
     */
    public function store(Request $request, $item = null)
    {
        $transaction = Transaction::find($item);
        if (!$transaction) {
            return $this->buildResponse([
                'message' => 'The requested transaction no longer exists',
                'status' => 'error',
                'response_code' => 404,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,complete,rejected',
        ]);

        if ($validator->fails()) {
            return $this->buildResponse([
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'response_code' => 422,
                'errors' => $validator->errors(),
            ]);
        }

        $transaction->status = $request->status;
        $transaction->save();

        return $this->buildResponse([
            'message' => 'Transaction status updated.',
            'status' =>  'success',
            'response_code' => 200,
            'plan' => $transaction,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($item = null)
    {
        $food = Transaction::whereId($item)->first();

        if ($food)
        {
            $food->delete();

            return $this->buildResponse([
                'message' => "Transaction has been deleted.",
                'status' =>  'success',
                'response_code' => 200,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested transaction no longer exists.',
            'status' => 'error',
            'response_code' => 404,
        ]);
    }
}