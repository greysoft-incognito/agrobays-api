<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FoodBag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Nette\Utils\Html;
use Illuminate\Validation\Rule;

class AdminFoodbagsController extends Controller
{
    public function index(Request $request, $limit = '15')
    {
        \Gate::authorize('usable', 'foodbags');
        $query = FoodBag::query()->with('plan');

        // Search and filter columns
        if ($request->search) {
            $query->where(function($query) use($request) {
                $query->where('title', 'like', "%$request->search%")
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
        \Gate::authorize('usable', 'foodbags');
        $bag = FoodBag::find($item);

        return $this->buildResponse([
            'message' => !$bag ? 'The requested foodbag no longer exists' : 'OK',
            'status' =>  !$bag ? 'info' : 'success',
            'response_code' => !$bag ? 404 : 200,
            'bag' => $bag ?? (object)[],
        ]);
    }

    public function store(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'foodbags');
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'min:3', 'max:25', Rule::unique('food_bags')->ignore($item)],
            'plan_id' => 'required|numeric',
            'description' => 'nullable|min:10|max:550',
        ], [], [
            'plan_id' => 'Plan'
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

        return $this->buildResponse([
            'message' => $item ? Str::of($bag->title)->append(' Has been updated!') : 'New foodbag has been created.',
            'status' =>  'success',
            'response_code' => 200,
            'bag' => $bag,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'foodbags');
        if ($request->items)
        {
            $count = collect($request->items)->map(function($item) {
                $bag = FoodBag::whereId($item)->first();
                if ($bag) {
                    $bag->image && Storage::delete($bag->image);
                    return $bag->delete();
                }
                return false;
            })->filter(fn($i)=>$i!==false)->count();

            return $this->buildResponse([
                'message' => "{$count} foods bags have been deleted.",
                'status' =>  'success',
                'response_code' => 200,
            ]);
        }
        else
        {
            $bag = FoodBag::whereId($item)->first();
        }

        if ($bag)
        {
            $bag->delete();

            return $this->buildResponse([
                'message' => "{$bag->title} has been deleted.",
                'status' =>  'success',
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