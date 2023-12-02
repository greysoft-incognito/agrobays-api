<?php

namespace App\Http\Controllers\v2\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\DeliverableNotificationCollection;
use App\Http\Resources\DeliverableNotificationResource;
use App\Jobs\DeliverableNotificationJob;
use App\Models\DeliverableNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliverableNotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = DeliverableNotification::query()->latest();

        $deliverables = $query->paginate($request->get('liimit', 15));

        return (new DeliverableNotificationCollection($deliverables))->additional([
            'status' => 'success',
            'message' => HttpStatus::message(HttpStatus::OK),
            'response_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject' => ['required', 'string', 'min:15', 'max:155'],
            'message' => [
                'required',
                'string',
                fn($a, $v, $f) => str($v)->stripTags()->length() < 15
                    ? $f('Message must have at least 15 characters.')
                    : ''
            ],
            'type' => ['required', 'string', 'in:mail,email,inapp,broadcast'],
            'draft' => ['nullable', 'boolean'],
            'recipient_ids' => ['required'],
        ]);

        $recipient_ids = $request->recipient_ids;

        if (
            is_string($recipient_ids) &&
            ! in_array($recipient_ids, ['all', 'savers', '!savers', 'buyers', '!buyers'])
        ) {
            $request->validate([
                'recipient_ids' => ['string', 'in:all,savers,!savers,buyers,!buyers'],
            ]);
        }

        if (! is_array($recipient_ids)) {
            $recipient_ids = [$recipient_ids];
        }

        $deliverable = new DeliverableNotification();
        $deliverable->type = $request->type;
        $deliverable->draft = $request->draft ?? false;
        $deliverable->user_id = $request->user()->id;
        $deliverable->subject = $request->subject;
        $deliverable->message = $request->message;
        $deliverable->recipient_ids = $recipient_ids;

        $deliverable->save();

        DeliverableNotificationJob::dispatchIf(! $deliverable->draft, $deliverable);

        $type_label = [
            'mail' => 'mail',
            'email' => 'mail',
            'inapp' => 'in-app notification',
            'broadcast' => 'broadcast notification',
        ][$request->type];

        return (new DeliverableNotificationResource($deliverable))->additional([
            'status' => 'success',
            'message' => __('Your :0 has been :1.', [
                $type_label, $deliverable->draft ? 'saved as draft' : 'queued for delivery',
            ]),
            'response_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DeliverableNotification  $deliverable
     * @return \Illuminate\Http\Response
     */
    public function show(DeliverableNotification $deliverable)
    {
        return (new DeliverableNotificationResource($deliverable))->additional([
            'status' => 'success',
            'message' => HttpStatus::message(HttpStatus::OK),
            'response_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DeliverableNotification  $deliverable
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DeliverableNotification $deliverable)
    {
        $request->validate([
            'subject' => ['required', 'string', 'min:15', 'max:155'],
            'message' => ['required', 'string', 'min:15'],
            'type' => ['required', 'string', 'in:mail,email,inapp,broadcast'],
            'draft' => ['nullable', 'boolean'],
            'resend' => ['nullable', 'boolean'],
            'recipient_ids' => ['required_if:draft,0'],
        ]);

        $recipient_ids = $request->recipient_ids;

        if (
            is_string($recipient_ids) &&
            ! in_array($recipient_ids, ['all', 'savers', '!savers', 'buyers', '!buyers'])
        ) {
            $request->validate([
                'recipient_ids' => ['string', 'in:all,savers,!savers,buyers,!buyers'],
            ]);
        }

        if (! is_array($recipient_ids)) {
            $recipient_ids = [$recipient_ids];
        }

        $deliverable->type = $request->type;
        $deliverable->draft = $request->draft ?? $request->draft;
        $deliverable->user_id = $request->user()->id;
        $deliverable->subject = $request->subject;
        $deliverable->message = $request->message;
        $deliverable->recipient_ids = $recipient_ids ?? $request->recipient_ids;

        $deliverable->save();

        DeliverableNotificationJob::dispatchIf(
            ! $deliverable->draft && ($deliverable->count_sent < 1 || $request->resend),
            $deliverable
        );

        $type_label = [
            'mail' => 'mail',
            'email' => 'mail',
            'inapp' => 'in-app notification',
            'broadcast' => 'broadcast notification',
        ][$request->type];

        return (new DeliverableNotificationResource($deliverable))->additional([
            'status' => 'success',
            'message' => __('Your :0 has been :1.', [
                $type_label, $deliverable->draft ? 'saved as draft' : 'queued for delivery',
            ]),
            'response_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) {
                $item = DeliverableNotification::whereId($item)->first();
                if ($item) {
                    return $item->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->responseBuilder([
                'message' => "{$count} deliverables have been deleted.",
                'status' => 'success',
                'response_code' => HttpStatus::OK,
                'data' => ['items' => $request->items]
            ]);
        } else {
            $item = DeliverableNotification::whereId($id)->firstOrFail();

            $status = $item->delete();

            return $this->responseBuilder([
                'message' => "{$item->subject} has been deleted.",
                'status' => 'success',
                'response_code' => HttpStatus::OK,
                'data' => ['items' => $id]
            ]);
        }
    }
}
