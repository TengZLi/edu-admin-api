<?php

namespace App\Exceptions;

use App\Http\ApiResponse;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    protected $dontReport = [
        AuthenticationException::class,
        ApiException::class
    ];
    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {


        $this->renderable(function (\Throwable $e, Request $request) {
            if($e instanceof NotFoundExceptionInterface){
                return response()->json([
                    'code' => ApiResponse::ERROR_CODE,
                    'message' => 'Record not found.'
                ], 404);
            }
            if ($request->is('api/*')) {
                if($e instanceof ApiException){
                    return ApiResponse::error($e->getMessage(), ApiResponse::ERROR_CODE);
                }

                if ($e instanceof AuthenticationException) {
                    return ApiResponse::error(lang('未登录'), ApiResponse::UNAUTHORIZED_CODE);
                }

                if(!env('APP_DEBUG')){
                    return ApiResponse::error('Internal Server Error', ApiResponse::SERVER_ERROR_CODE);
                }
            }
        });


    }



}
