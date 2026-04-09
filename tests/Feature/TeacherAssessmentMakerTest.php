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
        $response->assertSeeText('Create literacy and numeracy assessments');
        $response->assertSeeText('No assessments yet');
    }

    public function test_teacher_can_create_a_literacy_assessment(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->post(route('assessments.store'), [
            'title' => 'Reading Readiness Check',
            'subject' => 'literacy',
            'instructions' => 'Complete the reading tasks in order.',
            'status' => 'published',
        ]);

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

        $response = $this->actingAs($teacher)->post(route('assessments.store'), [
            'title' => 'Number Sense Builder',
            'subject' => 'numeracy',
            'instructions' => 'Answer each math item carefully.',
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('assessments.index'));

        $this->assertDatabaseHas('assessments', [
            'title' => 'Number Sense Builder',
            'subject' => 'numeracy',
            'status' => 'draft',
            'created_by' => $teacher->id,
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
        $response->assertSeeText('published');
    }
}
