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
    public function index(Request $request, $limit = '15', $type = 'all')
    {
        $query = FrontContent::query();

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        // Search and filter columns
        if ($request->search) {
            $query->where(function($query) use($request) {
                $query->where('title', 'like', "%$request->search%")
                    ->orWhere('type', 'like', "%$request->search%")
                    ->orWhere('content', 'like', "%$request->search%");
            });
        }

        // Reorder Columns
        if ($request->order && is_array($request->order)) {
            foreach ($request->order as $key => $dir) {
                if ($dir === 'desc') {
                    $query->orderByDesc($key??'id');
                } else {
                    $query->orderBy($key??'id');
                }
            }
        }

        $content = ($limit <= 0 || $limit === 'all') ? $query->get() : $query->paginate($limit);

        return $this->buildResponse([
            'message' => 'OK',
            'status' =>  $content->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'contents' => $content??[],
        ]);
    }

    public function getContent(Request $request, $item)
    {
        $content = FrontContent::whereId($item)->orWhere('slug', $item)->first();

        return $this->buildResponse([
            'message' => !$content ? 'The requested content no longer exists' : 'OK',
            'status' =>  !$content ? 'info' : 'success',
            'response_code' => !$content ? 404 : 200,
            'content' => $content ?? (object)[],
        ]);
    }
}
