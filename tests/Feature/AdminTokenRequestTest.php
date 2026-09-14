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
        $teacher = User::factory()->teacher()->pendingApproval()->create([
            'section' => 'Section A',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.token-requests.approve', $teacher), [
            'section' => 'Section B',
        ]);

        $response->assertRedirect(route('admin.token-requests.index'));
        $this->assertDatabaseHas('users', [
            'id' => $teacher->id,
            'section' => 'Section B',
            'approval_status' => 'approved',
            'approved_by' => $admin->id,
        ]);
    }

    public function test_pending_teacher_request_displays_an_editable_section(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->pendingApproval()->create([
            'section' => 'Section A',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.token-requests.index'));

        $response->assertOk();
        $response->assertSee('form="approve-request-'.$teacher->id.'"', false);
        $response->assertSee('value="Section A" selected', false);
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
    public function test_admin_can_search_pending_token_requests(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->teacher()->pendingApproval()->create([
            'name' => 'Mila Santos',
            'email' => 'mila.teacher@example.com',
            'section' => 'Section A',
        ]);
        User::factory()->teacher()->pendingApproval()->create([
            'name' => 'Noel Reyes',
            'email' => 'noel.teacher@example.com',
            'section' => 'Section B',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.token-requests.index', ['search' => 'mila']));

        $response
            ->assertOk()
            ->assertSee('Mila Santos')
            ->assertDontSee('<p class="font-medium text-on-surface">Noel Reyes</p>', false)
            ->assertSee('Showing matches for "mila"', false);
    }
    public function test_token_request_search_shows_possible_options(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->teacher()->pendingApproval()->create([
            'name' => 'Lyra Vale',
            'email' => 'lyra.teacher@example.com',
            'section' => 'Section C',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.token-requests.index'))
            ->assertOk()
            ->assertSee('token-search-suggestions')
            ->assertSee('Lyra Vale')
            ->assertSee('lyra.teacher@example.com')
            ->assertSee('Section C');
    }
}
