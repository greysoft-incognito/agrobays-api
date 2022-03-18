<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FruitBayCategory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminFruitBayCategoryController extends Controller
{
    public function index(Request $request)
    {
        $items = FruitBayCategory::paginate(15);

        return $this->buildResponse([
            'message' => $items->isEmpty() ? 'No categories have been added.' : '',
            'status' => $items->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'items' => $items,
        ]);
    }

    public function getItem(Request $request, $item)
    {
        $item = FruitBayCategory::whereId($item)->orWhere(['slug' => $item])->first();

        return $this->buildResponse([
            'message' => !$item ? 'The requested category no longer exists.' : '',
            'status' =>  !$item ? 'info' : 'success',
            'response_code' => !$item ? 404 : 200,
            'item' => $item,
        ]);
    }

    public function store(Request $request, $item = null)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|min:3|max:15',
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

        $category = FruitBayCategory::whereId($item)->orWhere(['slug' => $item])->first() ?? new FruitBayCategory();
        $category->title = $request->title;
        $category->description = $request->description;

        if ($request->image)
        {
            Storage::delete($category->image);
            $photo = new File($request->image);
            $filename =  rand() . '_' . rand() . '.' . $photo->extension();
            Storage::putFileAs('public/uploads/images', $photo, $filename);
            $category->image = 'uploads/images/'. $filename;
        }
        $category->save();

        return $this->buildResponse([
            'message' => $item ? Str::of($category->title)->append(' Has been updated!') : 'New category item created.',
            'status' =>  'success',
            'response_code' => 200,
            'item' => $category,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\FruitBayCategory  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy($item = null)
    {
        $item = FruitBayCategory::whereId($item)->first();

        if ($item)
        {
            $item->image && Storage::delete($item->image);

            $status = $item->delete();

            return $this->buildResponse([
                'message' => "{$item->title} has been deleted.",
                'status' =>  'success',
                'response_code' => 200,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested category no longer exists.',
            'status' => 'error',
            'response_code' => 404,
        ]);
    }
}