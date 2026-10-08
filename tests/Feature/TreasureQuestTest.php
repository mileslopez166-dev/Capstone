<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreasureQuestTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'The Island Library', 'subject' => 'literacy', 'quiz_type' => 'treasure_quest',
            'delivery_method' => 'manual', 'assessment_type' => 'silent_reading',
            'target_section' => 'all', 'status' => 'published', 'focus_areas' => ['Comprehension Depth'],
            'story_title' => 'The Island Library', 'story_description' => 'Mia brought books to the island. Everyone worked together to build a library.',
            'manual_questions' => [
                ['question' => 'Why did everyone work together?', 'answers' => ['A' => 'To build a library for everyone on the island', 'B' => 'To hide the books', 'C' => 'To close the school', 'D' => 'To leave the island'], 'correct_answer' => 'A', 'difficulty' => 'instructional'],
                ['question' => 'What did Mia bring?', 'answers' => ['A' => 'Sand', 'B' => 'Books', 'C' => 'Shoes', 'D' => 'Food'], 'correct_answer' => 'B', 'difficulty' => 'independent'],
            ],
        ], $overrides);
    }

    private function fixture(array $overrides = []): array
    {
        $teacher = User::factory()->teacher()->create(['section' => 'Section A']);
        $student = User::factory()->create(['section' => 'Section A', 'avatar_config' => ['character' => 'lyra']]);
        $assessment = Assessment::create($this->payload($overrides) + ['created_by' => $teacher->id]);

        return [$teacher, $student, $assessment];
    }

    public function test_teacher_can_publish_treasure_quest_and_students_see_its_name(): void
    {
        $this->actingAs(User::factory()->teacher()->create())->get(route('assessments.create'))
            ->assertOk()->assertSee('value="treasure_quest"', false)->assertSeeText('Treasure Quest');
        $this->post(route('assessments.store'), $this->payload())->assertSessionHasNoErrors()->assertRedirect();
        $assessment = Assessment::firstOrFail();
        $this->assertSame('treasure_quest', $assessment->quiz_type);
        $this->assertSame('instructional', $assessment->manual_questions[0]['difficulty']);
        $this->actingAs(User::factory()->create())->get(route('student.activities', ['subject' => 'literacy']))
            ->assertOk()->assertSeeText('Treasure Quest');
    }

    public function test_student_and_teacher_assisted_views_share_game_and_correct_endpoints(): void
    {
        [$teacher, $student, $assessment] = $this->fixture();
        $response = $this->actingAs($student)->get(route('student.assessments.show', $assessment));
        $response->assertOk()->assertSee('id="treasure-scene"', false)->assertSee('id="story-gate"', false)
            ->assertSee('assessment-reading')->assertSee('campus-character-custom')
            ->assertDontSee('id="fishing-sea-scene"', false)->assertDontSee('id="frog-pond-scene"', false);
        if (getenv('CAPTURE_TREASURE_FIXTURES')) {
            file_put_contents(storage_path('app/treasure-student.html'), $response->getContent());
        }
        $response = $this->actingAs($teacher)->get(route('teacher.assessments.take', [$assessment, $student]));
        $response->assertOk()->assertSee('id="treasure-scene"', false)->assertViewHas('student', fn ($value) => $value->is($student))
            ->assertSee(json_encode(route('teacher.assessments.submit', [$assessment, $student])), false);
        if (getenv('CAPTURE_TREASURE_FIXTURES')) {
            file_put_contents(storage_path('app/treasure-teacher.html'), $response->getContent());
        }
        $this->actingAs(User::factory()->teacher()->create())->get(route('teacher.assessments.take', [$assessment, $student]))->assertNotFound();
    }

    public function test_oral_reading_remains_reading_only_and_listening_can_use_treasure_quest(): void
    {
        [, $student, $assessment] = $this->fixture(['assessment_type' => 'oral_reading', 'manual_questions' => []]);
        $this->actingAs($student)->get(route('student.assessments.show', $assessment))->assertOk()
            ->assertSee('id="story-gate"', false)->assertDontSee('id="treasure-scene"', false);
        [, $student, $assessment] = $this->fixture(['assessment_type' => 'listening_comprehension']);
        $this->actingAs($student)->get(route('student.assessments.show', $assessment))->assertOk()->assertSee('id="treasure-scene"', false)
            ->assertDontSee('id="story-gate"', false);
    }

    public function test_answers_resume_and_server_scores_once_without_trusting_client_points(): void
    {
        [, $student, $assessment] = $this->fixture();
        $this->actingAs($student)->get(route('student.assessments.show', $assessment))->assertOk();
        $progress = AssessmentProgress::firstOrFail();
        $state = ['answers' => ['A'], 'phase' => 'questions', 'reading_seconds' => 12, 'timer_status' => 'finished',
            'word_marks' => [], 'sentence_marks' => [], 'mark_mode' => 'word', 'scroll_ratio' => 0];
        $this->postJson(route('student.assessments.progress', $assessment), [
            'attempt_key' => $progress->attempt_key, 'revision' => 10, 'state' => $state,
        ])->assertOk();
        $this->get(route('student.assessments.show', $assessment))->assertOk()
            ->assertViewHas('progress', fn ($saved) => $saved->state == $state && $saved->attempt_key === $progress->attempt_key);
        $payload = ['answers' => ['A', 'C'], 'attempt_key' => $progress->attempt_key, 'points' => 99999, 'accuracy' => 100];
        $this->postJson(route('student.assessments.submit', $assessment), $payload)->assertOk()
            ->assertJsonPath('points', 50)->assertJsonPath('correct_count', 1)->assertJsonPath('question_count', 2)->assertJsonPath('coins_earned', 10);
        $this->postJson(route('student.assessments.submit', $assessment), $payload)->assertOk()->assertJsonPath('points', 50);
        $this->assertDatabaseCount('assessment_submissions', 1);
    }
}
