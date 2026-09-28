<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsiveTablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_tables_have_named_keyboard_accessible_scroll_regions(): void
    {
        config(['tutor.enabled' => true, 'tutor.key' => 'test-placeholder']);
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create(['section' => 'Section A']);
        $student = User::factory()->create(['section' => 'Section A',
            'name' => 'Alexandra Maria Verylongfamilynameforlayoutchecking',
            'email' => 'long.student.email.for.layout.checks@example.test']);
        User::factory()->pendingApproval()->create(['section' => 'Section A']);
        User::factory()->teacher()->pendingApproval()->create(['section' => 'Section A']);
        User::factory()->create()->delete();
        $assessment = Assessment::create([
            'created_by' => $teacher->id, 'title' => 'The School Garden and Our Helpful Neighbors',
            'subject' => 'literacy', 'quiz_type' => 'multiple_choice', 'delivery_method' => 'manual',
            'target_section' => 'all', 'assessment_type' => 'silent_reading',
            'story_title' => 'A Helpful Neighbor', 'story_description' => 'Our class planted a school garden.',
            'focus_areas' => [], 'status' => 'published',
            'manual_questions' => [['question' => 'What did the class plant?', 'answers' => ['A' => 'A garden', 'B' => 'A tree'], 'correct_answer' => 'A']],
        ]);
        $completed = $assessment->replicate();
        $completed->title = 'Completed Reading Assessment';
        $completed->save();
        AssessmentSubmission::create([
            'assessment_id' => $completed->id, 'user_id' => $student->id, 'attempt_number' => 1,
            'answers' => ['A'], 'correct_count' => 1, 'question_count' => 1,
            'points' => 250, 'possible_points' => 250,
            'phil_iri' => \App\Support\PhilIri::initial($completed, 1, 1), 'submitted_at' => now(),
        ]);

        foreach ([
            [$admin, 'admin.dashboard', 'User management'],
            [$admin, 'admin.token-requests.index', 'Pending account requests'],
            [$admin, 'admin.users.trash', 'Deleted users'],
            [$teacher, 'students.index', 'Student roster'],
        ] as [$user, $route, $label]) {
            $response = $this->actingAs($user)->get(route($route));
            $response->assertOk()->assertSee('ui-data-table-scroll', false)
                ->assertSee('aria-label="'.$label.'"', false)->assertSee('tabindex="0"', false);
            $this->capture($route, $response->getContent());
        }

        $profile = $this->actingAs($teacher)->get(route('students.show', $student));
        $profile->assertOk()->assertSee('assisted-assignment', false)->assertSeeText('Take an Assessment Together');
        $this->capture('students.show', $profile->getContent());

        foreach ([
            [$teacher, 'teacher.dashboard'], [$teacher, 'reports.index'], [$teacher, 'assessments.create'],
            [$teacher, 'teacher.ai-assistant.index'], [$student, 'student.tutor.index'],
            [$student, 'student.dashboard'], [$student, 'student.activities'], [$student, 'student.leaderboard'],
        ] as [$user, $route]) {
            $response = $this->actingAs($user)->get(route($route, $route === 'student.activities' ? ['subject' => 'literacy'] : []));
            $response->assertOk();
            $this->capture($route, $response->getContent());
        }
    }

    private function capture(string $route, string $html): void
    {
        if (getenv('CAPTURE_RESPONSIVE_FIXTURES')) {
            file_put_contents(storage_path('app/responsive-'.$route.'.html'), $html);
        }
    }
}
