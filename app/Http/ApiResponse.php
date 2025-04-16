<?php

namespace App\Http;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    const SUCCESS_CODE = 0;
    const ERROR_CODE = 1;
    const SERVER_ERROR_CODE = 500;
    /**
     * Return a success response.
     *
     * @param  mixed  $data
     * @return JsonResponse
     */
    public static function success($data = null):JsonResponse
    {
        return response()->json([
            'code' => self::SUCCESS_CODE,
            'message' => 'success',
            'data' => $data,
        ], 200);
    }

    /**
     * Return an error response.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  mixed  $data
     * @return JsonResponse
     */
    public static function error(string $message = 'Error', int $code = self::ERROR_CODE, $data = null):JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], 200);
    }
}
