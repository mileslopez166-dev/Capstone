<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentRetakeRequest;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherStudentRosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_dashboard_keeps_summaries_without_the_student_roster(): void
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
        $response->assertDontSeeText('Student Roster');
        $response->assertDontSeeText('Open roster');
        $response->assertDontSee('class="teacher-roster"', false);
        $response->assertDontSeeText($studentA->email);
        $response->assertDontSeeText($studentB->email);
        $response->assertSeeText('2 student accounts');
        $response->assertSeeText('Student Performance Overview');
        $response->assertSeeText('Subject Progress');
        $response->assertSee('href="'.route('students.index').'"', false);
    }

    public function test_teacher_dashboard_recent_finished_assessments_are_clickable(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['name' => 'Miles Lopez']);
        $assessment = Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Finished Reading Check',
            'subject' => 'literacy',
            'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual',
            'target_section' => 'all',
            'assessment_type' => 'silent_reading',
            'focus_areas' => ['Comprehension Depth'],
            'manual_questions' => [],
            'status' => 'published',
        ]);
        $submission = AssessmentSubmission::query()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'attempt_number' => 1,
            'answers' => [],
            'correct_count' => 0,
            'question_count' => 0,
            'points' => 0,
            'possible_points' => 0,
            'submitted_at' => now(),
        ]);

        $this->actingAs($teacher)->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSeeText('Finished Reading Check')
            ->assertSee('href="'.route('teacher.phil-iri.show', $submission).'"', false);
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

    public function test_teacher_roster_includes_all_sections_and_supports_section_filters(): void
    {
        $teacher = User::factory()->teacher()->create(['section' => 'Magalang']);
        $magalang = User::factory()->create(['name' => 'Magalang Learner', 'section' => 'Magalang']);
        $masunurin = User::factory()->create(['name' => 'Masunurin Learner', 'section' => 'Masunurin']);
        $matapat = User::factory()->create(['name' => 'Matapat Learner', 'section' => 'Matapat']);
        $masipag = User::factory()->create(['name' => 'Masipag Learner', 'section' => 'Masipag']);

        $allSections = $this->actingAs($teacher)->get(route('students.index'));

        $allSections->assertOk()
            ->assertSeeText('All Sections')
            ->assertSeeText('Magalang')
            ->assertSeeText('Masunurin')
            ->assertSeeText('Matapat')
            ->assertSeeText('Masipag')
            ->assertSeeText($magalang->name)
            ->assertSeeText($masunurin->name)
            ->assertSeeText($matapat->name)
            ->assertSeeText($masipag->name);

        $allSections->assertViewHas('students', fn ($students): bool => $students->pluck('id')->sort()->values()->all() === collect([
            $magalang->id, $masunurin->id, $matapat->id, $masipag->id,
        ])->sort()->values()->all());

        $this->actingAs($teacher)->get(route('students.index', ['section' => 'Matapat']))
            ->assertOk()
            ->assertViewHas('selectedSection', 'Matapat')
            ->assertViewHas('students', fn ($students): bool => $students->count() === 1 && $students->first()->is($matapat));
    }

    public function test_teacher_sees_only_pending_enrollment_requests_for_their_section(): void
    {
        $teacher = User::factory()->teacher()->create(['section' => 'Magalang']);
        $matchingStudent = User::factory()->pendingApproval()->create([
            'name' => 'Matching Request',
            'section' => 'Magalang',
        ]);
        $otherStudent = User::factory()->pendingApproval()->create([
            'name' => 'Other Section Request',
            'section' => 'Masunurin',
        ]);

        $this->actingAs($teacher)->get(route('students.index'))
            ->assertOk()
            ->assertSeeText('Enrollment Requests')
            ->assertSeeText($matchingStudent->name)
            ->assertSee(route('students.enrollment.approve', $matchingStudent))
            ->assertSee(route('students.enrollment.decline', $matchingStudent))
            ->assertDontSeeText($otherStudent->name);
    }

    public function test_teacher_can_accept_a_pending_student_into_their_section(): void
    {
        $teacher = User::factory()->teacher()->create(['section' => 'Matapat']);
        $student = User::factory()->pendingApproval()->create([
            'name' => 'Pending Matapat Learner',
            'section' => 'Matapat',
        ]);

        $this->actingAs($teacher)
            ->post(route('students.enrollment.approve', $student))
            ->assertRedirect(route('students.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'section' => 'Matapat',
            'approval_status' => 'approved',
            'approved_by' => $teacher->id,
        ]);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $student->id,
            'type' => 'enrollment_approved',
        ]);
    }

    public function test_teacher_can_decline_a_pending_student_from_their_section(): void
    {
        $teacher = User::factory()->teacher()->create(['section' => 'Matapat']);
        $student = User::factory()->pendingApproval()->create([
            'name' => 'Pending Matapat Learner',
            'section' => 'Matapat',
        ]);

        $this->actingAs($teacher)
            ->post(route('students.enrollment.decline', $student))
            ->assertRedirect(route('students.index'))
            ->assertSessionHas('status', "{$student->name}'s enrollment request has been declined.");

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'section' => 'Matapat',
            'approval_status' => 'rejected',
            'approved_at' => null,
            'approved_by' => $teacher->id,
        ]);
    }

    public function test_teacher_cannot_decline_a_student_from_another_section(): void
    {
        $teacher = User::factory()->teacher()->create(['section' => 'Masipag']);
        $student = User::factory()->pendingApproval()->create(['section' => 'Magalang']);

        $this->actingAs($teacher)
            ->post(route('students.enrollment.decline', $student))
            ->assertNotFound();

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'approval_status' => 'pending',
            'approved_by' => null,
        ]);
    }

    public function test_teacher_cannot_accept_a_student_from_another_section(): void
    {
        $teacher = User::factory()->teacher()->create(['section' => 'Masipag']);
        $student = User::factory()->pendingApproval()->create(['section' => 'Magalang']);

        $this->actingAs($teacher)
            ->post(route('students.enrollment.approve', $student))
            ->assertNotFound();

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'approval_status' => 'pending',
            'approved_by' => null,
        ]);
    }

    public function test_non_teacher_cannot_accept_an_enrollment_request(): void
    {
        $student = User::factory()->pendingApproval()->create(['section' => 'Magalang']);
        $otherStudent = User::factory()->create(['section' => 'Magalang']);

        $this->actingAs($otherStudent)
            ->post(route('students.enrollment.approve', $student))
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'approval_status' => 'pending',
        ]);
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
        $response->assertSeeText('Your Avatar');
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
            'section' => 'Masunurin',
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
            'section' => 'Masunurin',
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
