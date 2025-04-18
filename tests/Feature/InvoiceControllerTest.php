<?php

namespace Tests\Feature;

use App\Http\Controllers\InvoiceController;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $teacher;
    protected $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = Teacher::factory()->create();
        $this->student = Student::factory()->create(['teacher_id' => $this->teacher->id]);
    }

    /** @test */
    public function teacher_can_create_invoice()
    {
        $response = $this->actingAs($this->teacher, 'teacher')
            ->postJson('/api/invoices', [
                'student_id' => $this->student->id,
                'amount' => 100.00,
                'due_date' => '2025-01-01'
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertDatabaseHas('invoices', [
            'student_id' => $this->student->id,
            'amount' => 100.00
        ]);
    }
}
