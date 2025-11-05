<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use function PHPUnit\Framework\isEmpty;

class NotificationController extends Controller
{
    public function getNotifications1(Request $request)
    {
        $user = Auth::user();

        if (!isEmpty($request->filter)) {
            if ($request->filter == 'unread') {
                $notifications = $user->notifications()->where('read_at', null)->latest()->paginate($request->per_page ?? 10);
            } elseif ($request->filter == 'read') {
                $notifications = $user->notifications()->where('read_at', '!=', null)->latest()->paginate($request->per_page ?? 10);
            } else {
                $notifications = $user->notifications()->latest()->paginate($request->per_page ?? 10);
            }
        } else {
            $notifications = $user->notifications()->latest()->paginate($request->per_page ?? 10);
        }


        return response()->json([
            'status' => true,
            'message' => 'Latest notifications',
            'data' => $notifications
        ]);
    }

    public function getNotifications(Request $request)
    {
        $user = Auth::user();

        $query = $user->notifications()->latest();

        if ($request->filled('filter')) {
            switch ($request->filter) {
                case 'unread':
                    $query->whereNull('read_at');
                    break;
                case 'read':
                    $query->whereNotNull('read_at');
                    break;
                default:
                    // all → কোন শর্ত না
                    break;
            }
        }

        $notifications = $query->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => true,
            'message' => 'Notifications fetched successfully',
            'data' => $notifications,
        ]);
    }
    public function read(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|string|exists:notifications,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $notification = DatabaseNotification::find($request->notification_id);

        $notification->markAsRead();

        return response()->json([
            'status' => true,
            'message' => 'Notification readed'
        ]);
    }
    public function readAll(Request $request)
    {
        $ids = Auth::user()->unreadNotifications()->pluck('id')->toArray();

        DatabaseNotification::whereIn('id', $ids)->update(['read_at' => now()]);

        return response()->json([
            'status' => true,
            'message' => 'All Notifications are readed'
        ]);
    }
    public function status()
    {
        return response()->json([
            'status' => true,
            'message' => 'How much unreaded notifications',
            'unread_count' => Auth::user()->unreadNotifications()->count(),
        ]);
    }
}
