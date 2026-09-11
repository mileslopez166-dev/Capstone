<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAssessmentMakerTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_view_assessment_maker_page(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->get(route('assessments.index'));

        $response->assertOk();
        $response->assertSeeText('Create New Assessment');
        $response->assertSeeText('No assessments yet');
        $response->assertSeeText('Import Questions from TXT');
        $response->assertSee('question-text-file');
        $response->assertSeeText('Frog Flashcards');
    }

    public function test_teacher_can_create_a_literacy_assessment(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->post(route('assessments.store'), $this->assessmentPayload([
            'title' => 'Reading Readiness Check',
            'subject' => 'literacy',
            'instructions' => 'Complete the reading tasks in order.',
            'status' => 'published',
        ]));

        $response->assertRedirect(route('assessments.index'));

        $this->assertDatabaseHas('assessments', [
            'title' => 'Reading Readiness Check',
            'subject' => 'literacy',
            'status' => 'published',
            'created_by' => $teacher->id,
        ]);
    }

    public function test_teacher_can_create_a_numeracy_assessment(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->post(route('assessments.store'), $this->assessmentPayload([
            'title' => 'Number Sense Builder',
            'subject' => 'numeracy',
            'instructions' => 'Answer each math item carefully.',
            'focus_areas' => ['Mental Arithmetic'],
            'status' => 'draft',
        ]));

        $response->assertRedirect(route('assessments.index'));

        $this->assertDatabaseHas('assessments', [
            'title' => 'Number Sense Builder',
            'subject' => 'numeracy',
            'status' => 'draft',
            'created_by' => $teacher->id,
        ]);
    }

    public function test_teacher_can_lock_and_unlock_an_assessment(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();

        $assessment = Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Lockable Story Mission',
            'subject' => 'literacy',
            'status' => 'published',
        ]);

        $this->actingAs($teacher)
            ->patch(route('assessments.availability', $assessment), ['status' => 'draft'])
            ->assertRedirect();

        $this->assertDatabaseHas('assessments', [
            'id' => $assessment->id,
            'status' => 'draft',
        ]);

        $this->actingAs($student)
            ->get(route('student.activities'))
            ->assertOk()
            ->assertDontSeeText('Lockable Story Mission');

        $this->actingAs($student)
            ->get(route('student.assessments.show', $assessment))
            ->assertNotFound();

        $this->actingAs($teacher)
            ->patch(route('assessments.availability', $assessment), ['status' => 'published'])
            ->assertRedirect();

        $this->assertDatabaseHas('assessments', [
            'id' => $assessment->id,
            'status' => 'published',
        ]);
    }

    public function test_student_cannot_open_teacher_assessment_maker_page(): void
    {
        $student = User::factory()->create();

        $response = $this->actingAs($student)->get(route('assessments.index'));

        $response->assertForbidden();
    }

    public function test_teacher_assessment_maker_lists_existing_assessments(): void
    {
        $teacher = User::factory()->teacher()->create();

        Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Story Comprehension Drill',
            'subject' => 'literacy',
            'status' => 'published',
        ]);

        $response = $this->actingAs($teacher)->get(route('assessments.index'));

        $response->assertOk();
        $response->assertSeeText('Story Comprehension Drill');
        $response->assertSeeText('Unlocked');
    }

    private function assessmentPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'title' => 'Reading Readiness Check',
            'subject' => 'literacy',
            'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual',
            'target_section' => 'all',
            'assessment_type' => 'silent_reading',
            'focus_areas' => ['Reading Fluency'],
            'manual_questions' => [[
                'question' => 'What is the main idea?',
                'answers' => [
                    'A' => 'A clear answer',
                    'B' => 'Another answer',
                    'C' => 'Third answer',
                    'D' => 'Fourth answer',
                ],
                'correct_answer' => 'A',
            ]],
            'instructions' => 'Complete the reading tasks in order.',
            'story_title' => 'Practice Story',
            'story_description' => 'A short practice story for assessment.',
            'status' => 'published',
        ], $overrides);
    }
}
