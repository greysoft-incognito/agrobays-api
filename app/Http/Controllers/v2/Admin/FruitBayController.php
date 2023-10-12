<?php

namespace App\Http\Controllers\v2\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\FruitbayCollection;
use App\Http\Resources\FruitbayResource;
use App\Models\FruitBay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FruitBayController extends Controller
{
    public function validate(Request $request, $rules = [], $messages = [], $customAttributes = []): array
    {
        $validator = \Illuminate\Support\Facades\Validator::make(
            $request->all(),
            array_merge([
                'bag' => 'nullable|array|max:10',
                'unit' => 'nullable|string|required_with:weight',
                'fees' => 'nullable|numeric|min:0',
                'name' => 'required|min:3|max:25',
                'price' => 'required|numeric|min:1',
                'weight' => 'nullable|numeric|min:0',
                'description' => 'required|min:10|max:550',
                'category_id' => 'required|exists:fruit_bay_categories,id',
                'available' => 'nullable|boolean',
            ], $rules),
            $messages,
            array_merge([
                'fees' => 'Shipping/Handling Fees',
                'category_id' => 'Category',
            ], $customAttributes),
        );

        // Validate image if it is present and not a string
        if ($request->hasFile('image')) {
            $this->validate($request, [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
        }

        return $validator->validate();
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        \Gate::authorize('usable', 'fruitbay');

        $query = FruitBay::where('available', true)
            ->when($request->has('category_id'), function ($q) use ($request) {
                $q->whereHas('category', function (Builder $q) use ($request) {
                    $q->where('id', $request->category_id);
                    $q->orWhere('slug', $request->category_id);
                });
            })
            ->when($request->has('desc') && $request->has('sortBy'), function (Builder $q) use ($request) {
                if ($request->boolean('desc')) {
                    $q->orderByDesc($request->sortBy);
                } else {
                    $q->orderBy($request->sortBy);
                }
            })
            ->when($request->has('q'), function (Builder $q) use ($request) {
                $q->where('name', 'like', "%{$request->q}%");
                $q->orWhereFullText('description', $request->q);
                $q->orWhereHas('category', function (Builder $q) use ($request) {
                    $q->where('name', 'like', "%{$request->q}%");
                });
            });

        $items = $query->paginate($request->get('limit', 15));

        return (new FruitbayCollection($items))->additional([
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

        $this->validate($request);

        $fruitbay = new FruitBay();

        $fruitbay->bag = $request->bag;
        $fruitbay->fees = $request->fees ?? 0.00;
        $fruitbay->name = $request->name;
        $fruitbay->unit = $request->unit ?? 'kg';
        $fruitbay->price = $request->price;
        $fruitbay->weight = $request->weight ?? 0.00;
        $fruitbay->available = $request->available ?? true;
        $fruitbay->description = $request->description;
        $fruitbay->fruit_bay_category_id = $request->category_id;

        $fruitbay->save();

        return (new FruitbayResource($fruitbay))->additional([
            'message' => 'New fruit bay item added.',
            'status' => 'success',
            'response_code' => HttpStatus::CREATED,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\FruitBay  $fruitbay
     * @return \Illuminate\Http\Response
     */
    public function show(FruitBay $fruitbay)
    {
        \Gate::authorize('usable', 'fruitbay');

        return (new FruitbayResource($fruitbay))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\FruitBay  $fruitbay
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, FruitBay $fruitbay)
    {
        \Gate::authorize('usable', 'fruitbay');

        $this->validate($request, [
            'category_id' => 'nullable|exists:fruit_bay_categories,id',
        ]);

        $fruitbay->bag = $request->bag ?? $fruitbay->bag;
        $fruitbay->fees = $request->fees ?? $fruitbay->fees;
        $fruitbay->name = $request->name ?? $fruitbay->name;
        $fruitbay->unit = $request->unit ?? $fruitbay->unit;
        $fruitbay->price = $request->price ?? $fruitbay->price;
        $fruitbay->weight = $request->weight ?? $fruitbay->weight;
        $fruitbay->available = $request->available ?? $fruitbay->available;
        $fruitbay->description = $request->description ?? $fruitbay->description;
        $fruitbay->fruit_bay_category_id = $request->category_id ?? $fruitbay->fruit_bay_category_id;

        $fruitbay->save();

        return (new FruitbayResource($fruitbay))->additional([
            'message' => 'Fruit bay item updated!',
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
                $item = FruitBay::whereId($item)->first();
                if ($item) {
                    return $item->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->responseBuilder([
                'message' => "{$count} items have been deleted.",
                'status' => 'success',
                'response_code' => HttpStatus::OK,
            ]);
        } else {
            $item = FruitBay::whereId($id)->firstOrFail();

            $status = $item->delete();

            return $this->responseBuilder([
                'message' => "{$item->name} has been deleted.",
                'status' => 'success',
                'response_code' => HttpStatus::OK,
            ]);
        }
    }
}