<?php

namespace App\Http\Controllers\v2\Vendor;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\VendorResource;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    protected $docLabels = [
        'drivers_license' => "Driver's License",
        'national_passport' => 'International Passport',
        'nin_wo_face' => 'National ID Card',
        'nin' => 'NIN Slip',
        'voters_card' => 'Voters Card',
        'drivers_license' => "Driver's License",
        'initial' => 'Information',
        'bvn' => 'Facial Data',
    ];

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        /** @var \App\Models\Vendor $vendor */
        $vendor = $user->vendor;

        $this->validate($request, [
            "reg_image" => "nullable|image|mimes:png,jpg",
            "business_reg" => "nullable|string|min:3|unique:vendors,business_reg,{$vendor->id},id",
            "business_name" => "required|string|unique:vendors,business_name,{$vendor->id},id",
            "business_email" => "required|email|unique:vendors,business_email,{$vendor->id},id",
            "business_phone" => "required|string|unique:vendors,business_phone,{$vendor->id},id",
            "business_address" => "required|string",
            "business_country" => "required|string",
            "business_state" => "required|string",
            "business_city" => "required|string",
        ]);

        $vendor->business_reg = $request->business_reg;
        $vendor->business_name = $request->business_name;
        $vendor->business_email = $request->business_email;
        $vendor->business_phone = $request->business_phone;
        $vendor->business_address = $request->business_address;
        $vendor->business_country = $request->business_country;
        $vendor->business_state = $request->business_state;
        $vendor->business_city = $request->business_city;

        $vendor->save();

        return (new VendorResource($vendor))->additional([
            'message' => __('Your documentation has been submitted and will be reviewed shortly.'),
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Verify the user account
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $type
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $type = 'bvn')
    {
        $user = $request->user();
        $vendor = $user->vendor;

        $request_data = $request->data;
        $verification_data = $vendor->verification_data;

        if ($type === 'finalize') {
            $verification_data['platform'] = $request_data;
            $message = __('Your verification request has been submitted and will be reviewed soon.');
        } else {
            $vendor->id_type = $type !== 'initial' ?  $type : 'national_passport';
            $verification_data[$type] = $request_data;
            $message = __('Your :0 has been submited successfully.', [
                $this->docLabels[$type] ?? $this->docLabels['initial']
            ]);
        }

        $vendor->verification_data = $verification_data;
        $vendor->save();

        return (new VendorResource($vendor))->additional([
            'message' => $message,
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }
}
