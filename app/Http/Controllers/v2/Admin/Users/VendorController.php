<?php

namespace App\Http\Controllers\v2\Admin\Users;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\VendorResource;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('usable', 'users.vendor');
        $request->merge(['role' => 'vendor']);

        return (new UsersController())->index($request);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Vendor $vendor
     * @return \Illuminate\Http\Response
     */
    public function show(Vendor $vendor)
    {
        $this->authorize('usable', 'users.vendor');

        return (new VendorResource($vendor))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Vendor $vendor
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Vendor $vendor)
    {
        $this->authorize('usable', 'users.vendor');

        $this->validate($request, [
            'blocked' => ['nullable', 'boolean'],
            'verified' => ['nullable', 'boolean'],
        ]);

        $vendor->blocked = $request->boolean('blocked');
        $vendor->verified = $request->boolean('verified');

        return (new VendorResource($vendor))->additional([
            'message' => __(':0\'s vendor status has been updated successfully.', [
                $vendor->user->fullname,
            ]),
            'status' => 'success',
            'response_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Vendor   $vendor
     * @return \Illuminate\Http\Response
     */
    public function destroy(Vendor $vendor)
    {
        $this->authorize('usable', 'users.vendor.delete');

        $vendor->delete();

        return (new VendorResource($vendor))->additional([
            'message' => __(':0 has been removed from the vendor programme.', [$vendor->user->fullname]),
            'status' => 'success',
            'response_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }
}
