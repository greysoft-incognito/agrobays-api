<?php

namespace App\Http\Controllers\User;

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
                $query->whereRaw('concat_ws(" ", firstname, lastname) like ?', ['%'.$request->search.'%']);
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
        $this->authorize('manage', [$cooperative, 'manage_members']);

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

        $this->authorize('manage', [$cooperative, 'manage_admins']);
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
     * Invite a user to [manage] a Cooperative
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  App\Models\Cooperative  $cooperative
     * @return \Illuminate\Http\Response
     */
    public function invitations(Request $request, Cooperative $cooperative)
    {
        $abilities = implode(',', $cooperative->permissions);

        $this->authorize('manage', [$cooperative, 'manage_admins']);

        $this->validate($request, [
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('model_members')
                    ->where(fn (Builder $query) => $query
                    ->where('user_id', $request->user_id)
                    ->where('model_id', $cooperative->id))
                    ->where('model_type', Cooperative::class),
            ],
            'abilities' => [
                'nullable',
                'array',
                "in:all,$abilities",
            ],
        ], [
            'user_id.required' => 'Please select a user to add.',
            'user_id.exists' => 'This user does not exist.',
            'user_id.unique' => 'This user is already a member of this cooperative or has a pending request/invitation.',
            'abilities.required' => 'Please grant at least one abilty to the user.',
        ]);

        $user = User::find($request->user_id);

        // Create a new member
        $member = $cooperative->members()->create([
            'user_id' => $request->user_id,
            'abilities' => $request->abilities,
        ]);

        // Send notification to the user
        $user->notify(new \App\Notifications\MemberInvitation($member, auth()->user()));

        // Return the Cooperative
        return (new CooperativeResource($cooperative))->additional([
            'message' => __('Your invitation to :0 has been sent', [$user->fullname]),
            'status' => 'success',
            'response_code' => HttpStatus::CREATED,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }

    /**
     * Invite a user to [manage] a Cooperative
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Cooperative  $cooperative
     * @return \Illuminate\Http\Response
     */
    public function invitationsStatus(Request $request, Cooperative $cooperative, $status)
    {
        $this->authorize('manage', [$cooperative, 'exists', false, 'You do not have any pending invitations.']);

        // Validate the status
        Validator::make(['status' => $status], [
            'status' => 'required|in:accepted,rejected',
        ], [
            'status.required' => 'Please select a status.',
            'status.in' => 'Invalid status.',
        ])->validate();

        /**
         * Get the invitation
         *
         * @var \App\Models\v1\Modelmember
         */
        $invitation = $cooperative->members()->forUser(auth()->user())->isAccepted(false)->firstOrFail();

        // Update the status of the invitation
        $invitation->update([
            'accepted' => $status == 'accepted',
        ]);

        // Delete the invitation if it was rejected
        if ($status == 'rejected') {
            $invitation->delete();
        }

        // Update the notification
        $invitation->notification && $invitation->notification->update([
            'read_at' => $invitation->notification->read_at ?? now(), 'data->actions' => [],
        ]);

        // Return the Cooperative
        return (new CooperativeResource($cooperative))->additional([
            'message' => __('You have :0 the invitation to become a member of :1.', [$status, $cooperative->name]),
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
        $this->authorize('manage', [$cooperative, 'manage_admins']);

        $member = $cooperative->members()->findOrFail($member);

        $deleted = $member->delete();

        return $this->buildResponse([
            'message' => $deleted
                ? __(':0 has been removed as a member of this cooperative.', [$member->user->fullname])
                : __('Unable to remove :0 as a member of this cooperative.', [$member->user->fullname]),
            'status' => 'success',
            'response_code' => HttpStatus::ACCEPTED,
        ]);
    }
}
