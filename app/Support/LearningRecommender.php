<?php

namespace App\Support;

use App\Models\AiActiveConfiguration;
use App\Models\AiRecommendationEvaluation;
use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\LearningRecommendation;
use App\Models\User;
use Illuminate\Support\Collection;

class LearningRecommender
{
    public const APPROACHES = ['content_based', 'collaborative', 'hybrid'];

    public function recommend(User $student, ?AssessmentSubmission $source = null, ?string $approach = null, bool $persist = true): array
    {
        $source ??= AssessmentSubmission::with('assessment')->where('user_id', $student->id)->latest('submitted_at')->first();
        $approach ??= AiActiveConfiguration::where('configuration_type', 'recommendation')
            ->where('subject', 'all')->value('active_key') ?: 'content_based';
        [$approach, $alpha] = $this->parseApproach($approach);
        $interactions = $this->interactions();
        $completed = $interactions->where('user_id', $student->id)->pluck('assessment_id')->unique();
        $candidates = $this->eligible($student)->whereNotIn('id', $completed);
        $ranked = $this->rank($student, $source, $candidates, $interactions, $approach, $alpha);

        if ($persist && $source) {
            LearningRecommendation::where('student_id', $student->id)->where('status', 'active')->update(['status' => 'replaced']);
            foreach ($ranked['items']->take((int) config('ai_models.recommendation_k', 5)) as $item) {
                LearningRecommendation::updateOrCreate([
                    'student_id' => $student->id,
                    'source_submission_id' => $source->id,
                    'assessment_id' => $item['assessment']->id,
                ], [
                    'approach' => $ranked['approach'], 'score' => $item['score'],
                    'reasons' => $item['reasons'], 'status' => 'active',
                ]);
            }
        }

        return $ranked;
    }

    public function evaluate(int $requestedBy): void
    {
        $interactions = $this->interactions();
        $catalog = Assessment::query()->where('status', 'published')->inActivityCatalog()->get();
        $students = User::where('role', 'student')->where('approval_status', 'approved')->get();
        $holdouts = collect();
        foreach ($students as $student) {
            $history = $interactions->where('user_id', $student->id)->sortByDesc('submitted_at');
            $relevant = $history->first(fn ($row) => $row['score'] >= 75);
            if ($history->count() >= 2 && $relevant) {
                $holdouts->push($relevant);
            }
        }
        $training = $interactions->reject(fn ($row) => $holdouts->contains('id', $row['id']))->values();
        $alphas = [.25, .5, .75];
        $runs = ['content_based' => null, 'collaborative' => null];
        foreach (array_keys($runs) as $approach) {
            $runs[$approach] = $this->evaluateOne($approach, .7, $holdouts, $training, $catalog, $students);
        }
        $hybrids = collect($alphas)->mapWithKeys(fn ($alpha) => [(string) $alpha => $this->evaluateOne('hybrid', $alpha, $holdouts, $training, $catalog, $students)]);
        $validHybrids = $hybrids->filter(fn ($result) => $result['status'] === 'evaluated');
        $bestAlpha = $validHybrids->sortByDesc('metrics.ndcg_at_5')->keys()->first();
        $runs['hybrid'] = $bestAlpha !== null ? $hybrids[$bestAlpha] + ['selected_alpha' => (float) $bestAlpha]
            : ['status' => 'insufficient_data', 'metrics' => null, 'notes' => 'Collaborative interaction coverage is insufficient.'];

        foreach ($runs as $approach => $result) {
            AiRecommendationEvaluation::create([
                'requested_by' => $requestedBy, 'subject' => 'all', 'approach' => $approach,
                'status' => $result['status'], 'metrics' => $result['metrics'] ?? null,
                'dataset_info' => ['interactions' => $interactions->count(), 'learners' => $interactions->pluck('user_id')->unique()->count(),
                    'activities' => $catalog->count(), 'holdouts' => $holdouts->count()],
                'notes' => $result['notes'] ?? ($bestAlpha !== null && $approach === 'hybrid' ? 'Validation selected alpha '.$bestAlpha.'.' : null),
                'evaluated_at' => now(),
            ]);
        }
    }

    public function activate(User $admin, string $approach, ?float $alpha = null): void
    {
        if (! in_array($approach, self::APPROACHES, true)) {
            throw new \InvalidArgumentException('Unknown recommendation approach.');
        }
        $evaluation = AiRecommendationEvaluation::where('approach', $approach)->latest('evaluated_at')->first();
        if (! $evaluation || $evaluation->status !== 'evaluated') {
            throw new \InvalidArgumentException('Only an evaluated recommendation approach can be activated.');
        }
        $key = $approach === 'hybrid' ? 'hybrid:'.($alpha ?? data_get($evaluation->metrics, 'alpha', .7)) : $approach;
        $configuration = AiActiveConfiguration::firstOrNew(['configuration_type' => 'recommendation', 'subject' => 'all']);
        $previous = $configuration->active_key;
        $configuration->fill(['active_key' => $key, 'previous_key' => $previous,
            'activated_by' => $admin->id, 'activated_at' => now()])->save();
        \App\Models\AiModelActivationLog::create(['configuration_type' => 'recommendation', 'subject' => 'all',
            'previous_key' => $previous, 'new_key' => $key, 'activated_by' => $admin->id,
            'details' => ['evaluation_id' => $evaluation->id]]);
    }

    private function evaluateOne(string $approach, float $alpha, Collection $holdouts, Collection $training, Collection $catalog, Collection $students): array
    {
        if ($holdouts->count() < 5) {
            return ['status' => 'insufficient_data', 'metrics' => null, 'notes' => 'At least five held-out relevant interactions are required.'];
        }
        if ($approach !== 'content_based' && ! $this->collaborativeReady($training)) {
            return ['status' => 'insufficient_data', 'metrics' => null, 'notes' => 'Interaction coverage is too sparse for collaborative filtering.'];
        }
        $clock = microtime(true);
        $hits = 0;
        $ndcg = 0;
        $fallbacks = 0;
        $recommended = collect();
        foreach ($holdouts as $heldOut) {
            $student = $students->firstWhere('id', $heldOut['user_id']);
            if (! $student) {
                continue;
            }
            $seen = $training->where('user_id', $student->id)->pluck('assessment_id');
            $sourceRow = $training->where('user_id', $student->id)->sortByDesc('submitted_at')->first();
            $source = $sourceRow ? AssessmentSubmission::with('assessment')->find($sourceRow['id']) : null;
            $candidates = $this->eligible($student)->whereNotIn('id', $seen);
            $ranking = $this->rank($student, $source, $candidates, $training, $approach, $alpha);
            $fallbacks += $ranking['fallback'] ? 1 : 0;
            $top = $ranking['items']->take(5);
            $rank = $top->pluck('assessment.id')->search($heldOut['assessment_id']);
            $recommended = $recommended->merge($top->pluck('assessment.id'));
            if ($rank !== false) {
                $hits++;
                $ndcg += 1 / log($rank + 2, 2);
            }
        }
        $count = max(1, $holdouts->count());

        return ['status' => 'evaluated', 'metrics' => ['precision_at_5' => $hits / ($count * 5),
            'recall_at_5' => $hits / $count, 'ndcg_at_5' => $ndcg / $count,
            'coverage' => $catalog->isEmpty() ? 0 : $recommended->unique()->count() / $catalog->count(),
            'response_time_ms' => round((microtime(true) - $clock) * 1000 / $count, 2),
            'fallback_rate' => $fallbacks / $count,
            'cold_start_compatible' => $approach === 'content_based' || $approach === 'hybrid', 'alpha' => $approach === 'hybrid' ? $alpha : null]];
    }

    private function rank(User $student, ?AssessmentSubmission $source, Collection $candidates, Collection $interactions, string $approach, float $alpha): array
    {
        $content = $this->contentScores($source, $candidates);
        $collaborative = $this->collaborativeScores($student, $candidates, $interactions);
        $actual = $approach;
        if ($approach !== 'content_based' && (! $this->collaborativeReady($interactions)
            || $collaborative->isEmpty() || (float) $collaborative->max() <= 0)) {
            $actual = 'content_based';
        }
        $contentNormalized = $this->normalize($content->pluck('score', 'assessment.id'));
        $collaborativeNormalized = $this->normalize($collaborative);
        $items = $content->map(function ($item) use ($actual, $alpha, $contentNormalized, $collaborativeNormalized) {
            $id = $item['assessment']->id;
            $contentScore = $contentNormalized[$id] ?? 0;
            $collaborativeScore = $collaborativeNormalized[$id] ?? 0;
            $score = match ($actual) {
                'collaborative' => $collaborativeScore,
                'hybrid' => $alpha * $contentScore + (1 - $alpha) * $collaborativeScore,
                default => $contentScore,
            };

            return $item + ['score' => round($score, 6), 'reasons' => array_values(array_filter([
                ...$item['reasons'], $actual !== 'content_based' && $collaborativeScore > 0 ? 'Helpful to learners with similar activity results' : null,
            ]))];
        })->sortByDesc('score')->values();

        return ['approach' => $actual, 'requested_approach' => $approach, 'fallback' => $actual !== $approach, 'items' => $items];
    }

    private function contentScores(?AssessmentSubmission $source, Collection $candidates): Collection
    {
        $sourceAssessment = $source?->assessment;
        $level = $source ? (PhilIri::forSubmission($source)['level'] ?? $source->mlPrediction?->prediction) : null;
        $preferredDifficulty = match ($level) {
            'Frustration' => 'frustration', 'Instructional' => 'instructional', 'Independent' => 'independent', default => null,
        };
        $sourceFocus = collect($sourceAssessment?->focus_areas ?? [])->map(fn ($value) => strtolower((string) $value));

        return $candidates->map(function (Assessment $assessment) use ($sourceAssessment, $preferredDifficulty, $sourceFocus): array {
            $score = .15;
            $reasons = ['Not completed yet'];
            if ($sourceAssessment && $assessment->subject === $sourceAssessment->subject) {
                $score += .4;
                $reasons[] = 'Matches the current learning subject';
            }
            $focus = collect($assessment->focus_areas ?? [])->map(fn ($value) => strtolower((string) $value));
            if ($sourceFocus->intersect($focus)->isNotEmpty()) {
                $score += .25;
                $reasons[] = 'Practices a related competency';
            }
            $difficulties = collect($assessment->manual_questions ?? [])->pluck('difficulty')->filter();
            if ($preferredDifficulty && $difficulties->contains($preferredDifficulty)) {
                $score += .2;
                $reasons[] = 'Difficulty fits the current proficiency evidence';
            }

            return ['assessment' => $assessment, 'score' => min(1, $score), 'reasons' => $reasons];
        });
    }

    private function collaborativeScores(User $student, Collection $candidates, Collection $interactions): Collection
    {
        $target = $interactions->where('user_id', $student->id)->pluck('score', 'assessment_id');
        if ($target->isEmpty()) {
            return collect();
        }
        $similarities = collect();
        foreach ($interactions->groupBy('user_id') as $userId => $rows) {
            if ((int) $userId === (int) $student->id) {
                continue;
            }
            $ratings = $rows->pluck('score', 'assessment_id');
            $common = $target->keys()->intersect($ratings->keys());
            if ($common->count() < 2) {
                continue;
            }
            $dot = $common->sum(fn ($id) => $target[$id] * $ratings[$id]);
            $left = sqrt($common->sum(fn ($id) => $target[$id] ** 2));
            $right = sqrt($common->sum(fn ($id) => $ratings[$id] ** 2));
            if ($left > 0 && $right > 0) {
                $similarities[(int) $userId] = $dot / ($left * $right);
            }
        }

        return $candidates->mapWithKeys(function (Assessment $assessment) use ($interactions, $similarities) {
            $ratings = $interactions->where('assessment_id', $assessment->id)->filter(fn ($row) => isset($similarities[$row['user_id']]));
            $weight = $ratings->sum(fn ($row) => $similarities[$row['user_id']]);
            $score = $weight > 0 ? $ratings->sum(fn ($row) => $row['score'] * $similarities[$row['user_id']]) / $weight : 0;

            return [$assessment->id => $score];
        });
    }

    private function interactions(): Collection
    {
        return AssessmentSubmission::with('assessment')->whereNotNull('submitted_at')->get()
            ->sortByDesc('points')->unique(fn ($submission) => $submission->user_id.':'.$submission->assessment_id)
            ->map(fn ($submission) => ['id' => $submission->id, 'user_id' => $submission->user_id,
                'assessment_id' => $submission->assessment_id, 'score' => AssessmentScores::percentage($submission) ?? 0,
                'submitted_at' => $submission->submitted_at])->values();
    }

    private function eligible(User $student): Collection
    {
        $section = $student->section ? strtolower(str_replace(' ', '_', $student->section)) : null;

        return Assessment::query()->with('teacher')->where('status', 'published')->inActivityCatalog()
            ->where(fn ($query) => $query->where('target_section', 'all')->orWhereNull('target_section')
                ->when($section, fn ($query) => $query->orWhere('target_section', $section)))->get();
    }

    private function collaborativeReady(Collection $interactions): bool
    {
        $minimum = config('ai_models.collaborative_minimum');

        return $interactions->count() >= $minimum['interactions']
            && $interactions->pluck('user_id')->unique()->count() >= $minimum['students']
            && $interactions->pluck('assessment_id')->unique()->count() >= $minimum['activities'];
    }

    private function normalize(Collection $scores): Collection
    {
        if ($scores->isEmpty()) {
            return collect();
        }
        $minimum = $scores->min();
        $maximum = $scores->max();

        return $scores->map(fn ($score) => $maximum === $minimum ? ($maximum > 0 ? 1.0 : 0.0) : ($score - $minimum) / ($maximum - $minimum));
    }

    private function parseApproach(string $value): array
    {
        [$approach, $configured] = array_pad(explode(':', $value, 2), 2, null);
        if (! in_array($approach, self::APPROACHES, true)) {
            $approach = 'content_based';
        }
        $alpha = is_numeric($configured) ? (float) $configured : (float) config('ai_models.hybrid_alpha', .7);

        return [$approach, max(0, min(1, $alpha))];
    }
}
