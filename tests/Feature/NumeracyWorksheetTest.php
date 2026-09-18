<?php

namespace Tests\Feature;

use App\Models\{Assessment, AssessmentProgress, AssessmentRetakeRequest, AssessmentSubmission, AppNotification, User, WorksheetAttempt};
use App\Support\NumeracyWorksheets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumeracyWorksheetTest extends TestCase
{
    use RefreshDatabase;

    private function assignment(User $teacher, array $extra = []): Assessment
    {
        return Assessment::create(array_merge(['created_by' => $teacher->id, 'title' => 'ARAL Worksheet 1',
            'subject' => 'numeracy', 'quiz_type' => 'worksheet', 'delivery_method' => 'manual', 'assessment_type' => 'worksheet',
            'worksheet_number' => 1, 'worksheet_total' => 20, 'target_section' => 'all', 'status' => 'published',
            'manual_questions' => [], 'focus_areas' => ['Problem Solving']], $extra));
    }

    private function payload(AssessmentProgress $progress, int $revision = 1): array
    {
        return ['attempt_key' => $progress->attempt_key, 'revision' => $revision, 'page' => 1, 'pages' => [
            ['text' => '1. 245 is divisible by 5.', 'strokes' => []],
            ['text' => '', 'strokes' => [['color' => '#174d97', 'width' => 3, 'points' => [[.2, .3], [.4, .5]]]]],
        ]];
    }

    public function test_catalog_preserves_all_35_real_worksheets_and_71_parts(): void
    {
        $library = NumeracyWorksheets::all();
        $this->assertSame(range(1, 35), array_column($library, 'number'));
        $this->assertSame(71, collect($library)->sum(fn ($sheet) => count($sheet['pages'])));
        foreach ($library as $sheet) foreach ($sheet['pages'] as $page) {
            $this->assertFileExists(resource_path('worksheets/aral-g6/'.$page['image']));
            $this->assertGreaterThan(1000, $page['width']);
        }
    }

    public function test_teacher_can_preview_and_assign_one_worksheet_without_changing_source(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher)->get(route('worksheets.index'))->assertOk()->assertSee('Worksheet 35')->assertDontSee('Worksheet 36');
        $this->get(route('worksheets.create', 25))->assertOk()->assertSee('Part 3');
        $this->get(route('worksheets.create', 36))->assertNotFound();
        $this->post(route('worksheets.store', 25), ['title' => 'Polygon workbook', 'target_section' => 'section_a',
            'worksheet_total' => 18, 'retry_limit' => 'unlimited', 'status' => 'draft', 'created_by' => 999])
            ->assertSessionHasNoErrors()->assertRedirect();
        $assignment = Assessment::firstOrFail();
        $this->assertSame($teacher->id, $assignment->created_by);
        $this->assertSame(25, $assignment->worksheet_number);
        $this->assertTrue($assignment->hasUnlimitedRetries());
        $this->get(route('assessments.show', $assignment))->assertOk()->assertSee('18 scored items');
        $this->assertDatabaseCount(AssessmentSubmission::class, 0);
        $this->actingAs(User::factory()->create())->get(route('worksheets.index'))->assertForbidden();
        $this->post(route('worksheets.store', 1), [])->assertForbidden();
    }

    public function test_page_assets_and_assignments_enforce_student_visibility_and_teacher_ownership(): void
    {
        $teacher = User::factory()->teacher()->create();
        $assignment = $this->assignment($teacher, ['target_section' => 'section_a']);
        $this->actingAs(User::factory()->create(['section' => 'Section B']))->get(route('student.assessments.show', $assignment))->assertNotFound();
        $this->get(route('worksheets.image', [1, 1]))->assertNotFound();
        $this->actingAs(User::factory()->create(['section' => 'Section A']))->get(route('student.assessments.show', $assignment))
            ->assertOk()->assertSee('data-worksheet-reader', false)->assertDontSee('assessment-game-music');
        $this->get(route('worksheets.image', [1, 1]))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->get(route('worksheets.image', [1, 3]))->assertNotFound();
        $assignment->update(['status' => 'draft']);
        $this->get(route('student.assessments.show', $assignment))->assertNotFound();
        $this->get(route('worksheets.image', [1, 1]))->assertNotFound();
        $this->actingAs(User::factory()->teacher()->create())->get(route('assessments.show', $assignment))->assertNotFound();
    }

    public function test_typed_answers_drawings_and_current_part_resume_and_stale_saves_do_not_overwrite(): void
    {
        $student = User::factory()->create();
        $assignment = $this->assignment(User::factory()->teacher()->create());
        $progress = AssessmentProgress::forAttempt($assignment, $student, 1);
        $payload = $this->payload($progress, 3);
        $this->actingAs($student)->postJson(route('worksheets.save', $assignment), $payload)->assertOk()->assertJsonPath('revision', 3);
        $older = $this->payload($progress, 1); $older['pages'][0]['text'] = 'old';
        $this->postJson(route('worksheets.save', $assignment), $older)->assertOk()->assertJsonPath('revision', 3);
        $this->assertSame($payload['pages'], $progress->fresh()->state['pages']);
        $this->get(route('student.assessments.show', $assignment))->assertOk()->assertSee('245 is divisible by 5');
        $this->assertDatabaseCount(AssessmentProgress::class, 1);
        $this->actingAs(User::factory()->create())->postJson(route('worksheets.save', $assignment), $payload)->assertNotFound();
    }

    public function test_invalid_drawing_pages_and_old_game_submission_endpoint_are_rejected(): void
    {
        $student = User::factory()->create();
        $assignment = $this->assignment(User::factory()->teacher()->create());
        $progress = AssessmentProgress::forAttempt($assignment, $student, 1);
        $payload = $this->payload($progress);
        $payload['pages'][1]['strokes'][0]['points'][0][0] = 2;
        $this->actingAs($student)->postJson(route('worksheets.save', $assignment), $payload)->assertUnprocessable();
        $payload = $this->payload($progress); $payload['pages'][0]['text'] = '';
        $this->postJson(route('worksheets.submit', $assignment), $payload)->assertUnprocessable();
        $this->postJson(route('student.assessments.submit', $assignment), ['answers' => []])->assertUnprocessable();
        $this->assertDatabaseCount(AssessmentSubmission::class, 0);
        $this->assertDatabaseCount(WorksheetAttempt::class, 0);
    }

    public function test_interactive_answers_and_reading_step_are_saved_submitted_and_reviewed(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $assignment = $this->assignment($teacher);
        $progress = AssessmentProgress::forAttempt($assignment, $student, 1);
        $payload = $this->payload($progress);
        $payload['step'] = 'answer';
        $payload['pages'] = [
            ['text' => '', 'strokes' => [], 'answers' => [['label' => 'A.1', 'answer' => 'Divisible by 5'], ['label' => 'A.2', 'answer' => '3/4']]],
            ['text' => '', 'strokes' => [], 'answers' => [['label' => 'B.1', 'answer' => '120']]],
        ];
        $this->actingAs($student)->postJson(route('worksheets.save', $assignment), $payload)->assertOk();
        $this->assertSame('answer', $progress->fresh()->state['step']);
        $this->assertEquals($payload['pages'], $progress->fresh()->state['pages']);
        $this->get(route('student.assessments.show', $assignment))->assertOk()
            ->assertSee('Ready to Answer')->assertSee('Reading settings')->assertSee('A.1');
        $this->postJson(route('worksheets.submit', $assignment), $payload)->assertOk();
        $attempt = WorksheetAttempt::firstOrFail();
        $this->assertEquals($payload['pages'], $attempt->pages);
        $this->assertDatabaseCount(AssessmentSubmission::class, 0);
        $this->actingAs($teacher)->get(route('worksheets.review', $attempt))->assertOk()->assertSee('Divisible by 5')->assertSee('Earlier saved answers');
        $this->post(route('worksheets.grade', $attempt), ['score' => 17, 'feedback' => 'Check A.2.'])->assertSessionHasNoErrors();
        $this->actingAs($student)->get(route('worksheets.review', $attempt))->assertOk()->assertSee('Check A.2.');
    }

    public function test_answer_rows_are_validated_and_empty_rows_do_not_complete_parts(): void
    {
        $student = User::factory()->create();
        $assignment = $this->assignment(User::factory()->teacher()->create());
        $progress = AssessmentProgress::forAttempt($assignment, $student, 1);
        $payload = $this->payload($progress);
        $payload['pages'][0] = ['text' => '', 'strokes' => [], 'answers' => [['label' => '1', 'answer' => '   ']]];
        $this->actingAs($student)->postJson(route('worksheets.save', $assignment), $payload)->assertOk();
        $this->postJson(route('worksheets.submit', $assignment), $payload)->assertUnprocessable();
        $payload['pages'][0]['answers'][0]['answer'] = str_repeat('a', 2001);
        $this->postJson(route('worksheets.save', $assignment), $payload)->assertUnprocessable()->assertJsonValidationErrors('pages.0.answers.0.answer');
        $payload['pages'][0]['answers'] = ['malformed' => ['label' => '1', 'answer' => '5']];
        $this->postJson(route('worksheets.save', $assignment), $payload)->assertUnprocessable();
        $payload['pages'][0]['answers'] = array_fill(0, 101, ['label' => '1', 'answer' => '5']);
        $this->postJson(route('worksheets.save', $assignment), $payload)->assertUnprocessable()->assertJsonValidationErrors('pages.0.answers');
        $payload['pages'][0]['answers'] = [];
        $payload['step'] = 'invalid';
        $this->postJson(route('worksheets.save', $assignment), $payload)->assertUnprocessable()->assertJsonValidationErrors('step');
        $this->assertDatabaseCount(WorksheetAttempt::class, 0);
    }

    public function test_submission_waits_for_teacher_review_then_score_flows_into_activities_and_reports_once(): void
    {
        $teacher = User::factory()->teacher()->create(); $student = User::factory()->create();
        $assignment = $this->assignment($teacher);
        $progress = AssessmentProgress::forAttempt($assignment, $student, 1);
        $this->actingAs($student)->postJson(route('worksheets.submit', $assignment), $this->payload($progress))->assertOk();
        $attempt = WorksheetAttempt::firstOrFail();
        $this->postJson(route('worksheets.submit', $assignment), $this->payload($progress))->assertOk();
        $this->assertDatabaseCount(WorksheetAttempt::class, 1);
        $this->assertDatabaseCount(AssessmentSubmission::class, 0);
        $this->assertDatabaseHas(AppNotification::class, ['user_id' => $teacher->id, 'type' => 'worksheet_submitted']);
        $this->get(route('student.assessments.show', $assignment))->assertRedirect(route('worksheets.review', $attempt));
        $this->get(route('student.activities'))->assertOk()->assertSee('Worksheets Awaiting Review');
        $this->get(route('worksheets.review', $attempt))->assertOk()->assertSee('Awaiting Teacher Review');
        $this->postJson(route('worksheets.save', $assignment), $this->payload($progress, 2))->assertStatus(409);
        $this->post(route('worksheets.grade', $attempt), ['score' => 20, 'feedback' => 'self'])->assertForbidden();
        $this->actingAs(User::factory()->teacher()->create())->get(route('worksheets.review', $attempt))->assertNotFound();
        $this->post(route('worksheets.grade', $attempt), ['score' => 20, 'feedback' => 'other'])->assertNotFound();
        $this->actingAs($teacher)->get(route('worksheets.review', $attempt))->assertOk()->assertSee($student->name);
        $this->post(route('worksheets.grade', $attempt), ['score' => 21, 'feedback' => 'Too high'])->assertSessionHasErrors('score');
        $this->post(route('worksheets.grade', $attempt), ['score' => 15, 'feedback' => 'Check items 3 and 7.'])->assertSessionHasNoErrors();
        $this->post(route('worksheets.grade', $attempt), ['score' => 20, 'feedback' => 'Duplicate'])->assertRedirect();
        $this->assertDatabaseCount(AssessmentSubmission::class, 1);
        $this->assertDatabaseHas(AssessmentSubmission::class, ['correct_count' => 15, 'question_count' => 20, 'points' => 3750, 'possible_points' => 5000]);
        $this->get(route('reports.student', $student))->assertOk()->assertSee($assignment->title);
        $this->actingAs($student)->get(route('student.activities'))->assertOk()->assertSee('Worksheet &amp; Teacher Feedback', false)->assertDontSee('Worksheets Awaiting Review');
        $this->get(route('worksheets.review', $attempt))->assertOk()->assertSee('Check items 3 and 7.');
        $this->get(route('student.assessments.show', $assignment))->assertRedirect(route('student.dashboard'));
        $this->assertDatabaseHas(AssessmentRetakeRequest::class, ['assessment_id' => $assignment->id, 'user_id' => $student->id, 'status' => 'pending']);
    }

    public function test_retry_token_is_consumed_once_at_submission_and_not_again_at_grading(): void
    {
        $teacher = User::factory()->teacher()->create(); $student = User::factory()->create();
        $assignment = $this->assignment($teacher);
        AssessmentSubmission::create(['assessment_id' => $assignment->id, 'user_id' => $student->id, 'attempt_number' => 1,
            'answers' => [], 'correct_count' => 10, 'question_count' => 20, 'points' => 2500, 'possible_points' => 5000, 'submitted_at' => now()]);
        $token = AssessmentRetakeRequest::create(['assessment_id' => $assignment->id, 'teacher_id' => $teacher->id, 'user_id' => $student->id,
            'status' => 'approved', 'requested_tries' => 1, 'approved_tries' => 1, 'remaining_tries' => 1]);
        $progress = AssessmentProgress::forAttempt($assignment, $student, 2);
        $this->actingAs($student)->postJson(route('worksheets.submit', $assignment), $this->payload($progress))->assertOk();
        $this->postJson(route('worksheets.submit', $assignment), $this->payload($progress))->assertOk();
        $this->assertSame(0, $token->fresh()->remaining_tries);
        $attempt = WorksheetAttempt::firstOrFail();
        $this->get(route('student.assessments.show', $assignment))->assertRedirect(route('worksheets.review', $attempt));
        $this->actingAs($teacher)->post(route('worksheets.grade', $attempt), ['score' => 18, 'feedback' => 'Much improved.'])->assertSessionHasNoErrors();
        $this->assertSame(0, $token->fresh()->remaining_tries);
        $this->assertDatabaseHas(AssessmentSubmission::class, ['attempt_number' => 2, 'correct_count' => 18]);
    }
}
