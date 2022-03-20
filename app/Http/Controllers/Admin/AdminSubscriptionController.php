<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use Illuminate\Support\Facades\Validator;

class AdminSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $model = Subscription::query();
        return app('datatables')->eloquent($model)
            ->editColumn('created_at', function(Subscription $item) {
                return $item->created_at->format('Y-m-d H:i');
            })
            ->addColumn('action', function (Subscription $item) {
                return '<a href="#edit-'.$item->id.'" class="btn btn-xs btn-primary"><i class="fa fa-pen-alt"></i> Edit</a>';
            })
            ->removeColumn('updated_at')->toJson();

        // $subscription = Subscription::paginate(15);

        // return $this->buildResponse([
        //     'message' => $subscription->isEmpty() ? 'No food has been created' : '',
        //     'status' => $subscription->isEmpty() ? 'info' : 'success',
        //     'response_code' => 200,
        //     'subscription' => $subscription,
        // ]);
    }

    public function getItem(Request $request, $item)
    {
        $subscription = Subscription::whereId($item)->first();

        return $this->buildResponse([
            'message' => !$subscription ? 'The requested subscription no longer exists' : 'OK',
            'status' =>  !$subscription ? 'info' : 'success',
            'response_code' => !$subscription ? 404 : 200,
            'subscription' => $subscription,
        ]);
    }

    /**
     * Update the subscription status
     *
     * @param Request $request
     * @param integer $item
     * @return void
     */
    public function store(Request $request, $item = null)
    {
        $subscription = Subscription::find($item);
        if (!$subscription) {
            return $this->buildResponse([
                'message' => 'The requested subscription no longer exists',
                'status' => 'error',
                'response_code' => 404,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,active,complete',
        ]);

        if ($validator->fails()) {
            return $this->buildResponse([
                'message' => 'Your input has a few errors',
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