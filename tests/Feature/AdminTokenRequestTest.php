<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTokenRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_pending_account_requests(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->pendingApproval()->create([
            'name' => 'Pending Teacher',
            'email' => 'pending.teacher@example.com',
        ]);
        $student = User::factory()->pendingApproval()->create([
            'name' => 'Pending Student',
            'email' => 'pending.student@example.com',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.token-requests.index'));

        $response->assertOk();
        $response->assertSee('Token Requests');
        $response->assertSee('Pending Teacher');
        $response->assertSee('Pending Student');
        $response->assertSee('Teacher Approval');
        $response->assertSee('Student Approval');
    }

    public function test_admin_can_approve_a_pending_teacher_request(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->pendingApproval()->create();

        $response = $this->actingAs($admin)->post(route('admin.token-requests.approve', $teacher));

        $response->assertRedirect(route('admin.token-requests.index'));
        $this->assertDatabaseHas('users', [
            'id' => $teacher->id,
            'approval_status' => 'approved',
            'approved_by' => $admin->id,
        ]);
    }

    public function test_admin_can_decline_a_pending_teacher_request(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->pendingApproval()->create();

        $response = $this->actingAs($admin)->post(route('admin.token-requests.decline', $teacher));

        $response->assertRedirect(route('admin.token-requests.index'));
        $this->assertDatabaseHas('users', [
            'id' => $teacher->id,
            'approval_status' => 'rejected',
            'approved_by' => $admin->id,
        ]);
    }

    public function test_admin_can_approve_a_pending_student_request(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->pendingApproval()->create();

        $response = $this->actingAs($admin)->post(route('admin.token-requests.approve', $student));

        $response->assertRedirect(route('admin.token-requests.index'));
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'approval_status' => 'approved',
            'approved_by' => $admin->id,
        ]);
    }

    public function test_non_admin_users_can_not_access_the_token_request_page(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->get(route('admin.token-requests.index'))
            ->assertForbidden();
    }
}
