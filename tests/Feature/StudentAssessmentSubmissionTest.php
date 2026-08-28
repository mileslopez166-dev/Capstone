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
}
