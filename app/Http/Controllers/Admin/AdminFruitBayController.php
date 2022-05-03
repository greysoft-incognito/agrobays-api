<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FruitBay;
use App\Models\FruitBayCategory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Nette\Utils\Html;

class AdminFruitBayController extends Controller
{
    public function index(Request $request)
    {
        $model = FruitBay::query();
        return app('datatables')->eloquent($model)
            ->editColumn('created_at', function(FruitBay $item) {
                return $item->created_at->format('Y-m-d H:i');
            })
            ->editColumn('description', function(FruitBay $item) {
                return Str::words($item->description, '8');
            })
            ->addColumn('action', function (FruitBay $item) {
                return implode([
                    Html::el('a')->title(__('Edit'))->href('transactions/invoice/'.$item->id)->setHtml(Html::el('i')->class('ri-edit-circle-fill ri-2x text-primary')),
                    Html::el('a')->title(__('Delete'))->href('transactions/invoice/'.$item->id)->setHtml(Html::el('i')->class('ri-delete-bin-2-fill ri-2x text-primary'))
                ]);
            })
            ->removeColumn('updated_at')->toJson();

        // $items = FruitBay::paginate(15);

        // return $this->buildResponse([
        //     'message' => $items->isEmpty() ? 'No Fruit Bay item has been added' : '',
        //     'status' => $items->isEmpty() ? 'info' : 'success',
        //     'response_code' => 200,
        //     'items' => $items,
        // ]);
    }

    public function getItem(Request $request, $item)
    {
        $item = FruitBay::whereId($item)->orWhere(['slug' => $item])->first();

        return $this->buildResponse([
            'message' => !$item ? 'The requested item no longer exists' : 'OK',
            'status' =>  !$item ? 'info' : 'success',
            'response_code' => !$item ? 404 : 200,
            'item' => $item,
        ]);
    }

    public function store(Request $request, $item = null)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:25',
            'price' => 'required|numeric|min:1',
            'description' => 'nullable|min:10|max:550',
        ]);

        if ($validator->fails()) {
            return $this->buildResponse([
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'response_code' => 422,
                'errors' => $validator->errors(),
            ]);
        }

        $fruitbay = FruitBay::whereId($item)->orWhere(['slug' => $item])->first() ?? new FruitBay;

        $fruitbay->name = $request->name;
        $fruitbay->price = $request->price;
        $fruitbay->description = $request->description;
        $fruitbay->fruit_bay_category_id = $request->category_id??FruitBayCategory::first()->id??null;

        if ($request->hasFile('image'))
        {
            $fruitbay->image && Storage::delete($fruitbay->image??'');
            $fruitbay->image = $request->file('image')->storeAs(
                'public/uploads/images', rand() . '_' . rand() . '.' . $request->file('image')->extension()
            );
        }
        $fruitbay->save();

        return $this->buildResponse([
            'message' => $item ? Str::of($fruitbay->name)->append(' Has been updated!') : 'New fruit bay item added.',
            'status' =>  'success',
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
        if ($request->items)
        {
            $count = collect($request->items)->map(function($item) {
                $item = FruitBay::whereId($item)->first();
                if ($item) {
                    $item->image && Storage::delete($item->image);
                    return $item->delete();
                }
                return false;
            })->filter(fn($i)=>$i!==false)->count();

            return $this->buildResponse([
                'message' => "{$count} items bags have been deleted.",
                'status' =>  'success',
                'response_code' => 200,
            ]);
        }
        else
        {
            $item = FruitBay::whereId($item)->first();
        }

        if ($item)
        {
            $item->image && Storage::delete($item->image);

            $status = $item->delete();

            return $this->buildResponse([
                'message' => "{$item->name} has been deleted.",
                'status' =>  'success',
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
