<?php

namespace App\Http\Controllers\v2\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserBasicDataCollection;
use App\Http\Resources\UserBasicDataResource;
use App\Models\User;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = $request->user()->affiliates();

        $affiliates = $query->paginate($request->get('limit', 15));

        return (new UserBasicDataCollection($affiliates))->additional([
            'referrer' => new UserBasicDataResource($request->user()->referrer),
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $message = __('You have already joined the :0 affiliate program.', [config('app.name')]);
        $user = $request->user();

        if (! $user->referral_code) {
            $user->referral_code = $this->generateAfCode();

            $user->save();
            $message = __('You have successfully joined the :0 affiliate program.', [config('app.name')]);
        }

        return (new UserBasicDataResource($user))->additional([
            'message' => $message,
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Generate affiliate code.
     *
     * @return string
     */
    protected function generateAfCode()
    {
        // Generate referral code
        $referral_code = str(\Str::random(8))->upper();

        // Check if referral code exists
        $check = User::where('referral_code', $referral_code)->exists();

        // Check if referral code exists
        if ($check) {
            // Generate new referral code
            $this->generateAfCode();
        }

        return $referral_code;
    }
}
