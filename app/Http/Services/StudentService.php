<?php


namespace App\Http\Services;


use App\Exceptions\ApiException;
use App\Http\ApiResponse;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentService
{
    public static function checkStudentStatus($student_id)
    {
        // 验证学生状态
        $student = Student::where('id', $student_id)->select('id', 'status')->first();
        if( empty($student)){
            throw new ApiException(lang('该学生不存在'));
        }
        if($student->status === Student::STATUS_DISABLE){
            throw new ApiException(lang('该学生状态异常'));
        }
    }

    public static function teacherStudentList($teacher_id)
    {
        $students = Student::query()->where('teacher_id', $teacher_id)->select('id', 'name')
            ->orderBy('id', 'asc')
            ->get();
        return $students;

    }
}
