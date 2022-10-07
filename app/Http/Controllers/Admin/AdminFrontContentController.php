<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FrontContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminFrontContentController extends Controller
{
    /**
     * Display a listing of all front content based on the type.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $type
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $limit = '15', $type = 'faq')
    {
        \Gate::authorize('usable', 'content');
        $query = FrontContent::query();

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('title', 'like', "%$request->search%")
                    ->orWhere('type', 'like', "%$request->search%")
                    ->orWhere('content', 'like', "%$request->search%");
            });
        }

        // Reorder Columns
        if ($request->order && is_array($request->order)) {
            foreach ($request->order as $key => $dir) {
                if ($dir === 'desc') {
                    $query->orderByDesc($key ?? 'id');
                } else {
                    $query->orderBy($key ?? 'id');
                }
            }
        }

        $content = ($limit <= 0 || $limit === 'all') ? $query->get() : $query->paginate($limit);

        return $this->buildResponse([
            'message' => 'OK',
            'status' => $content->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'contents' => $content ?? [],
        ]);
    }

    public function getContent(Request $request, $item)
    {
        \Gate::authorize('usable', 'content');
        $content = FrontContent::find($item);

        return $this->buildResponse([
            'message' => ! $content ? 'The requested content no longer exists' : 'OK',
            'status' => ! $content ? 'info' : 'success',
            'response_code' => ! $content ? 404 : 200,
            'content' => $content ?? (object) [],
        ]);
    }

    public function store(Request $request, $item = '')
    {
        \Gate::authorize('usable', 'content');
        $content = FrontContent::find($item);
        if ($item && ! $content) {
            return $this->buildResponse([
                'message' => 'The requested content no longer exists',
                'status' => 'info',
                'response_code' => 404,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'min:3', 'max:150'],
            'type' => 'required|string',
            'image' => 'nullable|mimes:png,jpg',
            'content' => 'required|min:10|max:550',
        ], );

        if ($validator->fails()) {
            return $this->buildResponse([
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'response_code' => 422,
                'errors' => $validator->errors(),
            ]);
        }

        $content = $content ?? new FrontContent;

        $content->title = $request->title;
        $content->type = $request->type;
        $content->content = $request->content;

        if ($request->hasFile('image')) {
            $content->image && Storage::delete($content->image ?? '');
            $content->image = $request->file('image')->storeAs(
                'public/uploads/images', rand().'_'.rand().'.'.$request->file('image')->extension()
            );
        }
        $content->save();

        return $this->buildResponse([
            'message' => $item ? Str::of($content->title)->append(' Has been updated!') : 'New content has been created.',
            'status' => 'success',
            'response_code' => 200,
            'content' => $content,
        ]);
    }

    /**
     * Remove the specified content from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'content');
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) {
                $content = FrontContent::whereId($item)->first();
                if ($content) {
                    $content->image && Storage::delete($content->image);

                    return $content->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} contents have been deleted.",
                'status' => 'success',
                'response_code' => 200,
            ]);
        } else {
            $content = FrontContent::whereId($item)->first();
        }

        if ($content) {
            $content->delete();

            return $this->buildResponse([
                'message' => "{$content->title} has been deleted.",
                'status' => 'success',
                'response_code' => 200,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested content no longer exists.',
            'status' => 'error',
            'response_code' => 404,
        ]);
    }
}
