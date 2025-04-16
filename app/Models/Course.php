<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'year_month',
        'fee',
        'teacher_id',
    ];

    /**
     * 获取课程的教师
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * 获取课程的学生
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, CourseStudent::class, 'course_id', 'student_id');
    }

    /**
     * 获取课程的账单
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
