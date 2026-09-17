<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentProgress;
use App\Models\AssessmentSubmission;
use App\Models\PracticeMission;
use App\Models\User;
use App\Support\AvatarWardrobe;
use App\Support\PracticeSuggestions;
use App\Support\StudentLeaderboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PracticeMissionTest extends TestCase
{
    use RefreshDatabase;

    private function sourceResult(): AssessmentSubmission
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $assessment = Assessment::create([
            'created_by' => $teacher->id, 'title' => 'A New Day', 'subject' => 'literacy',
            'quiz_type' => 'multiple_choice', 'delivery_method' => 'manual', 'assessment_type' => 'oral_reading',
            'target_section' => 'all', 'status' => 'published', 'story_description' => "We read. We learn!\n\nRead slowly.",
            'manual_questions' => [
                ['question' => 'What do we do?', 'answers' => ['A' => 'Read', 'B' => 'Sleep', 'C' => 'Run', 'D' => 'Fly'], 'correct_answer' => 'A'],
                ['question' => 'How do we read?', 'answers' => ['A' => 'Quickly', 'B' => 'Slowly', 'C' => 'Never', 'D' => 'Loudly'], 'correct_answer' => 'B'],
            ],
        ]);
        $submission = AssessmentSubmission::create([
            'assessment_id' => $assessment->id, 'user_id' => $student->id, 'attempt_number' => 1,
            'answers' => ['B', 'B'], 'correct_count' => 1, 'question_count' => 2, 'points' => 250,
            'possible_points' => 500, 'submitted_at' => now(),
        ]);
        AssessmentProgress::create([
            'assessment_id' => $assessment->id, 'user_id' => $student->id, 'attempt_number' => 1,
            'attempt_key' => (string) Str::uuid(), 'submission_id' => $submission->id,
            'state' => ['word_marks' => [0, 1, 0, 2, 2, 0]], 'revision' => 1,
        ]);

        return $submission;
    }

    private function assign(AssessmentSubmission $result, array $items = ['q0', 'w0', 'w1']): PracticeMission
    {
        $this->actingAs($result->assessment->teacher)->post(route('teacher.practice.store', $result), [
            'title' => 'Read and discover', 'instructions' => 'Take your time.', 'items' => $items,
            'reward_coins' => 9999, 'student_id' => 99999,
        ])->assertSessionHasNoErrors()->assertRedirect();

        return PracticeMission::where('submission_id', $result->id)->firstOrFail();
    }

    private function check(PracticeMission $mission, array $overrides = []): array
    {
        return array_replace([
            'action' => 'check', 'attempt_key' => (string) Str::uuid(),
            'answers' => array_fill(0, count($mission->questions), 'A'),
            'practiced_words' => array_keys($mission->words),
        ], $overrides);
    }

    public function test_suggestions_use_missed_questions_and_exact_saved_word_positions(): void
    {
        $result = $this->sourceResult();
        $items = PracticeSuggestions::forSubmission($result);
        $this->assertSame(['q0', 'w0', 'w1'], array_keys($items));
        $this->assertSame('B', $items['q0']['original_answer']);
        $this->assertSame('Read', $items['w0']['word']);
        $this->assertSame(2, $items['w0']['mark']);
        $this->assertSame('Read slowly.', $items['w0']['context']);
        $this->assertSame('learn', $items['w1']['word']);
        $result->assessment->update(['assessment_type' => 'listening_comprehension']);
        $this->assertSame(['q0'], array_keys(PracticeSuggestions::forSubmission($result)));
    }

    public function test_assignment_is_owned_snapshotted_and_not_duplicated(): void
    {
        $result = $this->sourceResult();
        $this->actingAs($result->assessment->teacher)->get(route('teacher.practice.create', $result))->assertOk()->assertSee('Mispronounced');
        $mission = $this->assign($result);
        $this->assertSame(25, $mission->reward_coins);
        $this->assertSame($result->user_id, $mission->student_id);
        $this->assertCount(1, $mission->questions);
        $this->assertCount(2, $mission->words);
        $this->assign($result);
        $this->assertDatabaseCount('practice_missions', 1);
        $this->assertDatabaseCount('app_notifications', 1);
        $this->get(route('teacher.practice.create', $result))->assertRedirect(route('teacher.practice.show', $mission));
        $this->get(route('teacher.practice.index'))->assertOk()->assertSee('Read and discover');
        $this->get(route('teacher.practice.show', $mission))->assertOk();
        $result->assessment->update(['story_description' => 'Changed', 'manual_questions' => []]);
        $this->assertSame('What do we do?', $mission->fresh()->questions[0]['question']);
        $result->assessment->delete();
        $this->assertNull($mission->fresh()->submission_id);
        $this->actingAs($result->student)->get(route('student.practice.show', $mission))->assertOk()->assertSee('Read slowly.');
    }

    public function test_only_source_teacher_can_assign_and_only_owners_access_missions(): void
    {
        $result = $this->sourceResult();
        $mission = $this->assign($result);
        $otherTeacher = User::factory()->teacher()->create();
        $this->actingAs($otherTeacher)->get(route('teacher.practice.create', $result))->assertNotFound();
        $this->post(route('teacher.practice.store', $result), [])->assertNotFound();
        $this->get(route('teacher.practice.show', $mission))->assertNotFound();
        $this->patch(route('teacher.practice.review', $mission))->assertNotFound();
        $this->post(route('teacher.practice.cancel', $mission))->assertNotFound();
        $this->get(route('teacher.practice.index'))->assertOk()->assertDontSee('Read and discover');
        $this->actingAs(User::factory()->create())->get(route('student.practice.show', $mission))->assertNotFound();
        $this->post(route('student.practice.submit', $mission), $this->check($mission))->assertNotFound();
        $this->get(route('teacher.practice.index'))->assertForbidden();
        $this->get(route('teacher.practice.create', $result))->assertForbidden();
        $this->actingAs($otherTeacher)->get(route('student.practice.index'))->assertForbidden();
        $this->get(route('student.practice.show', $mission))->assertForbidden();
    }

    public function test_teacher_cannot_assign_fabricated_or_already_correct_questions(): void
    {
        $result = $this->sourceResult();
        $this->actingAs($result->assessment->teacher);
        foreach ([['q1'], ['w999'], ['q0', 'q0'], []] as $items) {
            $this->post(route('teacher.practice.store', $result), ['title' => 'Practice', 'items' => $items])->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('practice_missions', 0);
    }

    public function test_result_without_mistakes_has_no_assignable_items_and_guests_must_log_in(): void
    {
        $this->get(route('student.practice.index'))->assertRedirect(route('login'));
        $this->get(route('teacher.practice.index'))->assertRedirect(route('login'));
        $result = $this->sourceResult();
        $result->update(['answers' => ['A', 'B']]);
        AssessmentProgress::where('submission_id', $result->id)->update(['state' => []]);
        $this->assertSame([], PracticeSuggestions::forSubmission($result));
        $this->actingAs($result->assessment->teacher)->get(route('teacher.practice.create', $result))
            ->assertOk()->assertSee('No practice items in this result')->assertDontSee('Assign mission');
        $this->post(route('teacher.practice.store', $result), ['title' => 'Empty', 'items' => ['q0']])->assertSessionHasErrors();
        $this->assertDatabaseCount('practice_missions', 0);
    }

    public function test_progress_can_resume_and_only_a_complete_check_earns_once_without_changing_assessment_scores(): void
    {
        $result = $this->sourceResult();
        $mission = $this->assign($result);
        $this->actingAs($result->student);
        $save = $this->check($mission, ['action' => 'save', 'answers' => [0 => 'B'], 'practiced_words' => [0]]);
        $this->post(route('student.practice.submit', $mission), $save)->assertSessionHasNoErrors();
        $this->get(route('student.practice.show', $mission))->assertOk()->assertViewHas('mission', fn ($saved) => $saved->progress['answers'][0] === 'B');
        $this->assertDatabaseCount('practice_attempts', 0);
        $this->assertSame(0, $result->student->practiceCoinBalance());
        $this->post(route('student.practice.submit', $mission), $this->check($mission, ['practiced_words' => [0]]))->assertSessionHasErrors('practiced_words');
        $wrong = $this->check($mission, ['answers' => [0 => 'B']]);
        $this->post(route('student.practice.submit', $mission), $wrong)->assertSessionHasNoErrors();
        $this->post(route('student.practice.submit', $mission), $wrong)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('practice_attempts', 1);
        $this->assertSame('assigned', $mission->fresh()->status);
        $this->get(route('student.practice.show', $mission))->assertOk()->assertSee('Correct answer: A. Read');
        $pass = $this->check($mission);
        $this->post(route('student.practice.submit', $mission), $pass)->assertSessionHasNoErrors();
        $this->post(route('student.practice.submit', $mission), $pass)->assertSessionHasNoErrors();
        $this->post(route('student.practice.submit', $mission), $save)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('practice_attempts', 2);
        $this->assertSame('completed', $mission->fresh()->status);
        $this->assertSame('A', $mission->fresh()->progress['answers'][0]);
        $this->assertSame(25, $result->student->practiceCoinBalance());
        $this->assertDatabaseCount('practice_coin_transactions', 1);
        $this->assertDatabaseCount('assessment_submissions', 1);
        $this->assertSame(['B', 'B'], $result->fresh()->answers);
        $rank = StudentLeaderboard::entries()->first();
        $this->assertSame(250, $rank['points']);
        $this->assertSame(1, $rank['completed']);
        $this->get(route('student.practice.index'))->assertOk()->assertSee('25 coins');
    }

    public function test_oral_only_mission_needs_teacher_review_before_claiming_reading_improvement(): void
    {
        $result = $this->sourceResult();
        $mission = $this->assign($result, ['w0']);
        $this->actingAs($result->student)->post(route('student.practice.submit', $mission), $this->check($mission))->assertSessionHasNoErrors();
        $this->get(route('student.practice.show', $mission))->assertOk()->assertSee('Waiting for your teacher');
        $this->assertNull($mission->fresh()->reviewed_at);
        $this->actingAs($result->assessment->teacher)->patch(route('teacher.practice.review', $mission), [])->assertSessionHasErrors('word_reviews.0');
        $this->patch(route('teacher.practice.review', $mission), [
            'word_reviews' => ['clear'], 'feedback' => 'Much clearer today.',
        ])->assertSessionHasNoErrors();
        $this->get(route('teacher.practice.show', $mission))->assertOk()->assertSee('Much clearer today.');
        $this->actingAs($result->student)->get(route('student.practice.show', $mission))->assertOk()->assertSee('Read clearly')->assertSee('Much clearer today.');
        $this->assertSame(25, $result->student->practiceCoinBalance());
        $this->assertDatabaseCount('app_notifications', 3);
    }

    public function test_cancelled_missions_award_nothing_and_question_only_missions_work(): void
    {
        $result = $this->sourceResult();
        $mission = $this->assign($result, ['q0']);
        $this->post(route('teacher.practice.cancel', $mission))->assertRedirect();
        $this->actingAs($result->student)->post(route('student.practice.submit', $mission), $this->check($mission))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('practice_attempts', 0);
        $this->assertSame(0, $result->student->practiceCoinBalance());
        $other = $this->sourceResult();
        $otherMission = $this->assign($other, ['q0']);
        $this->actingAs($other->student)->post(route('student.practice.submit', $otherMission), $this->check($otherMission))->assertSessionHasNoErrors();
        $this->assertSame(25, $other->student->practiceCoinBalance());
        $this->actingAs($other->assessment->teacher)->patch(route('teacher.practice.review', $otherMission), ['feedback' => 'Well done.'])->assertSessionHasNoErrors();
        $this->post(route('teacher.practice.cancel', $otherMission))->assertRedirect();
        $this->assertSame('completed', $otherMission->fresh()->status);
    }

    public function test_invalid_progress_cannot_inject_extra_answers_or_words(): void
    {
        $result = $this->sourceResult();
        $mission = $this->assign($result);
        $this->actingAs($result->student);
        foreach ([['answers' => [2 => 'A']], ['answers' => ['A', 'B']], ['answers' => ['Z']], ['practiced_words' => [0, 0]], ['practiced_words' => [0, 55]]] as $invalid) {
            $this->post(route('student.practice.submit', $mission), $this->check($mission, $invalid))->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('practice_attempts', 0);
        $this->assertSame(0, $result->student->practiceCoinBalance());
    }

    public function test_wardrobe_purchases_require_earned_coins_and_use_fixed_prices_once(): void
    {
        $student = User::factory()->create();
        $look = array_replace(AvatarWardrobe::defaults(), ['headwear' => 'star_cap']);
        $this->actingAs($student)->patch(route('student.wardrobe.update'), ['avatar' => $look])->assertSessionHasErrors('avatar.headwear');
        $this->post(route('student.wardrobe.purchase'), ['item' => 'headwear:star_cap'])->assertSessionHasErrors('item');
        $this->post(route('student.wardrobe.purchase'), ['item' => 'headwear:none'])->assertSessionHasErrors('item');
        $student->practiceCoinTransactions()->create(['amount' => 100, 'description' => 'Test practice rewards']);
        $payload = ['item' => 'headwear:star_cap', 'cost' => 0, 'user_id' => User::factory()->create()->id];
        $this->post(route('student.wardrobe.purchase'), $payload)->assertSessionHasNoErrors();
        $this->post(route('student.wardrobe.purchase'), $payload)->assertSessionHasNoErrors();
        $this->assertSame(75, $student->practiceCoinBalance());
        $this->assertDatabaseCount('practice_coin_transactions', 2);
        $this->patch(route('student.wardrobe.update'), ['avatar' => $look])->assertSessionHasNoErrors();
        $this->assertSame('star_cap', $student->fresh()->avatar_config['headwear']);
        $this->get(route('student.wardrobe.edit'))->assertOk()->assertSee('75 practice coins')->assertViewHas('options', fn ($items) => !$items['headwear']['items']['star_cap']['locked'] && $items['headwear']['items']['crown']['locked']);
        $this->post(route('student.wardrobe.purchase'), ['item' => 'headwear:crown'])->assertSessionHasNoErrors();
        $this->assertSame(0, $student->practiceCoinBalance());
        $this->post(route('student.wardrobe.purchase'), ['item' => 'accessory:medal'])->assertSessionHasErrors('item');
        $this->actingAs(User::factory()->teacher()->create())->post(route('student.wardrobe.purchase'), $payload)->assertForbidden();
    }
}
