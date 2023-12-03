<?php

namespace App\Http\Controllers\v2\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\FoodBagCollection;
use App\Http\Resources\FoodBagResource;
use App\Http\Resources\FoodCollection;
use App\Models\FoodBag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FoodbagController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        \Gate::authorize('usable', 'foodbags');
        $query = FoodBag::query()->with('plan');

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('title', 'like', "%$request->search%")
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

        if ($request->with_foods) {
            $query->with('foods');
        }

        $items = $query->paginate($request->get('limit', 30));

        return (new FoodBagCollection($items))->additional([
            'message' => 'OK',
            'status' => $items->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
        ])->response()->setStatusCode(200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        \Gate::authorize('usable', 'foodbags');

        $this->validate($request, [
            'title' => ['required', 'min:3', 'max:25', Rule::unique('food_bags')],
            'plan_id' => 'required|numeric',
            'description' => 'nullable|min:10|max:550',
            'fees' => 'nullable|min:0|numeric',
        ], [], [
            'plan_id' => 'Plan',
        ]);

        $foodbag = new FoodBag();

        $foodbag->fees = $request->fees;
        $foodbag->title = $request->title;
        $foodbag->plan_id = $request->plan_id;
        $foodbag->description = $request->description;
        $foodbag->save();

        return (new FoodBagResource($foodbag))->additional([
            'message' => __('New food item ":0" has been created successfully.', [$foodbag->title]),
            'status' => 'success',
            'response_code' => HttpStatus::CREATED,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  FoodBag  $foodbag
     * @return \Illuminate\Http\Response
     */
    public function show(FoodBag $foodbag)
    {
        \Gate::authorize('usable', 'foodbags');

        return (new FoodBagResource($foodbag))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  FoodBag  $foodbag
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, FoodBag $foodbag)
    {
        \Gate::authorize('usable', 'foodbags');

        $this->validate($request, [
            'title' => ['required', 'min:3', 'max:25', Rule::unique('food_bags')->ignore($foodbag)],
            'plan_id' => 'required|numeric',
            'description' => 'nullable|min:10|max:550',
            'fees' => 'nullable|min:0|numeric',
        ], [], [
            'plan_id' => 'Plan',
        ]);

        $foodbag->fees = $request->fees;
        $foodbag->title = $request->title;
        $foodbag->plan_id = $request->plan_id;
        $foodbag->description = $request->description;
        $foodbag->save();

        return (new FoodBagResource($foodbag))->additional([
            'message' => __(':0 has been updated successfully.', [$foodbag->title]),
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
        \Gate::authorize('usable', 'foodbags');
        $items = FoodBag::whereIn('id', $request->items ? $request->items : [$id])->get();

        $items->each(function ($item) {
            $item->image && Storage::delete($item->image);
            $item->delete();
        });

        return (new FoodBagCollection($items))->additional([
            'message' => __(':0 food bags have been deleted.', [$items->count()]),
            'status' => 'success',
            'response_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }
}
