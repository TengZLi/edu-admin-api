<?php

namespace App\Http;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    const SUCCESS_CODE = 0;
    const ERROR_CODE = 1;
    const UNAUTHORIZED_CODE = 401;
    const SERVER_ERROR_CODE = 500;
    /**
     * Return a success response.
     *
     * @param  mixed  $data
     * @return JsonResponse
     */
    public static function success($data = null, $msg='success'):JsonResponse
    {
        return response()->json([
            'code' => self::SUCCESS_CODE,
            'message' => $msg,
            'data' => $data,
        ], 200);
    }

    /**
     * Return an error response.
     *
     * @param  string  $message
     * @param  int  $code
     * @return JsonResponse
     */
    public static function error(string $message = 'Error', int $code = self::ERROR_CODE, $httpCode=200):JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => null,
        ], $httpCode);
    }
}
