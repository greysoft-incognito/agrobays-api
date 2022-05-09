<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Nette\Utils\Html;

class AdminPlansController extends Controller
{
    public function index(Request $request, $limit = '15')
    {
        \Gate::authorize('usable', 'savings_plans');
        $query = Plan::query();

        // Search and filter columns
        if ($request->search) {
            $query->where(function($query) use($request) {
                $query->where('title', 'like', "%$request->search%")
                    ->orWhere('description', 'like', "%$request->search%");
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

        $items = ($limit <= 0 || $limit === 'all') ? $query->get() : $query->paginate($limit);

        return $this->buildResponse([
            'message' => 'OK',
            'status' =>  $items->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'items' => $items??[],
        ]);
    }

    public function getItem(Request $request, $item)
    {
        \Gate::authorize('usable', 'savings_plans');
        $plan = Plan::whereId($item)->orWhere(['slug' => $item])->first();

        return $this->buildResponse([
            'message' => !$plan ? 'The requested plan no longer exists' : 'OK',
            'status' =>  !$plan ? 'info' : 'success',
            'response_code' => !$plan ? 404 : 200,
            'plan' => $plan,
        ]);
    }

    public function store(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'savings_plans');
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'min:3', 'max:25', Rule::unique('plans')->ignore($item)],
            'amount' => 'required|numeric|min:1',
            'duration' => 'required|numeric|min:1',
            'icon' => 'nullable|string',
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

        $plan = Plan::whereId($item)->orWhere(['slug' => $item])->first() ?? new Plan;

        $plan->title = $request->title;
        $plan->amount = $request->amount;
        $plan->icon = $request->icon;
        $plan->duration = $request->duration;
        $plan->description = $request->description;
        $plan->status = $request->status ?? true;

        if ($request->image)
        {
            $plan->image && Storage::delete($plan->image??'');
            $plan->image = $request->file('image')->storeAs(
                'public/uploads/images', rand() . '_' . rand() . '.' . $request->file('image')->extension()
            );
        }
        $plan->save();

        return $this->buildResponse([
            'message' => $item ? Str::of($plan->title)->append(' Has been updated!') : 'New plan has been created.',
            'status' =>  'success',
            'response_code' => 200,
            'plan' => $plan,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'savings_plans');
        if ($request->items)
        {
            $count = collect($request->items)->map(function($item) {
                $plan = Plan::whereId($item)->first();
                if ($plan) {
                    $plan->image && Storage::delete($plan->image);
                    return $plan->delete();
                }
                return false;
            })->filter(fn($i)=>$i!==false)->count();

            return $this->buildResponse([
                'message' => "{$count} plans have been deleted.",
                'status' =>  'success',
                'response_code' => 200,
            ]);
        }
        else
        {
            $plan = Plan::whereId($item)->first();
        }

        if ($plan)
        {
            $plan->image && Storage::delete($plan->image);
            $plan->delete();

            return $this->buildResponse([
                'message' => "{$plan->title} has been deleted.",
                'status' =>  'success',
                'response_code' => 200,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested plan no longer exists.',
            'status' => 'error',
            'response_code' => 404,
        ]);
    }
}
