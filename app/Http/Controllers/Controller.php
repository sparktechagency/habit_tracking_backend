<?php

namespace App\Http\Controllers;

abstract class Controller
{
    public function sendResponse($data, $message = 'Success', $status = true, $code = 200)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public function sendError($error = 'Error', $errorMessages = [], $code = 500)
    {
        return response()->json([
            'status' => false,
            'message' => $error,
            'errors' => $errorMessages
        ], $code);
    }
}
