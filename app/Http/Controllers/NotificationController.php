<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function index(Request $request, $type = 'unread')
    {
        if ($type === 'unread') {
            $list = \Auth::user()->unreadNotifications()->cursorPaginate(15);
        } else {
            $list = \Auth::user()->notifications()->cursorPaginate(15);
        }
        $items = [];
        foreach ($list as $key => $item) {
            $items[] = $item;
            if ($request->get($list->getCursorName())) {
                $item->markAsRead();
            }
        }
        preg_match('/[?&]' . $list->getCursorName() . '=([^&]+).*$/', $list->previousPageUrl(), $prev_cursor);
        preg_match('/[?&]' . $list->getCursorName() . '=([^&]+).*$/', $list->nextPageUrl(), $next_cursor);
        return (new Controller)->buildResponse([
            "message" => $items ? "OK" : "No notifications available!",
            "status" =>  $items ? "success" : "info",
            "response_code" => 200,
            "data" => $items,
            "navigation" => [
                "cursorName"  => $list->getCursorName(),
                "prev_cursor" => $prev_cursor[1]??null,
                "next_cursor" => $next_cursor[1]??null,
                "prev_page" => $list->previousPageUrl(),
                "next_page" => $list->nextPageUrl(),
                "has_pages" => $list->hasPages(),
                "has_more"  => $list->hasMorePages(),
                "per_page"  => $list->perPage(),
                "count"  => $list->count(),
                "unread"  => \Auth::user()->unreadNotifications()->count(),
            ]
        ]);
    }

    public function markAsRead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->buildResponse([
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'response_code' => 422,
                'errors' => $validator->errors(),
            ]);
        }
        $items = $request->post('items');
        $list = \Auth::user()->unreadNotifications()->whereIn('id', is_string($items) ? [$items] : $items );
        $list->update(['read_at' => now()]);
        return (new Controller)->buildResponse([
            "message" => $list ? $list->count() . " Marked as read." : "No notifications available!",
            "status" =>  $list ? "success" : "info",
            "response_code" => 200,
            "data" => [
                "unread" => \Auth::user()->unreadNotifications()->count()
            ],
        ]);
    }
}