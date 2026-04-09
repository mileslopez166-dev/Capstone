<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentActivitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_activities_page_requires_authentication(): void
    {
        $response = $this->get(route('student.activities'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_the_student_activities_page(): void
    {
        $user = User::factory()->create([
            'name' => 'Mateo Cruz',
        ]);

        $response = $this->actingAs($user)->get(route('student.activities'));

        $response->assertOk();
        $response->assertSeeText('Pending Assessments');
        $response->assertSeeText('No pending assessment');
        $response->assertSeeText('Awaiting teacher assignment');
    }

    public function test_student_activities_page_lists_published_teacher_assessments(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();

        Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Reading Fluency Check',
            'subject' => 'literacy',
            'instructions' => 'Read the passage before answering.',
            'status' => 'published',
        ]);

        Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Fractions Drill',
            'subject' => 'numeracy',
            'instructions' => 'Solve the number problems carefully.',
            'status' => 'published',
        ]);

        $response = $this->actingAs($student)->get(route('student.activities'));

        $response->assertOk();
        $response->assertSeeText('Pending assessments');
        $response->assertSeeText('Reading Fluency Check');
        $response->assertSeeText('Fractions Drill');
        $response->assertSeeText('Assessment available to open');
    }
}
