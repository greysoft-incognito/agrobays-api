<?php

namespace App\Http\Controllers\v2;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\FruitbayCategoryCollection;
use App\Http\Resources\FruitbayCategoryResource;
use App\Models\FruitBayCategory;
use Illuminate\Http\Request;

class FruitBayCategoryController extends Controller
{
    public function __construct(Request $request)
    {
        if (!str($request->route()?->getAction('uses'))->afterLast('@')->contains(['index', 'show'])) {
            $this->middleware('auth:sanctum');
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = FruitBayCategory::query()
            ->when($request->has('q'), function (Builder $q) use ($request) {
                $q->where('name', 'like', "%{$request->q}%");
            });

        $categories = $query->paginate($request->get('limit', 15));

        return (new FruitbayCategoryCollection($categories))->additional([
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
    public function show(FruitBayCategory $category)
    {
        return (new FruitbayCategoryResource($category))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }
}