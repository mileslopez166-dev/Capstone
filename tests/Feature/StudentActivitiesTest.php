<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentActivitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_activities_page_requires_authentication(): void
    {
        $response = $this->get(route('student.activities'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_the_student_activities_page(): void
    {
        $user = User::factory()->create([
            'name' => 'Mateo Cruz',
        ]);

        $response = $this->actingAs($user)->get(route('student.activities'));

        $response->assertOk();
        $response->assertSeeText('Pending Assessments');
        $response->assertSeeText('No pending assessment');
        $response->assertSeeText('Awaiting teacher assignment');
    }


    public function test_student_can_review_wrong_answers_from_recorded_outputs(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create([
            'name' => 'Miles Lopez',
            'section' => 'Section A',
            'approval_status' => 'approved',
        ]);

        $assessment = Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Apple Story Quiz',
            'subject' => 'literacy',
            'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual',
            'target_section' => 'section_a',
            'assessment_type' => 'silent_reading',
            'focus_areas' => ['Comprehension Depth'],
            'instructions' => 'Answer carefully.',
            'status' => 'published',
            'manual_questions' => [[
                'question' => 'What is an apple?',
                'answers' => ['A' => 'Fruit', 'B' => 'Color', 'C' => 'Thing', 'D' => 'Person'],
                'correct_answer' => 'A',
            ]],
        ]);

        AssessmentSubmission::query()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'attempt_number' => 1,
            'answers' => [0 => 'B'],
            'correct_count' => 0,
            'question_count' => 1,
            'points' => 0,
            'possible_points' => 250,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($student)->get(route('student.activities'));

        $response
            ->assertOk()
            ->assertSeeText('Assessment Review')
            ->assertSee('<details class="group mt-5', false)
            ->assertSeeText('What is an apple?')
            ->assertSeeText('B. Color')
            ->assertSeeText('A. Fruit')
            ->assertSeeText('1 Wrong');
    }
    public function test_student_activities_page_lists_published_teacher_assessments(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();

        Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Reading Fluency Check',
            'subject' => 'literacy',
            'instructions' => 'Read the passage before answering.',
            'status' => 'published',
        ]);

        Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Fractions Drill',
            'subject' => 'numeracy',
            'instructions' => 'Solve the number problems carefully.',
            'status' => 'published',
        ]);

        $response = $this->actingAs($student)->get(route('student.activities'));

        $response->assertOk();
        $response->assertSeeText('Pending assessments');
        $response->assertSeeText('Reading Fluency Check');
        $response->assertSeeText('Fractions Drill');
        $response->assertSeeText('Assessment available to open');
    }
}
