<?php

namespace App\Support;

use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Support\Collection;

class StudentLeaderboard
{
    public static function entries(?string $section = null): Collection
    {
        $students = User::query()
            ->where('role', 'student')
            ->where('approval_status', 'approved')
            ->when($section !== null, fn ($query) => $query->where('section', $section))
            ->orderBy('name')
            ->get();

        $submissions = AssessmentSubmission::query()
            ->with('assessment')
            ->whereIn('user_id', $students->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('user_id');

        return $students
            ->map(function (User $student) use ($submissions): array {
                // Keep one best attempt per activity; tied retries retain the earlier result.
                $bestAttempts = $submissions->get($student->id, collect())
                    ->sortByDesc('points')
                    ->unique('assessment_id');

                return [
                    'student' => $student,
                    'points' => (int) $bestAttempts->sum('points'),
                    'completed' => $bestAttempts->count(),
                    'accuracy' => AssessmentScores::average($bestAttempts),
                ];
            })
            ->filter(fn (array $entry): bool => $entry['completed'] > 0)
            ->sortBy([
                ['points', 'desc'],
                ['accuracy', 'desc'],
                ['completed', 'desc'],
                fn (array $entry): string => $entry['student']->name,
            ])
            ->values()
            ->map(function (array $entry, int $index): array {
                $entry['rank'] = $index + 1;

                return $entry;
            });
    }
}
