<?php

namespace Tests\Feature;

use App\Models\{Assessment, AssessmentProgress, PracticeCoinTransaction, User, WorksheetAttempt};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentCoinRewardsTest extends TestCase
{
    use RefreshDatabase;

    private function assessment(User $teacher, int $questions = 100, array $attributes = []): Assessment
    {
        return $teacher->createdAssessments()->create(array_merge([
            'title' => 'Assessment coin check', 'subject' => 'literacy', 'status' => 'published',
            'target_section' => 'all', 'assessment_type' => 'silent_reading', 'quiz_type' => 'multiple_choice',
            'retry_limit' => 0, 'story_description' => implode(' ', array_fill(0, 100, 'word')),
            'manual_questions' => array_fill(0, $questions, [
                'question' => 'Which answer?', 'answers' => ['A' => 'Yes', 'B' => 'No', 'C' => 'Later', 'D' => 'Never'], 'correct_answer' => 'A',
            ]),
        ], $attributes));
    }

    private function payload(Assessment $assessment, AssessmentProgress $progress, int $correct): array
    {
        return ['attempt_key' => $progress->attempt_key,
            'answers' => array_merge(array_fill(0, $correct, 'A'), array_fill(0, count($assessment->manual_questions) - $correct, 'B'))];
    }

    public function test_rewards_use_server_scores_at_every_boundary_including_rounded_percentages(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $total = 0;
        foreach ([[0,100,10], [75,100,10], [76,100,20], [90,100,20], [91,100,35], [100,100,35],
            [3,4,10], [151,200,20], [180,199,20], [181,200,35]] as [$correct, $count, $coins]) {
            $assessment = $this->assessment($teacher, $count);
            $progress = AssessmentProgress::forAttempt($assessment, $student, 1);
            $this->actingAs($student)->postJson(route('student.assessments.submit', $assessment),
                $this->payload($assessment, $progress, $correct) + ['coins_earned' => 9999, 'accuracy' => 100, 'points' => 999999])
                ->assertOk()->assertJsonPath('coins_earned', $coins)->assertJsonPath('coins_pending', false)
                ->assertJsonPath('points', $correct * 250);
            $total += $coins;
            $this->assertSame($total, $student->practiceCoinBalance());
            $this->assertDatabaseHas('practice_coin_transactions', [
                'user_id' => $student->id, 'assessment_submission_id' => $progress->fresh()->submission_id, 'amount' => $coins,
            ]);
        }
        $this->assertDatabaseCount('practice_coin_transactions', 10);
        $this->get(route('student.activities'))->assertOk()->assertSeeText('35 coins earned');
        $this->get(route('student.wardrobe.edit'))->assertOk()->assertSeeText($total.' coins');
    }

    public function test_all_quiz_games_and_scored_reading_types_reward_the_same_wallet(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        foreach (['multiple_choice', 'data_egg', 'flashcards'] as $game) {
            foreach (['silent_reading', 'listening_comprehension', 'group_screening'] as $type) {
                $assessment = $this->assessment($teacher, 20, ['quiz_type' => $game, 'assessment_type' => $type]);
                $progress = AssessmentProgress::forAttempt($assessment, $student, 1);
                $this->actingAs($student)->postJson(route('student.assessments.submit', $assessment), $this->payload($assessment, $progress, 18))
                    ->assertOk()->assertJsonPath('coins_earned', 20);
            }
        }
        $this->assertSame(180, $student->practiceCoinBalance());
    }

    public function test_saving_and_invalid_answers_give_nothing_and_repeat_submissions_reward_only_once(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $assessment = $this->assessment($teacher, 1);
        $progress = AssessmentProgress::forAttempt($assessment, $student, 1);
        $this->actingAs($student)->postJson(route('student.assessments.progress', $assessment), [
            'attempt_key' => $progress->attempt_key, 'revision' => 1,
            'state' => ['answers' => [0 => 'A'], 'phase' => 'questions', 'reading_seconds' => 0, 'timer_status' => 'idle'],
        ])->assertOk();
        $this->postJson(route('student.assessments.submit', $assessment), ['answers' => ['Z']])->assertUnprocessable();
        $this->assertSame(0, $student->practiceCoinBalance());
        $payload = $this->payload($assessment, $progress, 1);
        $this->postJson(route('student.assessments.submit', $assessment), $payload)->assertOk()->assertJsonPath('coins_earned', 35);
        $this->postJson(route('student.assessments.submit', $assessment), array_merge($payload, ['answers' => ['B']]))
            ->assertOk()->assertJsonPath('coins_earned', 35)->assertJsonPath('correct_count', 1);
        $this->postJson(route('student.assessments.submit', $assessment), ['answers' => ['A']])->assertForbidden();
        $this->assertSame(35, $student->practiceCoinBalance());
        $this->assertDatabaseCount('practice_coin_transactions', 1);
    }

    public function test_allowed_retakes_each_earn_once_and_coins_can_buy_existing_wardrobe_items(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $assessment = $this->assessment($teacher, 1, ['retry_limit' => 1]);
        foreach ([1 => 0, 2 => 1] as $attempt => $correct) {
            $progress = AssessmentProgress::forAttempt($assessment, $student, $attempt);
            $payload = $this->payload($assessment, $progress, $correct);
            $this->actingAs($student)->postJson(route('student.assessments.submit', $assessment), $payload)->assertOk();
            $this->postJson(route('student.assessments.submit', $assessment), $payload)->assertOk();
        }
        $this->assertSame(45, $student->practiceCoinBalance());
        $this->post(route('student.wardrobe.purchase'), ['item' => 'headwear:star_cap'])->assertSessionHasNoErrors();
        $this->assertSame(20, $student->practiceCoinBalance());
        $this->assertDatabaseCount('practice_coin_transactions', 3);
        $this->actingAs($teacher)->delete(route('assessments.destroy', $assessment))->assertRedirect();
        $this->assertSame(20, $student->practiceCoinBalance(), 'Deleting an assessment must not remove earned coins.');
    }

    public function test_teacher_assisted_completion_credits_only_the_selected_student(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $other = User::factory()->create();
        $assessment = $this->assessment($teacher, 1);
        $progress = AssessmentProgress::forAttempt($assessment, $student, 1);
        $payload = $this->payload($assessment, $progress, 1) + ['user_id' => $other->id, 'coins_earned' => 9999];
        $this->actingAs($teacher)->postJson(route('teacher.assessments.submit', [$assessment, $student]), $payload)
            ->assertOk()->assertJsonPath('coins_earned', 35);
        $this->postJson(route('teacher.assessments.submit', [$assessment, $student]), $payload)->assertOk();
        $this->assertSame(35, $student->practiceCoinBalance());
        $this->assertSame(0, $teacher->practiceCoinBalance());
        $this->assertSame(0, $other->practiceCoinBalance());
    }

    public function test_oral_reading_uses_red_mark_formula_for_rewards_and_pays_once(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        foreach ([25 => 10, 24 => 20, 10 => 20, 9 => 35, 0 => 35] as $miscues => $coins) {
            $assessment = $this->assessment($teacher, 0, ['assessment_type' => 'oral_reading']);
            $progress = AssessmentProgress::forAttempt($assessment, $student, 1);
            $this->actingAs($student)->postJson(route('student.assessments.submit', $assessment),
                $this->payload($assessment, $progress, 0) + ['revision' => 1, 'state' => [
                    'answers' => [], 'phase' => 'finished', 'reading_seconds' => 30, 'timer_status' => 'finished',
                    'word_marks' => array_merge(array_fill(0, $miscues, 2), array_fill(0, 100 - $miscues, 0)),
                ]])->assertOk()->assertJsonPath('coins_earned', $coins)->assertJsonPath('coins_pending', false);
            $submission = $progress->fresh()->submission;
            $this->assertSame($coins, (int) $submission->coinReward->amount);
            $grade = ['word_count' => 100, 'miscues' => $miscues];
            $this->patch(route('teacher.phil-iri.update', $submission), $grade)->assertForbidden();
            $this->actingAs(User::factory()->teacher()->create())->patch(route('teacher.phil-iri.update', $submission), $grade)->assertNotFound();
            $this->actingAs($teacher)->patch(route('teacher.phil-iri.update', $submission), $grade)->assertSessionHasNoErrors();
            $this->patch(route('teacher.phil-iri.update', $submission), $grade)->assertSessionHasNoErrors();
            $this->assertSame($coins, (int) $submission->fresh()->coinReward->amount);
        }
        $this->assertSame(120, $student->practiceCoinBalance());
        $this->assertDatabaseCount('practice_coin_transactions', 5);
        $this->actingAs($student)->get(route('student.activities'))->assertOk()->assertSeeText('35 coins earned');
    }

    public function test_worksheet_rewards_wait_for_owner_grading_and_survive_duplicate_grades(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        foreach ([75 => 10, 76 => 20, 90 => 20, 91 => 35, 100 => 35] as $score => $coins) {
            $assessment = $this->assessment($teacher, 0, [
                'subject' => 'numeracy', 'assessment_type' => 'worksheet', 'worksheet_number' => 1, 'worksheet_total' => 100,
            ]);
            $progress = AssessmentProgress::forAttempt($assessment, $student, 1);
            $payload = ['attempt_key' => $progress->attempt_key, 'revision' => 1, 'page' => 1,
                'pages' => [['text' => 'My first answer', 'strokes' => []], ['text' => 'My second answer', 'strokes' => []]]];
            $this->actingAs($student)->postJson(route('worksheets.submit', $assessment), $payload)->assertOk();
            $attempt = WorksheetAttempt::where('progress_id', $progress->id)->firstOrFail();
            $this->assertNull($progress->fresh()->submission_id);
            $this->post(route('worksheets.grade', $attempt), ['score' => $score, 'feedback' => 'Self grading'])->assertForbidden();
            $this->actingAs(User::factory()->teacher()->create())->post(route('worksheets.grade', $attempt), ['score' => $score, 'feedback' => 'Other teacher'])->assertNotFound();
            $grade = ['score' => $score, 'feedback' => 'Checked your worksheet.'];
            $this->actingAs($teacher)->post(route('worksheets.grade', $attempt), $grade)->assertSessionHasNoErrors();
            $this->post(route('worksheets.grade', $attempt), $grade)->assertSessionHasNoErrors();
            $this->assertSame($coins, (int) $progress->fresh()->submission->coinReward->amount);
            $this->actingAs($student)->get(route('worksheets.review', $attempt))->assertOk()->assertSeeText($coins.' coins earned');
        }
        $this->assertSame(120, $student->practiceCoinBalance());
        $this->assertDatabaseCount('practice_coin_transactions', 5);
    }

    public function test_database_rejects_a_second_reward_for_the_same_submission(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $assessment = $this->assessment($teacher, 1);
        $progress = AssessmentProgress::forAttempt($assessment, $student, 1);
        $this->actingAs($student)->postJson(route('student.assessments.submit', $assessment), $this->payload($assessment, $progress, 1))->assertOk();
        $this->expectException(\Illuminate\Database\QueryException::class);
        PracticeCoinTransaction::create(['assessment_submission_id' => $progress->fresh()->submission_id,
            'user_id' => $student->id, 'amount' => 35, 'description' => 'Duplicate reward']);
    }
}
