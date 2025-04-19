<?php


namespace App\Http\Middleware;


use App\Http\ApiResponse;
use App\Models\Teacher;
use Illuminate\Http\Request;

class CheckUserStatus
{

    public function handle(Request $request, \Closure $next)
    {
        if($request->user()->status == Teacher::STATUS_DISABLE){
            //踢出用户
            $request->user()->token()->revoke();
            return  ApiResponse::error(lang('用户状态异常'), ApiResponse::UNAUTHORIZED_CODE);
        }
        return $next($request);
    }

}
