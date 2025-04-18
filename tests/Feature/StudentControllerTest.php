<?php

namespace Tests\Feature;

use App\Http\Controllers\StudentController;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = Teacher::factory()->create();
    }

    /** @test */
    public function teacher_can_get_student_list()
    {
        Student::factory()->count(3)->create(['teacher_id' => $this->teacher->id]);

        $response = $this->actingAs($this->teacher, 'teacher')
            ->getJson('/api/students');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonCount(3, 'data');
    }
}
