<?php

namespace App\Traits;

trait ResponseTrait
{
    public function apiResponse($successFlag =true, $message = null, $data = null, $statusCode = 200)
    {
        return response()->json([
            'success' => $successFlag,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
}
