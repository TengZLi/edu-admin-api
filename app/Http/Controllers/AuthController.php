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
use Illuminate\Support\Facades\Cache;
use Spatie\FlareClient\Api;
use Gregwar\Captcha\CaptchaBuilder;

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
                'user_type' => 'required|in:teacher,student',

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
        //获取请求的域名
        $oauthServerDomain = env('PASSPORT_SERVER_DOMAIN', $request->getSchemeAndHttpHost());
        $tokenInfo =Http::asForm()->post($oauthServerDomain.'/oauth/token', [
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

        if ($userType === 'teacher') {
            $model = Teacher::query();
        } else {
            $model = Student::query();
        }
        $user = $model->where('username', $request->username)
        ->first(['id','name','username','status']);
        return ApiResponse::success([
            'username' => $user->username,
            'name' => $user->name,
            'status' => $user->status,
            'user_type' => $request->user_type,
            'access_token' => $tokenInfo['token_type'].' '.$tokenInfo['access_token'],
            'expires_in' => $tokenInfo['expires_in'],
            'token_type' => $tokenInfo['token_type'],
        ]);

    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return ApiResponse::success();
    }

    public function user(Request $request)
    {
        $user = $request->user();
        return ApiResponse::success([
            "username" => $user->username,
            "name" => $user->name,
            "status" => $user->status,
        ]);
    }

    /**
     * 更新用户名和密码
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        try {
            $request->validate([
                'username' => ['required',
                 'regex:'.AuthService::USERNAME_REGEX,
                                'unique:'. ($request->user() instanceof Teacher? 'teachers' :'students'). ',username,'. $request->user()->id],
                'name' => 'required',
                'current_password' => 'required_with:password',
                'password' => ['regex:'.AuthService::PASSWORD_REGEX] ,
            ],['username.regex'=>lang('用户名必须为2到20位数字/英文或下划线'),
                'username.unique'=>lang('用户名已存在'),
                'password.regex'=>lang('密码为6-20位数字/英文或下划线，且至少包含一个字母和一个数字'),
                'current_password.required_with'=>lang('当前密码不能为空')]);
        } catch (\Throwable $throwable) {
            return ApiResponse::error($throwable->getMessage());
        }

        $user = $request->user();

        // 验证当前密码
        if ($request->has('password') && !empty($request->password)) {
            if (!Hash::check($request->current_password, $user->password)) {
                return ApiResponse::error(lang('当前密码不正确'));
            }
            $user->password = Hash::make($request->password);
            $request->user()->token()->revoke();
        }

        // 更新用户名
        $user->username = $request->username;
        $user->name = $request->name;
        $user->save();

        return ApiResponse::success();
    }
}
