<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeedbackCollection;
use App\Http\Resources\FeedbackResource;
// use App\Jobs\ProcessFeedback;
use App\Models\Feedback;
// use GrahamCampbell\GitHub\GitHubManager;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('usable', ['feedback.manage']);
        // $this->authorize('usable', 'content');
        $query = Feedback::query();

        // Search and filter columns
        if ($request->search) {
            $query->whereFullText('message', $request->search);
        }

        // Get by type
        if ($request->type && $request->type != 'all') {
            $query->where('type', $request->type);
        }

        // Get by thread
        $query->thread($request->thread ?? false);

        // Reorder Columns
        if (! $request->thread) {
            foreach ($request->get('order', []) as $key => $dir) {
                if ($dir == 'desc') {
                    $query->orderByDesc($key ?? 'id');
                } else {
                    $query->orderBy($key ?? 'id');
                }
            }
        }

        $feedbacks = $query->paginate($request->get('limit', 15))->withQueryString();

        return (new FeedbackCollection($feedbacks))->additional([
            'message' => $feedbacks->isEmpty() ? __('There are no feedbacks for now.') : 'OK',
            'status' => $feedbacks->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
        ])->response()->setStatusCode(200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $thread_id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $thread_id)
    {
        $this->authorize('usable', ['feedback.manage']);

        $request->validate([
            'image' => ['sometimes', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
            'reply' => ['required', 'string', 'min:1'],
        ]);

        $user = auth()->user();
        $thread = Feedback::findOrfail($thread_id);
        $feedback = new Feedback();

        $feedback->user_id = $user->id ?? 0;
        $feedback->thread_id = $thread_id;

        $feedback->type = 'thread';
        $feedback->name = $user->name ?? '';
        $feedback->email = $user->email ?? '';
        $feedback->phone = $user->phone ?? '';
        $feedback->title = $thread->title;
        $feedback->origin = $thread->origin;
        $feedback->message = $request->reply;
        $feedback->priority = $thread->priority;
        $feedback->save();

        return (new FeedbackResource($thread))->additional([
            'status' => 'success',
            'message' => __('Reply to feedback #:0(:1) sent.', [$thread->id, $thread->title]),
            'response_code' => 202,
        ])->response()->setStatusCode(202);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function status(Request $request)
    {
        $this->authorize('usable', ['feedback.manage']);
        $this->validate($request, [
            'id' => ['required', 'exists:feedback,id'],
            'status' => ['required', 'string', 'in:pending,seen,reviewing,reviewed,resolved'],
        ]);

        $feedback = Feedback::findOrfail($request->id);

        $feedback->status = $request->status;
        $feedback->save();

        return (new FeedbackResource($feedback))->additional([
            'message' => __('Feedback status has successfully been changed to :0.', [$feedback->status]),
            'status' => 'success',
            'response_code' => 202,
        ])->response()->setStatusCode(202);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function github(Request $request, GitHubManager $github)
    {
        $this->authorize('usable', ['feedback.manage']);
        $this->validate($request, [
            'id' => ['required', 'exists:feedback,id'],
            'type' => ['required', 'string', 'in:issue,pull_request'],
            'action' => ['required', 'string', 'in:open,close'],
        ]);

        $feedback = Feedback::findOrfail($request->id);
        ProcessFeedback::dispatch($feedback, $request->type, $request->action);

        return (new FeedbackResource($feedback))->additional([
            'message' => __("Github :0 for Feedback #:1 has been :2 (Please don't resend this request, refresh the page after a few seconds to check for updated status).", [ucfirst($request->type), $feedback->id, $request->action]),
            'status' => 'success',
            'response_code' => 202,
        ])->response()->setStatusCode(202);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Feedback $feedback)
    {
        $this->authorize('usable', ['feedback.manage']);

        return (new FeedbackResource($feedback))->additional([
            'message' => 'OK',
            'status' => 'success',
            'response_code' => 200,
        ])->response()->setStatusCode(200);
    }

    /**
     * Delete the specified company in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id = null)
    {
        $this->authorize('usable', ['feedback.manage']);
        if ($request->items) {
            $items = collect($request->items)->map(function ($item) use ($request) {
                $item = Feedback::whereId($item)->first();
                if ($item) {
                    Feedback::thread($item->id)->delete();
                    $delete = $item->delete();

                    return count($request->items) === 1 ? $item->id : $delete;
                }

                return false;
            })->filter(fn ($i) => $i !== false);

            return $this->buildResponse([
                'message' => $items->count() === 1
                    ? __('Feedback #:0 has been deleted', [$items->first()])
                    : __(':0 feedbacks have been deleted.', [$items->count()]),
                'status' => 'success',
                'response_code' => 202,
            ]);
        } else {
            $item = Feedback::findOrFail($id);
            Feedback::thread($item->id)->delete();
            $item->delete();

            return $this->buildResponse([
                'message' => __('Feedback #:0 has been deleted.', [$item->id]),
                'status' => 'success',
                'response_code' => 202,
            ]);
        }
    }
}
