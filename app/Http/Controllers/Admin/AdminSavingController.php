<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Saving;
use App\Models\Transaction;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Nette\Utils\Html;

class AdminSavingController extends Controller
{
    public function index(Request $request)
    {
        $model = Saving::query();
        return app('datatables')->eloquent($model)
            ->editColumn('created_at', function(Saving $item) {
                return $item->created_at->format('Y-m-d H:i');
            })
            ->addColumn('action', function (Saving $item) {
                return implode([
                    Html::el('a')->title(__('Edit'))->href('transactions/invoice/'.$item->id)->setHtml(Html::el('i')->class('ri-edit-circle-fill ri-2x text-primary')),
                    Html::el('a')->title(__('Delete'))->href('transactions/invoice/'.$item->id)->setHtml(Html::el('i')->class('ri-delete-bin-2-fill ri-2x text-primary'))
                ]);
            })
            ->removeColumn('updated_at')->toJson();

        // $saving = Saving::paginate(15);

        // return $this->buildResponse([
        //     'message' => $saving->isEmpty() ? 'No food has been created' : '',
        //     'status' => $saving->isEmpty() ? 'info' : 'success',
        //     'response_code' => 200,
        //     'saving' => $saving,
        // ]);
    }

    public function getItem(Request $request, $item)
    {
        $saving = Saving::whereId($item)->first();

        return $this->buildResponse([
            'message' => !$saving ? 'The requested saving no longer exists' : 'OK',
            'status' =>  !$saving ? 'info' : 'success',
            'response_code' => !$saving ? 404 : 200,
            'saving' => $saving,
        ]);
    }

    /**
     * Update the saving status
     *
     * @param Request $request
     * @param integer $item
     * @return void
     */
    public function store(Request $request, $item = null)
    {
        $saving = Saving::find($item);
        if (!$saving) {
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
            'status' =>  'success',
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
        if ($request->items)
        {
            $count = collect($request->items)->map(function($item) {
                $saving = Saving::whereId($item)->first();
                if ($saving) {
                    return $saving->delete();
                }
                return false;
            })->filter(fn($i)=>$i!==false)->count();

            return $this->buildResponse([
                'message' => "{$count} savings bags have been deleted.",
                'status' =>  'success',
                'response_code' => 200,
            ]);
        }
        else
        {
            $saving = Saving::whereId($item)->first();
        }

        if ($saving)
        {
            return $this->buildResponse([
                'message' => "Saving has been deleted.",
                'status' =>  'success',
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