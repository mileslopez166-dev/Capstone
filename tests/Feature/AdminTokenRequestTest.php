<?php

namespace Tests\Feature;

use App\Models\ProfileChangeRequest;
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
            'section' => 'Magalang',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.token-requests.approve', $teacher), [
            'section' => 'Masunurin',
        ]);

        $response->assertRedirect(route('admin.token-requests.index'));
        $this->assertDatabaseHas('users', [
            'id' => $teacher->id,
            'section' => 'Masunurin',
            'approval_status' => 'approved',
            'approved_by' => $admin->id,
        ]);
    }

    public function test_pending_teacher_request_displays_an_editable_section(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->pendingApproval()->create([
            'section' => 'Magalang',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.token-requests.index'));

        $response->assertOk();
        $response->assertSee('form="approve-request-'.$teacher->id.'"', false);
        $response->assertSee('value="Magalang" selected', false);
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
            'section' => 'Magalang',
        ]);
        User::factory()->teacher()->pendingApproval()->create([
            'name' => 'Noel Reyes',
            'email' => 'noel.teacher@example.com',
            'section' => 'Masunurin',
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
            'section' => 'Matapat',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.token-requests.index'))
            ->assertOk()
            ->assertSee('token-search-suggestions')
            ->assertSee('Lyra Vale')
            ->assertSee('lyra.teacher@example.com')
            ->assertSee('Matapat');
    }

    public function test_admin_can_view_pending_profile_change_requests(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create([
            'name' => 'Current Student',
            'email' => 'current.student@example.com',
            'section' => 'Magalang',
        ]);
        ProfileChangeRequest::query()->create([
            'user_id' => $student->id,
            'current_name' => $student->name,
            'current_email' => $student->email,
            'requested_name' => 'Requested Student',
            'requested_email' => 'requested.student@example.com',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->get(route('admin.token-requests.index'))
            ->assertOk()
            ->assertSeeText('Profile Change Requests')
            ->assertSeeText('Current Student')
            ->assertSeeText('Requested Student')
            ->assertSeeText('requested.student@example.com')
            ->assertSeeText('Magalang');
    }

    public function test_admin_can_approve_a_profile_change_request(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create([
            'name' => 'Current Student',
            'email' => 'current.student@example.com',
        ]);
        $change = ProfileChangeRequest::query()->create([
            'user_id' => $student->id,
            'current_name' => $student->name,
            'current_email' => $student->email,
            'requested_name' => 'Approved Student',
            'requested_email' => 'approved.student@example.com',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.profile-change-requests.approve', $change))
            ->assertRedirect(route('admin.token-requests.index'));

        $student->refresh();
        $this->assertSame('Approved Student', $student->name);
        $this->assertSame('approved.student@example.com', $student->email);
        $this->assertNull($student->email_verified_at);
        $this->assertDatabaseHas('profile_change_requests', [
            'id' => $change->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $student->id,
            'type' => 'profile_change_approved',
        ]);
    }

    public function test_admin_can_decline_a_profile_change_request_without_changing_the_account(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create([
            'name' => 'Current Teacher',
            'email' => 'current.teacher@example.com',
        ]);
        $change = ProfileChangeRequest::query()->create([
            'user_id' => $teacher->id,
            'current_name' => $teacher->name,
            'current_email' => $teacher->email,
            'requested_name' => 'Requested Teacher',
            'requested_email' => 'requested.teacher@example.com',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.profile-change-requests.decline', $change))
            ->assertRedirect(route('admin.token-requests.index'));

        $teacher->refresh();
        $this->assertSame('Current Teacher', $teacher->name);
        $this->assertSame('current.teacher@example.com', $teacher->email);
        $this->assertDatabaseHas('profile_change_requests', [
            'id' => $change->id,
            'status' => 'declined',
            'reviewed_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $teacher->id,
            'type' => 'profile_change_declined',
        ]);
    }

    public function test_non_admin_cannot_review_a_profile_change_request(): void
    {
        $student = User::factory()->create();
        $otherStudent = User::factory()->create();
        $change = ProfileChangeRequest::query()->create([
            'user_id' => $student->id,
            'current_name' => $student->name,
            'current_email' => $student->email,
            'requested_name' => 'Requested Name',
            'requested_email' => 'requested@example.com',
            'status' => 'pending',
        ]);

        $this->actingAs($otherStudent)
            ->post(route('admin.profile-change-requests.approve', $change))
            ->assertForbidden();

        $this->assertSame('pending', $change->fresh()->status);
    }

    public function test_profile_change_cannot_be_approved_when_requested_email_is_taken(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        $otherStudent = User::factory()->create(['email' => 'taken@example.com']);
        $change = ProfileChangeRequest::query()->create([
            'user_id' => $student->id,
            'current_name' => $student->name,
            'current_email' => $student->email,
            'requested_name' => 'Requested Name',
            'requested_email' => $otherStudent->email,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.token-requests.index'))
            ->post(route('admin.profile-change-requests.approve', $change))
            ->assertRedirect(route('admin.token-requests.index'))
            ->assertSessionHasErrors('email');

        $this->assertSame('pending', $change->fresh()->status);
        $this->assertSame($student->email, $student->fresh()->email);
    }
}
