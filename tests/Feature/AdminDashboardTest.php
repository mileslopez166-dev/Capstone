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
}
