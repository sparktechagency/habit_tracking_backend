<?php

namespace App\Traits\Traits;

trait ApiResponseTrait
{
    public function sendResponse($data = null, $message = 'Success', $status = true, $code = 200)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public function sendError($message = 'Something went wrong', $code = 500)
    {
        return response()->json([
            'status' => false,
            'message' => $message
        ], $code);
    }
}
