<?php

namespace Tests\Feature;

use App\Http\Controllers\CourseController;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CourseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $teacher;
    protected $students;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = Teacher::factory()->create();
        $this->students = Student::factory()->count(3)->create();
    }

    /** @test */
    public function teacher_can_create_course()
    {
        $response = $this->actingAs($this->teacher, 'teacher')
            ->postJson('/api/teacher/courses', [
                'name' => '测试课程',
                'year_month' => 202501,
                'fee' => 100.00,
                'student_ids' => $this->students->pluck('id')->toArray()
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertDatabaseHas('courses', [
            'name' => '测试课程',
            'teacher_id' => $this->teacher->id
        ]);
    }

    /** @test */
    public function teacher_can_get_course_list()
    {
        $courses = Course::factory()->count(3)->create([
            'teacher_id' => $this->teacher->id
        ]);

        $response = $this->actingAs($this->teacher, 'teacher')
            ->getJson('/api/courses');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonCount(3, 'data.data');
    }

    /** @test */
    public function teacher_can_get_course_detail()
    {
        $course = Course::factory()->create([
            'teacher_id' => $this->teacher->id
        ]);

        $response = $this->actingAs($this->teacher, 'teacher')
            ->getJson('/api/courses/' . $course->id);

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonPath('data.id', $course->id);
    }

    /** @test */
    public function teacher_can_update_course()
    {
        $course = Course::factory()->create([
            'teacher_id' => $this->teacher->id
        ]);

        $response = $this->actingAs($this->teacher, 'teacher')
            ->putJson('/api/courses/' . $course->id, [
                'student_ids' => $this->students->pluck('id')->toArray()
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertDatabaseCount('course_students', 3);
    }
}
