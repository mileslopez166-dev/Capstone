<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentProgress;
use App\Models\AssessmentSubmission;
use App\Models\PracticeMission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTaskQueueTest extends TestCase
{
    use RefreshDatabase;

    private function assessment(array $attributes = []): Assessment
    {
        return Assessment::create(array_merge([
            'created_by' => User::factory()->teacher()->create()->id, 'title' => 'Reading Journey',
            'subject' => 'literacy', 'quiz_type' => 'multiple_choice', 'delivery_method' => 'manual',
            'target_section' => 'all', 'status' => 'published', 'manual_questions' => [], 'retry_limit' => 3,
        ], $attributes));
    }

    private function submission(Assessment $assessment, User $student, int $attempt, int $correct): AssessmentSubmission
    {
        return AssessmentSubmission::create([
            'assessment_id' => $assessment->id, 'user_id' => $student->id, 'attempt_number' => $attempt,
            'answers' => [], 'correct_count' => $correct, 'question_count' => 4,
            'points' => $correct * 250, 'possible_points' => 1000, 'submitted_at' => now()->addSeconds($attempt),
        ]);
    }

    public function test_dashboard_prioritizes_saved_current_attempt_and_counts_only_best_scores(): void
    {
        $student = User::factory()->create();
        $assessment = $this->assessment();
        $this->submission($assessment, $student, 1, 4);
        $latest = $this->submission($assessment, $student, 2, 1);
        $this->assessment(['title' => 'New assignment']);
        AssessmentProgress::forAttempt($assessment, $student, 3)->update(['state' => ['phase' => 'questions', 'answers' => ['A']]]);
        $this->actingAs($student)->get(route('student.dashboard'))->assertOk()
            ->assertViewHas('studentMetrics', fn ($metrics) => $metrics['completed_count'] === 1 && $metrics['total_points'] === 1000 && $metrics['average_accuracy'] === 100)
            ->assertViewHas('nextTask', fn ($task) => $task['title'] === $assessment->title && $task['status'] === 'in_progress' && $task['action'] === 'Continue Assessment')
            ->assertViewHas('recentSubmissions', fn ($results) => $results->count() === 1 && $results->first()->id === $latest->id);
        $this->get(route('student.activities'))->assertOk()->assertSee('Continue Assessment')->assertSee('result-'.$latest->id);
    }

    public function test_empty_or_stale_progress_is_not_a_current_attempt(): void
    {
        $student = User::factory()->create();
        $assessment = $this->assessment();
        AssessmentProgress::forAttempt($assessment, $student, 1);
        $this->actingAs($student)->get(route('student.dashboard'))->assertOk()
            ->assertViewHas('nextTask', fn ($task) => $task['status'] === 'not_started');
        $this->submission($assessment, $student, 1, 2);
        AssessmentProgress::where('assessment_id', $assessment->id)->update(['state' => ['phase' => 'questions']]);
        $this->get(route('student.dashboard'))->assertOk()
            ->assertViewHas('nextTask', fn ($task) => $task['status'] === 'completed' && $task['action'] === 'Retake Assessment');
    }

    public function test_queue_excludes_other_sections_locked_work_and_other_students_progress(): void
    {
        $student = User::factory()->create(['section' => 'Section A']);
        $assessment = $this->assessment();
        AssessmentProgress::forAttempt($assessment, User::factory()->create(), 1)->update(['state' => ['phase' => 'questions']]);
        $this->assessment(['status' => 'draft', 'title' => 'Hidden draft']);
        $this->assessment(['target_section' => 'section_b', 'title' => 'Other section']);
        $this->actingAs($student)->get(route('student.dashboard'))->assertOk()
            ->assertDontSee('Hidden draft')->assertDontSee('Other section')
            ->assertViewHas('pendingAssessments', fn ($items) => $items->count() === 1)
            ->assertViewHas('nextTask', fn ($task) => $task['status'] === 'not_started');
    }

    public function test_practice_can_be_next_task_and_teacher_review_is_distinct_from_completion(): void
    {
        $student = User::factory()->create();
        $teacher = User::factory()->teacher()->create();
        $mission = PracticeMission::create([
            'student_id' => $student->id, 'teacher_id' => $teacher->id, 'title' => 'Read slowly',
            'source_title' => 'Reading', 'questions' => [], 'words' => [], 'status' => 'assigned',
            'progress' => ['answers' => ['A']], 'reward_coins' => 25,
        ]);
        $this->assessment();
        $this->actingAs($student)->get(route('student.dashboard'))->assertOk()
            ->assertViewHas('nextTask', fn ($task) => $task['kind'] === 'Practice Mission' && $task['action'] === 'Continue Practice');
        $mission->update(['status' => 'completed']);
        $this->assertSame('completed', $mission->displayStatus());
        $this->assertSame('needs_review', $mission->displayStatus(true));
        $this->actingAs($teacher)->get(route('teacher.practice.index'))->assertOk()->assertSee('Needs Review');
        $mission->update(['reviewed_at' => now()]);
        $this->assertSame('reviewed', $mission->displayStatus(true));
    }

    public function test_all_role_headers_have_comfort_controls_and_empty_dashboard_is_usable(): void
    {
        $student = User::factory()->create();
        $this->actingAs($student)->get(route('student.dashboard'))->assertOk()->assertViewHas('nextTask', null)->assertSee('Sound and motion settings');
        $this->actingAs(User::factory()->teacher()->create())->get(route('teacher.dashboard'))->assertOk()->assertSee('Sound and motion settings');
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->assertOk()->assertSee('Sound and motion settings');
    }

    public function test_locked_preview_cannot_be_saved_and_does_not_spend_coins(): void
    {
        $student = User::factory()->create(['avatar_config' => \App\Support\AvatarWardrobe::defaults('male')]);
        $student->practiceCoinTransactions()->create(['amount' => 100, 'description' => 'Practice rewards']);
        $this->actingAs($student)->get(route('student.wardrobe.edit'))->assertOk()->assertSee('Item ownership')->assertSee('Preview Cloud bomber');
        $this->patch(route('student.wardrobe.update'), ['avatar' => array_merge($student->avatar_config, ['outfit' => 'bomber'])])->assertSessionHasErrors('avatar.outfit');
        $this->assertSame(100, $student->practiceCoinBalance());
        $this->assertSame('hoodie', $student->fresh()->avatar_config['outfit']);
    }
}
