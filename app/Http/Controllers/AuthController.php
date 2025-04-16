<?php
namespace App\Http\Controllers;
use App\Http\ApiResponse;
use App\Http\Services\AuthService;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    /**
     * 统一登录接口，支持教师和学生登录
     *
     * @param Request $request
     * @param AuthService $authService
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try{
            $request->validate([
                'username' => 'required|alpha_dash',
                'password' => 'required',
                'user_type' => 'required|in:teacher,student'
            ]);
        }catch (\Throwable $throwable){
            return ApiResponse::error($throwable->getMessage());
        }

        $userType = $request->user_type;
        if ($userType === 'teacher') {
            $client_id = config('passport.passport_teacher_password_client.id');
            $client_secret = config('passport.passport_teacher_password_client.secret');
        } else {
            $client_id = config('passport.passport_student_password_client.id');
            $client_secret = config('passport.passport_student_password_client.secret');
        }

        $oauthServerDomain = env('PASSPORT_SERVER_DOMAIN', 'http://127.0.0.1');
        $tokenInfo =Http::asForm()->post($oauthServerDomain.'/1oauth/token', [
            'grant_type' => 'password',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'username' => $request->username,
            'password' => $request->password,
            'scope' => '',
        ]);
        $tokenInfo = $tokenInfo->json();
        if (isset($tokenInfo['error'])){
            return ApiResponse::error(lang('用户名或密码错误，或者被禁用登录'));
        }

        return ApiResponse::success([
            'user_type' => $request->user_type,
            'access_token' => $tokenInfo['token_type'].' '.$tokenInfo['access_token'],
            'expires_in' => $tokenInfo['expires_in'],
            'token_type' => $tokenInfo['token_type'],
        ]);
        // 生成Passport访问令牌
//        $authToken = $user->createToken('auth_token');
//        $tokenType = 'Bearer';
//        return ApiResponse::success([
//            'access_token' => "{$tokenType} {$authToken->accessToken}",
//            'expires_at' => $authToken->token->expires_at->toDateTimeString(),
//            'token_type' => $tokenType,
//            'user_type' => $userType,
//            'user' => $user,
//        ]);

    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return ApiResponse::success();
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
