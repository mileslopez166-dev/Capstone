<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_search_students_and_assessments(): void
    {
        $teacher = User::factory()->teacher()->create(['name' => 'Teacher One']);
        $student = User::factory()->create([
            'name' => 'Miles Aidrian Lopez',
            'email' => 'miles@example.com',
            'section' => 'Section A',
        ]);
        $assessment = $this->assessment($teacher, [
            'title' => 'Frog Reading Mission',
            'status' => 'published',
        ]);

        $studentResponse = $this->actingAs($teacher)->get(route('teacher.search', ['q' => 'Miles']));

        $studentResponse
            ->assertOk()
            ->assertSeeText('Results for "Miles"')
            ->assertSeeText($student->name)
            ->assertSee(route('students.show', $student), false);

        $assessmentResponse = $this->actingAs($teacher)->get(route('teacher.search', ['q' => 'Frog']));

        $assessmentResponse
            ->assertOk()
            ->assertSeeText($assessment->title)
            ->assertSee(route('assessments.show', $assessment), false);
    }

    public function test_student_can_search_visible_assessments_and_pages(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create([
            'name' => 'Student One',
            'section' => 'Section A',
            'approval_status' => 'approved',
        ]);
        $visibleAssessment = $this->assessment($teacher, [
            'title' => 'Apple Story Quiz',
            'target_section' => 'section_a',
            'status' => 'published',
        ]);
        $hiddenAssessment = $this->assessment($teacher, [
            'title' => 'Banana Story Quiz',
            'target_section' => 'section_b',
            'status' => 'published',
        ]);

        $response = $this->actingAs($student)->get(route('student.search', ['q' => 'Story']));

        $response
            ->assertOk()
            ->assertSeeText($visibleAssessment->title)
            ->assertSee(route('student.assessments.show', $visibleAssessment), false)
            ->assertDontSeeText($hiddenAssessment->title);

        $this->actingAs($student)
            ->get(route('student.search', ['q' => 'leaderboard']))
            ->assertOk()
            ->assertSeeText('Leaderboard')
            ->assertSee(route('student.leaderboard'), false);
    }

    public function test_role_search_pages_reject_the_wrong_role(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['approval_status' => 'approved']);

        $this->actingAs($student)->get(route('teacher.search', ['q' => 'student']))->assertForbidden();
        $this->actingAs($teacher)->get(route('student.search', ['q' => 'assessment']))->assertForbidden();
    }

    public function test_student_and_teacher_headers_submit_to_search_routes(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['approval_status' => 'approved']);

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('action="'.route('teacher.search').'"', false)
            ->assertSee('name="q"', false);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('action="'.route('student.search').'"', false)
            ->assertSee('name="q"', false);
    }

    private function assessment(User $teacher, array $overrides = []): Assessment
    {
        return Assessment::query()->create(array_merge([
            'created_by' => $teacher->id,
            'title' => 'Reading Mission',
            'subject' => 'literacy',
            'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual',
            'target_section' => 'all',
            'assessment_type' => 'silent_reading',
            'focus_areas' => ['Reading Fluency'],
            'instructions' => 'Answer carefully.',
            'status' => 'published',
            'manual_questions' => [],
        ], $overrides));
    }
}