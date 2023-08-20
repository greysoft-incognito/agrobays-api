<?php

namespace App\Http\Controllers\Admin\Cooperative;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CooperativeResource;
use App\Http\Resources\ModelMemberCollection;
use App\Models\Cooperative;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CooperativeMemberController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Cooperative $cooperative)
    {
        $query = $cooperative->members();

        if ($request->search) {
            $query->whereHas('user', function ($query) use ($request) {
                $query->whereRaw('concat_ws(" ", firstname, lastname) like ?', ['%' . $request->search . '%']);
            });
        }

        if ($request->admins) {
            $query->whereJsonLength('abilities', '>', 0);
        }

        if ($request->requesting) {
            $query->where('requesting', true);
        }

        if ($request->pending) {
            $query->where('accepted', false);
        }

        if ($request->active) {
            $query->where('accepted', true);
            $query->where('requesting', false);
        }

        $members = $query->paginate($request->get('limit', 30));

        return (new ModelMemberCollection($members))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Approve a member to join the cooperative.
     *
     * @param  \App\Models\Cooperative  $cooperative
     * @param  \App\Models\User  $member
     * @param  string  $status accepted|declined
     * @return \Illuminate\Http\Response
     */
    public function appprove(Cooperative $cooperative, User $member, $status = 'accepted')
    {
        \Gate::authorize('usable', 'cooperatives.manage');

        Validator::make(['status' => $status], [
            'status' => ['required', 'string', 'in:accepted,declined'],
        ])->validate();

        if ($status == 'accepted') {
            $cooperative->approveRequest($member);
        } else {
            $cooperative->approveRequest($member, false);
        }

        // Update the notification
        $notification = auth()->user()->notifications()
            ->whereType(FollowRequest::class)
            ->where('data->request->id', $cooperative->id)
            ->where('data->request->type', Cooperative::class)
            ->where('data->request->sender_id', $member->id)
            ->first();

        $notification && $notification->update([
            'read_at' => $notification->read_at ?? now(),
            'data->actions' => [],
        ]);

        // Notify the member of the decision
        $member->notify(new \App\Notifications\FollowRequest(
            $member,
            $cooperative,
            $status === 'accepted' ? 'approve' : 'decline'
        ));

        return (new CooperativeResource($cooperative))->additional([
            'message' => __('You have :status the membership request from :user', [
                'status' => $status,
                'user' => $member->fullname,
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Cooperative $cooperative, $id)
    {
        $abilities = implode(',', $cooperative->permissions);

        \Gate::authorize('usable', 'cooperatives.manage');
        $this->validate($request, [
            'abilities' => [
                'required',
                'array',
                "in:all,$abilities",
            ],
        ], [
            'user_id.required' => 'Please select a member to update.',
            'user_id.exists' => 'This user is not a member for this cooperative.',
            'abilities.required' => 'Please grant at least one abilty to the user.',
        ]);

        // Update the member
        $member = $cooperative->members()->findOrFail($id);
        $member->update([
            'abilities' => $request->abilities,
        ]);

        $user = $member->user;

        // Return the Cooperative
        return (new CooperativeResource($cooperative))->additional([
            'message' => __(":0's abilities have been updated successfully", [$user->fullname]),
            'status' => 'success',
            'response_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Cooperative  $cooperative
     * @param  int  $member
     * @return \Illuminate\Http\Response
     */
    public function destroy(Cooperative $cooperative, $member)
    {
        \Gate::authorize('usable', 'cooperatives.manage');

        $member = $cooperative->members()->findOrFail($member);

        $deleted = $member->delete();

        return $this->responseBuilder([
            'message' => $deleted
                ? __(':0 has been removed as a member of this cooperative.', [$member->user->fullname])
                : __('Unable to remove :0 as a member of this cooperative.', [$member->user->fullname]),
            'status' => 'success',
            'response_code' => HttpStatus::ACCEPTED,
        ]);
    }
}