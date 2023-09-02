<?php

namespace App\Http\Controllers\Admin\Cooperative;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Resources\CooperativeResource;
use App\Http\Resources\WalletCollection;
use App\Models\Cooperative;
use Illuminate\Http\Request;

class CooperativeMgtController extends CooperativeController
{
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

        \Gate::authorize('usable', 'cooperatives.manage');

        $this->validate($request, [
            'name' => 'required|string|min:3',
            'email' => 'required|email|unique:cooperatives,email,'.$cooperative->id,
            'phone' => 'nullable|string|unique:cooperatives,phone,'.$cooperative->id,
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Cooperative  $cooperative
     * @return \Illuminate\Http\Response
     */
    public function toggleVerification(Request $request, Cooperative $cooperative)
    {
        \Gate::authorize('usable', 'cooperatives.manage');
        $cooperative->verified = ! $cooperative->verified;
        $cooperative->save();

        return (new CooperativeResource($cooperative))->additional([
            'message' => __(':0 has been :1 successfully', [
                $cooperative->name,
                $cooperative->verified ? 'verified' : 'unverified',
            ]),
            'status' => 'success',
            'response_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Cooperative  $cooperative
     * @return \Illuminate\Http\Response
     */
    public function walletTopup(Request $request, Cooperative $cooperative)
    {
        \Gate::authorize('usable', 'cooperatives.wallet');

        $this->validate($request, [
            'amount' => 'required|numeric|min:1',
        ]);

        $cooperative->wallet()->make()->topup('Admin', $request->amount, 'Admin Topup');

        return (new CooperativeResource($cooperative->fresh()))->additional([
            'message' => __(":0's wallet has been funded with :1 successfully", [
                $cooperative->name,
                money($request->amount),
            ]),
            'status' => 'success',
            'response_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Display a listing of the wallet resource.
     *
     * @param  Request  $request
     * @param  \App\Models\Cooperative  $cooperative
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
        \Gate::authorize('usable', 'cooperatives.manage');

        $cooperative->delete();

        return $this->buildResponse([
            'message' => 'Cooperative deleted successfully.',
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ]);
    }
}
