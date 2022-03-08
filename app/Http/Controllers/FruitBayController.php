<?php

namespace App\Http\Controllers;

use App\Models\FruitBay;
use Illuminate\Http\Request;

class FruitBayController extends Controller
{
    public function index(Request $request)
    {
        $items = FruitBay::paginate(12);

        return $this-> buildResponse([
            'message' => $items->isEmpty() ? 'The fruit bay is empty for now' : '',
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
}
