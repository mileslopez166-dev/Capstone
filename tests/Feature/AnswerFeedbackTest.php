<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAnswerFeedback;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AnswerFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private const REPLY = "Understand it\nThe class grew vegetables, not flowers.\n\nTry this strategy\nLook for the word after grew.\n\nPractice\nAna planted beans. What did she plant?";

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['tutor.enabled' => true, 'tutor.key' => 'test-only-key']);
        $this->fakeReply();
    }

    public function test_review_uses_saved_item_not_bank_or_browser_and_never_changes_score(): void
    {
        $student = User::factory()->create(['name' => 'PRIVATE LEARNER', 'email' => 'private@example.test']);
        $submission = $this->submission($student);
        $before = $submission->fresh()->getAttributes();
        $response = $this->actingAs($student)->postJson($this->url($submission), [
            'question' => 'FORGED QUESTION', 'correct_answer' => 'D', 'points' => 10000,
        ])->assertOk()->assertJsonPath('answer', self::REPLY)->assertJsonPath('saved', true);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame($before, $submission->fresh()->getAttributes());
        Http::assertSent(function ($request) {
            if (! str_ends_with($request->url(), '/responses')) return false;
            $material = json_decode(explode("\n", $request['input'][0]['content'], 2)[1], true);
            $this->assertSame('What did the class grow?', $material['question']);
            $this->assertSame('A', $material['correct_answer']);
            $this->assertSame('B', $material['selected_answer']);
            foreach (['PRIVATE LEARNER', 'private@example.test', 'FORGED QUESTION', 'UNUSED BANK ITEM', 'selection_context', 'frustration'] as $private) {
                $this->assertStringNotContainsString($private, $request->body());
            }
            $this->assertFalse($request['store']);
            $this->assertStringContainsString('Practice', $request['instructions']);
            return true;
        });
        $this->assertNotSame(self::REPLY, DB::table('assessment_answer_feedback')->value('answer'));
        $this->assertSame(self::REPLY, AssessmentAnswerFeedback::first()->answer);
        Http::assertSentCount(3);
    }

    public function test_saved_feedback_survives_reopening_and_disabled_api_without_recharging_quota(): void
    {
        $student = User::factory()->create();
        $submission = $this->submission($student);
        $this->actingAs($student)->postJson($this->url($submission))->assertOk();
        config(['tutor.key' => '', 'tutor.enabled' => false]);
        RateLimiter::hit('tutor:'.$student->id.':day', 86400);
        config(['tutor.per_day' => 1]);
        $this->postJson($this->url($submission))->assertOk()->assertJsonPath('answer', self::REPLY);
        Http::assertSentCount(3);
        $this->assertDatabaseCount('assessment_answer_feedback', 1);
    }

    public function test_other_students_and_roles_cannot_access_feedback(): void
    {
        $owner = User::factory()->create();
        $submission = $this->submission($owner);
        $this->postJson($this->url($submission))->assertUnauthorized();
        foreach ([User::factory()->teacher()->create(), User::factory()->admin()->create(), User::factory()->create(['approval_status' => 'pending'])] as $user) {
            $this->actingAs($user)->postJson($this->url($submission))->assertForbidden();
        }
        $this->actingAs(User::factory()->create())->postJson($this->url($submission))->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_correct_missing_and_unsubmitted_items_cannot_generate_feedback(): void
    {
        $student = User::factory()->create();
        $submission = $this->submission($student);
        $this->actingAs($student)->postJson($this->url($submission, 1))->assertUnprocessable();
        $this->postJson($this->url($submission, 99))->assertNotFound();
        $submission->update(['submitted_at' => null]);
        $this->postJson($this->url($submission))->assertNotFound();
        $submission->update(['submitted_at' => now(), 'question_snapshot' => [['question' => 'Missing key', 'answers' => ['A' => 'A']]]]);
        $this->postJson($this->url($submission))->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_shared_tutor_limits_and_lock_stop_duplicate_or_excess_calls(): void
    {
        $student = User::factory()->create();
        $submission = $this->submission($student);
        $this->actingAs($student);
        $lock = Cache::lock('tutor:user:'.$student->id, 90);
        $lock->get();
        $this->postJson($this->url($submission))->assertStatus(429);
        $lock->release();
        config(['tutor.per_day' => 1]);
        RateLimiter::hit('tutor:'.$student->id.':day', 86400);
        $this->postJson($this->url($submission))->assertStatus(429);
        Http::assertNothingSent();
    }

    public function test_failed_calls_are_retryable_and_never_saved(): void
    {
        $student = User::factory()->create();
        $submission = $this->submission($student);
        $this->actingAs($student);
        config(['tutor.key' => '']);
        $this->postJson($this->url($submission))->assertStatus(503);
        Http::assertNothingSent();
        config(['tutor.key' => 'test-only-key']);
        $this->fakeReply(['api.openai.com/v1/responses' => Http::response(['error' => 'PRIVATE UPSTREAM'], 500)]);
        $this->postJson($this->url($submission))->assertStatus(503)->assertDontSee('PRIVATE UPSTREAM');
        $this->assertDatabaseCount('assessment_answer_feedback', 0);
        $this->fakeReply();
        $this->postJson($this->url($submission))->assertOk();
        $this->assertDatabaseCount('assessment_answer_feedback', 1);
    }

    public function test_moderation_rejects_unsafe_output_and_notifies_teacher(): void
    {
        $student = User::factory()->create();
        $submission = $this->submission($student);
        $this->fakeReply(['api.openai.com/v1/moderations' => Http::sequence()
            ->push(['results' => [['flagged' => false]]])->push(['results' => [['flagged' => true]]])]);
        $this->actingAs($student)->postJson($this->url($submission))->assertStatus(422)->assertDontSee(self::REPLY);
        $this->assertDatabaseCount('assessment_answer_feedback', 0);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $submission->assessment->created_by, 'type' => 'tutor_attention']);
    }

    public function test_legacy_key_changes_invalidate_saved_feedback_and_attempts_stay_separate(): void
    {
        $student = User::factory()->create();
        $submission = $this->submission($student);
        $questions = $submission->question_snapshot;
        $submission->assessment->update(['manual_questions' => $questions]);
        $submission->update(['question_snapshot' => null]);
        $this->actingAs($student)->postJson($this->url($submission))->assertOk();
        $hash = AssessmentAnswerFeedback::first()->source_hash;
        $questions[0]['answers']['A'] = 'Green vegetables';
        $submission->assessment->update(['manual_questions' => $questions]);
        $this->postJson($this->url($submission))->assertOk();
        $this->assertNotSame($hash, AssessmentAnswerFeedback::first()->source_hash);
        $next = $submission->replicate();
        $next->attempt_number = 2;
        $next->save();
        $this->postJson($this->url($next))->assertOk();
        $this->assertDatabaseCount('assessment_answer_feedback', 2);
        Http::assertSentCount(9);
    }

    public function test_review_button_is_opt_in_and_only_on_incorrect_answers(): void
    {
        $student = User::factory()->create();
        $submission = $this->submission($student);
        $response = $this->actingAs($student)->get(route('student.activities', ['subject' => 'literacy']))->assertOk();
        $response->assertSeeText('Help me understand')->assertSee((string) \Illuminate\Support\Js::from($this->url($submission)), false);
        $this->assertSame(1, substr_count($response->getContent(), 'x-data="answerFeedback('));
        Http::assertNothingSent();
        if (getenv('CAPTURE_FEEDBACK_FIXTURE')) file_put_contents(storage_path('app/answer-feedback-fixture.html'), $response->getContent());
    }

    private function url(AssessmentSubmission $submission, int $index = 0): string
    {
        return route('student.answers.help', ['submission' => $submission->id, 'question' => $index]);
    }

    private function fakeReply(array $overrides = []): void
    {
        Http::swap(new Factory());
        Http::preventStrayRequests();
        Http::fake(array_replace([
            'api.openai.com/v1/moderations' => Http::response(['results' => [['flagged' => false]]]),
            'api.openai.com/v1/responses' => Http::response(['status' => 'completed', 'output' => [
                ['type' => 'message', 'role' => 'assistant', 'content' => [['type' => 'output_text', 'text' => self::REPLY]]],
            ]]),
        ], $overrides));
    }

    private function submission(User $student): AssessmentSubmission
    {
        $questions = [
            ['question' => 'What did the class grow?', 'answers' => ['A' => 'Vegetables', 'B' => 'Flowers'], 'correct_answer' => 'A', 'difficulty' => 'frustration'],
            ['question' => 'Where did they plant?', 'answers' => ['A' => 'Garden', 'B' => 'Road'], 'correct_answer' => 'A'],
        ];
        $assessment = Assessment::create([
            'created_by' => User::factory()->teacher()->create()->id, 'title' => 'Our garden',
            'subject' => 'literacy', 'assessment_type' => 'silent_reading', 'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual', 'target_section' => 'all', 'focus_areas' => [], 'status' => 'published',
            'story_title' => 'Our garden', 'story_description' => 'The class grew vegetables in the garden.',
            'manual_questions' => [['question' => 'UNUSED BANK ITEM', 'answers' => ['D' => 'Other answer'], 'correct_answer' => 'D']],
        ]);
        return AssessmentSubmission::create([
            'assessment_id' => $assessment->id, 'user_id' => $student->id, 'attempt_number' => 1,
            'answers' => ['B', 'A'], 'question_snapshot' => $questions, 'correct_count' => 1, 'question_count' => 2,
            'points' => 250, 'possible_points' => 500, 'submitted_at' => now(),
        ]);
    }
}
