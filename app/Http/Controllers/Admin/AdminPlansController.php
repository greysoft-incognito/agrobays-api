<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminPlansController extends Controller
{
    public function index(Request $request)
    {
        $plans = Plan::paginate(15);

        return $this->buildResponse([
            'message' => $plans->isEmpty() ? 'No savings plan has been created' : '',
            'status' => $plans->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'plans' => $plans,
        ]);
    }

    public function getItem(Request $request, $item)
    {
        $plan = Plan::whereId($item)->orWhere(['slug' => $item])->first();

        return $this->buildResponse([
            'message' => !$plan ? 'The requested plan no longer exists' : '',
            'status' =>  !$plan ? 'info' : 'success',
            'response_code' => !$plan ? 404 : 200,
            'plan' => $plan,
        ]);
    }

    public function store(Request $request, $item = null)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|min:3|max:15|unique:plans',
            'amount' => 'required|numeric|min:1',
            'duration' => 'required|numeric|min:1',
            'icon' => 'required|string',
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

        $plan = Plan::whereId($item)->orWhere(['slug' => $item])->first() ?? new Plan;

        $plan->title = $request->title;
        $plan->amount = $request->amount;
        $plan->icon;
        $plan->duration = $request->duration;
        $plan->description = $request->description;
        $plan->status = $request->status ?? true;

        if ($request->image)
        {
            Storage::delete($plan->image);
            $photo = new File($request->image);
            $filename =  rand() . '_' . rand() . '.' . $photo->extension();
            Storage::putFileAs('public/uploads/images', $photo, $filename);
            $plan->image = 'uploads/images/'. $filename;
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
    public function destroy($item = null)
    {
        $plan = Plan::whereId($item)->first();

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
