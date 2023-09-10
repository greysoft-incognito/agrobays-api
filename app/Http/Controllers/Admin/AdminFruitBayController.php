<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FruitBay;
use App\Models\FruitBayCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminFruitBayController extends Controller
{
    public function index(Request $request, $limit = '15')
    {
        \Gate::authorize('usable', 'fruitbay');
        $query = FruitBay::query();

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

        return $this->buildResponse([
            'message' => 'OK',
            'status' => $items->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'items' => $items ?? [],
        ]);
    }

    public function getItem(Request $request, $item)
    {
        \Gate::authorize('usable', 'fruitbay');
        $item = FruitBay::whereId($item)->orWhere(['slug' => $item])->first();

        return $this->buildResponse([
            'message' => ! $item ? 'The requested item no longer exists' : 'OK',
            'status' => ! $item ? 'info' : 'success',
            'response_code' => ! $item ? 404 : 200,
            'item' => $item,
        ]);
    }

    public function store(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'fruitbay');
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:25',
            'price' => 'required|numeric|min:1',
            'fees' => 'nullable|numeric|min:0',
            'bag' => 'nullable|array|max:10',
            'description' => 'nullable|min:10|max:550',
        ], [], [
            'fees' => 'Shipping/Handling Fees',
        ]);

        if ($validator->fails()) {
            return $this->buildResponse([
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'response_code' => 422,
                'errors' => $validator->errors(),
            ]);
        }

        $fruitbay = FruitBay::whereId($item)->orWhere(['slug' => $item])->first() ?? new FruitBay();

        $fruitbay->fees = $request->fees ?? 0.00;
        $fruitbay->name = $request->name;
        $fruitbay->price = $request->price;
        $fruitbay->description = $request->description;
        $fruitbay->bag = $request->bag;
        $fruitbay->fruit_bay_category_id = $request->category_id ?? FruitBayCategory::first()->id ?? null;

        if ($request->hasFile('image')) {
            $fruitbay->image && Storage::delete($fruitbay->image ?? '');
            $fruitbay->image = $request->file('image')->storeAs(
                'public/uploads/images',
                rand().'_'.rand().'.'.$request->file('image')->extension()
            );
        }
        $fruitbay->save();

        return $this->buildResponse([
            'message' => $item ? Str::of($fruitbay->name)->append(' Has been updated!') : 'New fruit bay item added.',
            'status' => 'success',
            'response_code' => 200,
            'item' => $fruitbay,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'fruitbay');
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) {
                $item = FruitBay::whereId($item)->first();
                if ($item) {
                    $item->image && Storage::delete($item->image);

                    return $item->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} items bags have been deleted.",
                'status' => 'success',
                'response_code' => 200,
            ]);
        } else {
            $item = FruitBay::whereId($item)->first();
        }

        if ($item) {
            $item->image && Storage::delete($item->image);

            $status = $item->delete();

            return $this->buildResponse([
                'message' => "{$item->name} has been deleted.",
                'status' => 'success',
                'response_code' => 200,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested item no longer exists.',
            'status' => 'error',
            'response_code' => 404,
        ]);
    }
}
