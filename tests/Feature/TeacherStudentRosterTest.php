<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentRetakeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherStudentRosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_dashboard_lists_real_student_accounts_from_database(): void
    {
        $teacher = User::factory()->teacher()->create();
        $studentA = User::factory()->create([
            'name' => 'Ana Reyes',
            'email' => 'ana@example.com',
        ]);
        $studentB = User::factory()->create([
            'name' => 'Ben Cruz',
            'email' => 'ben@example.com',
        ]);

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));

        $response->assertOk();
        $response->assertSeeText($studentA->name);
        $response->assertSeeText($studentB->name);
        $response->assertSeeText($studentA->email);
        $response->assertSeeText($studentB->email);
    }

    public function test_students_index_lists_real_student_accounts_from_database(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create([
            'name' => 'Carla Santos',
            'email' => 'carla@example.com',
        ]);

        $response = $this->actingAs($teacher)->get(route('students.index'));

        $response->assertOk();
        $response->assertSeeText($student->name);
        $response->assertSeeText($student->email);
    }

    public function test_students_index_shows_online_and_offline_student_statuses(): void
    {
        $teacher = User::factory()->teacher()->create();
        $onlineStudent = User::factory()->create([
            'name' => 'Online Learner',
            'email' => 'online@example.com',
        ]);
        $offlineStudent = User::factory()->create([
            'name' => 'Offline Learner',
            'email' => 'offline@example.com',
        ]);

        DB::table('sessions')->insert([
            'id' => 'active-student-session',
            'user_id' => $onlineStudent->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Feature test',
            'payload' => 'test',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($teacher)->get(route('students.index'));

        $response->assertOk();
        $response->assertSeeText('Online Learner');
        $response->assertSeeText('Offline Learner');
        $response->assertSeeText('Online');
        $response->assertSeeText('Offline');
    }

    public function test_student_profile_displays_clean_avatar_card(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create([
            'name' => 'Lyra Student',
            'email' => 'lyra@example.com',
            'gender' => 'female',
        ]);

        $response = $this->actingAs($teacher)->get(route('students.show', $student));

        $response->assertOk();
        $response->assertSeeText('Pixel Avatar');
        $response->assertSeeText('Lyra Vale');
        $response->assertSeeText('Offline');
        $response->assertSeeText($student->name);
    }

    public function test_student_profile_displays_online_status_for_active_student_session(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create([
            'name' => 'Nova Student',
            'email' => 'nova@example.com',
            'gender' => 'male',
        ]);

        DB::table('sessions')->insert([
            'id' => 'student-session',
            'user_id' => $student->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Feature test',
            'payload' => 'test',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($teacher)->get(route('students.show', $student));

        $response->assertOk();
        $response->assertSeeText('Nova Finch');
        $response->assertSeeText('Online');
        $response->assertDontSeeText('Neon Courier');
    }

    public function test_teacher_student_profile_shows_assessment_retake_requests(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['name' => 'Requesting Learner']);
        $assessment = Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Story Retake Check',
            'subject' => 'literacy',
            'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual',
            'target_section' => 'all',
            'focus_areas' => ['Reading Fluency'],
            'instructions' => 'Read and answer.',
            'status' => 'published',
            'manual_questions' => [],
        ]);

        AssessmentRetakeRequest::query()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'teacher_id' => $teacher->id,
            'status' => 'pending',
            'requested_tries' => 2,
            'message' => 'Please let me try again.',
        ]);

        $response = $this->actingAs($teacher)->get(route('students.show', $student));

        $response->assertOk();
        $response->assertSeeText('Assessment Requests');
        $response->assertSeeText('Story Retake Check');
        $response->assertSeeText('Please let me try again.');
        $response->assertSeeText('Approve');
    }

    public function test_teacher_can_create_a_student_account(): void
    {
        $teacher = User::factory()->teacher()->create([
            'section' => 'Section B',
        ]);

        $response = $this->actingAs($teacher)->post(route('students.store'), [
            'first_name' => 'New',
            'middle_name' => 'Middle',
            'last_name' => 'Learner',
            'email' => 'new.learner@example.com',
            'gender' => 'female',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('students.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'New Middle Learner',
            'email' => 'new.learner@example.com',
            'role' => 'student',
            'section' => 'Section B',
            'gender' => 'female',
            'approval_status' => 'approved',
            'approved_by' => $teacher->id,
        ]);
    }

    public function test_student_can_not_create_student_accounts_from_teacher_roster(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->post(route('students.store'), [
            'first_name' => 'Blocked',
            'last_name' => 'Learner',
            'email' => 'blocked.learner@example.com',
            'gender' => 'male',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'email' => 'blocked.learner@example.com',
        ]);
    }
}
