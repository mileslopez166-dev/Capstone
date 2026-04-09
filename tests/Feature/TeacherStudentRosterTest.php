<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherStudentRosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_dashboard_lists_real_student_accounts_from_database(): void
    {
        $teacher = User::factory()->teacher()->create();
        $studentA = User::factory()->create([
            'name' => 'Ana Reyes',
            'email' => 'ana@example.com',
        ]);
        $studentB = User::factory()->create([
            'name' => 'Ben Cruz',
            'email' => 'ben@example.com',
        ]);

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));

        $response->assertOk();
        $response->assertSeeText($studentA->name);
        $response->assertSeeText($studentB->name);
        $response->assertSeeText($studentA->email);
        $response->assertSeeText($studentB->email);
    }

    public function test_students_index_lists_real_student_accounts_from_database(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create([
            'name' => 'Carla Santos',
            'email' => 'carla@example.com',
        ]);

        $response = $this->actingAs($teacher)->get(route('students.index'));

        $response->assertOk();
        $response->assertSeeText($student->name);
        $response->assertSeeText($student->email);
    }
}
