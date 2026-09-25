<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\InterventionPlan;
use App\Models\User;
use Illuminate\Support\Collection;

class InterventionFollowUp
{
    public static function availableAssessments(User $teacher, User $student): Collection
    {
        return Assessment::where('created_by', $teacher->id)->where('status', 'published')
            ->orderBy('title')->get()
            ->filter(fn ($assessment) => AssessmentParticipant::matchesSection($assessment, $student));
    }

    public static function comparable(AssessmentSubmission $baseline, Assessment $assessment): bool
    {
        if ($baseline->assessment?->subject !== $assessment->subject) {
            return false;
        }

        if ($assessment->subject === 'literacy') {
            $type = $baseline->phil_iri['assessment_type'] ?? $baseline->assessment->assessment_type ?? 'silent_reading';
            return $type === ($assessment->assessment_type ?? 'silent_reading');
        }

        return $baseline->assessment->worksheet_number === $assessment->worksheet_number;
    }

    public static function recordSubmission(AssessmentSubmission $submission): void
    {
        if (!$submission->submitted_at) return;

        // The guarded update keeps the first result even when the learner retries later.
        InterventionPlan::where('follow_up_assessment_id', $submission->assessment_id)
            ->whereNull('follow_up_submission_id')
            ->where('follow_up_linked_at', '<=', $submission->submitted_at)
            ->where('assessment_submission_id', '<>', $submission->id)
            ->whereHas('submission', fn ($query) => $query->where('user_id', $submission->user_id))
            ->update(['follow_up_submission_id' => $submission->id]);
    }

    public static function status(InterventionPlan $plan): array
    {
        if ($plan->follow_up_submission_id) {
            return ['label' => 'Follow-up completed', 'tone' => 'green'];
        }
        if ($plan->status === 'done') return ['label' => 'Plan closed', 'tone' => 'gray'];
        if ($plan->follow_up_date?->lt(today())) return ['label' => 'Overdue', 'tone' => 'red'];
        if ($plan->follow_up_date?->isToday()) return ['label' => 'Due today', 'tone' => 'amber'];
        if ($plan->follow_up_linked_at && !$plan->follow_up_assessment_id) return ['label' => 'Assessment unavailable', 'tone' => 'red'];
        if (!$plan->follow_up_assessment_id) return ['label' => 'Needs assessment', 'tone' => 'gray'];

        return ['label' => 'Awaiting result', 'tone' => 'blue'];
    }

    public static function comparison(InterventionPlan $plan, AssessmentSubmission $baseline): array
    {
        $followUp = $plan->followUpSubmission;
        $before = self::metrics($baseline);
        $after = $followUp ? self::metrics($followUp) : null;
        $comparable = $followUp && $followUp->user_id === $baseline->user_id
            && $followUp->assessment?->created_by === $baseline->assessment?->created_by
            && self::comparable($baseline, $followUp->assessment)
            && $before['type'] === $after['type'];

        $difference = $comparable && $before['score'] !== null && $after['score'] !== null
            ? round($after['score'] - $before['score'], 2) : null;

        return [
            'before' => $before, 'after' => $after, 'comparable' => (bool) $comparable,
            'difference' => $difference,
            'change_label' => $difference === null ? 'Comparison pending'
                : ($difference > 0 ? 'Improved' : ($difference < 0 ? 'Needs support' : 'No score change')),
        ];
    }

    private static function metrics(AssessmentSubmission $submission): array
    {
        $result = PhilIri::forSubmission($submission);
        $oral = ($result['assessment_type'] ?? null) === 'oral_reading';
        $score = $oral ? ($result['word_reading_percent'] ?? null)
            : ($result['comprehension_percent'] ?? ($submission->question_count > 0
                ? round($submission->correct_count * 100 / $submission->question_count, 2) : null));

        return [
            'score' => $score, 'score_label' => $oral ? 'Word reading' : ($result ? 'Comprehension' : 'Assessment score'),
            'type' => $result['assessment_type'] ?? 'numeracy',
            'level' => $result['level'] ?? null,
            'comprehension' => $result['comprehension_percent'] ?? null,
            'miscues' => $oral && $score !== null
                ? (($result['word_reading_source'] ?? null) === 'teacher_review' ? $result['miscues'] : $result['marked_miscues']) : null,
            'word_count' => $oral ? $result['word_count'] : null,
        ];
    }
}
