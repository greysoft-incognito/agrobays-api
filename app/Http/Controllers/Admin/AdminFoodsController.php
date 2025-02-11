<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\FoodCollection;
use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminFoodsController extends Controller
{
    public function index(Request $request, $limit = '15')
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

        $items = ($limit <= 0 || $limit === 'all') ? $query->get() : $query->paginate($limit);

        return (new FoodCollection($items))->additional([
            'message' => 'OK',
            'status' => $items->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
        ])->response()->setStatusCode(200);
    }

    public function getItem(Request $request, $item)
    {
        \Gate::authorize('usable', 'foods');
        $food = Food::whereId($item)->first();

        return $this->buildResponse([
            'message' => ! $food ? 'The requested food no longer exists' : 'OK',
            'status' => ! $food ? 'info' : 'success',
            'response_code' => ! $food ? 404 : 200,
            'food' => $food,
        ]);
    }

    public function store(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'foods');
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:25', Rule::unique('foods')->ignore($item),
            'weight' => 'numeric|required|min:0.1',
            'price' => 'required|numeric|min:1',
            'unit' => 'required|in:kg,g,lb,oz,ml,l',
            'image' => 'nullable|mimes:jpg,jpeg,png',
            'description' => 'nullable|min:10|max:550',
            'available' => 'nullable|boolean',
        ], [], [
            'food_bag_id' => 'Food Bag',
        ]);

        if ($validator->fails()) {
            return $this->buildResponse([
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'response_code' => 422,
                'errors' => $validator->errors(),
            ]);
        }

        $food = Food::whereId($item)->first() ?? new Food();

        $food->name = $request->name;
        $food->weight = $request->weight ?? $food->weight ?? 1;
        $food->unit = $request->unit ?? $food->unit ?? 'kg';
        $food->price = $request->price ?? $food->price ?? 1;
        $food->description = $request->description;
        $food->available = $request->boolean('available');

        $food->save();

        return $this->buildResponse([
            'message' => $item ? __(':0 Has been updated!', [$food->name]) : 'New food has been added.',
            'status' => 'success',
            'response_code' => 200,
            'food' => $food,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'foods');
        if ($request->items) {
            $count = collect($request->items)->each(function ($item) {
                $food = Food::whereId($item)->first();
                if ($food) {
                    $food->image && Storage::delete($food->image);

                    return $food->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} foods have been deleted.",
                'status' => 'success',
                'response_code' => 200,
            ]);
        } else {
            $food = Food::whereId($item)->first();
        }

        if ($food) {
            $food->image && Storage::delete($food->image);

            $food->delete();

            return $this->buildResponse([
                'message' => "{$food->name} has been deleted.",
                'status' => 'success',
                'response_code' => 200,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested food no longer exists.',
            'status' => 'error',
            'response_code' => 404,
        ]);
    }
}
