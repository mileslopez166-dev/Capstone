<?php

namespace Tests\Feature;

use App\Models\{Assessment, AssessmentProgress, User, WorksheetAttempt};
use App\Support\WorksheetMission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorksheetMissionTest extends TestCase
{
    use RefreshDatabase;

    private function worksheet(User $teacher, int $number, array $extra = []): Assessment
    {
        return Assessment::create(array_merge(['created_by' => $teacher->id, 'title' => 'Worksheet '.$number,
            'subject' => 'numeracy', 'quiz_type' => 'worksheet', 'assessment_type' => 'worksheet',
            'worksheet_number' => $number, 'worksheet_total' => 10, 'target_section' => 'section_a', 'status' => 'published'], $extra));
    }

    private function submit(Assessment $assessment, User $student, int $attempt = 1, bool $reviewed = false): WorksheetAttempt
    {
        $progress = AssessmentProgress::forAttempt($assessment, $student, $attempt);
        return WorksheetAttempt::create(['assessment_id' => $assessment->id, 'user_id' => $student->id,
            'progress_id' => $progress->id, 'pages' => [], 'total' => 10,
            'score' => $reviewed ? 8 : null, 'reviewed_at' => $reviewed ? now() : null]);
    }

    public function test_numeracy_builder_has_no_quiz_type_and_students_get_a_locked_35_step_mission(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher)->get(route('worksheets.index'))->assertOk()->assertSee('Numeracy Worksheet Mission')
            ->assertDontSee('Choose Quiz Type')->assertDontSee('Frog Flashcards')->assertDontSee('Interactive Egg');
        $this->get(route('assessments.index'))->assertSee('data-numeracy-mission', false)->assertDontSee('value="numeracy"', false);
        $this->get(route('worksheets.mission'))->assertForbidden();
        $student = User::factory()->create(['section' => 'Section A']);
        $this->actingAs($student)->get(route('worksheets.mission'))->assertOk()->assertSee('0 / 35 submitted')->assertSee('Worksheet 35');
        $mission = WorksheetMission::forStudent($student);
        $this->assertSame(35, $mission['steps']->where('state', 'locked')->count());
        $this->assertNull($mission['next']);
        $this->get(route('student.activities', ['subject' => 'numeracy']))->assertSee(route('worksheets.mission'), false);
        $this->get(route('student.dashboard'))->assertSee('Your Numeracy Mission');
    }

    public function test_path_tracks_each_worksheet_once_and_prioritizes_saved_work(): void
    {
        $teacher = User::factory()->teacher()->create(); $student = User::factory()->create(['section' => 'Section A']);
        $one = $this->worksheet($teacher, 1); $two = $this->worksheet($teacher, 2); $three = $this->worksheet($teacher, 3);
        $this->worksheet($teacher, 4, ['status' => 'draft']);
        $this->worksheet($teacher, 5, ['target_section' => 'section_b']);
        $done = $this->submit($one, $student);
        $this->submit($one, $student, 2, true);
        AssessmentProgress::forAttempt($three, $student, 1)->update(['state' => ['page' => 1, 'pages' => [], 'phase' => 'questions']]);
        $mission = WorksheetMission::forStudent($student);
        $this->assertSame(1, $mission['finished']);
        $this->assertSame(1, $mission['reviewed']);
        $this->assertSame(3, $mission['next']['number']);
        $this->assertSame('ready', $mission['steps']->firstWhere('number', 2)['state']);
        foreach ([4, 5] as $number) $this->assertSame('locked', $mission['steps']->firstWhere('number', $number)['state']);
        $one->update(['status' => 'draft']);
        $this->assertSame(1, WorksheetMission::forStudent($student)['finished']);
        $this->assertSame(0, WorksheetMission::forStudent(User::factory()->create())['finished']);
        $this->actingAs($student)->get(route('worksheets.review', $done))->assertSee('Continue Mission');
    }

    public function test_submitting_all_35_finishes_the_mission_without_inventing_grades(): void
    {
        $teacher = User::factory()->teacher()->create(); $student = User::factory()->create(['section' => 'Section A']);
        foreach (range(1, 35) as $number) $this->submit($this->worksheet($teacher, $number), $student);
        $mission = WorksheetMission::forStudent($student);
        $this->assertTrue($mission['complete']);
        $this->assertSame(35, $mission['finished']);
        $this->assertSame(0, $mission['reviewed']);
        $this->assertNull($mission['next']);
        $this->assertDatabaseCount('assessment_submissions', 0);
        $this->actingAs($student)->get(route('worksheets.mission'))->assertOk()->assertSee('Mission Complete!')->assertSee('35 / 35 submitted');
    }
}
