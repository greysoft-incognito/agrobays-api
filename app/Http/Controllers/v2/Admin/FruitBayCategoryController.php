<?php

namespace App\Http\Controllers\v2\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\FruitbayCategoryCollection;
use App\Http\Resources\FruitbayCategoryResource;
use App\Models\FruitBayCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FruitBayCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        \Gate::authorize('usable', 'fruitbay');

        $query = FruitBayCategory::query()
            ->when($request->has('q'), function (Builder $q) use ($request) {
                $q->where('name', 'like', "%{$request->q}%");
            })
            ->when($request->has('desc') && $request->has('sortBy'), function (Builder $q) use ($request) {
                if ($request->boolean('desc')) {
                    $q->orderByDesc($request->sortBy);
                } else {
                    $q->orderBy($request->sortBy);
                }
            });

        $categories = $query->paginate($request->get('limit', 15));

        return (new FruitbayCategoryCollection($categories))->additional([
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
        \Gate::authorize('usable', 'fruitbay');

        $this->validate($request, [
            'title' => 'required|min:3|max:25',
            'description' => 'nullable|min:10|max:550',
        ]);

        // Validate image if it is present and not a string
        if ($request->hasFile('image')) {
            $this->validate($request, [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
        }

        $category = new FruitBayCategory();

        $category->title = $request->title;
        $category->description = $request->description;

        $category->save();

        return (new FruitbayCategoryResource($category))->additional([
            'message' => 'Category has been created.',
            'status' => 'success',
            'response_code' => HttpStatus::CREATED,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(FruitBayCategory $category)
    {
        \Gate::authorize('usable', 'fruitbay');

        return (new FruitbayCategoryResource($category))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\FruitBayCategory  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, FruitBayCategory $category)
    {
        \Gate::authorize('usable', 'fruitbay');

        $this->validate($request, [
            'title' => 'required|min:3|max:25',
            'description' => 'nullable|min:10|max:550',
        ]);

        // Validate image if it is present and not a string
        if ($request->hasFile('image')) {
            $this->validate($request, [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
        }

        $category->title = $request->title;
        $category->description = $request->description;

        $category->save();

        return (new FruitbayCategoryResource($category))->additional([
            'message' => 'Category has been updated!',
            'status' => 'success',
            'response_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        \Gate::authorize('usable', 'fruitbay');

        if ($request->items) {
            $count = collect($request->items)->map(function ($item) {
                $item = FruitBayCategory::whereId($item)->first();
                if ($item) {
                    return $item->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->responseBuilder([
                'message' => "{$count} categories have been deleted.",
                'status' => 'success',
                'response_code' => HttpStatus::OK,
            ]);
        } else {
            $item = FruitBayCategory::whereId($id)->firstOrFail();

            $status = $item->delete();

            return $this->responseBuilder([
                'message' => "{$item->name} has been deleted.",
                'status' => 'success',
                'response_code' => HttpStatus::OK,
            ]);
        }
    }
}
