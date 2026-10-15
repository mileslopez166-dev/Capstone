<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\ProfileChangeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_teacher_profile_has_a_back_link_to_teacher_dashboard(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this
            ->actingAs($teacher)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Back to Dashboard')
            ->assertSee(route('teacher.dashboard'), false);
    }

    public function test_student_profile_has_a_back_link_to_student_dashboard(): void
    {
        $student = User::factory()->create();

        $this
            ->actingAs($student)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Back to Dashboard')
            ->assertSee(route('student.dashboard'), false);
    }

    public function test_student_profile_shows_school_and_section_details(): void
    {
        $student = User::factory()->create([
            'section' => 'Matapat',
            'approval_status' => 'approved',
        ]);

        $this->actingAs($student)->get('/profile')->assertOk()
            ->assertSeeText('Student Details')
            ->assertSeeText('Assigned Section')
            ->assertSeeText('Matapat')
            ->assertSeeText('Grade 6')
            ->assertSeeText('Enrollment Status')
            ->assertSeeText('Approved')
            ->assertSeeText('STU-'.str_pad((string) $student->id, 5, '0', STR_PAD_LEFT));
    }

    public function test_student_profile_shows_leaderboard_rank_effect(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create([
            'name' => 'Miles Lopez',
            'section' => 'Magalang',
            'approval_status' => 'approved',
        ]);
        $topStudent = User::factory()->create([
            'name' => 'Lyra Vale',
            'section' => 'Masunurin',
            'approval_status' => 'approved',
        ]);

        $assessment = Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Profile Rank Check',
            'subject' => 'literacy',
            'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual',
            'target_section' => 'all',
            'assessment_type' => 'silent_reading',
            'focus_areas' => ['Reading Fluency'],
            'instructions' => 'Answer carefully.',
            'status' => 'published',
            'manual_questions' => [],
        ]);

        AssessmentSubmission::query()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $topStudent->id,
            'attempt_number' => 1,
            'answers' => [],
            'correct_count' => 2,
            'question_count' => 2,
            'points' => 500,
            'possible_points' => 500,
            'submitted_at' => now(),
        ]);

        AssessmentSubmission::query()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'attempt_number' => 1,
            'answers' => [],
            'correct_count' => 1,
            'question_count' => 2,
            'points' => 250,
            'possible_points' => 500,
            'submitted_at' => now(),
        ]);

        $this->actingAs($student)
            ->get('/profile')
            ->assertOk()
            ->assertSeeText('Diamond')
            ->assertSeeText('#2');
    }
    public function test_student_profile_change_requires_admin_approval(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create([
            'name' => 'Current Student',
            'email' => 'current.student@example.com',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'gender' => $user->gender,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'profile-change-requested')
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Current Student', $user->name);
        $this->assertSame('current.student@example.com', $user->email);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('profile_change_requests', [
            'user_id' => $user->id,
            'current_name' => 'Current Student',
            'current_email' => 'current.student@example.com',
            'requested_name' => 'Test User',
            'requested_email' => 'test@example.com',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $admin->id,
            'type' => 'profile_change_requested',
        ]);
    }

    public function test_teacher_profile_change_requires_admin_approval(): void
    {
        User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create([
            'name' => 'Current Teacher',
            'email' => 'current.teacher@example.com',
        ]);

        $this
            ->actingAs($teacher)
            ->patch('/profile', [
                'name' => 'Updated Teacher',
                'email' => 'updated.teacher@example.com',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'profile-change-requested');

        $this->assertSame('Current Teacher', $teacher->fresh()->name);
        $this->assertDatabaseHas('profile_change_requests', [
            'user_id' => $teacher->id,
            'requested_name' => 'Updated Teacher',
            'requested_email' => 'updated.teacher@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_admin_profile_information_can_be_updated_immediately(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch('/profile', [
            'name' => 'Updated Admin',
            'email' => 'updated.admin@example.com',
        ])->assertSessionHasNoErrors()->assertSessionHas('status', 'profile-updated');

        $admin->refresh();
        $this->assertSame('Updated Admin', $admin->name);
        $this->assertSame('updated.admin@example.com', $admin->email);
        $this->assertNull($admin->email_verified_at);
        $this->assertDatabaseCount('profile_change_requests', 0);
    }

    public function test_admin_email_verification_status_is_unchanged_when_email_is_unchanged(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch('/profile', [
            'name' => 'Test Admin',
            'email' => $admin->email,
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($admin->refresh()->email_verified_at);
    }

    public function test_student_can_cancel_a_pending_profile_change_by_submitting_current_information(): void
    {
        $student = User::factory()->create([
            'name' => 'Current Student',
            'email' => 'current@example.com',
        ]);
        $change = ProfileChangeRequest::query()->create([
            'user_id' => $student->id,
            'current_name' => $student->name,
            'current_email' => $student->email,
            'requested_name' => 'Requested Student',
            'requested_email' => 'requested@example.com',
            'status' => 'pending',
        ]);

        $this->actingAs($student)->patch('/profile', [
            'name' => $student->name,
            'email' => $student->email,
        ])->assertSessionHasNoErrors()->assertSessionHas('status', 'profile-change-cancelled');

        $this->assertSame('cancelled', $change->fresh()->status);
    }

    public function test_profile_page_shows_pending_change_details(): void
    {
        $student = User::factory()->create();
        ProfileChangeRequest::query()->create([
            'user_id' => $student->id,
            'current_name' => $student->name,
            'current_email' => $student->email,
            'requested_name' => 'Pending Name',
            'requested_email' => 'pending.name@example.com',
            'status' => 'pending',
        ]);

        $this->actingAs($student)->get('/profile')
            ->assertOk()
            ->assertSeeText('Pending administrator approval')
            ->assertSeeText('Pending Name')
            ->assertSeeText('pending.name@example.com')
            ->assertSeeText('Update Request');
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
