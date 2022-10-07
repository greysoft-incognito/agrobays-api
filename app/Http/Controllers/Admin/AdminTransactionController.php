<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminTransactionController extends Controller
{
    public function index(Request $request, $limit = '15')
    {
        \Gate::authorize('usable', 'transactions');
        $query = Transaction::query()->with('user');

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('reference', 'like', "%$request->search%")
                    ->orWhere('amount', 'like', "%$request->search%")
                    ->orWhere('status', 'like', "%$request->search%")
                    ->orWhere('method', 'like', "%$request->search%");
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

        $items = ($limit <= 0 || $limit === 'all') ? $query->get() : $query->paginate($limit);

        return $this->buildResponse([
            'message' => 'OK',
            'status' => $items->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'items' => $items ?? [],
        ]);
    }

    public function getItem(Request $request, $item)
    {
        \Gate::authorize('usable', 'transactions');
        $transaction = Transaction::whereId($item)->first();

        return $this->buildResponse([
            'message' => ! $transaction ? 'The requested transaction no longer exists' : 'OK',
            'status' => ! $transaction ? 'info' : 'success',
            'response_code' => ! $transaction ? 404 : 200,
            'transaction' => $transaction,
        ]);
    }

    /**
     * Update the transaction status
     *
     * @param  Request  $request
     * @param  int  $item
     * @return void
     */
    public function store(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'transactions');
        $transaction = Transaction::find($item);
        if (! $transaction) {
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
            'status' => 'success',
            'response_code' => 200,
            'plan' => $transaction,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'transactions');
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) {
                $transaction = Transaction::whereId($item)->first();
                if ($transaction) {
                    return $transaction->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} transactions bags have been deleted.",
                'status' => 'success',
                'response_code' => 200,
            ]);
        } else {
            $transaction = Transaction::whereId($item)->first();
        }

        if ($transaction) {
            $food->delete();

            return $this->buildResponse([
                'message' => 'Transaction has been deleted.',
                'status' => 'success',
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
