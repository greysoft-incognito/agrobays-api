<?php

namespace App\Http\Controllers\v2;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\FoodCollection;
use App\Http\Resources\FoodResource;
use App\Models\Food;

class FoodController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Food::query()
        ->when($request->search, function ($query) use ($request) {
            // Search and filter columns
            $query->where(function ($query) use ($request) {
                $query->where('name', 'like', "%$request->search%")
                ->orWhere('description', 'like', "%$request->search%");
            });
        })
        ->when(is_array($request->order), function ($query) use ($request) {
            foreach ($request->order as $key => $dir) {
                if ($dir === 'desc') {
                    $query->orderByDesc($key ?? 'id');
                } else {
                    $query->orderBy($key ?? 'id');
                }
            }
        })
        ->when($request->has('available'), function ($query) use ($request) {
            $query->where('available', $request->boolean('available'));
        });

        $items = $query->paginate($request->get('limit', 30));

        return (new FoodCollection($items))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  Food  $food
     * @return \Illuminate\Http\Response
     */
    public function show(Food $food)
    {
        return (new FoodResource($food))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }
}
