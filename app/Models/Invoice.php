<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    // 账单状态常量
    const STATUS_PENDING = 0;  // 待处理
    const STATUS_SENT = 1;     // 已发送/待支付
    const STATUS_PAID_SUCCESS = 2;     // 已支付
    const STATUS_PAID_FAILD = 3;     // 支付失败
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'course_id',
        'student_id',
        'teacher_id',
        'amount',
        'status',
        'sent_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * 获取关联的课程
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * 获取关联的学生
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * 获取关联的教师
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * 获取关联的支付记录
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
