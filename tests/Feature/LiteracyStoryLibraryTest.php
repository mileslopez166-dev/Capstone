<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiteracyStoryLibraryTest extends TestCase
{
    use RefreshDatabase;

    private function stories(): array
    {
        return json_decode(file_get_contents(resource_path('data/literacy-stories.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    private function payload(array $story, array $overrides = []): array
    {
        return array_merge([
            'title' => $story['story_title'], 'subject' => 'literacy', 'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual', 'target_section' => 'all', 'assessment_type' => 'silent_reading',
            'focus_areas' => ['Comprehension Depth'], 'story_title' => $story['story_title'],
            'story_description' => $story['story_description'], 'manual_questions' => $story['manual_questions'],
            'status' => 'draft', 'retry_limit' => 0,
        ], $overrides);
    }

    public function test_library_contains_all_five_complete_stories_and_original_answer_keys(): void
    {
        $stories = $this->stories();
        $this->assertSame([
            'The School Garden Project', 'The Lost Notebook', 'The Helpful Neighbor',
            'The Clean River Campaign', 'The Reading Challenge',
        ], array_column($stories, 'story_title'));
        $this->assertCount(5, array_unique(array_column($stories, 'id')));
        $answerKeys = ['BBACBAAB', 'BCADBBAA', 'ABCAABAA', 'BABBAAAA', 'BACBAAAA'];
        foreach ($stories as $index => $story) {
            $this->assertNotEmpty($story['story_description']);
            $this->assertCount(8, $story['manual_questions']);
            $this->assertSame($answerKeys[$index], implode('', array_column($story['manual_questions'], 'correct_answer')));
            foreach ($story['manual_questions'] as $question) {
                $this->assertNotEmpty($question['question']);
                $this->assertSame(['A', 'B', 'C', 'D'], array_keys($question['answers']));
                $this->assertArrayHasKey($question['correct_answer'], $question['answers']);
                foreach ($question['answers'] as $answer) $this->assertNotEmpty($answer);
            }
        }
    }

    public function test_only_teachers_can_open_the_library_and_opening_it_creates_no_assessments(): void
    {
        $this->get(route('assessments.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('assessments.index'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('assessments.index'))->assertForbidden();
        $response = $this->actingAs(User::factory()->teacher()->create())->get(route('assessments.index'));
        $response->assertOk()->assertSeeText('Default story')->assertSeeText('Use Story');
        foreach ($this->stories() as $story) $response->assertSeeText($story['story_title']);
        $this->assertDatabaseCount('assessments', 0);
    }

    public function test_each_template_can_be_saved_as_a_teacher_owned_database_copy(): void
    {
        $teacher = User::factory()->teacher()->create();
        foreach ($this->stories() as $story) {
            $this->actingAs($teacher)->post(route('assessments.store'), $this->payload($story))->assertSessionHasNoErrors()->assertRedirect(route('assessments.index'));
            $assessment = Assessment::where('title', $story['story_title'])->firstOrFail();
            $this->assertSame($teacher->id, $assessment->created_by);
            $this->assertSame('draft', $assessment->status);
            $this->assertSame($story['story_title'], $assessment->story_title);
            $this->assertSame($story['story_description'], $assessment->story_description);
            $this->assertEquals($story['manual_questions'], $assessment->manual_questions);
        }
    }

    public function test_edits_to_a_loaded_copy_do_not_change_the_original_library(): void
    {
        $original = $this->stories();
        $custom = $original[0];
        $custom['story_title'] = 'Our Class Garden';
        $custom['story_description'] .= ' Our class has a garden too.';
        $custom['manual_questions'][0]['question'] = 'What did the classmates work on?';
        $custom['manual_questions'][0]['answers']['A'] = 'A garden';
        $custom['manual_questions'][0]['correct_answer'] = 'A';
        $this->actingAs(User::factory()->teacher()->create())->post(route('assessments.store'), $this->payload($custom))->assertSessionHasNoErrors()->assertRedirect();
        $assessment = Assessment::firstOrFail();
        $this->assertSame($custom['story_description'], $assessment->story_description);
        $this->assertEquals($custom['manual_questions'], $assessment->manual_questions);
        $this->assertSame($original, $this->stories());
    }

    public function test_oral_reading_uses_the_passage_without_required_online_questions(): void
    {
        $story = $this->stories()[0];
        $payload = $this->payload($story, ['assessment_type' => 'oral_reading']);
        unset($payload['manual_questions']);
        $this->actingAs(User::factory()->teacher()->create())->post(route('assessments.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $assessment = Assessment::firstOrFail();
        $this->assertSame($story['story_description'], $assessment->story_description);
        $this->assertSame([], $assessment->manual_questions);
    }

    public function test_students_only_receive_the_selected_story_after_teacher_publication(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $story = $this->stories()[1];
        $this->actingAs($teacher)->post(route('assessments.store'), $this->payload($story))->assertRedirect();
        $assessment = Assessment::firstOrFail();
        $this->actingAs($student)->get(route('student.activities'))->assertOk()->assertDontSeeText($story['story_title']);
        $this->get(route('student.assessments.show', $assessment))->assertNotFound();
        $this->actingAs($teacher)->patch(route('assessments.availability', $assessment), ['status' => 'published'])->assertRedirect();
        $this->actingAs($student)->get(route('student.assessments.show', $assessment))->assertOk()
            ->assertSeeText($story['story_title'])->assertSeeText('Carlo')->assertDontSee('storyTemplates')->assertDontSee('The Reading Challenge');
        $this->actingAs(User::factory()->teacher()->create())->get(route('assessments.show', $assessment))->assertNotFound();
    }

    public function test_default_story_answer_keys_work_with_existing_comprehension_grading(): void
    {
        $teacher = User::factory()->teacher()->create();
        foreach ($this->stories() as $story) {
            $this->actingAs($teacher)->post(route('assessments.store'), $this->payload($story, ['status' => 'published', 'assessment_type' => 'listening_comprehension']))->assertRedirect();
            $assessment = Assessment::latest('id')->firstOrFail();
            $this->actingAs(User::factory()->create())->postJson(route('student.assessments.submit', $assessment), [
                'answers' => array_column($story['manual_questions'], 'correct_answer'),
            ])->assertOk()->assertJsonPath('correct_count', 8)->assertJsonPath('points', 2000)->assertJsonPath('phil_iri.level', 'Independent');
        }
    }
}
