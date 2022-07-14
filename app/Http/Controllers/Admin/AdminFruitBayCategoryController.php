<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FruitBayCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminFruitBayCategoryController extends Controller
{
    public function index(Request $request, $limit = '15')
    {
        $query = FruitBayCategory::query();

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('title', 'like', "%$request->search%")
                    ->orWhere('description', 'like', "%$request->search%");
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

        $items = ($limit <= 0 || $limit === 'all') ? $query->get() : $query->paginate($limit);

        return $this->buildResponse([
            'message' => 'OK',
            'status' =>  $items->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'items' => $items ?? [],
        ]);
    }

    public function getItem(Request $request, $item)
    {
        $item = FruitBayCategory::whereId($item)->orWhere(['slug' => $item])->first();

        return $this->buildResponse([
            'message' => ! $item ? 'The requested category no longer exists.' : 'OK',
            'status' =>  ! $item ? 'info' : 'success',
            'response_code' => ! $item ? 404 : 200,
            'item' => $item ?? (object) [],
        ]);
    }

    public function store(Request $request, $item = null)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|min:3|max:25',
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

        $category = FruitBayCategory::whereId($item)->orWhere(['slug' => $item])->first() ?? new FruitBayCategory();
        $category->title = $request->title;
        $category->description = $request->description;

        if ($request->hasFile('image')) {
            $category->image && Storage::delete($category->image ?? '');
            $category->image = $request->file('image')->storeAs(
                'public/uploads/images', rand().'_'.rand().'.'.$request->file('image')->extension()
            );
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
    public function destroy(Request $request, $item = null)
    {
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) {
                $item = FruitBayCategory::whereId($item)->first();
                if ($item) {
                    $item->image && Storage::delete($item->image);

                    return $item->delete();
                }

                return false;
            })->filter(fn ($i) =>$i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} fruit bay categories have been deleted.",
                'status' =>  'success',
                'response_code' => 200,
            ]);
        } else {
            $item = FruitBayCategory::whereId($item)->first();
        }

        if ($item) {
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
