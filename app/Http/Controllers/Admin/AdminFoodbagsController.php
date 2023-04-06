<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\FoodBagCollection;
use App\Http\Resources\FoodBagResource;
use App\Http\Resources\FoodResource;
use App\Models\Food;
use App\Models\FoodBag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminFoodbagsController extends Controller
{
    public function index(Request $request, $limit = '15')
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

        $items = ($limit <= 0 || $limit === 'all') ? $query->get() : $query->paginate($limit);

        return (new FoodBagCollection($items))->additional([
            'message' => 'OK',
            'status' => $items->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
        ])->response()->setStatusCode(200);
    }

    public function getItem(Request $request, $item)
    {
        \Gate::authorize('usable', 'foodbags');

        $bag = FoodBag::find($item);

        if ($request->with_foods && $bag) {
            $bag->load('foods');
        }

        return (new FoodBagResource($bag))->additional([
            'message' => !$bag ? 'The requested foodbag no longer exists' : 'OK',
            'status' => !$bag ? 'info' : 'success',
            'response_code' => !$bag ? 404 : 200,
        ])->response()->setStatusCode(!$bag ? 404 : 200);
    }

    /**
     * Put food into a foodbag
     *
     * @return \Illuminate\Http\Response
     */
    public function putFood(Request $request, $item)
    {
        \Gate::authorize('usable', 'foodbags');
        $validator = Validator::make($request->all(), [
            'food_id' => 'required|numeric|exists:food,id|unique:food_bag_items,food_id,NULL,id,food_bag_id,' . $item,
            'quantity' => 'nullable|numeric|min:1|max:100',
            'is_active' => 'nullable|boolean',
        ], [
            'food_id.exists' => 'The selected food is invalid',
            'food_id.unique' => 'The selected food is already in the foodbag',
        ], [
            'food_id' => 'Food',
        ]);

        if ($validator->fails()) {
            return $this->buildResponse([
                'message' => $validator->errors()->first(),
                'status' => 'error',
                'response_code' => 422,
                'errors' => $validator->errors(),
            ]);
        }

        $food = Food::find($request->food_id);
        $bag = FoodBag::find($item);
        $bag->foods()->attach($food->id, [
            'quantity' => $request->quantity ?? 1,
            'is_active' => $request->is_active ?? true,
        ]);

        return (new FoodBagResource($bag))->additional([
            'food' => new FoodResource($food),
            'message' => __(':0 has been added to ":1"', [$food->name, $bag->title]),
            'status' => 'success',
            'response_code' => 202,
        ])->response()->setStatusCode(202);
    }

    public function removeFood(Request $request, $item, Food $food)
    {
        \Gate::authorize('usable', 'foodbags');
        $bag = FoodBag::findOrfail($item);

        // Check if the food is still in the bag
        $bag->load('foods');
        if (!$bag->foods->contains($food->id)) {
            return $this->buildResponse([
                'message' => __(':0 is no longer in ":1"', [$food->name, $bag->title]),
                'status' => 'info',
                'response_code' => 422,
                'bag' => $bag,
            ]);
        }
        $bag->foods()->detach($food);


        return (new FoodBagResource($bag))->additional([
            'message' => __(':0 has been removed from ":1"', [$food->name, $bag->title]),
            'status' => 'success',
            'response_code' => 202,
        ])->response()->setStatusCode(202);
    }

    public function store(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'foodbags');
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'min:3', 'max:25', Rule::unique('food_bags')->ignore($item)],
            'plan_id' => 'required|numeric',
            'description' => 'nullable|min:10|max:550',
        ], [], [
            'plan_id' => 'Plan',
        ]);

        if ($validator->fails()) {
            return $this->buildResponse([
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'response_code' => 422,
                'errors' => $validator->errors(),
            ]);
        }

        $bag = FoodBag::find($item) ?? new FoodBag;

        $bag->title = $request->title;
        $bag->plan_id = $request->plan_id;
        $bag->description = $request->description;
        $bag->save();

        return (new FoodBagResource($bag))->additional([
            'message' => $item ? Str::of($bag->title)->append(' Has been updated!') : 'New foodbag has been created.',
            'status' => 'success',
            'response_code' => $item ? 202 : 201,
        ])->response()->setStatusCode($item ? 202 : 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'foodbags');
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) {
                $bag = FoodBag::whereId($item)->first();
                if ($bag) {
                    $bag->image && Storage::delete($bag->image);

                    return $bag->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} foods bags have been deleted.",
                'status' => 'success',
                'response_code' => 200,
            ]);
        } else {
            $bag = FoodBag::whereId($item)->first();
        }

        if ($bag) {
            $bag->delete();

            return $this->buildResponse([
                'message' => "{$bag->title} has been deleted.",
                'status' => 'success',
                'response_code' => 200,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested foodbag no longer exists.',
            'status' => 'error',
            'response_code' => 404,
        ]);
    }
}