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
            'story_description' => $story['story_description'], 'manual_questions' => $story['question_sets']['instructional'],
            'status' => 'draft', 'retry_limit' => 0,
        ], $overrides);
    }

    public function test_library_contains_five_new_stories_with_128_grouped_questions_and_matching_guides(): void
    {
        $stories = $this->stories();
        $this->assertSame([
            'A Clearer Book Fair', 'The Garden Schedule', 'The Changed Meeting Time',
            'A Fair Turn on the Court', 'The Quiet Recording Corner',
        ], array_column($stories, 'story_title'));
        $this->assertCount(5, array_unique(array_column($stories, 'id')));
        $total = 0;
        foreach ($stories as $index => $story) {
            $this->assertNotEmpty($story['story_description']);
            $this->assertSame($index === 0 ? ['frustration', 'instructional', 'independent', 'advanced'] : ['frustration', 'instructional', 'independent'], array_keys($story['question_sets']));
            foreach ($story['question_sets'] as $level => $set) {
                $this->assertCount(8, $set);
                $keys = array_count_values(array_column($set, 'correct_answer'));
                ksort($keys);
                $this->assertSame(['A' => 2, 'B' => 2, 'C' => 2, 'D' => 2], $keys);
                $this->assertSame([$level], array_values(array_unique(array_column($set, 'difficulty'))));
            }
            $questions = array_merge(...array_values($story['question_sets']));
            $total += count($questions);
            $this->assertCount(count($questions), $story['answer_guide']);
            foreach ($questions as $number => $question) {
                $this->assertNotEmpty($question['question']);
                $this->assertSame(['A', 'B', 'C', 'D'], array_keys($question['answers']));
                $this->assertArrayHasKey($question['correct_answer'], $question['answers']);
                foreach ($question['answers'] as $answer) {
                    $this->assertNotEmpty($answer);
                }
                $guide = $story['answer_guide'][$number];
                $this->assertSame($number + 1, $guide['number']);
                $this->assertSame($question['correct_answer'], $guide['correct_answer']);
                $this->assertSame($question['difficulty'], $guide['difficulty']);
                $this->assertNotEmpty($guide['skill']);
                $this->assertNotEmpty($guide['explanation']);
            }
        }
        $this->assertSame(128, $total);
    }

    public function test_only_teachers_can_open_the_library_and_opening_it_creates_no_assessments(): void
    {
        $this->get(route('assessments.create'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('assessments.create'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('assessments.create'))->assertForbidden();
        $response = $this->actingAs(User::factory()->teacher()->create())->get(route('assessments.create'));
        $response->assertOk()->assertSeeText('Default story')->assertSeeText('Use Story')->assertSeeText('Question difficulty')
            ->assertDontSeeText('The School Garden Project')->assertDontSeeText('The Lost Notebook');
        foreach ($this->stories() as $story) {
            $response->assertSeeText($story['story_title']);
        }
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
            $this->assertEquals($story['question_sets']['instructional'], $assessment->manual_questions);
        }
    }

    public function test_edits_to_a_loaded_copy_do_not_change_the_original_library(): void
    {
        $original = $this->stories();
        $custom = $original[0];
        $custom['story_title'] = 'Our Class Garden';
        $custom['story_description'] .= ' Our class has a garden too.';
        $custom['question_sets']['instructional'][0]['question'] = 'What did the classmates work on?';
        $custom['question_sets']['instructional'][0]['answers']['A'] = 'A garden';
        $custom['question_sets']['instructional'][0]['correct_answer'] = 'A';
        $this->actingAs(User::factory()->teacher()->create())->post(route('assessments.store'), $this->payload($custom))->assertSessionHasNoErrors()->assertRedirect();
        $assessment = Assessment::firstOrFail();
        $this->assertSame($custom['story_description'], $assessment->story_description);
        $this->assertEquals($custom['question_sets']['instructional'], $assessment->manual_questions);
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
            ->assertSeeText($story['story_title'])->assertSeeText('Aya')->assertDontSee('storyTemplates')->assertDontSee('Template answer guide')
            ->assertDontSee('Frustration - support')->assertDontSee('Advanced - enrichment');
        $this->actingAs(User::factory()->teacher()->create())->get(route('assessments.show', $assessment))->assertNotFound();
    }

    public function test_default_story_answer_keys_work_with_existing_comprehension_grading(): void
    {
        $teacher = User::factory()->teacher()->create();
        foreach ($this->stories() as $story) {
            foreach ($story['question_sets'] as $questions) {
                $this->actingAs($teacher)->post(route('assessments.store'), $this->payload($story, ['manual_questions' => $questions, 'status' => 'published', 'assessment_type' => 'listening_comprehension']))->assertSessionHasNoErrors()->assertRedirect();
                $assessment = Assessment::latest('id')->firstOrFail();
                $this->assertEquals($questions, $assessment->manual_questions);
                $this->actingAs(User::factory()->create())->postJson(route('student.assessments.submit', $assessment), [
                    'answers' => array_column($questions, 'correct_answer'),
                ])->assertOk()->assertJsonPath('correct_count', 8)->assertJsonPath('points', 400)->assertJsonPath('phil_iri.level', 'Independent');
            }
        }
    }

    public function test_invalid_difficulty_is_rejected_and_existing_assessments_remain_unchanged(): void
    {
        $teacher = User::factory()->teacher()->create();
        $payload = $this->payload($this->stories()[0], ['title' => 'The Lost Notebook', 'story_description' => 'An existing teacher-created passage.']);
        $this->actingAs($teacher)->post(route('assessments.store'), $payload)->assertSessionHasNoErrors();
        $existing = Assessment::firstOrFail()->toArray();
        $this->get(route('assessments.create'))->assertOk();
        $this->assertSame($existing, Assessment::firstOrFail()->toArray());
        $payload['manual_questions'][0]['difficulty'] = 'made-up';
        $this->post(route('assessments.store'), $payload)->assertSessionHasErrors('manual_questions.0.difficulty');
        $this->assertDatabaseCount('assessments', 1);
    }
}
