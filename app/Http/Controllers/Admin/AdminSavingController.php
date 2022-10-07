<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Saving;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminSavingController extends Controller
{
    public function index(Request $request, $limit = '15')
    {
        \Gate::authorize('usable', 'savings');
        $query = Saving::query()->with('user');

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('payment_ref', 'like', "%$request->search%")
                    ->orWhere('status', 'like', "%$request->search%")
                    ->orWhere('amount', 'like', "%$request->search%");
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
            'status' => $items->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'items' => $items ?? [],
        ]);
    }

    public function getItem(Request $request, $item)
    {
        \Gate::authorize('usable', 'savings');
        $saving = Saving::whereId($item)->first();

        return $this->buildResponse([
            'message' => ! $saving ? 'The requested saving no longer exists' : 'OK',
            'status' => ! $saving ? 'info' : 'success',
            'response_code' => ! $saving ? 404 : 200,
            'saving' => $saving,
        ]);
    }

    /**
     * Update the saving status
     *
     * @param  Request  $request
     * @param  int  $item
     * @return void
     */
    public function store(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'savings');
        $saving = Saving::find($item);
        if (! $saving) {
            return $this->buildResponse([
                'message' => 'The requested saving no longer exists',
                'status' => 'error',
                'response_code' => 404,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,complete,rejected',
        ]);

        if ($validator->fails()) {
            return $this->buildResponse([
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'response_code' => 422,
                'errors' => $validator->errors(),
            ]);
        }

        $saving->status = $request->status;
        $saving->save();

        return $this->buildResponse([
            'message' => 'Saving status updated.',
            'status' => 'success',
            'response_code' => 200,
            'plan' => $saving,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'savings');
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) {
                $saving = Saving::whereId($item)->first();
                if ($saving) {
                    return $saving->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} savings bags have been deleted.",
                'status' => 'success',
                'response_code' => 200,
            ]);
        } else {
            $saving = Saving::whereId($item)->first();
        }

        if ($saving) {
            return $this->buildResponse([
                'message' => 'Saving has been deleted.',
                'status' => 'success',
                'response_code' => 200,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested savings no longer exists.',
            'status' => 'error',
            'response_code' => 404,
        ]);
    }
}
