<?php

namespace App\Support;

use App\Models\AssessmentSubmission;
use App\Models\PracticeCoinTransaction;
use Illuminate\Support\Str;

class AssessmentCoinRewards
{
    public static function percentage(AssessmentSubmission $submission): ?int
    {
        return AssessmentScores::percentage($submission);
    }

    // Called inside the grading transaction, with the student's wallet locked.
    public static function award(AssessmentSubmission $submission): ?PracticeCoinTransaction
    {
        $percentage = self::percentage($submission);
        if ($percentage === null) return null;

        $amount = $percentage <= 75 ? 10 : ($percentage <= 90 ? 20 : 35);

        return $submission->coinReward()->firstOrCreate([], [
            'user_id' => $submission->user_id,
            'amount' => $amount,
            'description' => Str::limit('Assessment reward ('.$percentage.'%): '.$submission->assessment?->title, 255, ''),
        ]);
    }
}
