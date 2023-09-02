<?php

namespace App\Http\Controllers\v2;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\FruitbayCollection;
use App\Http\Resources\FruitbayResource;
use App\Models\FruitBay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FruitBayController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = FruitBay::where('available', true)
            ->when($request->has('category_id'), function ($q) use ($request) {
                $q->where('fruit_bay_category_id', $request->category_id);
            })
            ->when($request->has('q'), function (Builder $q) use ($request) {
                $q->where('name', 'like', "%{$request->q}%");
                $q->orWhereFullText('description', $request->q);
                $q->orWhereHas('category', function (Builder $q) use ($request) {
                    $q->where('name', 'like', "%{$request->q}%");
                });
            });

        $items = $query->paginate($request->get('limit', 15));

        return (new FruitbayCollection($items))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(FruitBay $fruitbay)
    {
        return (new FruitbayResource($fruitbay))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }
}
