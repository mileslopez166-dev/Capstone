<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_displays_live_metrics(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'System Administrator',
            'email' => 'admin@example.com',
        ]);

        User::factory()->teacher()->create();
        User::factory()->count(2)->create();

        Assessment::query()->create([
            'created_by' => $admin->id,
            'status' => 'published',
            'subject' => 'literacy',
            'title' => 'Reading Checkpoint',
        ]);

        DB::table('sessions')->insert([
            'id' => 'session-admin-1',
            'user_id' => $admin->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Admin Overview');
        $response->assertSee('Verified Accounts');
        $response->assertSee('Active Sessions');
        $response->assertSee('Published Assessments');
        $response->assertSee('System Administrator');
        $response->assertSee('4');
    }

    public function test_non_admin_users_can_not_access_the_admin_dashboard(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_user_from_user_management(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'first_name' => 'Created',
            'middle_name' => 'Middle',
            'last_name' => 'Teacher',
            'email' => 'created.teacher@example.com',
            'role' => 'teacher',
            'section' => 'Section A',
            'approval_status' => 'approved',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard').'#user-management');
        $this->assertDatabaseHas('users', [
            'name' => 'Created Middle Teacher',
            'email' => 'created.teacher@example.com',
            'role' => 'teacher',
            'section' => 'Section A',
            'approval_status' => 'approved',
            'approved_by' => $admin->id,
        ]);
    }

    public function test_admin_can_create_a_student_with_avatar_style(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'first_name' => 'Created',
            'middle_name' => '',
            'last_name' => 'Student',
            'email' => 'created.student@example.com',
            'role' => 'student',
            'section' => 'Section A',
            'gender' => 'male',
            'approval_status' => 'approved',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard').'#user-management');
        $this->assertDatabaseHas('users', [
            'name' => 'Created Student',
            'email' => 'created.student@example.com',
            'role' => 'student',
            'gender' => 'male',
            'approval_status' => 'approved',
        ]);
    }

    public function test_admin_can_empty_trash_without_hitting_user_delete_route(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create([
            'email' => 'trashed.teacher@example.com',
        ]);
        $student = User::factory()->create([
            'email' => 'trashed.student@example.com',
        ]);

        $teacher->delete();
        $student->delete();

        $response = $this->actingAs($admin)->delete(route('admin.users.trash.empty'));

        $response->assertRedirect(route('admin.users.trash'));
        $response->assertSessionHas('status', 'All trashed users have been permanently deleted.');
        $this->assertDatabaseMissing('users', ['email' => 'trashed.teacher@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'trashed.student@example.com']);
    }

    public function test_system_administrator_is_marked_as_fixed_on_user_management_dashboard(): void
    {
        config(['auth.bootstrap_admin.email' => 'admin@aipgaals.com']);

        $admin = User::factory()->admin()->create();
        $this->systemAdministratorAccount([
            'name' => 'System Administrator',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Fixed System User');
    }

    public function test_system_administrator_profile_can_not_be_updated(): void
    {
        config(['auth.bootstrap_admin.email' => 'admin@aipgaals.com']);

        $admin = User::factory()->admin()->create();
        $systemAdmin = $this->systemAdministratorAccount([
            'name' => 'System Administrator',
            'section' => 'Admin-Office',
            'approval_status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $systemAdmin), [
                'name' => 'Changed Administrator',
                'role' => 'student',
                'section' => 'Section A',
                'approval_status' => 'pending',
            ])
            ->assertRedirect(route('admin.dashboard').'#user-management');

        $this->assertDatabaseHas('users', [
            'id' => $systemAdmin->id,
            'name' => 'System Administrator',
            'role' => 'admin',
            'section' => 'Admin-Office',
            'approval_status' => 'approved',
        ]);
    }

    public function test_system_administrator_can_not_be_deleted(): void
    {
        config(['auth.bootstrap_admin.email' => 'admin@aipgaals.com']);

        $admin = User::factory()->admin()->create();
        $systemAdmin = $this->systemAdministratorAccount();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $systemAdmin))
            ->assertRedirect(route('admin.dashboard').'#user-management');

        $this->assertFalse($systemAdmin->fresh()->trashed());
    }

    private function systemAdministratorAccount(array $attributes = []): User
    {
        $systemAdmin = User::withTrashed()->where('email', 'admin@aipgaals.com')->first();

        if (! $systemAdmin) {
            return User::factory()->admin()->create([
                'name' => 'System Administrator',
                'email' => 'admin@aipgaals.com',
                ...$attributes,
            ]);
        }

        if ($systemAdmin->trashed()) {
            $systemAdmin->restore();
        }

        $systemAdmin->forceFill([
            'role' => 'admin',
            ...$attributes,
        ])->save();

        return $systemAdmin;
    }
    public function test_admin_search_routes_role_queries_to_user_management(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.search', ['q' => 'student']))
            ->assertRedirect(route('admin.dashboard', ['role' => 'student']).'#user-management');
    }

    public function test_admin_search_routes_token_queries_to_token_requests(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.search', ['q' => 'token request']))
            ->assertRedirect(route('admin.token-requests.index', ['search' => 'token request']));
    }
    public function test_admin_dashboard_search_shows_possible_options(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create([
            'name' => 'Miles Lopez',
            'email' => 'miles.student@example.com',
            'role' => 'student',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('admin-search-suggestions')
            ->assertSee('Token Requests')
            ->assertSee('Miles Lopez')
            ->assertSee('miles.student@example.com');
    }
}
