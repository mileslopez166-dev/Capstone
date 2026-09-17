<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherWorkspaceAppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_workspace_pages_share_the_teacher_shell(): void
    {
        $teacher = User::factory()->teacher()->create();
        foreach (['teacher.dashboard', 'students.index', 'assessments.index', 'reports.index', 'teacher.practice.index', 'teacher.search', 'profile.edit', 'support.developing'] as $route) {
            $this->actingAs($teacher)->get(route($route))->assertOk()
                ->assertSee('staff-theme teacher-theme', false)
                ->assertSee('Teacher navigation')
                ->assertSee('Sound and motion settings');
        }
    }

    public function test_shared_support_and_profile_pages_keep_other_roles_separate(): void
    {
        foreach ([User::factory()->create(), User::factory()->admin()->create()] as $user) {
            foreach (['profile.edit', 'support.developing'] as $route) {
                $this->actingAs($user)->get(route($route))->assertOk()
                    ->assertDontSee('staff-theme teacher-theme', false)
                    ->assertDontSee('Teacher navigation');
            }
        }
    }
}
