<?php

namespace App\Support;

use App\Models\AssessmentSubmission;
use Illuminate\Support\Collection;

class StudentProgress
{
    public static function literacy(iterable $submissions): array
    {
        $history = collect($submissions)
            ->filter(fn (AssessmentSubmission $submission): bool => ($submission->assessment?->subject ?? null) === 'literacy')
            ->map(function (AssessmentSubmission $submission): ?array {
                $score = AssessmentScores::percentage($submission);
                if ($score === null) {
                    return null;
                }

                $result = PhilIri::forSubmission($submission);

                return [
                    'id' => $submission->id,
                    'title' => $submission->assessment?->title ?? 'Assessment',
                    'score' => $score,
                    'level' => $result['level'] ?? null,
                    'submitted_at' => $submission->submitted_at,
                    'attempt_number' => $submission->attempt_number,
                ];
            })
            ->filter()
            ->sortBy(fn (array $row): int => (($row['submitted_at']?->getTimestamp() ?? 0) * 1000000) + (int) $row['id'])
            ->values();

        if ($history->isEmpty()) {
            return [
                'has_data' => false,
                'has_comparison' => false,
                'trend' => 'none',
                'tone' => 'empty',
                'status_label' => 'No reading data yet',
                'headline' => 'Complete a literacy assessment first',
                'summary' => 'Reading progress will appear after the student finishes a scored literacy assessment.',
                'latest_score' => null,
                'previous_score' => null,
                'best_score' => null,
                'change' => null,
                'change_label' => 'No history',
                'level_transition' => null,
                'series' => [],
            ];
        }

        $latest = $history->last();
        $previous = $history->count() > 1 ? $history->slice(-2, 1)->first() : null;
        $best = $history->reduce(function (?array $best, array $row): array {
            if (! $best || $row['score'] > $best['score']) {
                return $row;
            }

            if ($row['score'] === $best['score'] && (($row['submitted_at']?->getTimestamp() ?? 0) > ($best['submitted_at']?->getTimestamp() ?? 0))) {
                return $row;
            }

            return $best;
        });
        $change = $previous ? $latest['score'] - $previous['score'] : null;
        $trend = self::trend($change);

        return [
            'has_data' => true,
            'has_comparison' => $previous !== null,
            'trend' => $trend,
            'tone' => self::tone($trend),
            'status_label' => self::statusLabel($trend),
            'headline' => self::headline($latest, $previous, $change),
            'summary' => self::summary($latest, $previous, $change),
            'latest_score' => $latest['score'],
            'latest_title' => $latest['title'],
            'latest_level' => $latest['level'],
            'latest_date' => $latest['submitted_at'],
            'previous_score' => $previous['score'] ?? null,
            'previous_title' => $previous['title'] ?? null,
            'previous_level' => $previous['level'] ?? null,
            'best_score' => $best['score'],
            'best_title' => $best['title'],
            'change' => $change,
            'change_label' => self::changeLabel($change),
            'level_transition' => self::levelTransition($latest, $previous),
            'series' => self::series($history),
        ];
    }

    private static function trend(?int $change): string
    {
        if ($change === null) {
            return 'baseline';
        }

        if ($change > 0) {
            return 'improved';
        }

        return $change < 0 ? 'declined' : 'steady';
    }

    private static function tone(string $trend): string
    {
        return match ($trend) {
            'improved' => 'positive',
            'declined' => 'support',
            'steady' => 'steady',
            default => 'baseline',
        };
    }

    private static function statusLabel(string $trend): string
    {
        return match ($trend) {
            'improved' => 'Improved',
            'declined' => 'Needs support',
            'steady' => 'No change yet',
            default => 'New baseline',
        };
    }

    private static function headline(array $latest, ?array $previous, ?int $change): string
    {
        if (! $previous) {
            return 'Latest reading score is '.$latest['score'].'%';
        }

        if ($change > 0) {
            return 'Improved by '.$change.' points';
        }

        if ($change < 0) {
            return 'Dropped by '.abs($change).' points';
        }

        return 'No score change yet';
    }

    private static function summary(array $latest, ?array $previous, ?int $change): string
    {
        if (! $previous) {
            return 'This is the first scored literacy result. Complete another literacy assessment to see a trend.';
        }

        if ($change > 0) {
            return 'The latest literacy score moved up from '.$previous['score'].'% to '.$latest['score'].'%. Keep building from the recommended practice.';
        }

        if ($change < 0) {
            return 'The latest literacy score moved from '.$previous['score'].'% to '.$latest['score'].'%. Use the support recommendation before the next assessment.';
        }

        return 'The latest literacy score matched the previous result at '.$latest['score'].'%. Continue practice to move the trend forward.';
    }

    private static function changeLabel(?int $change): string
    {
        if ($change === null) {
            return 'Baseline';
        }

        return ($change > 0 ? '+' : '').$change.' pts';
    }

    private static function levelTransition(array $latest, ?array $previous): ?string
    {
        if (! $previous || ! $latest['level'] || ! $previous['level']) {
            return $latest['level'] ?? null;
        }

        return $previous['level'].' to '.$latest['level'];
    }

    private static function series(Collection $history): array
    {
        return $history->take(-5)->map(fn (array $row): array => [
            'score' => $row['score'],
            'title' => $row['title'],
            'level' => $row['level'],
            'label' => $row['submitted_at']?->format('M j') ?? 'Saved',
        ])->values()->all();
    }
}
