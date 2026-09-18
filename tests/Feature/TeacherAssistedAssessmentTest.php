<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentProgress;
use App\Models\AssessmentRetakeRequest;
use App\Models\User;
use App\Models\WorksheetAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAssistedAssessmentTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(array $attributes = []): array
    {
        $teacher = User::factory()->teacher()->create(['section' => 'Section A']);
        $student = User::factory()->create(['section' => 'Section A']);
        $assessment = Assessment::create(array_merge([
            'created_by' => $teacher->id, 'title' => 'Shared Device Reading', 'subject' => 'literacy',
            'quiz_type' => 'flashcards', 'delivery_method' => 'manual', 'assessment_type' => 'silent_reading',
            'target_section' => 'section_a', 'status' => 'published', 'retry_limit' => 0,
            'story_title' => 'A new day', 'story_description' => 'We read. We learn.',
            'manual_questions' => [['question' => 'What do we do?', 'answers' => ['A' => 'Read', 'B' => 'Run', 'C' => 'Cook', 'D' => 'Sleep'], 'correct_answer' => 'A']],
        ], $attributes));

        return [$teacher, $student, $assessment];
    }

    private function state(): array
    {
        return ['answers' => [], 'phase' => 'reading', 'reading_seconds' => 15, 'timer_status' => 'paused',
            'word_marks' => [2, 0, 0, 0], 'sentence_marks' => [], 'mark_mode' => 'word', 'scroll_ratio' => .2];
    }

    private function open(User $teacher, User $student, Assessment $assessment): AssessmentProgress
    {
        $this->actingAs($teacher)->get(route('teacher.assessments.take', [$assessment, $student]))
            ->assertOk()->assertViewHas('student', fn ($user) => $user->is($student))->assertViewHas('assisted', true)
            ->assertSee('Teacher-Assisted Assessment')->assertSee($student->name);
        $this->assertAuthenticatedAs($teacher);

        return AssessmentProgress::where('assessment_id', $assessment->id)->where('user_id', $student->id)->firstOrFail();
    }

    public function test_picker_and_profile_only_offer_eligible_students_and_owned_assessments(): void
    {
        [$teacher, $student, $assessment] = $this->fixture();
        User::factory()->create(['section' => 'Section B']);
        User::factory()->pendingApproval()->create(['section' => 'Section A']);
        $this->actingAs($teacher)->get(route('assessments.show', $assessment))->assertOk()
            ->assertViewHas('eligibleStudents', fn ($users) => $users->pluck('id')->all() === [$student->id])
            ->assertSee('Take with a Student');
        $this->get(route('teacher.assessments.start', [$assessment, 'student_id' => $student->id]))
            ->assertRedirect(route('teacher.assessments.take', [$assessment, $student]));
        $this->get(route('teacher.assessments.start', $assessment))->assertSessionHasErrors('student_id');
        $this->fixture();
        $this->get(route('students.show', $student))->assertOk()
            ->assertViewHas('assistedAssessments', fn ($rows) => $rows->pluck('id')->all() === [$assessment->id]);
        $assessment->update(['status' => 'draft']);
        $this->get(route('assessments.show', $assessment))->assertOk()->assertSee('This assessment is locked.');
        $this->get(route('students.show', $student))->assertViewHas('assistedAssessments', fn ($rows) => $rows->isEmpty());
    }

    public function test_teacher_saves_resumes_and_submits_under_student_without_switching_login(): void
    {
        [$teacher, $student, $assessment] = $this->fixture();
        $progress = $this->open($teacher, $student, $assessment);
        $payload = ['attempt_key' => $progress->attempt_key, 'revision' => 10, 'state' => $this->state()];
        $this->postJson(route('teacher.assessments.progress', [$assessment, $student]), $payload)->assertOk();
        $this->get(route('teacher.assessments.take', [$assessment, $student]))->assertOk()
            ->assertViewHas('progress', fn ($saved) => $saved->state == $this->state() && $saved->attempt_key === $progress->attempt_key);
        $this->postJson(route('teacher.assessments.submit', [$assessment, $student]), $payload + ['answers' => ['A']])
            ->assertOk()->assertJsonPath('points', 250);
        $this->postJson(route('teacher.assessments.submit', [$assessment, $student]), $payload + ['answers' => ['B']])
            ->assertOk()->assertJsonPath('points', 250);
        $this->assertAuthenticatedAs($teacher);
        $this->assertDatabaseCount('assessment_submissions', 1);
        $this->assertDatabaseHas('assessment_submissions', ['assessment_id' => $assessment->id, 'user_id' => $student->id, 'points' => 250]);
        $this->assertDatabaseMissing('assessment_submissions', ['user_id' => $teacher->id]);
        $this->assertSame($teacher->id, $progress->fresh()->administered_by);
        $this->actingAs($student)->get(route('student.activities'))->assertOk()
            ->assertViewHas('completedSubmissions', fn ($rows) => $rows->contains('assessment_id', $assessment->id));
    }

    public function test_student_can_continue_teachers_saved_progress_and_cannot_spoof_participant_or_assistance(): void
    {
        [$teacher, $student, $assessment] = $this->fixture();
        $other = User::factory()->create(['section' => 'Section A']);
        $progress = $this->open($teacher, $student, $assessment);
        $this->postJson(route('teacher.assessments.progress', [$assessment, $student]), [
            'attempt_key' => $progress->attempt_key, 'revision' => 10, 'state' => $this->state(),
        ])->assertOk();
        $this->actingAs($student)->get(route('student.assessments.show', $assessment))
            ->assertOk()->assertViewHas('assisted', false)->assertViewHas('progress', fn ($row) => $row->state == $this->state());
        $this->postJson(route('student.assessments.submit', [$assessment, 'student_id' => $other->id]), [
            'answers' => ['A'], 'attempt_key' => $progress->attempt_key, 'student_id' => $other->id, 'administered_by' => $other->id,
        ])->assertOk();
        $this->assertDatabaseHas('assessment_submissions', ['user_id' => $student->id]);
        $this->assertDatabaseMissing('assessment_submissions', ['user_id' => $other->id]);
        $this->assertSame($teacher->id, $progress->fresh()->administered_by);
    }

    public function test_attempts_and_local_backups_are_isolated_between_students(): void
    {
        [$teacher, $student, $assessment] = $this->fixture();
        $other = User::factory()->create(['section' => 'Section A']);
        $first = $this->open($teacher, $student, $assessment);
        $second = $this->open($teacher, $other, $assessment);
        $this->assertNotSame($first->attempt_key, $second->attempt_key);
        $this->get(route('teacher.assessments.take', [$assessment, $other]))
            ->assertSee('assessment-progress:'.$other->id.':'.$assessment->id.':'.$second->attempt_key, false);
        $payload = ['attempt_key' => $first->attempt_key, 'revision' => 1, 'state' => $this->state(), 'answers' => ['A']];
        foreach (['progress', 'submit'] as $action) {
            $this->postJson(route('teacher.assessments.'.$action, [$assessment, $other]), $payload)->assertNotFound();
        }
        $this->postJson(route('teacher.assessments.submit', [$assessment, $student]), ['answers' => ['A']])
            ->assertUnprocessable()->assertJsonValidationErrors('attempt_key');
        $this->assertDatabaseCount('assessment_submissions', 0);
    }

    public function test_teachers_cannot_bypass_retries_and_duplicate_requests_do_not_consume_tokens_twice(): void
    {
        [$teacher, $student, $assessment] = $this->fixture();
        $progress = $this->open($teacher, $student, $assessment);
        $this->postJson(route('teacher.assessments.submit', [$assessment, $student]), [
            'attempt_key' => $progress->attempt_key, 'answers' => ['A'],
        ])->assertOk();
        for ($i = 0; $i < 2; $i++) {
            $this->get(route('teacher.assessments.take', [$assessment, $student]))
                ->assertRedirect(route('students.show', $student))->assertSessionHas('status');
        }
        $this->assertDatabaseCount('assessment_retake_requests', 1);
        $token = AssessmentRetakeRequest::firstOrFail();
        $this->post(route('students.assessment-requests.approve', [$student, $token]), ['approved_tries' => 1])->assertRedirect();
        $second = AssessmentProgress::forAttempt($assessment, $student, 2);
        $this->assertSame('approved', $token->fresh()->status);
        $payload = ['attempt_key' => $second->attempt_key, 'answers' => ['A']];
        $this->get(route('teacher.assessments.take', [$assessment, $student]))->assertOk();
        $this->postJson(route('teacher.assessments.submit', [$assessment, $student]), $payload)->assertOk();
        $this->postJson(route('teacher.assessments.submit', [$assessment, $student]), $payload)->assertOk();
        $this->assertSame(0, $token->fresh()->remaining_tries);
        $this->assertDatabaseCount('assessment_submissions', 2);
        $third = AssessmentProgress::forAttempt($assessment, $student, 3);
        $this->postJson(route('teacher.assessments.submit', [$assessment, $student]), ['attempt_key' => $third->attempt_key, 'answers' => ['A']])->assertForbidden();
    }

    public function test_oral_reading_marks_are_saved_for_the_selected_student(): void
    {
        [$teacher, $student, $assessment] = $this->fixture(['assessment_type' => 'oral_reading', 'manual_questions' => []]);
        $progress = $this->open($teacher, $student, $assessment);
        $this->postJson(route('teacher.assessments.submit', [$assessment, $student]), [
            'attempt_key' => $progress->attempt_key, 'answers' => [], 'state' => $this->state(), 'revision' => 1,
        ])->assertOk()->assertJsonPath('question_count', 0);
        $this->assertSame([2, 0, 0, 0], $progress->fresh()->state['word_marks']);
        $this->assertSame($student->id, $progress->fresh()->submission->user_id);
    }

    public function test_all_assisted_endpoints_recheck_owner_role_section_approval_and_availability(): void
    {
        [$teacher, $student, $assessment] = $this->fixture();
        $this->open($teacher, $student, $assessment);
        $mutations = [
            [fn () => $this->actingAs(User::factory()->teacher()->create()), 404],
            [fn () => $this->actingAs($student), 403],
            [fn () => $this->actingAs(User::factory()->admin()->create()), 403],
            [fn () => $student->update(['section' => 'Section B']), 404],
            [fn () => $student->update(['approval_status' => 'pending']), 404],
            [fn () => $student->update(['role' => 'teacher']), 404],
            [fn () => $assessment->update(['status' => 'draft']), 404],
            [fn () => $teacher->update(['approval_status' => 'pending']), 403],
        ];
        foreach ($mutations as [$mutate, $status]) {
            $this->actingAs($teacher);
            $mutate();
            $this->get(route('teacher.assessments.take', [$assessment, $student]))->assertStatus($status);
            $this->get(route('teacher.assessments.start', [$assessment, 'student_id' => $student->id]))->assertStatus($status);
            foreach (['progress', 'submit', 'worksheet.save', 'worksheet.submit', 'worksheet.table'] as $action) {
                $this->postJson(route('teacher.assessments.'.$action, [$assessment, $student]), [])->assertStatus($status);
            }
            $student->update(['section' => 'Section A', 'approval_status' => 'approved', 'role' => 'student']);
            $assessment->update(['status' => 'published']);
            $teacher->update(['approval_status' => 'approved']);
        }
        $this->assertDatabaseCount('assessment_submissions', 0);
    }

    public function test_worksheet_can_be_answered_reviewed_and_graded_for_student_with_teacher_login(): void
    {
        [$teacher, $student, $assessment] = $this->fixture([
            'subject' => 'numeracy', 'quiz_type' => 'worksheet', 'assessment_type' => 'worksheet',
            'worksheet_number' => 2, 'worksheet_total' => 20, 'manual_questions' => [],
        ]);
        $this->actingAs($teacher)->get(route('assessments.show', $assessment))->assertOk()->assertSee('Take with a Student');
        $progress = $this->open($teacher, $student, $assessment);
        $pages = array_map(fn ($page) => ['text' => 'My working', 'strokes' => [], 'responses' => []], \App\Support\NumeracyWorksheets::find(2)['pages']);
        $data = ['attempt_key' => $progress->attempt_key, 'revision' => 10, 'page' => 0, 'step' => 'answer', 'pages' => $pages];
        $this->postJson(route('teacher.assessments.worksheet.save', [$assessment, $student]), $data)->assertOk();
        $this->get(route('teacher.assessments.take', [$assessment, $student]))->assertOk()
            ->assertViewHas('progress', fn ($row) => $row->state['pages'] === $pages);
        $this->postJson(route('teacher.assessments.worksheet.table', [$assessment, $student]), ['attempt_key' => $progress->attempt_key])->assertUnprocessable();
        $this->postJson(route('teacher.assessments.worksheet.table', [$assessment, $student]), ['attempt_key' => $progress->attempt_key, 'password' => 'password'])->assertOk()->assertJsonPath('table.11.11', 144);
        $other = User::factory()->create(['section' => 'Section A']);
        foreach (['worksheet.save', 'worksheet.submit', 'worksheet.table'] as $action) {
            $this->postJson(route('teacher.assessments.'.$action, [$assessment, $other]), $data)->assertNotFound();
        }
        $this->postJson(route('teacher.assessments.submit', [$assessment, $student]), ['attempt_key' => $progress->attempt_key, 'answers' => []])->assertUnprocessable();
        $this->postJson(route('teacher.assessments.worksheet.submit', [$assessment, $student]), $data)->assertOk();
        $attempt = WorksheetAttempt::firstOrFail();
        $this->assertSame($student->id, $attempt->user_id);
        $this->assertSame($teacher->id, $progress->fresh()->administered_by);
        $this->postJson(route('teacher.assessments.worksheet.submit', [$assessment, $student]), $data)->assertOk();
        $this->assertDatabaseCount('worksheet_attempts', 1);
        $this->get(route('teacher.assessments.take', [$assessment, $student]))->assertRedirect(route('worksheets.review', $attempt));
        $this->get(route('worksheets.review', $attempt))->assertOk()->assertSee('Teacher-assisted: '.$teacher->name);
        $this->postJson(route('teacher.assessments.worksheet.save', [$assessment, $student]), $data)->assertStatus(409);
        $this->post(route('worksheets.grade', $attempt), ['score' => 17, 'feedback' => 'Good working. Review divisibility by 9.'])->assertRedirect();
        $this->assertDatabaseHas('assessment_submissions', ['user_id' => $student->id, 'assessment_id' => $assessment->id, 'points' => 4250]);
        $this->assertAuthenticatedAs($teacher);
        $this->actingAs($student)->get(route('worksheets.review', $attempt))->assertOk()->assertSee('17 / 20');
        $this->actingAs(User::factory()->teacher()->create())->get(route('worksheets.review', $attempt))->assertNotFound();
    }
}
