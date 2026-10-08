<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentProgress;
use App\Models\AssessmentSubmission;
use App\Models\User;
use App\Support\AdaptiveQuestions;
use App\Support\PracticeSuggestions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdaptiveQuestionsTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        $story = json_decode(file_get_contents(resource_path('data/literacy-stories.json')), true)[0];

        return array_replace([
            'title' => $story['story_title'], 'subject' => 'literacy', 'quiz_type' => 'treasure_quest',
            'assessment_type' => 'silent_reading', 'delivery_method' => 'manual', 'target_section' => 'all',
            'story_title' => $story['story_title'], 'story_description' => $story['story_description'],
            'manual_questions' => array_merge(...array_values($story['question_sets'])),
            'question_selection' => 'automatic', 'focus_areas' => ['Comprehension Depth'], 'status' => 'published',
        ], $overrides);
    }

    private function assessment(User $teacher, array $overrides = []): Assessment
    {
        return Assessment::create($this->payload($overrides) + ['created_by' => $teacher->id]);
    }

    private function open(User $student, Assessment $assessment): AssessmentProgress
    {
        $this->actingAs($student)->get(route('student.assessments.show', $assessment))->assertOk();

        return AssessmentProgress::where('assessment_id', $assessment->id)->where('user_id', $student->id)->latest('attempt_number')->firstOrFail();
    }

    private function submit(Assessment $assessment, AssessmentProgress $progress, callable $correct): AssessmentSubmission
    {
        $answers = $positions = [];
        foreach ($progress->question_snapshot as $index => $question) {
            $level = $question['difficulty'];
            $position = $positions[$level] ?? 0;
            $positions[$level] = $position + 1;
            $answers[$index] = $correct($level, $position) ? $question['correct_answer'] : ($question['correct_answer'] === 'A' ? 'B' : 'A');
        }
        $this->postJson(route('student.assessments.submit', $assessment), ['attempt_key' => $progress->attempt_key, 'answers' => $answers])
            ->assertOk()->assertJsonPath('question_count', 8);

        return AssessmentSubmission::latest('id')->firstOrFail();
    }

    private function assertMix(AssessmentProgress $progress, string $plan): void
    {
        $this->assertSame($plan, $progress->selection_context['plan']);
        $this->assertCount(8, $progress->question_snapshot);
        $this->assertCount(8, array_unique(array_column($progress->question_snapshot, 'bank_index')));
        $this->assertEquals(AdaptiveQuestions::MIXES[$plan], array_count_values(array_column($progress->question_snapshot, 'difficulty')));
    }

    public function test_teacher_creates_automatic_bank_or_keeps_optional_fixed_override(): void
    {
        $this->actingAs(User::factory()->teacher()->create())->post(route('assessments.store'), $this->payload())->assertSessionHasNoErrors()->assertRedirect();
        $assessment = Assessment::firstOrFail();
        $this->assertSame('automatic', $assessment->question_selection);
        $this->assertCount(32, $assessment->manual_questions);
        $this->get(route('assessments.show', $assessment))->assertOk()->assertSeeText('Teacher answer key')->assertSeeText('Correct answer:');
        $this->post(route('assessments.store'), $this->payload(['manual_questions' => array_slice($assessment->manual_questions, 0, 8)]))->assertSessionHasErrors('question_selection');
        $this->post(route('assessments.store'), $this->payload(['question_selection' => 'fixed', 'manual_questions' => array_slice($assessment->manual_questions, 0, 8)]))->assertSessionHasNoErrors();
        $this->post(route('assessments.store'), $this->payload(['assessment_type' => 'group_screening']))->assertSessionHasErrors('question_selection');
        $this->post(route('assessments.store'), $this->payload(['assessment_type' => 'oral_reading', 'manual_questions' => null]))->assertSessionHasNoErrors();
        $this->assertSame('fixed', Assessment::latest('id')->firstOrFail()->question_selection);
    }

    public function test_first_attempt_is_balanced_for_every_game_without_exposing_placement_or_the_bank(): void
    {
        $teacher = User::factory()->teacher()->create();
        foreach (['multiple_choice', 'flashcards', 'treasure_quest'] as $game) {
            $student = User::factory()->create();
            $assessment = $this->assessment($teacher, ['quiz_type' => $game]);
            $progress = $this->open($student, $assessment);
            $this->assertMix($progress, 'balanced');
            $response = $this->get(route('student.assessments.show', $assessment));
            $response->assertOk()->assertDontSee('Teacher answer key')->assertDontSee('rules-v1')->assertDontSee('evidence_assessments');
            foreach ($assessment->manual_questions as $index => $question) {
                if (! in_array($index, array_column($progress->question_snapshot, 'bank_index'), true)) {
                    $response->assertDontSee(e($question['question']), false);
                }
            }
            $this->assertArrayNotHasKey('question_snapshot', $progress->toArray());
        }
    }

    public function test_future_assessments_use_band_evidence_and_never_change_an_open_attempt(): void
    {
        foreach (['support', 'developing', 'challenge'] as $expected) {
            $teacher = User::factory()->teacher()->create();
            $student = User::factory()->create();
            $first = $this->assessment($teacher);
            $progress = $this->open($student, $first);
            $alreadyOpen = $this->assessment($teacher);
            $frozen = $this->open($student, $alreadyOpen);
            $this->submit($first, $progress, fn ($level, $index) => $expected === 'challenge'
                || ($expected === 'developing' && ($level === 'frustration' || ($level === 'instructional' && $index < 2) || ($level === 'independent' && $index < 1))));
            $this->assertMix($this->open($student, $this->assessment($teacher)), $expected);
            $resumed = $this->open($student, $alreadyOpen);
            $this->assertSame($frozen->question_snapshot, $resumed->question_snapshot);
            $this->assertMix($resumed, 'balanced');
        }
    }

    public function test_selected_keys_survive_bank_changes_and_drive_scoring_review_and_practice(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $assessment = $this->assessment($teacher);
        $progress = $this->open($student, $assessment);
        $snapshot = $progress->question_snapshot;
        $assessment->update(['manual_questions' => array_reverse($assessment->manual_questions)]);
        $this->assertSame($snapshot, $this->open($student, $assessment)->question_snapshot);
        $submission = $this->submit($assessment, $progress, fn ($level, $index) => $index !== 0);
        $this->assertSame($snapshot, $submission->questionsForReview());
        $this->assertSame(5, $submission->correct_count);
        $this->assertSame(250, $submission->points);
        $this->assertCount(3, PracticeSuggestions::forSubmission($submission));
        $this->get(route('student.activities', ['subject' => 'literacy']))->assertOk()
            ->assertViewHas('completedSubmissions', fn ($rows) => $rows->first()->review_items->pluck('question')->all() === array_column($snapshot, 'question'));
        $this->actingAs($teacher)->get(route('teacher.phil-iri.show', $submission))->assertOk()->assertSeeText('Teacher answer key')->assertSeeText('Student answer:')->assertSeeText('Balanced starter');
        $this->actingAs(User::factory()->teacher()->create())->get(route('teacher.phil-iri.show', $submission))->assertNotFound();
        $this->actingAs($student)->get(route('teacher.phil-iri.show', $submission))->assertForbidden();
    }

    public function test_wrong_attempt_keys_counts_indexes_and_client_snapshot_cannot_change_selection(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $assessment = $this->assessment($teacher);
        $progress = $this->open($student, $assessment);
        $answers = array_column($progress->question_snapshot, 'correct_answer');
        $this->postJson(route('student.assessments.submit', $assessment), ['answers' => $answers])->assertUnprocessable();
        $this->postJson(route('student.assessments.submit', $assessment), ['attempt_key' => $progress->attempt_key, 'answers' => array_fill(0, 32, 'A')])->assertUnprocessable();
        $bad = $answers;
        unset($bad[0]);
        $bad['00'] = 'A';
        $this->postJson(route('student.assessments.submit', $assessment), ['attempt_key' => $progress->attempt_key, 'answers' => $bad])->assertUnprocessable();
        $this->actingAs(User::factory()->create())->postJson(route('student.assessments.submit', $assessment), ['attempt_key' => $progress->attempt_key, 'answers' => $answers])->assertNotFound();
        $this->actingAs($student)->postJson(route('student.assessments.submit', $assessment), [
            'attempt_key' => $progress->attempt_key, 'answers' => $answers, 'question_snapshot' => [], 'selection_context' => ['plan' => 'support'], 'points' => 99999,
        ])->assertOk()->assertJsonPath('points', 400)->assertJsonMissingPath('selection_context');
        $this->postJson(route('student.assessments.submit', $assessment), ['attempt_key' => $progress->attempt_key, 'answers' => $answers])->assertOk();
        $this->assertDatabaseCount('assessment_submissions', 1);
        $this->assertSame($progress->question_snapshot, AssessmentSubmission::firstOrFail()->question_snapshot);
    }

    public function test_teacher_assisted_attempt_has_same_snapshot_and_private_key(): void
    {
        $teacher = User::factory()->teacher()->create(['section' => 'Section A']);
        $student = User::factory()->create(['section' => 'Section A']);
        $assessment = $this->assessment($teacher);
        $progress = $this->open($student, $assessment);
        $response = $this->actingAs($teacher)->get(route('teacher.assessments.take', [$assessment, $student]));
        $response->assertOk()->assertSeeText('Teacher answer key')->assertSeeText('Balanced starter')->assertViewHas('attemptQuestions', $progress->question_snapshot);
        if (getenv('CAPTURE_ADAPTIVE_FIXTURES')) {
            file_put_contents(storage_path('app/adaptive-assisted.html'), $response->getContent());
            file_put_contents(storage_path('app/adaptive-create.html'), $this->get(route('assessments.create'))->getContent());
            file_put_contents(storage_path('app/adaptive-bank.html'), $this->get(route('assessments.show', $assessment))->getContent());
        }
        $this->postJson(route('teacher.assessments.submit', [$assessment, $student]), [
            'attempt_key' => $progress->attempt_key, 'answers' => array_column($progress->question_snapshot, 'correct_answer'),
        ])->assertOk()->assertJsonPath('correct_count', 8);
        $submission = AssessmentSubmission::firstOrFail();
        $this->assertSame($student->id, $submission->user_id);
        if (getenv('CAPTURE_ADAPTIVE_FIXTURES')) {
            file_put_contents(storage_path('app/adaptive-report.html'), $this->get(route('teacher.phil-iri.show', $submission))->getContent());
        }
    }

    public function test_placement_ignores_other_students_teachers_subjects_modes_and_duplicate_retakes(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $first = $this->assessment($teacher, ['retry_limit' => 2]);
        $progress = $this->open($student, $first);
        $this->submit($first, $progress, fn () => false);
        for ($i = 0; $i < 2; $i++) {
            $progress = $this->open($student, $first);
            $this->submit($first, $progress, fn () => true);
        }
        $target = $this->assessment($teacher);
        $placement = AdaptiveQuestions::placement($target, $student);
        $this->assertSame(1, $placement['evidence_assessments']);
        $this->assertSame(8, array_sum(array_column($placement['bands'], 'total')));
        $this->assertSame('balanced', AdaptiveQuestions::placement($target, User::factory()->create())['plan']);
        $this->assertSame('balanced', AdaptiveQuestions::placement($this->assessment(User::factory()->teacher()->create()), $student)['plan']);
        $this->assertSame('balanced', AdaptiveQuestions::placement($this->assessment($teacher, ['assessment_type' => 'listening_comprehension']), $student)['plan']);
        $this->assertSame('balanced', AdaptiveQuestions::placement($this->assessment($teacher, ['subject' => 'numeracy']), $student)['plan']);
    }
}
