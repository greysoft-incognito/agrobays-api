<?php

namespace App\Http\Controllers;

use App\Http\Resources\FruitbayCategoryCollection;
use App\Http\Resources\FruitbayCategoryResource;
use App\Http\Resources\FruitbayCollection;
use App\Http\Resources\FruitbayResource;
use App\Models\FruitBay;
use App\Models\FruitBayCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FruitBayController extends Controller
{
    /**
     * Get a list of all fruitbay items
     *
     * @param  Request  $request
     * @param  string|int|null  $category
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function index(Request $request, $category = null)
    {
        $get_cat = (((request()->segment(1) !== 'api' && request()->segment(2) === 'category') ||
            request()->segment(3) === 'category')
            || $category);
        if ($get_cat) {
            $getCategory = FruitBayCategory::where(['id' => $category])->orWhere(['slug' => $category])->first();
            if (! $getCategory) {
                return $this->buildResponse([
                    'message' => 'This category does not exist',
                    'status' => 'error',
                    'response_code' => 404,
                ]);
            }
            $items = FruitBay::with('category')->where(['fruit_bay_category_id' => $getCategory->id])->paginate(12);
        } else {
            $items = FruitBay::with('category')->paginate(12);
        }

        return (new FruitbayCollection($items))->additional([
            'message' => $items->isEmpty() ? 'The fruit bay is empty for now' : 'OK',
            'status' => $items->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'category' => $get_cat ? new FruitbayCategoryResource($getCategory) : null,
        ])->response()->setStatusCode(200);
    }

    /**
     * Search for fruitbay items
     *
     * @param  Request  $request
     * @param  string|int|null  $category
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function search(Request $request)
    {
        $query = FruitBay::where('name', 'like', "%{$request->q}%");
        $query->with('category');

        if (in_array($request->paginate, [true, 'true'], true)) {
            $search = $query->paginate($request->limit ?? 15);
        } else {
            if ($request->limit && $request->limit > 0) {
                $query->limit($request->limit);
            }
            $search = $query->get();
        }

        return (new FruitbayCollection($search))->additional([
            'message' => $search->isEmpty() ? "\"{$request->q}\" not found." : 'OK',
            'status' => $search->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'ignore' => [404],
            'found' => $search->count(),
        ])->response()->setStatusCode(200);
    }

    /**
     * Get a particular fruit bay item by it's {id}
     *
     * @param  Request  $request
     * @param  string|int  $item
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function getItem(Request $request, $item)
    {
        $item = FruitBay::whereId($item)->orWhere(['slug' => $item])->first();
        $item->load('category');

        return (new FruitbayResource($item))->additional([
            'message' => ! $item ? 'The requested item no longer exists' : 'OK',
            'status' => ! $item ? 'error' : 'success',
            'response_code' => ! $item ? 404 : 200,
        ])->response()->setStatusCode(! $item ? 404 : 200);
    }

    /**
     * Process payment for the selected fruit bay item
     *
     * @param  Request  $request
     * @param  string|int  $item
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function buyItem(Request $request, $item)
    {
        // $item = FruitBay::whereId($item)->orWhere(['slug' => $item])->first();

        // if (! $item) {
        //     return $this->buildResponse([
        //         'message' => 'The requested item no longer exists',
        //         'status' => 'error',
        //         'response_code' => 404,
        //     ]);
        // }

        // $trans = $item->transaction();
        // $transaction = $trans->create([
        //     'user_id' => Auth::id(),
        //     'reference' => config('settings.trx_prefix', 'AGB-') . Str::random(12),
        //     'method' => 'direct',
        //     'amount' => $item->price,
        //     'due' => $item->price,
        // ]);

        // return $this->buildResponse([
        //     'message' => 'Transaction successful',
        //     'status' => 'success',
        //     'response_code' => 200,
        //     'response_data' => [[]],
        //     'transaction' => $transaction,
        // ]);
    }

    /**
     * Get a list of all fruitbay categories and optionally find a category by it's {id}
     *
     * @param  Request  $request
     * @param  string|int|null  $category
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function categories(Request $request, $category = null)
    {
        if ($category) {
            $item = FruitBayCategory::whereId($category)->orWhere(['slug' => $category])->first();

            return $this->buildResponse([
                'message' => ! $item ? 'The requested category no longer exists.' : 'OK',
                'status' => ! $item ? 'info' : 'success',
                'response_code' => ! $item ? 404 : 200,
                'item' => $item,
            ]);
        }

        $items = FruitBayCategory::get();

        return (new FruitbayCategoryCollection($items))->additional([
            'message' => $items->isEmpty() ? 'There are no categories for now.' : 'OK',
            'status' => $items->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
        ])->response()->setStatusCode(200);
    }
}
