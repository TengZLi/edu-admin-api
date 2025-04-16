<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\HasApiTokens;

class Teacher extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    const ROLE_TYPE_ORDINARY_TEACHER = 1;
    const ROLE_TYPE_ADMIN = 2;
    const ROLE_TYPE_SUPER_ADMIN = 3;

    const STATUS_NORMAL = 1;
    const STATUS_DISABLE = 0;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * 获取教师创建的课程
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * 获取教师创建的账单
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }


    /**
     * 查找给定用户名的用户实例。
     */
    public function findForPassport(string $username)
    {
        return $this->where('username', $username)->first();
    }

    /**
     * 验证用户的密码以获得 Passport 密码授权。
     */
    public function validateForPassportPasswordGrant(string $password): bool
    {
        if($this->status === self::STATUS_DISABLE) {
            return false;
        }
        return Hash::check($password, $this->password);
    }
}
