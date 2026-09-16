<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
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
        $response->assertSeeText('No answered student reports yet');
    }

    public function test_individual_report_without_answered_assessments_returns_to_reports_page(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->get(route('reports.student'));

        $response->assertRedirect(route('reports.index'));
        $response->assertSessionHas('status', 'No answered assessment reports are available yet.');
    }

    public function test_authenticated_teacher_can_view_individual_student_report_for_owned_assessment(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['name' => 'Juan Dela Cruz']);
        $assessment = $this->createAssessment($teacher, ['title' => 'Owned Reading Mission']);
        $this->createSubmission($assessment, $student);

        $response = $this->actingAs($teacher)->get(route('reports.student'));

        $response->assertOk();
        $response->assertSeeText('Juan Dela Cruz');
        $response->assertSeeText('Owned Reading Mission');
    }

    public function test_teacher_cannot_open_report_for_student_without_teacher_owned_answers(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['name' => 'Hidden Student']);
        $foreignAssessment = $this->createAssessment($otherTeacher, ['title' => 'Other Teacher Assessment']);
        $this->createSubmission($foreignAssessment, $student);

        $this->actingAs($teacher)
            ->get(route('reports.student', ['student' => $student]))
            ->assertNotFound();
    }

    public function test_teacher_report_only_shows_submissions_from_assessments_they_created(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['name' => 'Shared Student']);

        $ownedAssessment = $this->createAssessment($teacher, ['title' => 'My Fluency Check']);
        $foreignAssessment = $this->createAssessment($otherTeacher, ['title' => 'Other Teacher Check']);
        $this->createSubmission($ownedAssessment, $student, ['points' => 250, 'correct_count' => 1]);
        $this->createSubmission($foreignAssessment, $student, ['points' => 500, 'correct_count' => 2]);

        $response = $this->actingAs($teacher)->get(route('reports.student', ['student' => $student]));

        $response->assertOk();
        $response->assertSeeText('My Fluency Check');
        $response->assertDontSeeText('Other Teacher Check');
        $response->assertSeeText('250');
        $response->assertDontSeeText('500');
    }

    public function test_reports_index_counts_only_teacher_owned_answers(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $ownedStudent = User::factory()->create(['name' => 'Owned Report Student']);
        $foreignStudent = User::factory()->create(['name' => 'Foreign Report Student']);

        $this->createSubmission($this->createAssessment($teacher), $ownedStudent);
        $this->createSubmission($this->createAssessment($otherTeacher), $foreignStudent);

        $response = $this->actingAs($teacher)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSeeText('1 completed assessments');
        $response->assertSeeText('Students with results: 1');
        $response->assertSee(route('reports.student', ['student' => $ownedStudent]));
        $response->assertDontSee(route('reports.student', ['student' => $foreignStudent]));
    }

    private function createAssessment(User $teacher, array $overrides = []): Assessment
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
            'manual_questions' => [[
                'question' => 'What happened first?',
                'answers' => [
                    'A' => 'The story began',
                    'B' => 'The story ended',
                    'C' => 'Nothing happened',
                    'D' => 'Everyone left',
                ],
                'correct_answer' => 'A',
            ]],
            'status' => 'published',
        ], $overrides));
    }

    private function createSubmission(Assessment $assessment, User $student, array $overrides = []): AssessmentSubmission
    {
        return AssessmentSubmission::query()->create(array_merge([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'attempt_number' => 1,
            'answers' => [0 => 'A'],
            'correct_count' => 1,
            'question_count' => 1,
            'points' => 250,
            'possible_points' => 250,
            'submitted_at' => now(),
        ], $overrides));
    }
}
