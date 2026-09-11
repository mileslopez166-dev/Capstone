<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAssessmentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_assessment_submission_is_scored_and_saved(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();

        $assessment = Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Data Egg Check',
            'subject' => 'literacy',
            'quiz_type' => 'data_egg',
            'delivery_method' => 'manual',
            'target_section' => 'all',
            'focus_areas' => ['Reading Fluency'],
            'instructions' => 'Capture the packets.',
            'status' => 'published',
            'manual_questions' => [
                [
                    'question' => 'Choose the synonym for fast.',
                    'answers' => ['A' => 'Quick', 'B' => 'Slow', 'C' => 'Late', 'D' => 'Still'],
                    'correct_answer' => 'A',
                ],
                [
                    'question' => 'Choose the opposite of cold.',
                    'answers' => ['A' => 'Wet', 'B' => 'Warm', 'C' => 'Dark', 'D' => 'Soft'],
                    'correct_answer' => 'B',
                ],
            ],
        ]);

        $response = $this
            ->actingAs($student)
            ->postJson(route('student.assessments.submit', $assessment), [
                'answers' => [
                    0 => 'A',
                    1 => 'C',
                ],
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'correct_count' => 1,
                'question_count' => 2,
                'points' => 250,
                'possible_points' => 500,
                'accuracy' => 50.0,
            ]);

        $this->assertDatabaseHas(AssessmentSubmission::class, [
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'correct_count' => 1,
            'question_count' => 2,
            'points' => 250,
            'possible_points' => 500,
        ]);
    }
    public function test_oral_reading_assessment_shows_pronunciation_legend(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();

        $assessment = Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Oral Story Check',
            'subject' => 'literacy',
            'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual',
            'assessment_type' => 'oral_reading',
            'target_section' => 'all',
            'focus_areas' => ['Reading Fluency'],
            'story_title' => 'Practice Passage',
            'story_description' => 'The learner reads this short passage aloud.',
            'instructions' => 'Read aloud for pronunciation marking.',
            'status' => 'published',
            'manual_questions' => [],
        ]);

        $response = $this
            ->actingAs($student)
            ->get(route('student.assessments.show', $assessment));

        $response
            ->assertOk()
            ->assertSeeText('Reading')
            ->assertSeeText('Teacher Check')
            ->assertSeeText('Legend')
            ->assertSeeText('Mispronounced')
            ->assertSeeText('Getting Closer')
            ->assertSee('data-sync-scroll="oral-story"', false);

        $this->assertSame(2, substr_count($response->getContent(), 'data-sync-scroll="oral-story">'));
    }
    public function test_flashcards_assessment_uses_the_frog_mosquito_game(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();

        $assessment = Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Frog Vocabulary Mission',
            'subject' => 'literacy',
            'quiz_type' => 'flashcards',
            'delivery_method' => 'manual',
            'assessment_type' => 'listening_comprehension',
            'target_section' => 'all',
            'focus_areas' => ['Reading Fluency'],
            'instructions' => 'Catch the correct answer.',
            'status' => 'published',
            'manual_questions' => [
                [
                    'question' => 'Which word means quick?',
                    'answers' => ['A' => 'Fast', 'B' => 'Slow', 'C' => 'Late', 'D' => 'Still'],
                    'correct_answer' => 'A',
                ],
            ],
        ]);

        $response = $this
            ->actingAs($student)
            ->get(route('student.assessments.show', $assessment));

        $response
            ->assertOk()
            ->assertSee('frog-flashcards-game')
            ->assertSee('data-frog-answer="A"', false)
            ->assertSeeText('Which word means quick?')
            ->assertSeeText('Question 1/1');
    }
}