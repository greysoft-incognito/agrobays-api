<?php

namespace App\Http\Controllers;

use App\Http\Resources\FeedbackCollection;
use App\Http\Resources\FeedbackResource;
use App\Models\Feedback;
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
        $user = auth()->user();

        if ($request->thread) {
            $query = Feedback::whereUserId($user->id)->orWhereHas('reply_thread', fn ($q) => $q->whereUserId($user->id));
        } else {
            $query = $user->feedbacks()->latest()->with('user');
        }

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
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => ['nullable', 'in:bug,feedback,suggestion,complaint,other'],
            'name' => ['required', 'string', 'min:3'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'title' => ['required', 'string', 'min:3'],
            'image' => ['sometimes', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
            'origin' => ['required', 'string'],
            'message' => ['required', 'string', 'min:10'],
            'priority' => ['integer', 'min:1', 'max:10'],
        ]);

        $user = auth()->user();

        if ($user && $user->feedbacks()->where('title', $request->title)->where('message', $request->message)->exists()) {
            return $this->buildResponse([
                'message' => 'You already submited this feedback.',
                'status' => 'error',
                'response_code' => 422,
            ]);
        }

        $feedback = new Feedback();

        $feedback->user_id = $user?->id ?? 0;
        $feedback->type = $request->type ?? 'feedback';
        $feedback->name = $request->name ?? $user->fullname ?? '';
        $feedback->email = $request->email ?? $user->email ?? '';
        $feedback->phone = $request->phone ?? $user->phone ?? '';
        $feedback->title = $request->title;
        $feedback->origin = $request->origin;
        $feedback->message = $request->message;
        $feedback->priority = $request->priority || 1;
        $feedback->save();

        return (new FeedbackResource($feedback))->additional([
            'status' => 'success',
            'message' => __('Your feedback has been submited successfully, thanks for looking out for us!'),
            'response_code' => 201,
        ])->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $feedback_id
     * @return \Illuminate\Http\Response
     */
    public function show($feedback_id)
    {
        $feedback = auth()->user()->feedbacks()->findOrFail($feedback_id);

        return (new FeedbackResource($feedback))->additional([
            'status' => 'success',
            'message' => 'OK',
            'response_code' => 200,
        ])->response()->setStatusCode(200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $thread_id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $thread_id)
    {
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
}
