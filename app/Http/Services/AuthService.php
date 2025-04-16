<?php


namespace App\Http\Services;


use App\Exceptions\ApiException;
use App\Http\ApiResponse;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    const USERNAME_REGEX = '/^[a-zA-Z0-9_]{2,20}$/';
    const PASSWORD_REGEX = '/^(?=.*[a-zA-Z])(?=.*\d)[a-zA-Z0-9_]{6,20}$/';
    private Model $user;
    private string $userType;
    private string $username;
    private string $password;
    public function getUser():Model
    {
        return $this->user;
    }
    public function getUserType():string
    {
        return $this->userType;
    }
    private function findUser( Model $model,$where=[]):bool
    {
        // 根据用户名查找Teacher或Student, 用户名全局唯一
        $user = $model::where('username', $this->username)
        ->where($where)
        ->select([
            'id',
            'username',
            'name',
            'password',
            'status',
        ])->first();
        if($user){
            //检验状态
            if($user->status == $user::STATUS_DISABLE){
                throw new ApiException(lang('账号已被禁用'));
            }
            // 验证密码
            if(!Hash::check($this->password, $user->password)){
                throw new ApiException(lang('用户名或密码错误'));
            }
            $this->user = $user;
            return true;
        }
        return false;
    }

    /**
     * 验证用户身份
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @return bool 验证是否成功
     * @throws ApiException 当账号被禁用或密码错误时抛出异常
     */
    public function auth(string $username, string $password):bool
    {
        $this->username = $username;
        $this->password = $password;

        // 先尝试查找学生用户
        $result = $this->findUser(new Student());
        if($result){
            $this->userType = 'student';
            return true;
        }

        $result = $this->findUser(new Teacher(), ['role_type'=>Teacher::ROLE_TYPE_ORDINARY_TEACHER]);
        if($result){
            $this->userType = 'teacher';
            return true;
        }

        return false;
    }

}
