<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampusThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_theme_uses_saved_avatar_and_real_xp(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['gender' => 'female']);
        $assessment = Assessment::query()->create([
            'created_by' => $teacher->id, 'title' => 'Reading Adventure',
            'subject' => 'literacy', 'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual', 'target_section' => 'all',
            'status' => 'published', 'manual_questions' => [],
        ]);

        $this->actingAs($student)->get('/student/dashboard')
            ->assertOk()->assertSee('campus-theme campus-student', false)
            ->assertSee('images/campus/student-girl.png', false)
            ->assertSee('Level 1, 0 total XP', false);

        AssessmentSubmission::query()->create([
            'assessment_id' => $assessment->id, 'user_id' => $student->id,
            'answers' => [], 'correct_count' => 5, 'question_count' => 5,
            'points' => 500, 'possible_points' => 500, 'submitted_at' => now(),
        ]);

        $this->get('/student/activities')->assertOk()
            ->assertSee('Level 2, 500 total XP', false)
            ->assertSee('aria-valuenow="0"', false);
    }

    public function test_teacher_has_distinct_workspace_without_student_characters(): void
    {
        $teacher = User::factory()->teacher()->create();
        foreach (['/teacher/dashboard', '/profile'] as $uri) {
            $this->actingAs($teacher)->get($uri)->assertOk()
                ->assertSee('staff-theme teacher-theme', false)
                ->assertDontSee('campus-theme campus-', false)
                ->assertSee('Teacher navigation')
                ->assertDontSee('images/campus/student-boy.png', false)
                ->assertDontSee('images/campus/student-girl.png', false);
        }
    }

    public function test_admin_profile_keeps_its_existing_layout(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/profile')->assertOk()
            ->assertDontSee('campus-theme campus-', false)
            ->assertDontSee('Choose Your Avatar');
    }
}
