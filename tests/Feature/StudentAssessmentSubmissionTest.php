<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\AssessmentRetakeRequest;
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

    public function test_multiple_choice_assessment_loads_hook_sound_effect(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();

        $assessment = Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Hook Sound Check',
            'subject' => 'literacy',
            'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual',
            'target_section' => 'all',
            'focus_areas' => ['Reading Fluency'],
            'instructions' => 'Catch the correct answer.',
            'status' => 'published',
            'manual_questions' => [[
                'question' => 'Choose the synonym for fast.',
                'answers' => ['A' => 'Quick', 'B' => 'Slow', 'C' => 'Late', 'D' => 'Still'],
                'correct_answer' => 'A',
            ]],
        ]);

        $this->actingAs($student)
            ->get(route('student.assessments.show', $assessment))
            ->assertOk()
            ->assertSee('multiple-choice-hook-sound')
            ->assertSee('audio/multiple-choice-hook-reel.mp3');

        $this->assertFileExists(public_path('audio/multiple-choice-hook-reel.mp3'));
    }

    public function test_student_needs_teacher_token_before_taking_assessment_again(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();

        $assessment = Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Retake Reading Check',
            'subject' => 'literacy',
            'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual',
            'target_section' => 'all',
            'focus_areas' => ['Reading Fluency'],
            'instructions' => 'Choose carefully.',
            'status' => 'published',
            'manual_questions' => [
                [
                    'question' => 'Choose the synonym for fast.',
                    'answers' => ['A' => 'Quick', 'B' => 'Slow', 'C' => 'Late', 'D' => 'Still'],
                    'correct_answer' => 'A',
                ],
            ],
        ]);

        $this->actingAs($student)
            ->postJson(route('student.assessments.submit', $assessment), ['answers' => [0 => 'A']])
            ->assertOk()
            ->assertJson(['attempt_number' => 1]);

        $this->actingAs($student)
            ->postJson(route('student.assessments.submit', $assessment), ['answers' => [0 => 'A']])
            ->assertForbidden();

        $this->actingAs($student)
            ->post(route('student.assessments.retake-request', $assessment), [
                'requested_tries' => 2,
                'message' => 'I want to improve my score.',
            ])
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas('status', 'Token has been requested for retake. Please wait for your teacher approval.');

        $retakeRequest = AssessmentRetakeRequest::query()->firstOrFail();

        $this->actingAs($teacher)
            ->post(route('students.assessment-requests.approve', [$student, $retakeRequest]), [
                'approved_tries' => 2,
            ])
            ->assertRedirect();

        $this->actingAs($student)
            ->postJson(route('student.assessments.submit', $assessment), ['answers' => [0 => 'A']])
            ->assertOk()
            ->assertJson(['attempt_number' => 2]);

        $this->assertDatabaseCount('assessment_submissions', 2);
        $this->assertDatabaseHas('assessment_retake_requests', [
            'id' => $retakeRequest->id,
            'status' => 'approved',
            'approved_tries' => 2,
            'remaining_tries' => 1,
        ]);
    }
    public function test_student_is_redirected_to_dashboard_when_retake_token_is_needed(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();

        $assessment = Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Retake Gate Check',
            'subject' => 'literacy',
            'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual',
            'target_section' => 'all',
            'focus_areas' => ['Reading Fluency'],
            'instructions' => 'Choose carefully.',
            'status' => 'published',
            'manual_questions' => [[
                'question' => 'Choose the synonym for fast.',
                'answers' => ['A' => 'Quick', 'B' => 'Slow', 'C' => 'Late', 'D' => 'Still'],
                'correct_answer' => 'A',
            ]],
        ]);

        AssessmentSubmission::query()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'attempt_number' => 1,
            'answers' => [0 => 'A'],
            'correct_count' => 1,
            'question_count' => 1,
            'points' => 250,
            'possible_points' => 250,
            'submitted_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('student.assessments.show', $assessment))
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas('status', 'Token has been requested for retake. Please wait for your teacher approval.');

        $this->assertDatabaseHas('assessment_retake_requests', [
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'teacher_id' => $teacher->id,
            'status' => 'pending',
            'requested_tries' => 1,
            'remaining_tries' => 0,
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
            ->assertDontSeeText('Getting Closer')
            ->assertDontSee('data-mark-mode="sentence-1"', false)
            ->assertSee('id="oral-mark-count"', false)
            ->assertSeeText('Mark Mode')
            ->assertSeeText('Sentence: Mispronounced')
            ->assertSee('data-sentence-mark="0"', false)
            ->assertSee('data-mark-mode="sentence-2"', false)
            ->assertSee('data-sync-scroll="oral-story"', false);

        $this->assertSame(2, substr_count($response->getContent(), 'data-sync-scroll="oral-story">'));
    }

    public function test_flashcards_assessment_uses_the_lily_pad_jumping_game(): void
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
            'instructions' => 'Choose the correct lily pad.',
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
            ->assertSeeText('Lily Pad Race')
            ->assertSeeText('FINISH')
            ->assertSeeText('To Finish')
            ->assertSee('id="frog-finish-pad"', false)
            ->assertSee('frog:finish-line')
            ->assertSee('frog-jump-sprite')
            ->assertSee('frog:jump')
            ->assertDontSee('mosquito')
            ->assertDontSee('frog-tongue')
            ->assertSee('data-frog-answer="A"', false)
            ->assertSeeText('Which word means quick?')
            ->assertSeeText('Question 1/1');
    }
}
