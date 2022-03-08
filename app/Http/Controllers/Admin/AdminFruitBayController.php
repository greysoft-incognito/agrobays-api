<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FruitBay;
use App\Models\FruitBayCategory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AdminFruitBayController extends Controller
{
    public function index(Request $request)
    {
        $items = FruitBay::paginate(12);

        return $this-> buildResponse([
            'message' => $items->isEmpty() ? 'No Fruit Bay item has been added' : '',
            'status' => $items->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'items' => $items,
        ]);
    }

    public function getItem(Request $request, $item)
    {
        $item = FruitBay::whereId($item)->orWhere(['slug' => $item])->first();

        return $this-> buildResponse([
            'message' => !$item ? 'The requested item no longer exists' : '',
            'status' =>  !$item ? 'info' : 'success',
            'response_code' => !$item ? 404 : 200,
            'item' => $item,
        ]);
    }

    public function store(Request $request, $item = null)
    {
        $request->validate([
            'name' => 'required|min:3|max:15',
            'price' => 'required|numeric|min:1',
            'description' => 'nullable|min:10|max:150',
        ]);

        $fruitbay = FruitBay::whereId($item)->orWhere(['slug' => $item])->first() ?? new FruitBay;

        $slug = Str::slug($request->name);

        $fruitbay->slug = !$item && FruitBay::whereSlug($slug)->first() ? ($slug . rand()) : ($fruitbay->slug??$slug);
        $fruitbay->name = $request->name;
        $fruitbay->price = $request->price;
        $fruitbay->description = $request->description;
        $fruitbay->fruit_bay_category_id = $request->fruit_bay_category_id??FruitBayCategory::first()->id??null;

        if ($request->image)
        {
            Storage::delete($fruitbay->image);
            $photo = new File($request->image);
            $filename =  rand() . '_' . rand() . '.' . $photo->extension();
            Storage::putFileAs('public/uploads/images', $photo, $filename);
            $fruitbay->image = 'uploads/images/'. $filename;
        }
        $fruitbay->save();

        return $this-> buildResponse([
            'message' => $item ? Str::of($fruitbay->name)->append(' Has been updated!') : 'New fruit bay item added.',
            'status' =>  'success',
            'response_code' => 200,
            'item' => $fruitbay,
        ]);
    }
}