<?php

namespace App\Support;

use App\Models\AssessmentSubmission;

class AssessmentScores
{
    public static function percentage(AssessmentSubmission $submission): ?int
    {
        $result = PhilIri::forSubmission($submission);
        $assessmentType = $result['assessment_type'] ?? $submission->assessment?->assessment_type;

        if ($assessmentType === 'oral_reading') {
            if (($result['word_reading_percent'] ?? null) === null) {
                return null;
            }

            return (int) round($result['word_reading_percent']);
        }

        if ((int) $submission->question_count > 0) {
            return (int) round($submission->correct_count * 100 / $submission->question_count);
        }

        if (($result['comprehension_percent'] ?? null) !== null) {
            return (int) round($result['comprehension_percent']);
        }

        return null;
    }

    public static function average(iterable $submissions): ?int
    {
        $count = 0;
        $total = 0;

        foreach ($submissions as $submission) {
            $percentage = self::percentage($submission);

            if ($percentage === null) {
                continue;
            }

            $total += $percentage;
            $count++;
        }

        return $count > 0 ? (int) round($total / $count) : null;
    }
}
