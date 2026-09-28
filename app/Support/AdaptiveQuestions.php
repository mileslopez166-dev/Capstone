<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\AssessmentProgress;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdaptiveQuestions
{
    public const LABELS = ['frustration' => 'Easier', 'instructional' => 'Moderate', 'independent' => 'Challenging', 'advanced' => 'Enrichment'];
    public const MIXES = [
        'balanced' => ['frustration' => 3, 'instructional' => 3, 'independent' => 2],
        'support' => ['frustration' => 5, 'instructional' => 2, 'independent' => 1],
        'developing' => ['frustration' => 2, 'instructional' => 4, 'independent' => 2],
        'challenge' => ['frustration' => 1, 'instructional' => 2, 'independent' => 5],
    ];

    public static function validateBank(array $questions): void
    {
        $counts = array_count_values(array_column($questions, 'difficulty'));
        foreach (['frustration', 'instructional', 'independent'] as $level) {
            if (($counts[$level] ?? 0) < 5) {
                throw ValidationException::withMessages(['question_selection' => 'Automatic selection needs at least 5 easier, 5 moderate, and 5 challenging questions. Use a full story bank or choose a fixed set.']);
            }
        }
    }

    public static function freeze(AssessmentProgress $progress, Assessment $assessment, User $student): AssessmentProgress
    {
        if ($assessment->worksheet_number || $progress->question_snapshot !== null) return $progress;

        return DB::transaction(function () use ($progress, $assessment, $student) {
            $locked = AssessmentProgress::whereKey($progress->id)->lockForUpdate()->firstOrFail();
            if ($locked->question_snapshot !== null) return $locked;
            $questions = array_values($assessment->manual_questions ?? []);
            $context = ['version' => 'rules-v1', 'mode' => 'fixed'];
            if ($assessment->question_selection === 'automatic' && $assessment->assessment_type !== 'oral_reading') {
                self::validateBank($questions);
                $context = self::placement($assessment, $student);
                $selected = [];
                foreach (self::MIXES[$context['plan']] as $level => $amount) {
                    $pool = collect($questions)->map(fn ($question, $index) => $question + ['bank_index' => $index])
                        ->where('difficulty', $level)
                        ->sortBy(fn ($question) => hash('sha256', $locked->attempt_key.':'.$question['bank_index']))
                        ->take($amount)->values()->all();
                    $selected[$level] = $pool;
                }
                // Interleave bands, starting with an easier item, rather than bunching hard items together.
                $questions = [];
                while (array_filter($selected)) {
                    foreach (array_keys($selected) as $level) {
                        if ($selected[$level]) $questions[] = array_shift($selected[$level]);
                    }
                }
            }
            $locked->forceFill(['question_snapshot' => $questions, 'selection_context' => $context])->save();
            return $locked;
        });
    }

    public static function placement(Assessment $assessment, User $student): array
    {
        $bands = array_fill_keys(['frustration', 'instructional', 'independent'], ['correct' => 0, 'total' => 0]);
        // Compare like reading modes, within this teacher's assessments. Retakes count only once.
        $history = AssessmentSubmission::where('user_id', $student->id)->whereNotNull('question_snapshot')
            ->whereHas('assessment', fn ($query) => $query->where('created_by', $assessment->created_by)
                ->where('subject', $assessment->subject)->where('assessment_type', $assessment->assessment_type))
            ->where('question_count', '>', 0)->orderByDesc('submitted_at')->orderByDesc('id')->get()
            ->unique('assessment_id')->take(3);
        $used = 0;
        foreach ($history as $submission) {
            $hasEvidence = false;
            foreach ($submission->question_snapshot as $index => $question) {
                $level = $question['difficulty'] ?? null;
                if (!isset($bands[$level]) || !isset($submission->answers[$index])) continue;
                $bands[$level]['total']++;
                $bands[$level]['correct'] += (int) ($submission->answers[$index] === ($question['correct_answer'] ?? null));
                $hasEvidence = true;
            }
            if ($hasEvidence) $used++;
        }
        $moderate = $bands['instructional'];
        $hard = $bands['independent'];
        $plan = 'balanced';
        // These transparent pilot rules select practice difficulty, not an official reading diagnosis.
        if ($moderate['total'] >= 2) {
            $rate = $moderate['correct'] / $moderate['total'];
            $plan = $rate < .6 ? 'support' : 'developing';
            if ($rate >= .75 && $hard['total'] >= 2 && $hard['correct'] / $hard['total'] >= .75) $plan = 'challenge';
        }
        return [
            'version' => 'rules-v1', 'mode' => 'automatic', 'plan' => $plan,
            'provisional' => true, 'evidence_assessments' => $used, 'bands' => $bands,
            'mix' => self::MIXES[$plan],
        ];
    }
}
