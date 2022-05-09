<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Food;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Nette\Utils\Html;

class AdminFoodsController extends Controller
{
    public function index(Request $request, $limit = '15')
    {
        \Gate::authorize('usable', 'foods');
        $query = Food::query();

        // Search and filter columns
        if ($request->search) {
            $query->where(function($query) use($request) {
                $query->where('name', 'like', "%$request->search%")
                    ->orWhere('description', 'like', "%$request->search%");
            });
        }

        // Reorder Columns
        if ($request->order && is_array($request->order)) {
            foreach ($request->order as $key => $dir) {
                if ($dir === 'desc') {
                    $query->orderByDesc($key??'id');
                } else {
                    $query->orderBy($key??'id');
                }
            }
        }

        $items = ($limit <= 0 || $limit === 'all') ? $query->get() : $query->paginate($limit);

        return $this->buildResponse([
            'message' => 'OK',
            'status' =>  $items->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'items' => $items??[],
        ]);
    }

    public function getItem(Request $request, $item)
    {
        \Gate::authorize('usable', 'foods');
        $food = Food::whereId($item)->first();

        return $this->buildResponse([
            'message' => !$food ? 'The requested food no longer exists' : 'OK',
            'status' =>  !$food ? 'info' : 'success',
            'response_code' => !$food ? 404 : 200,
            'food' => $food,
        ]);
    }

    public function store(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'foods');
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:25', Rule::unique('foods')->ignore($item),
            'food_bag_id' => 'required|numeric|min:1',
            'weight' => 'required|string|min:1',
            'image' => 'nullable|mimes:jpg,jpeg,png',
            'description' => 'nullable|min:10|max:550',
        ], [], [
            'food_bag_id' => 'Food Bag'
        ]);

        if ($validator->fails()) {
            return $this->buildResponse([
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'response_code' => 422,
                'errors' => $validator->errors(),
            ]);
        }

        $food = Food::whereId($item)->first() ?? new Food;

        $food->name = $request->name;
        $food->food_bag_id = $request->food_bag_id;
        $food->weight = $request->weight ?? $food->weight ?? '';
        $food->image = $request->image ?? $food->image ?? '';
        $food->description = $request->description;

        if ($request->hasFile('image'))
        {
            $food->image && Storage::delete($food->image??'');
            $food->image = $request->file('image')->storeAs(
                'public/uploads/images', rand() . '_' . rand() . '.' . $request->file('image')->extension()
            );
        }
        $food->save();

        return $this->buildResponse([
            'message' => $item ? Str::of($food->name)->append(' Has been updated!') : 'New food has been added.',
            'status' =>  'success',
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
        if ($request->items)
        {
            $count = collect($request->items)->each(function($item) {
                $food = Food::whereId($item)->first();
                if ($food) {
                    $food->image && Storage::delete($food->image);
                    return $food->delete();
                }
                return false;
            })->filter(fn($i)=>$i!==false)->count();

            return $this->buildResponse([
                'message' => "{$count} foods have been deleted.",
                'status' =>  'success',
                'response_code' => 200,
            ]);
        }
        else
        {
            $food = Food::whereId($item)->first();
        }

        if ($food)
        {
            $food->image && Storage::delete($food->image);

            $food->delete();

            return $this->buildResponse([
                'message' => "{$food->name} has been deleted.",
                'status' =>  'success',
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
