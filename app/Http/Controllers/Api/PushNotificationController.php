<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

class PushNotificationController extends Controller
{
    public function sendPush(Request $request, PushNotificationService $firebase)
    {
        $request->validate([
            'token' => 'required',
            'title' => 'required',
            'body' => 'required',
        ]);

        $data = $firebase->sendNotification(
            $request->token,
            $request->title,
            $request->body,
            [
                'status' => 'Active',
                'user_id' => '5',
                'product_id' => '1',
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Push notification sent successfully',
            'data' => $data['message'] ?? [],
        ]);
    }
}
