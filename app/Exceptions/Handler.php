<?php

namespace App\Exceptions;

use App\Http\ApiResponse;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Psr\Container\NotFoundExceptionInterface;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (Throwable $e, Request $request) {
            if($e instanceof NotFoundExceptionInterface){
                return response()->json([
                    'code' => ApiResponse::ERROR_CODE,
                    'message' => 'Record not found.'
                ], 404);
            }
            if ($request->is('api/*')) {
                if($e instanceof ApiException){
                    return ApiResponse::error($e->getMessage(), $e->getCode());
                }
                return ApiResponse::error('Internal Server Error', ApiResponse::SERVER_ERROR_CODE);
            }
        });

        $this->reportable(function (Throwable $e) {

        });
    }



}
