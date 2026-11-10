<?php

namespace App\Support;

use App\Models\AssessmentSubmission;

class TeacherProficiencySummary
{
    public static function fromSubmissions(iterable $submissions): array
    {
        $counts = [
            'literacy' => array_fill_keys(['Frustration', 'Instructional', 'Independent'], 0),
            'numeracy' => array_fill_keys(array_keys(NumeracyProficiency::BANDS), 0),
        ];

        // Count each learner once per subject, using the latest submitted result.
        $latest = collect($submissions)
            ->filter(fn (AssessmentSubmission $submission): bool => $submission->submitted_at !== null)
            ->sortBy([['submitted_at', 'desc'], ['id', 'desc']])
            ->unique(fn (AssessmentSubmission $submission): string => $submission->user_id.':'.$submission->assessment?->subject);

        foreach ($latest as $submission) {
            $subject = $submission->assessment?->subject;
            $level = match ($subject) {
                'literacy' => PhilIri::forSubmission($submission)['level'] ?? null,
                'numeracy' => NumeracyProficiency::forSubmission($submission),
                default => null,
            };
            if ($level !== null && isset($counts[$subject][$level])) {
                $counts[$subject][$level]++;
            }
        }

        return $counts;
    }
}
