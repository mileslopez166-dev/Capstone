<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAssessmentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_assign_across_sections_and_student_can_open_the_assessment(): void
    {
        $teacher = User::factory()->teacher()->create(['section' => 'Magalang']);
        $student = User::factory()->create(['section' => 'Masipag']);
        $assessment = Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Cross Section Reading Check',
            'subject' => 'literacy',
            'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual',
            'target_section' => 'section_d',
            'assessment_type' => 'silent_reading',
            'manual_questions' => [[
                'question' => 'Which answer is correct?',
                'answers' => ['A' => 'Correct', 'B' => 'No', 'C' => 'No', 'D' => 'No'],
                'correct_answer' => 'A',
            ]],
            'status' => 'published',
        ]);

        $this->actingAs($teacher)->get(route('assessments.show', $assessment))
            ->assertOk()
            ->assertSeeText($student->name);

        $this->actingAs($student)->get(route('student.assessments.show', $assessment))
            ->assertOk()
            ->assertSeeText('Cross Section Reading Check');
    }

    public function test_results_are_private_to_the_assessment_creator_even_when_teachers_share_a_student(): void
    {
        $teacherA = User::factory()->teacher()->create(['section' => 'Magalang']);
        $teacherB = User::factory()->teacher()->create(['section' => 'Masunurin']);
        $student = User::factory()->create(['name' => 'Cross Section Student', 'section' => 'Matapat']);

        $assessmentA = Assessment::query()->create([
            'created_by' => $teacherA->id,
            'title' => 'Teacher A Private Result',
            'subject' => 'literacy',
            'assessment_type' => 'silent_reading',
            'target_section' => 'all',
            'status' => 'published',
            'manual_questions' => [],
        ]);
        $assessmentB = Assessment::query()->create([
            'created_by' => $teacherB->id,
            'title' => 'Teacher B Private Result',
            'subject' => 'literacy',
            'assessment_type' => 'silent_reading',
            'target_section' => 'all',
            'status' => 'published',
            'manual_questions' => [],
        ]);
        $submissionA = AssessmentSubmission::query()->create([
            'assessment_id' => $assessmentA->id,
            'user_id' => $student->id,
            'attempt_number' => 1,
            'answers' => ['A'],
            'correct_count' => 1,
            'question_count' => 1,
            'points' => 50,
            'possible_points' => 50,
            'submitted_at' => now()->subMinute(),
        ]);
        AssessmentSubmission::query()->create([
            'assessment_id' => $assessmentB->id,
            'user_id' => $student->id,
            'attempt_number' => 1,
            'answers' => ['A'],
            'correct_count' => 0,
            'question_count' => 1,
            'points' => 0,
            'possible_points' => 50,
            'submitted_at' => now(),
        ]);

        $this->actingAs($teacherA)->get(route('students.show', $student))
            ->assertOk()
            ->assertSeeText($assessmentA->title)
            ->assertDontSeeText($assessmentB->title);
        $this->actingAs($teacherB)->get(route('students.show', $student))
            ->assertOk()
            ->assertSeeText($assessmentB->title)
            ->assertDontSeeText($assessmentA->title);

        $this->actingAs($teacherA)->get(route('reports.student', $student))
            ->assertOk()
            ->assertSeeText($assessmentA->title)
            ->assertDontSeeText($assessmentB->title);
        $this->actingAs($teacherB)->get(route('reports.student', $student))
            ->assertOk()
            ->assertSeeText($assessmentB->title)
            ->assertDontSeeText($assessmentA->title);

        $this->actingAs($teacherA)->get(route('teacher.phil-iri.show', $submissionA))->assertOk();
        $this->actingAs($teacherB)->get(route('teacher.phil-iri.show', $submissionA))->assertNotFound();
    }
}
