<?php

namespace App\Http\Controllers\Cooperative;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Cooperative\CooperativeController;
use App\Http\Resources\CooperativeResource;
use App\Http\Resources\WalletCollection;
use App\Models\Cooperative;
use Illuminate\Http\Request;

class CooperativeMgtController extends CooperativeController
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $classifications = implode(',', Cooperative::$classifications);

        $this->validate($request, [
            'name' => 'required|string|min:3',
            'about' => 'nullable|string|min:10',
            'email' => 'required|email|unique:cooperatives,email',
            'phone' => 'nullable|string|unique:cooperatives,phone',
            'website' => 'nullable|url',
            'state' => 'nullable|string',
            'lga' => 'nullable|string',
            'address' => 'nullable|string',
            'classification' => "required|string|in:$classifications",
        ]);

        /** @var Cooperative $cooperative */
        $cooperative = Cooperative::create([
            'name' => ucwords($request->name),
            'lga' => $request->lga,
            'about' => $request->about,
            'email' => $request->email,
            'phone' => $request->phone,
            'state' => $request->state,
            'user_id' => auth()->id(),
            'website' => $request->website,
            'address' => $request->address,
            'classification' => $request->classification,
        ]);

        // Add the creator as a member with [all] abilities
        $cooperative->members()->create([
            'user_id' => auth()->id(),
            'abilities' => ['all'],
            'accepted' => true,
        ]);

        return (new CooperativeResource($cooperative))->additional([
            'message' => 'Cooperative created successfully',
            'status' => 'success',
            'response_code' => HttpStatus::CREATED,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Cooperative  $cooperative
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Cooperative $cooperative)
    {
        $classifications = implode(',', Cooperative::$classifications);

        $this->authorize('manage', [$cooperative, 'update_profile']);

        $this->validate($request, [
            'name' => 'required|string|min:3',
            'email' => 'required|email|unique:cooperatives,email,' . $cooperative->id,
            'phone' => 'nullable|string|unique:cooperatives,phone,' . $cooperative->id,
            'about' => 'nullable|string|min:10',
            'website' => 'nullable|url',
            'address' => 'nullable|string',
            'state' => 'nullable|string',
            'lga' => 'nullable|string',
            'classification' => "required|string|in:$classifications",
        ]);

        $cooperative->name = ucwords($request->name);
        $cooperative->about = $request->about;
        $cooperative->email = $request->email;
        $cooperative->phone = $request->phone;
        $cooperative->website = $request->website;
        $cooperative->state = $request->state;
        $cooperative->lga = $request->lga;
        $cooperative->address = $request->address;
        $cooperative->classification = $request->classification;

        if ($request->settings) {
            $settings = $cooperative->settings;
            $settings = $settings->merge($request->settings);
            $cooperative->settings = $settings;
        }
        $cooperative->save();

        return (new CooperativeResource($cooperative))->additional([
            'message' => 'Cooperative updated successfully',
            'status' => 'success',
            'response_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Update the cooperative's photos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Cooperative  $cooperative
     * @return \Illuminate\Http\Response
     */
    public function photos(Request $request, Cooperative $cooperative)
    {
        $this->authorize('manage', [$cooperative, 'update_profile']);

        $this->validate($request, [
            'image' => 'required_without:cover|image|mimes:jpeg,png,jpg|max:2048',
            'cover' => 'required_without:image|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $text = '';
        if ($request->has('cover') && $request->has('image')) {
            $text = 'cover photo and profile photo';
        } elseif ($request->has('cover')) {
            $text = 'cover photo';
        } elseif ($request->has('image')) {
            $text = 'profile photo';
        }

        $cooperative->name = $cooperative->name;
        $cooperative->save();

        return (new CooperativeResource($cooperative))->additional([
            'message' => __('Cooperative :0 updated successfully', [$text]),
            'status' => 'success',
            'response_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Display a listing of the wallet resource.
     *
     * @param  Request  $request
     * @param  \App\Models\Cooperative  $cooperative
     *
     * @return \Illuminate\Http\Response
     */
    public function wallet(Request $request, Cooperative $cooperative)
    {
        $this->authorize('manage', [$cooperative, 'manage_plans']);

        $query = $cooperative->wallet()->orderByDesc('id');

        if ($p = $request->get('period')) {
            $period = explode('-', $p);
            $query->whereBetween('created_at', [new Carbon($period[0]), new Carbon($period[1])]);
        }

        return (new WalletCollection($query->paginate()))->additional([
            'balance' => $cooperative->wallet_balance,
            'message' => 'OK',
            'status' => 'success',
            'response_code' => 200,
        ])->response()->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Cooperative  $cooperative
     * @return \Illuminate\Http\Response
     */
    public function destroy(Cooperative $cooperative)
    {
        $this->authorize('manage', [$cooperative, 'delete_cooperative']);

        $cooperative->delete();

        return $this->buildResponse([
            'message' => 'Cooperative deleted successfully.',
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ]);
    }
}
