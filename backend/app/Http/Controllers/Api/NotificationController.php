<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    public function markRead(Request $request, \App\Models\Notification $notification)
    {
        abort_if($notification->user_id !== $request->user()->id, 403);

        $notification->markAsRead();

        return response()->json($notification);
    }
}
