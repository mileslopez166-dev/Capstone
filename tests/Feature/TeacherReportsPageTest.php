<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherReportsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_page_requires_authentication(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_teacher_can_view_reports_page(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSeeText('Reports');
        $response->assertSeeText('No report data yet');
    }

    public function test_authenticated_teacher_can_view_individual_student_report_page(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create([
            'name' => 'Juan Dela Cruz',
        ]);

        $response = $this->actingAs($teacher)->get(route('reports.student'));

        $response->assertOk();
        $response->assertSeeText('Juan Dela Cruz');
        $response->assertSeeText('No individual recommendations yet');
    }
}
