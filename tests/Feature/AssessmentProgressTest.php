<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentProgress;
use App\Models\AssessmentRetakeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentProgressTest extends TestCase
{
    use RefreshDatabase;

    private function assessment(array $attributes = []): Assessment
    {
        return Assessment::query()->create(array_merge([
            'created_by' => User::factory()->teacher()->create()->id,
            'title' => 'Resume Reading', 'subject' => 'literacy', 'quiz_type' => 'flashcards',
            'delivery_method' => 'manual', 'assessment_type' => 'silent_reading',
            'target_section' => 'all', 'status' => 'published',
            'story_title' => 'A new day', 'story_description' => 'We read. We learn.',
            'manual_questions' => [
                ['question' => 'First?', 'answers' => ['A' => 'One', 'B' => 'Two', 'C' => 'Three', 'D' => 'Four'], 'correct_answer' => 'A'],
                ['question' => 'Second?', 'answers' => ['A' => 'One', 'B' => 'Two', 'C' => 'Three', 'D' => 'Four'], 'correct_answer' => 'B'],
            ],
        ], $attributes));
    }

    private function state(array $overrides = []): array
    {
        return array_merge([
            'answers' => [0 => 'A'], 'phase' => 'questions', 'reading_seconds' => 42,
            'timer_status' => 'finished', 'word_marks' => [0, 1, 2, 0],
            'sentence_marks' => [1, 0], 'mark_mode' => 'sentence-1', 'scroll_ratio' => .5,
        ], $overrides);
    }

    public function test_progress_resumes_without_consuming_an_attempt(): void
    {
        $student = User::factory()->create();
        $assessment = $this->assessment();
        $this->actingAs($student)->get(route('student.assessments.show', $assessment))->assertOk();
        $progress = AssessmentProgress::query()->firstOrFail();
        $this->postJson(route('student.assessments.progress', $assessment), [
            'attempt_key' => $progress->attempt_key, 'revision' => 100, 'state' => $this->state(),
        ])->assertOk()->assertJsonPath('revision', 100);
        $this->get(route('student.assessments.show', $assessment))->assertOk()
            ->assertViewHas('progress', fn ($resumed) => $resumed->attempt_key === $progress->attempt_key && $resumed->state == $this->state());
        $this->assertDatabaseCount('assessment_progress', 1);
        $this->assertDatabaseCount('assessment_submissions', 0);
        $this->assertDatabaseCount('assessment_retake_requests', 0);
    }

    public function test_older_autosave_cannot_replace_newer_progress(): void
    {
        $assessment = $this->assessment();
        $this->actingAs(User::factory()->create())->get(route('student.assessments.show', $assessment));
        $progress = AssessmentProgress::query()->firstOrFail();
        $this->postJson(route('student.assessments.progress', $assessment), [
            'attempt_key' => $progress->attempt_key, 'revision' => 200, 'state' => $this->state(),
        ])->assertOk();
        $this->postJson(route('student.assessments.progress', $assessment), [
            'attempt_key' => $progress->attempt_key, 'revision' => 100, 'state' => $this->state(['answers' => [], 'phase' => 'reading']),
        ])->assertOk()->assertJsonPath('revision', 200);
        $this->assertEquals($this->state(), $progress->fresh()->state);
    }

    public function test_students_get_configured_retries_then_require_a_token(): void
    {
        $assessment = $this->assessment(['retry_limit' => 2]);
        $this->actingAs(User::factory()->create());
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->get(route('student.assessments.show', $assessment))->assertOk();
            $this->postJson(route('student.assessments.submit', $assessment), ['answers' => ['A', 'B']])
                ->assertOk()->assertJsonPath('attempt_number', $attempt);
            if ($attempt < 3) {
                $this->get(route('student.activities', ['subject' => $assessment->subject]))->assertOk()
                    ->assertViewHas('pendingAssessments', fn ($items) => $items->contains('id', $assessment->id))
                    ->assertViewHas('completedSubmissions', fn ($items) => $items->first()->remaining_retake_tries === 3 - $attempt);
                $this->get(route('student.dashboard'))->assertViewHas('studentMetrics', fn ($metrics) => $metrics['pending_count'] === 1);
                $this->get(route('student.search', ['q' => 'Resume']))->assertViewHas('assessments', fn ($items) => $items->first()->can_open);
            }
        }
        $this->postJson(route('student.assessments.submit', $assessment), ['answers' => ['A', 'B']])->assertForbidden();
        $this->get(route('student.assessments.show', $assessment))->assertRedirect(route('student.dashboard'));
        $this->assertDatabaseCount('assessment_submissions', 3);
        $this->assertDatabaseCount('assessment_retake_requests', 1);
    }

    public function test_unlimited_retries_do_not_require_a_retake_token(): void
    {
        $assessment = $this->assessment(['retry_limit' => Assessment::UNLIMITED_RETRY_LIMIT]);
        $this->actingAs(User::factory()->create());

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->get(route('student.assessments.show', $assessment))->assertOk();
            $this->postJson(route('student.assessments.submit', $assessment), ['answers' => ['A', 'B']])
                ->assertOk()
                ->assertJsonPath('attempt_number', $attempt);
        }

        $this->get(route('student.activities', ['subject' => $assessment->subject]))->assertOk()
            ->assertViewHas('pendingAssessments', fn ($items) => $items->contains('id', $assessment->id))
            ->assertViewHas('completedSubmissions', fn ($items) => $items->first()->remaining_retake_tries === PHP_INT_MAX);
        $this->assertDatabaseCount('assessment_submissions', 5);
        $this->assertDatabaseCount('assessment_retake_requests', 0);
    }

    public function test_duplicate_submission_does_not_consume_another_retry_or_token(): void
    {
        $student = User::factory()->create();
        $assessment = $this->assessment(['retry_limit' => 1]);
        $token = AssessmentRetakeRequest::query()->create([
            'assessment_id' => $assessment->id, 'user_id' => $student->id, 'teacher_id' => $assessment->created_by,
            'status' => 'approved', 'requested_tries' => 1, 'approved_tries' => 1, 'remaining_tries' => 1, 'decided_at' => now(),
        ]);
        $keys = [];
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->actingAs($student)->get(route('student.assessments.show', $assessment))->assertOk();
            $progress = AssessmentProgress::query()->where('attempt_number', $attempt)->firstOrFail();
            $keys[] = $progress->attempt_key;
            $this->assertSame([], $progress->state);
            $payload = ['attempt_key' => $progress->attempt_key, 'answers' => ['A', 'B'], 'state' => $this->state(), 'revision' => 100];
            $this->postJson(route('student.assessments.submit', $assessment), $payload)->assertOk()->assertJsonPath('attempt_number', $attempt);
            $this->postJson(route('student.assessments.submit', $assessment), array_merge($payload, ['answers' => ['C', 'D']]))
                ->assertOk()->assertJsonPath('attempt_number', $attempt)->assertJsonPath('points', 500);
            $this->postJson(route('student.assessments.progress', $assessment), $payload)->assertStatus(409);
            $this->assertSame($attempt < 3 ? 1 : 0, $token->fresh()->remaining_tries);
        }
        $this->assertCount(3, array_unique($keys));
        $this->assertDatabaseCount('assessment_submissions', 3);
        $this->assertSame(3, \App\Models\AppNotification::query()->where('type', 'assessment_completed')->count());
    }

    public function test_progress_is_private_and_locked_assessments_stay_locked(): void
    {
        $assessment = $this->assessment();
        $student = User::factory()->create();
        $this->actingAs($student)->get(route('student.assessments.show', $assessment));
        $progress = AssessmentProgress::query()->firstOrFail();
        $payload = ['attempt_key' => $progress->attempt_key, 'state' => $this->state(), 'revision' => 10];
        $this->actingAs(User::factory()->create())->postJson(route('student.assessments.progress', $assessment), $payload)->assertNotFound();
        $this->postJson(route('student.assessments.submit', $assessment), $payload + ['answers' => ['A', 'B']])->assertNotFound();
        $this->actingAs(User::factory()->teacher()->create())->postJson(route('student.assessments.progress', $assessment), $payload)->assertForbidden();
        $assessment->update(['status' => 'draft']);
        $this->actingAs($student)->postJson(route('student.assessments.progress', $assessment), $payload)->assertNotFound();
        $this->postJson(route('student.assessments.submit', $assessment), $payload + ['answers' => ['A', 'B']])->assertNotFound();
        $assessment->update(['status' => 'published', 'target_section' => 'section_c']);
        $student->update(['section' => 'Section A']);
        $this->actingAs($student)->postJson(route('student.assessments.progress', $assessment), $payload)->assertNotFound();
        $this->assertDatabaseCount('assessment_submissions', 0);
    }

    public function test_progress_validation_rejects_invalid_questions_and_marks(): void
    {
        $assessment = $this->assessment();
        $this->actingAs(User::factory()->create())->get(route('student.assessments.show', $assessment));
        $progress = AssessmentProgress::query()->firstOrFail();
        foreach ([['answers' => [999 => 'A']], ['answers' => ['oops' => 'B']], ['word_marks' => [3]], ['reading_seconds' => -1], ['phase' => 'invalid']] as $invalid) {
            $this->postJson(route('student.assessments.progress', $assessment), [
                'attempt_key' => $progress->attempt_key, 'revision' => 1, 'state' => $this->state($invalid),
            ])->assertUnprocessable();
        }
        $this->postJson(route('student.assessments.submit', $assessment), ['answers' => ['A']])->assertUnprocessable();
        $this->assertSame([], $progress->fresh()->state);
    }

    public function test_oral_reading_can_resume_marks_and_finish_without_questions(): void
    {
        $assessment = $this->assessment(['assessment_type' => 'oral_reading', 'manual_questions' => []]);
        $this->actingAs(User::factory()->create())->get(route('student.assessments.show', $assessment));
        $progress = AssessmentProgress::query()->firstOrFail();
        $state = $this->state(['answers' => [], 'phase' => 'reading', 'timer_status' => 'idle']);
        $this->postJson(route('student.assessments.progress', $assessment), [
            'attempt_key' => $progress->attempt_key, 'revision' => 100, 'state' => $state,
        ])->assertOk();
        $this->get(route('student.assessments.show', $assessment))->assertViewHas('progress', fn ($row) => $row->state == $state);
        $this->postJson(route('student.assessments.submit', $assessment), [
            'attempt_key' => $progress->attempt_key, 'answers' => [], 'state' => $state, 'revision' => 101,
        ])->assertOk()->assertJsonPath('question_count', 0);
        $this->assertSame([0, 1, 2, 0], $progress->fresh()->state['word_marks']);
        $this->assertSame('finished', $progress->fresh()->state['phase']);
    }
}
