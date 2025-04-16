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

class CourseService
{

    public static function create($request, $teacherId):void
    {
        DB::transaction(function () use ($request, $teacherId){
            // 创建课程
            $course = Course::create([
                'name' => $request->name,
                'year_month' => $request->year_month,
                'fee' => $request->fee,
                'teacher_id' => $teacherId,
            ]);

            // 关联学生
            if ($request->has('student_ids') && !empty($request->student_ids)) {
                //只能关联属于自己的学生
                $student_ids = $request->student_ids;
                $student_ids = Student::whereIn('id', $student_ids)->where('teacher_id', $teacherId)->pluck('id')->toArray();
                $course->students()->withPivotValue('created_at', date('Y-m-d H:i:s'))->attach($student_ids);
            }
        });

    }

    public static function update($course,$request){
        DB::transaction(function () use ($course, $request){
            // 更新课程信息
//            $course->name = $request->name;
//            $course->year_month = $request->year_month;
            $course->fee = $request->fee;
            $course->save();
            // 更新关联学生
            if ($request->has('student_ids')) {
                $student_ids = $request->student_ids;
                $student_ids = Student::whereIn('id', $student_ids)->where('teacher_id', $course->teacher_id)->pluck('id')->toArray();
                $course->students()->sync($student_ids);
            }
        });



    }
}
