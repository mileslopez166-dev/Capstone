<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Assessment;
use App\Models\User;
use App\Notifications\PhonePushNotification;
use App\Support\NotificationSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AppNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_is_notified_when_someone_registers(): void
    {
        $admin = User::factory()->admin()->create([
            'approval_status' => 'approved',
        ]);

        $this->post(route('register'), [
            'first_name' => 'Mila',
            'middle_name' => 'Reyes',
            'last_name' => 'Santos',
            'email' => 'mila@example.com',
            'role' => 'teacher',
            'section' => 'Magalang',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas(AppNotification::class, [
            'user_id' => $admin->id,
            'type' => 'registration',
            'title' => 'New account registration',
            'url' => route('admin.token-requests.index'),
        ]);
    }

    public function test_configured_phone_alert_is_queued_for_a_new_registration(): void
    {
        Notification::fake();
        config()->set('webpush.vapid.public_key', 'test-public-key');
        config()->set('webpush.vapid.private_key', 'test-private-key');

        $admin = User::factory()->admin()->create(['approval_status' => 'approved']);
        $registeredUser = User::factory()->teacher()->create(['approval_status' => 'pending']);

        NotificationSender::notifyAdminsAboutRegistration($registeredUser);

        Notification::assertSentTo(
            $admin,
            PhonePushNotification::class,
        );
    }

    public function test_teacher_is_notified_when_student_finishes_assessment(): void
    {
        $teacher = User::factory()->teacher()->create([
            'section' => 'Magalang',
            'approval_status' => 'approved',
        ]);
        $student = User::factory()->create([
            'section' => 'Magalang',
            'approval_status' => 'approved',
        ]);

        $assessment = Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Reading Check',
            'subject' => 'literacy',
            'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual',
            'target_section' => 'section_a',
            'assessment_type' => 'silent_reading',
            'focus_areas' => ['Reading Fluency'],
            'instructions' => 'Read and answer.',
            'status' => 'published',
            'manual_questions' => [[
                'question' => 'What letter comes first?',
                'answers' => ['A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D'],
                'correct_answer' => 'A',
            ]],
        ]);

        $this->actingAs($student)
            ->postJson(route('student.assessments.submit', $assessment), [
                'answers' => [0 => 'A'],
            ])
            ->assertOk();

        $this->assertDatabaseHas(AppNotification::class, [
            'user_id' => $teacher->id,
            'type' => 'assessment_completed',
            'title' => 'Assessment completed',
            'url' => route('students.show', $student),
        ]);
    }

    public function test_students_are_notified_when_teacher_publishes_assessment(): void
    {
        $teacher = User::factory()->teacher()->create([
            'section' => 'Magalang',
            'approval_status' => 'approved',
        ]);
        $student = User::factory()->create([
            'section' => 'Magalang',
            'approval_status' => 'approved',
        ]);
        $otherSectionStudent = User::factory()->create([
            'section' => 'Masunurin',
            'approval_status' => 'approved',
        ]);

        $assessment = Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Story Mission',
            'subject' => 'literacy',
            'quiz_type' => 'flashcards',
            'delivery_method' => 'manual',
            'target_section' => 'section_a',
            'assessment_type' => 'listening_comprehension',
            'focus_areas' => ['Comprehension Depth'],
            'instructions' => 'Listen and play.',
            'status' => 'draft',
            'manual_questions' => [[
                'question' => 'Who is the hero?',
                'answers' => ['A' => 'Nova', 'B' => 'Lyra', 'C' => 'Finn', 'D' => 'Moss'],
                'correct_answer' => 'A',
            ]],
        ]);

        $this->actingAs($teacher)
            ->patch(route('assessments.availability', $assessment), [
                'status' => 'published',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas(AppNotification::class, [
            'user_id' => $student->id,
            'type' => 'assessment_published',
            'title' => 'New teacher assessment',
            'url' => route('student.assessments.show', $assessment),
        ]);
        $this->assertDatabaseMissing(AppNotification::class, [
            'user_id' => $otherSectionStudent->id,
            'type' => 'assessment_published',
        ]);
    }

    public function test_admin_is_notified_when_teacher_creates_assessment(): void
    {
        $admin = User::factory()->admin()->create(['approval_status' => 'approved']);
        $teacher = User::factory()->teacher()->create([
            'section' => 'Magalang',
            'approval_status' => 'approved',
        ]);

        $this->actingAs($teacher)
            ->post(route('assessments.store'), [
                'title' => 'Admin Alert Reading Check',
                'subject' => 'literacy',
                'quiz_type' => 'multiple_choice',
                'question_selection' => 'fixed',
                'delivery_method' => 'manual',
                'target_section' => 'section_a',
                'assessment_type' => 'silent_reading',
                'focus_areas' => ['Reading Fluency'],
                'manual_questions' => [[
                    'question' => 'Which answer is correct?',
                    'answers' => ['A' => 'One', 'B' => 'Two', 'C' => 'Three', 'D' => 'Four'],
                    'correct_answer' => 'A',
                ]],
                'status' => 'draft',
                'retry_limit' => '0',
                'question_time_minutes' => 2,
                'question_time_seconds' => 0,
            ])
            ->assertRedirect(route('assessments.index'));

        $this->assertDatabaseHas(AppNotification::class, [
            'user_id' => $admin->id,
            'type' => 'assessment_created',
            'title' => 'New assessment created',
        ]);
    }

    public function test_teacher_and_classmates_are_notified_when_student_is_enrolled(): void
    {
        $teacher = User::factory()->teacher()->create([
            'section' => 'Magalang',
            'approval_status' => 'approved',
        ]);
        $classmate = User::factory()->create([
            'section' => 'Magalang',
            'approval_status' => 'approved',
        ]);

        $this->actingAs($teacher)
            ->post(route('students.store'), [
                'first_name' => 'Miles',
                'middle_name' => 'Aidirian',
                'last_name' => 'Lopez',
                'email' => 'miles.student@example.com',
                'section' => 'Magalang',
                'gender' => 'male',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('students.index'));

        $this->assertDatabaseHas(AppNotification::class, [
            'user_id' => $teacher->id,
            'type' => 'student_enrolled',
            'title' => 'New student enrolled',
        ]);
        $this->assertDatabaseHas(AppNotification::class, [
            'user_id' => $classmate->id,
            'type' => 'classmate_joined',
            'title' => 'New classmate in your section',
        ]);
    }

    public function test_user_can_mark_notifications_as_read(): void
    {
        $student = User::factory()->create([
            'approval_status' => 'approved',
        ]);

        AppNotification::query()->create([
            'user_id' => $student->id,
            'type' => 'sample',
            'title' => 'Sample notification',
        ]);

        $this->actingAs($student)
            ->post(route('notifications.mark-read'))
            ->assertRedirect();

        $this->assertDatabaseMissing(AppNotification::class, [
            'user_id' => $student->id,
            'read_at' => null,
        ]);
    }
}
