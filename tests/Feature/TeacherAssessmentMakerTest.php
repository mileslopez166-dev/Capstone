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
        $response->assertSeeText('Import Story and Questions from TXT');
        $response->assertSee('question-text-file');
        $response->assertSeeText('Frog Flashcards');
    }

    public function test_multiple_choice_upload_sample_includes_story_format(): void
    {
        $sample = file_get_contents(public_path('samples/multiple-choice-upload-sample.txt'));

        $this->assertStringContainsString('Story Title:', $sample);
        $this->assertStringContainsString('Story Description:', $sample);
        $this->assertStringContainsString('Question:', $sample);
        $this->assertStringContainsString('Correct Answer:', $sample);
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

    public function test_teacher_can_create_oral_reading_assessment_without_questions(): void
    {
        $teacher = User::factory()->teacher()->create();
        $payload = $this->assessmentPayload([
            'title' => 'Oral Story Check',
            'assessment_type' => 'oral_reading',
            'story_title' => 'The Garden Path',
            'story_description' => 'Read this passage aloud for fluency checking.',
        ]);
        unset($payload['manual_questions']);

        $this->actingAs($teacher)
            ->post(route('assessments.store'), $payload)
            ->assertRedirect(route('assessments.index'));

        $assessment = Assessment::query()
            ->where('title', 'Oral Story Check')
            ->firstOrFail();

        $this->assertSame([], $assessment->manual_questions);
    }

    public function test_silent_reading_assessment_requires_questions(): void
    {
        $teacher = User::factory()->teacher()->create();
        $payload = $this->assessmentPayload([
            'assessment_type' => 'silent_reading',
        ]);
        unset($payload['manual_questions']);

        $this->actingAs($teacher)
            ->from(route('assessments.index'))
            ->post(route('assessments.store'), $payload)
            ->assertRedirect(route('assessments.index'))
            ->assertSessionHasErrors('manual_questions');
    }

    public function test_numeracy_game_creation_redirects_to_worksheet_missions(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->post(route('assessments.store'), $this->assessmentPayload([
            'title' => 'Number Sense Builder',
            'subject' => 'numeracy',
            'instructions' => 'Answer each math item carefully.',
            'focus_areas' => ['Mental Arithmetic'],
            'status' => 'draft',
        ]));

        $response->assertRedirect(route('worksheets.index'));
        $this->assertDatabaseMissing('assessments', ['title' => 'Number Sense Builder']);
        $this->get(route('assessments.index', ['subject' => 'numeracy']))->assertRedirect(route('worksheets.index'));
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

    public function test_teacher_can_only_see_and_manage_assessments_they_created(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();

        $ownedAssessment = Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'My Reading Check',
            'subject' => 'literacy',
            'status' => 'published',
        ]);
        $foreignAssessment = Assessment::query()->create([
            'created_by' => $otherTeacher->id,
            'title' => 'Other Instructor Check',
            'subject' => 'literacy',
            'status' => 'published',
            'retry_limit' => 0,
        ]);

        $this->actingAs($teacher)
            ->get(route('assessments.index'))
            ->assertOk()
            ->assertSeeText('My Reading Check')
            ->assertDontSeeText('Other Instructor Check');

        $this->get(route('assessments.show', $ownedAssessment))->assertOk();
        $this->get(route('assessments.show', $foreignAssessment))->assertNotFound();
        $this->patch(route('assessments.availability', $foreignAssessment), ['status' => 'draft'])->assertNotFound();
        $this->patch(route('assessments.retries', $foreignAssessment), ['retry_limit' => 3])->assertNotFound();
        $this->delete(route('assessments.destroy', $foreignAssessment))->assertNotFound();

        $this->assertDatabaseHas('assessments', [
            'id' => $foreignAssessment->id,
            'status' => 'published',
            'retry_limit' => 0,
        ]);
    }

    public function test_teacher_cannot_spoof_assessment_creator_when_creating_assessment(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->post(route('assessments.store'), $this->assessmentPayload([
                'title' => 'Ownership Spoof Check',
                'created_by' => $otherTeacher->id,
            ]))
            ->assertRedirect(route('assessments.index'));

        $this->assertDatabaseHas('assessments', [
            'title' => 'Ownership Spoof Check',
            'created_by' => $teacher->id,
        ]);
        $this->assertDatabaseMissing('assessments', [
            'title' => 'Ownership Spoof Check',
            'created_by' => $otherTeacher->id,
        ]);
    }

    public function test_teacher_can_set_and_update_retry_limit(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher)->post(route('assessments.store'), $this->assessmentPayload(['retry_limit' => 2]))
            ->assertSessionHasNoErrors()->assertRedirect(route('assessments.index'));
        $assessment = Assessment::query()->firstOrFail();
        $this->assertSame(2, $assessment->retry_limit);
        $this->get(route('assessments.show', $assessment))->assertOk()->assertSeeText('3 total attempts per student');
        $this->patch(route('assessments.retries', $assessment), ['retry_limit' => 4])->assertSessionHasNoErrors();
        $this->assertSame(4, $assessment->fresh()->retry_limit);
        $this->patch(route('assessments.retries', $assessment), ['retry_limit' => 'unlimited'])->assertSessionHasNoErrors();
        $this->assertSame(Assessment::UNLIMITED_RETRY_LIMIT, $assessment->fresh()->retry_limit);
        $this->get(route('assessments.show', $assessment))->assertOk()->assertSeeText('Unlimited attempts per student');

        foreach ([-1, 11, 1.5, 'forever'] as $invalid) {
            $this->patch(route('assessments.retries', $assessment), ['retry_limit' => $invalid])->assertSessionHasErrors('retry_limit');
        }
        $this->actingAs(User::factory()->teacher()->create())
            ->patch(route('assessments.retries', $assessment), ['retry_limit' => 0])->assertNotFound();
        $this->actingAs(User::factory()->create())
            ->patch(route('assessments.retries', $assessment), ['retry_limit' => 0])->assertForbidden();
        $this->assertSame(Assessment::UNLIMITED_RETRY_LIMIT, $assessment->fresh()->retry_limit);
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
