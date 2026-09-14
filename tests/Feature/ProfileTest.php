<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
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

    public function test_student_profile_shows_leaderboard_rank_effect(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create([
            'name' => 'Miles Lopez',
            'section' => 'Section A',
            'approval_status' => 'approved',
        ]);
        $topStudent = User::factory()->create([
            'name' => 'Lyra Vale',
            'section' => 'Section B',
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
    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'gender' => 'female',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('female', $user->gender);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
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