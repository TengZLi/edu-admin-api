<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\ApiResponse;
use App\Http\Services\CourseService;
use App\Models\Course;
use App\Models\Invoice;
use App\Models\Student;
use App\Rules\YearMonthRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use function NunoMaduro\Collision\Exceptions\getClassName;

class CourseController extends Controller
{
    const GUARD = 'teacher';
    /**
     * 教师创建课程
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => ['required','string','max:255'],
                'year_month'=> ['required','integer', new YearMonthRule()],
                'fee' => ['required','numeric','min:0'],
                'student_ids' => ['array'],
                'student_ids.*' => ['integer'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error($e->getMessage());
        }

        CourseService::create($request, Auth::guard(self::GUARD)->id());
        return ApiResponse::success();
    }

    /**
     * 教师获取课程列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $teacherId = Auth::guard(self::GUARD)->id();

        $courses = Course::query()->where('teacher_id', $teacherId)
            ->with('students:id,name,username')
            ->orderBy('id', 'desc');
        $courses = paginate($courses);
        return ApiResponse::success($courses);
    }

    /**
     * 教师获取课程详情
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $teacherId = Auth::guard(self::GUARD)->id();

        $course = Course::where('id', $id)
            ->where('teacher_id', $teacherId)
            ->with('students:id,name,username')
            ->first();

        if (!$course) {
            return ApiResponse::error('课程不存在');
        }

        return ApiResponse::success($course);
    }

    /**
     * 教师更新课程
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        try {
            $request->validate([
//                'name' => ['required','string','max:255'],
//                'year_month'=> ['required','integer', new YearMonthRule()],
                'fee' => ['required','numeric','min:0'],
                'student_ids' => ['array'],
                'student_ids.*' => ['integer'],
            ]);



        }  catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error($e->getMessage());
        }

        $teacherId = Auth::guard(self::GUARD)->id();
        $course = Course::where('id', $id)
            ->where('teacher_id', $teacherId)
            ->first();
        if (!$course) {
            return ApiResponse::error(lang('课程不存在'));
        }
        CourseService::update($course, $request);
        return ApiResponse::success();
    }

    /**
     * 学生获取自己的课程列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function studentCourses(Request $request)
    {
        $student = Auth::guard('student')->user();

        $courses = $student->courses()
            ->with('teacher:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return ApiResponse::success($courses);
    }
}
