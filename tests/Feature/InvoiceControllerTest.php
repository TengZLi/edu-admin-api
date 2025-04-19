<?php

namespace Tests\Feature;

use App\Http\Controllers\InvoiceController;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Course;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $teacher;
    protected $student;
    protected $course;

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
        $this->teacher = Teacher::where('role_type', Teacher::ROLE_TYPE_ORDINARY_TEACHER)->where('id', $studentMaxTeacher->id)->first() ?? Teacher::factory()->create();
        $this->student = Student::where('teacher_id', $this->teacher->id)->first();

        // 如果没有该教师的学生，则创建一个
        if (!$this->student) {
            $this->student = Student::factory()->create(['teacher_id' => $this->teacher->id]);
        }

        // 确保有一个课程可用于测试
        $this->course = Course::where('teacher_id', $this->teacher->id)->first();
        if (!$this->course) {
            $this->course = Course::factory()->create(['teacher_id' => $this->teacher->id]);
        }
    }

    /** @test */
    public function teacher_can_create_invoice()
    {
        $response = $this->actingAs($this->teacher, 'teacher')
            ->postJson('/api/teacher/invoice', [
                'course_id' => $this->course->id,
                'student_id' => $this->student->id,
                'amount' => 100.00
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertDatabaseHas('invoices', [
            'student_id' => $this->student->id,
            'amount' => 100.00
        ]);
    }

    /** @test */
    public function invoice_creation_fails_with_missing_course_id()
    {
        $response = $this->actingAs($this->teacher, 'teacher')
            ->postJson('/api/teacher/invoice', [
                'student_id' => $this->student->id,
                'amount' => 100.00
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 1]); // 验证失败返回错误码
    }

    /** @test */
    public function invoice_creation_fails_with_missing_student_id()
    {
        $response = $this->actingAs($this->teacher, 'teacher')
            ->postJson('/api/teacher/invoice', [
                'course_id' => $this->course->id,
                'amount' => 100.00
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 1]); // 验证失败返回错误码
    }

    /** @test */
    public function invoice_creation_fails_with_negative_amount()
    {
        $response = $this->actingAs($this->teacher, 'teacher')
            ->postJson('/api/teacher/invoice', [
                'course_id' => $this->course->id,
                'student_id' => $this->student->id,
                'amount' => -50.00 // 负数金额
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 1]); // 验证失败返回错误码
    }
}
