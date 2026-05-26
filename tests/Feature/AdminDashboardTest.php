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
}
