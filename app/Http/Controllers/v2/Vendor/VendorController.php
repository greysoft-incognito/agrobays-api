<?php

namespace App\Http\Controllers\v2\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Actions\Greysoft\Charts;
use App\EnumsAndConsts\HttpStatus;
use App\Models\Subscription;

class VendorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return $this->responseBuilder([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
            'data' => [
                'pie' => (new Charts())->getPie('vendor', $user->id),
                'bar' => (new Charts())->getBar('vendor', $user->id),
                'transactions' => (new Charts())->totalTransactions('vendor', 'all', $user->id),
                'customers' => (new Charts())->customers('vendor', 'month', $user->id),
                'users' => (new Charts())->customers('vendor', 'all', $user->id),
                'income' => (new Charts())->income('vendor', 'month', $user->id),
                'sales' => (new Charts())->sales('vendor', 'week', $user->id),
                'total_sales' => (new Charts())->sales('vendor', 'all', $user->id),
                'total_income' => (new Charts())->income('vendor', 'all', $user->id),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}