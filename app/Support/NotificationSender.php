<?php

namespace App\Support;

use App\Models\AppNotification;
use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationSender
{
    public static function sendToUsers(iterable $users, string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        collect($users)
            ->filter(fn ($user): bool => $user instanceof User && $user->exists)
            ->unique('id')
            ->each(fn (User $user): AppNotification => AppNotification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'url' => $url,
            ]));
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
        return match ($targetSection) {
            'section_a' => 'Section A',
            'section_b' => 'Section B',
            'section_c' => 'Section C',
            default => null,
        };
    }
}