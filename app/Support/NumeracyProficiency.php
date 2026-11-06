<?php

namespace App\Support;

use App\Models\AssessmentSubmission;

class NumeracyProficiency
{
    public const BANDS = [
        'Beginning' => [0, 59],
        'Developing' => [60, 74],
        'Proficient' => [75, 89],
        'Advanced' => [90, 100],
    ];

    public static function forSubmission(AssessmentSubmission $submission): ?string
    {
        return self::forPercentage(AssessmentScores::percentage($submission));
    }

    public static function forPercentage(?int $percentage): ?string
    {
        if ($percentage === null || $percentage < 0 || $percentage > 100) {
            return null;
        }

        return match (true) {
            $percentage <= 59 => 'Beginning',
            $percentage <= 74 => 'Developing',
            $percentage <= 89 => 'Proficient',
            default => 'Advanced',
        };
    }

    public static function evidence(): array
    {
        return [
            'source' => 'Project-defined completed assessment score bands',
            'bands' => self::BANDS,
            'limitation' => 'The target is derived from the official assessment percentage; model metrics measure reproduction of these operational bands, not independent learning gains.',
        ];
    }
}
