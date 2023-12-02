<?php

namespace App\Http\Controllers\v2\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\FoodCollection;
use App\Http\Resources\FoodResource;
use App\Http\Resources\GenericResource;
use App\Models\Food;
use Illuminate\Validation\Rule;

class FoodController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        \Gate::authorize('usable', 'foods');
        $query = Food::query();

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('name', 'like', "%$request->search%")
                    ->orWhere('description', 'like', "%$request->search%");
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

        $items = $query->paginate($request->get('limit', 30));

        return (new FoodCollection($items))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        \Gate::authorize('usable', 'foods');
        $this->validate($request, [
            'name' => 'required|min:3|max:25|unique:food,name',
            'weight' => 'numeric|required|min:0.1',
            'price' => 'required|numeric|min:1',
            'unit' => 'required|in:kg,g,lb,oz,ml,l',
            'image' => 'nullable|mimes:jpg,jpeg,png',
            'description' => 'nullable|min:10|max:550',
            'available' => 'nullable|boolean',
        ], [], [
            'food_bag_id' => 'Food Bag',
        ]);

        $food = new Food();

        $food->name = $request->name;
        $food->weight = $request->weight;
        $food->unit = $request->unit;
        $food->price = $request->price;
        $food->description = $request->description;
        $food->available = $request->boolean('available');

        $food->save();

        return (new FoodResource($food))->additional([
            'message' => __('New food item ":0" has been created successfully.', [$food->name]),
            'status' => 'success',
            'response_code' => HttpStatus::CREATED,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  Food  $food
     * @return \Illuminate\Http\Response
     */
    public function show(Food $food)
    {
        \Gate::authorize('usable', 'foods');
        return (new FoodResource($food))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Food $food)
    {
        \Gate::authorize('usable', 'foods');
        $this->validate($request, [
            'name' => 'required|min:3|max:25', Rule::unique('food')->ignore($food),
            'weight' => 'numeric|required|min:0.1',
            'price' => 'required|numeric|min:1',
            'unit' => 'required|in:kg,g,lb,oz,ml,l',
            'image' => 'nullable|mimes:jpg,jpeg,png',
            'description' => 'nullable|min:10|max:550',
            'available' => 'nullable|boolean',
        ], [], [
            'food_bag_id' => 'Food Bag',
        ]);

        $food->name = $request->name;
        $food->weight = $request->weight ?? $food->weight ?? 1;
        $food->unit = $request->unit ?? $food->unit ?? 'kg';
        $food->price = $request->price ?? $food->price ?? 1;
        $food->description = $request->description;
        $food->available = $request->boolean('available');

        $food->save();

        return (new FoodResource($food))->additional([
            'message' => __(':0 has been updated successfully.', [$food->name]),
            'status' => 'success',
            'response_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        \Gate::authorize('usable', 'foods');

        $items = Food::whereIn('id', $request->items ? $request->items : [$id])->get();
        $items->each(fn ($item) => $item->delete());

        return GenericResource::collection($items)->additional([
            'message' => __(':0 food items have been deleted.', [
                $items->count()
            ]),
            'status' => 'success',
            'response_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }
}
