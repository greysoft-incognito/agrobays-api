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
    public function index(Request $request)
    {
        $model = Food::query();
        return app('datatables')->eloquent($model)
            ->editColumn('created_at', function(Food $item) {
                return $item->created_at->format('Y-m-d H:i');
            })
            ->editColumn('description', function(Food $item) {
                return Str::words($item->description, '8');
            })
            ->addColumn('action', function (Food $item) {
                return implode([
                    Html::el('a', ["onclick"=>"hotLink('/admin/edit-food/".$item->id."')", "href"=>"javascript:void(0)"])->title(__('Edit'))->setHtml(Html::el('i')->class('ri-edit-circle-fill ri-2x text-primary')),
                    Html::el('a', ["onclick"=>"hotLink('/admin/food/delete/".$item->id."')", "href"=>"javascript:void(0)"])->title(__('Delete'))->setHtml(Html::el('i')->class('ri-delete-bin-2-fill ri-2x text-negative'))
                ]);
            })
            ->removeColumn('updated_at')->toJson();

        // $foods = Plan::paginate(15);

        // return $this->buildResponse([
        //     'message' => $foods->isEmpty() ? 'No food has been created' : '',
        //     'status' => $foods->isEmpty() ? 'info' : 'success',
        //     'response_code' => 200,
        //     'foods' => $foods,
        // ]);
    }

    public function getItem(Request $request, $item)
    {
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
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:15', Rule::unique('foods')->ignore($item),
            'food_bag_id' => 'required|numeric|min:1',
            'weight' => 'nullable|string|min:1',
            'image' => 'nullable|mimes:jpg,jpeg,png',
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
    public function destroy($item = null)
    {
        $food = Food::whereId($item)->first();

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
