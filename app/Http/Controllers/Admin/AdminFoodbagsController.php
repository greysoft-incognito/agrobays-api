<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FoodBag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class AdminFoodbagsController extends Controller
{
    public function index(Request $request)
    {
        $model = FoodBag::query();
        return app('datatables')->eloquent($model)
            ->editColumn('created_at', function(FoodBag $item) {
                return $item->created_at->format('Y-m-d H:i');
            })
            ->editColumn('description', function(FoodBag $item) {
                return Str::words($item->description, '8');
            })
            ->addColumn('action', function (FoodBag $item) {
                return '<a href="#edit-'.$item->id.'" class="btn btn-xs btn-primary"><i class="fa fa-pen-alt"></i> Edit</a>';
            })
            ->removeColumn('updated_at')->toJson();

        // $bags = FoodBag::paginate(15);

        // return $this->buildResponse([
        //     'message' => $bags->isEmpty() ? 'No foodbag has been created' : '',
        //     'status' => $bags->isEmpty() ? 'info' : 'success',
        //     'response_code' => 200,
        //     'bags' => $bags,
        // ]);
    }

    public function getItem(Request $request, $item)
    {
        $bag = FoodBag::find($item);

        return $this->buildResponse([
            'message' => !$bag ? 'The requested foodbag no longer exists' : '',
            'status' =>  !$bag ? 'info' : 'success',
            'response_code' => !$bag ? 404 : 200,
            'bag' => $bag,
        ]);
    }

    public function store(Request $request, $item = null)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|min:3|max:15|unique:food_bags',
            'description' => 'nullable|min:10|max:150',
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
    public function destroy($item = null)
    {
        $bag = FoodBag::whereId($item)->first();

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
