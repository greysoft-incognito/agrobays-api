<?php

namespace App\Http\Controllers;

use App\Models\FrontContent;
use Illuminate\Http\Request;

class FrontContentController extends Controller
{
    /**
     * Display a listing of all front content based on the type.
     *
     * @param \Illuminate\Http\Request  $request
     * @param  String $type
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $type = 'faq', $limit = '15')
    {
        $query = FrontContent::query();

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        $content = $limit <= 0 ? $query->get() : $query->paginate($limit);

        return $this->buildResponse([
            'message' => 'OK',
            'status' =>  $content->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'content' => $content??[],
        ]);
    }
}
