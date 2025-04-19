<?php

namespace Tests\Feature;

use App\Http\Controllers\CourseController;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourseControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $teacher;
    protected $students;

    protected function setUp(): void
    {
        parent::setUp();
        $studentMaxTeacher =  Student::query()
            ->groupBy('teachers.id')
            ->join('teachers','students.teacher_id','=','teachers.id')
            ->where('teachers.status','=',Teacher::STATUS_NORMAL)
            ->selectRaw('count(*) as student_count, teachers.*')
            ->orderBy('student_count', 'desc')->first();;
        // 使用已有数据而不是每次创建新数据
        $this->teacher = Teacher::query()
                ->where('id', $studentMaxTeacher->id)->first() ?? Teacher::factory()->create();
        $this->students = Student::take(3)->get();

//        // 如果数据库中没有足够的学生，则创建
//        if ($this->students->count() < 3) {
//            $this->students = Student::factory()->count(3)->create();
//        }
    }

    /** @test */
    public function teacher_can_create_course()
    {
        $yearMonth =  date('Y') + 1 . '01';
        $response = $this->actingAs($this->teacher, 'teacher')
            ->postJson('/api/teacher/course', [
                'name' => '测试课程',
                'year_month' =>$yearMonth,
                'fee' => 100.00,
                'student_ids' => $this->students->pluck('id')->toArray()
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertDatabaseHas('courses', [
            'name' => '测试课程',
            'teacher_id' => $this->teacher->id,
            'year_month' =>  $yearMonth
        ]);
    }

    /** @test */
    public function course_creation_fails_with_missing_name()
    {
        $response = $this->actingAs($this->teacher, 'teacher')
            ->postJson('/api/teacher/course', [
                'year_month' => date('Y') + 1 . '01',
                'fee' => 100.00,
                'student_ids' => $this->students->pluck('id')->toArray()
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 1]); // 验证失败返回错误码
    }

    /** @test */
    public function course_creation_fails_with_invalid_year_month()
    {
        $response = $this->actingAs($this->teacher, 'teacher')
            ->postJson('/api/teacher/course', [
                'name' => '测试课程',
                'year_month' => 200001, // 过去的年月
                'fee' => 100.00,
                'student_ids' => $this->students->pluck('id')->toArray()
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 1]); // 验证失败返回错误码
    }

    /** @test */
    public function course_creation_fails_with_negative_fee()
    {
        $response = $this->actingAs($this->teacher, 'teacher')
            ->postJson('/api/teacher/course', [
                'name' => '测试课程',
                'year_month' => date('Y') + 1 . '01',
                'fee' => -50.00, // 负数费用
                'student_ids' => $this->students->pluck('id')->toArray()
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 1]); // 验证失败返回错误码
    }

    /** @test */
    public function teacher_can_get_course_list()
    {

        $response = $this->actingAs($this->teacher, 'teacher')
            ->getJson('/api/teacher/course');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        // 验证返回的数据中至少包含教师的课程
        $courseCount = Course::where('teacher_id', $this->teacher->id)->count();
        $this->assertGreaterThanOrEqual($courseCount, $response->json('data.total'));
    }


//    /** @test */
//    public function teacher_can_update_course()
//    {
//        $course = Course::factory()->create([
//            'teacher_id' => $this->teacher->id
//        ]);
//
//        $response = $this->actingAs($this->teacher, 'teacher')
//            ->putJson('/api/courses/' . $course->id, [
//                'student_ids' => $this->students->pluck('id')->toArray()
//            ]);
//
//        $response->assertStatus(200)
//            ->assertJson(['code' => 0]);
//
//        $this->assertDatabaseCount('course_students', 3);
//    }
}
