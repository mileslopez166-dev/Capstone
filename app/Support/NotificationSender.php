<?php

namespace App\Support;

use App\Models\AppNotification;
use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\User;
use App\Notifications\PhonePushNotification;
use Illuminate\Support\Collection;

class NotificationSender
{
    public static function sendToUsers(iterable $users, string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        collect($users)
            ->filter(fn ($user): bool => $user instanceof User && $user->exists)
            ->unique('id')
            ->each(function (User $user) use ($type, $title, $body, $url): void {
                AppNotification::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'title' => $title,
                    'body' => $body,
                    'url' => $url,
                ]);

                if (in_array($type, ['registration', 'assessment_created', 'assessment_published'], true)
                    && PhonePushNotification::isConfigured()) {
                    $user->notify(new PhonePushNotification($type, $title, $body, $url));
                }
            });
    }

    public static function notifyAdminsAboutRegistration(User $registeredUser): void
    {
        self::sendToUsers(
            User::query()->where('role', 'admin')->where('approval_status', 'approved')->get(),
            'registration',
            'New account registration',
            sprintf('%s registered as %s and is waiting for review.', $registeredUser->name, ucfirst($registeredUser->role)),
            route('admin.token-requests.index')
        );
    }

    public static function notifyStudentEnrolled(User $student, ?User $creator = null): void
    {
        if (! $student->isStudent() || blank($student->section)) {
            return;
        }

        $teachers = User::query()
            ->where('role', 'teacher')
            ->where('approval_status', 'approved')
            ->where('section', $student->section)
            ->get();

        self::sendToUsers(
            $teachers,
            'student_enrolled',
            'New student enrolled',
            sprintf('%s joined %s.', $student->name, $student->section),
            route('students.index')
        );

        $classmates = User::query()
            ->where('role', 'student')
            ->where('approval_status', 'approved')
            ->where('section', $student->section)
            ->whereKeyNot($student->id)
            ->get();

        self::sendToUsers(
            $classmates,
            'classmate_joined',
            'New classmate in your section',
            sprintf('%s joined your %s class.', $student->name, $student->section),
            route('student.dashboard')
        );
    }

    public static function notifyAssessmentPublished(Assessment $assessment): void
    {
        self::sendToUsers(
            self::studentsForAssessment($assessment),
            'assessment_published',
            'New teacher assessment',
            sprintf('%s is now available.', $assessment->title),
            route('student.assessments.show', $assessment)
        );
    }

    public static function notifyAdminsAboutAssessmentCreated(Assessment $assessment): void
    {
        $teacherName = $assessment->teacher?->name ?? 'A teacher';

        self::sendToUsers(
            User::query()->where('role', 'admin')->where('approval_status', 'approved')->get(),
            'assessment_created',
            'New assessment created',
            sprintf('%s created %s.', $teacherName, $assessment->title),
            route('admin.dashboard')
        );
    }

    public static function notifyAssessmentCompleted(AssessmentSubmission $submission): void
    {
        $assessment = $submission->assessment;
        $teacher = $assessment?->teacher;
        $student = $submission->student;

        if (! $assessment || ! $teacher || ! $student) {
            return;
        }

        self::sendToUsers(
            [$teacher],
            'assessment_completed',
            'Assessment completed',
            sprintf('%s finished %s.', $student->name, $assessment->title),
            route('students.show', $student)
        );
    }

    private static function studentsForAssessment(Assessment $assessment): Collection
    {
        return User::query()
            ->where('role', 'student')
            ->where('approval_status', 'approved')
            ->when(
                ! in_array($assessment->target_section, ['all', null], true),
                fn ($query) => $query->where('section', self::sectionLabelFromTarget($assessment->target_section))
            )
            ->get();
    }

    private static function sectionLabelFromTarget(?string $targetSection): ?string
    {
        return SectionCatalog::labelForTarget($targetSection);
    }
}
