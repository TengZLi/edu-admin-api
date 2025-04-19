<?php

namespace Tests\Feature;

use App\Http\Controllers\StudentController;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StudentControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $studentMaxTeacher = Student::query()
            ->groupBy('teacher_id')->selectRaw('count(*) as student_count, teacher_id')
            ->orderBy('student_count', 'desc')->first();
        // 使用已有数据而不是每次创建新数据
        $this->teacher = Teacher::where('role_type', Teacher::ROLE_TYPE_ORDINARY_TEACHER)->where('id', $studentMaxTeacher->teacher_id)->first() ?? Teacher::factory()->create();
    }

    /** @test */
    public function teacher_can_get_student_list()
    {

        $response = $this->actingAs($this->teacher, 'teacher')
            ->getJson('/api/teacher/students');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    /** @test */
    public function unauthorized_user_cannot_access_student_list()
    {
        $response = $this->getJson('/api/teacher/students');

        $response->assertStatus(200)
            ->assertJson(['code' => 401]);
    }
}
