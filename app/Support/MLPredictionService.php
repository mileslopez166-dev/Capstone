<?php

namespace App\Support;

use App\Models\AssessmentSubmission;
use App\Models\InterventionPlan;
use App\Models\MLPrediction;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MLPredictionService
{
    private const LABELS = ['Independent', 'Instructional', 'Frustration'];

    public function predictForSubmission(AssessmentSubmission $submission): ?MLPrediction
    {
        if (! config('ml.enabled') || blank(config('ml.endpoint'))) {
            return null;
        }
        $features = $this->features($submission);
        $result = $this->predictFeatures($features);
        if (! $result) {
            return null;
        }

        return MLPrediction::query()->updateOrCreate([
            'assessment_submission_id' => $submission->id,
            'model_name' => $result['model_name'],
        ], [
            'student_id' => $submission->user_id,
            'prediction' => $result['prediction'],
            'confidence_score' => $result['confidence'],
            'model_evaluation' => $result['evaluation'],
            'input_data' => $features,
            'recommendation' => $this->recommendation($features, $result['prediction']),
        ]);
    }

    private function predictFeatures(array $features): ?array
    {
        if (! config('ml.enabled') || blank(config('ml.endpoint'))) {
            return null;
        }

        try {
            $response = Http::acceptJson()->asJson()
                ->timeout((int) config('ml.timeout', 5))
                ->post(rtrim((string) config('ml.endpoint'), '/').'/predict', $features);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful() || ! is_array($response->json())) {
            return null;
        }

        $data = $response->json();
        $prediction = (string) ($data['prediction'] ?? '');
        if (! in_array($prediction, self::LABELS, true)) {
            return null;
        }

        $confidence = is_numeric($data['confidence'] ?? null)
            ? max(0, min(1, (float) $data['confidence']))
            : null;
        $modelName = (string) ($data['model_name'] ?? config('ml.model_name'));

        return [
            'model_name' => $modelName,
            'prediction' => $prediction,
            'confidence' => $confidence,
            'evaluation' => $this->evaluation($data['evaluation'] ?? null),
        ];
    }

    public function features(AssessmentSubmission $submission): array
    {
        return $this->assessmentFeatures($submission) + [
            'assessment_attempts' => (int) $submission->attempt_number,
            'previous_score' => $this->previousScore($submission),
            'intervention_count' => InterventionPlan::query()
                ->whereHas('submission', fn ($query) => $query->where('user_id', $submission->user_id))
                ->count(),
        ];
    }

    public function overallForStudent(User $teacher, User $student): array
    {
        abort_unless($teacher->isTeacher(), 403);
        abort_unless($student->isStudent(), 404);

        $attempts = AssessmentSubmission::query()
            ->with(['assessment', 'interventionPlan'])
            ->where('user_id', $student->id)
            ->whereHas('assessment', fn ($query) => $query->where('created_by', $teacher->id))
            ->orderByDesc('submitted_at')->orderByDesc('id')->get()
            ->filter(fn ($attempt) => AssessmentScores::percentage($attempt) !== null);
        $latest = $attempts->unique('assessment_id');
        $summary = [
            'assessment_count' => $latest->count(),
            'overall_percentage' => AssessmentScores::average($latest),
            'prediction' => null,
        ];
        if ($latest->isEmpty()) {
            return $summary + ['status' => 'no_data'];
        }
        if (! config('ml.enabled') || blank(config('ml.endpoint'))) {
            return $summary + ['status' => 'disabled'];
        }

        // Each assessment contributes once; absent subject features stay null, not zero.
        $rows = $latest->map(fn ($attempt) => $this->assessmentFeatures($attempt));
        $features = [];
        foreach (array_keys($rows->first()) as $key) {
            $values = $rows->pluck($key)->filter(fn ($value) => $value !== null);
            $features[$key] = $values->isEmpty() ? null : round($values->avg(), 4);
        }
        $features['assessment_attempts'] = $latest->avg('attempt_number');
        $features['previous_score'] = AssessmentScores::average($attempts->whereNotIn('id', $latest->pluck('id'))->unique('assessment_id'));
        $features['intervention_count'] = $attempts->filter(fn ($attempt) => $attempt->interventionPlan !== null)->count();
        $key = 'ml-overall-v1:'.$teacher->id.':'.$student->id.':'.hash('sha256', json_encode([
            config('ml.endpoint'), config('ml.model_name'), $features, $latest->pluck('id')->all(),
        ]));
        $cached = Cache::get($key);
        if ($cached === null) {
            $result = $this->predictFeatures($features);
            $cached = ['status' => $result ? 'ready' : 'unavailable', 'prediction' => $result];
            Cache::put($key, $cached, $result ? now()->addMinutes(10) : now()->addSeconds(30));
        }

        return array_merge($summary, $cached);
    }

    private function assessmentFeatures(AssessmentSubmission $submission): array
    {
        $submission->loadMissing('assessment');
        $assessment = $submission->assessment;
        $philIri = PhilIri::forSubmission($submission) ?? [];
        $score = AssessmentScores::percentage($submission);
        $subject = $assessment?->subject;
        $type = $assessment?->assessment_type;

        return [
            'reading_score' => $subject === 'literacy' ? $score : null,
            'reading_accuracy' => $type === 'oral_reading'
                ? $this->number($philIri['word_reading_percent'] ?? null)
                : ($subject === 'literacy' ? $score : null),
            'reading_speed' => $this->number($philIri['words_per_minute'] ?? null),
            'comprehension_score' => $this->number($philIri['comprehension_percent'] ?? ($subject === 'literacy' ? $score : null)),
            'listening_score' => $type === 'listening_comprehension' ? $score : null,
            'numeracy_score' => $subject === 'numeracy' ? $score : null,
            'completion_time' => $this->number($philIri['reading_seconds'] ?? null),
        ];
    }

    public function recommendation(array $features, ?string $prediction = null): string
    {
        if (($features['comprehension_score'] ?? null) !== null && $features['comprehension_score'] < 70) {
            return 'Provide reading comprehension activities';
        }

        if (($features['numeracy_score'] ?? null) !== null && $features['numeracy_score'] < 70) {
            return 'Provide a numeracy practice worksheet';
        }

        if (($features['reading_accuracy'] ?? null) !== null && $features['reading_accuracy'] < 75) {
            return 'Provide a reading fluency exercise';
        }

        return match ($prediction) {
            'Independent' => 'Provide enrichment reading tasks',
            'Frustration' => 'Provide teacher-guided support with easier text',
            default => 'Provide guided practice and monitor progress',
        };
    }

    private function evaluation(mixed $evaluation): ?array
    {
        if (! is_array($evaluation) || ($evaluation['method'] ?? null) !== 'held_out_test'
            || ! is_numeric($evaluation['accuracy'] ?? null)
            || $evaluation['accuracy'] < 0 || $evaluation['accuracy'] > 1
            || ! is_int($evaluation['test_rows'] ?? null) || $evaluation['test_rows'] < 1) {
            return null;
        }

        return [
            'accuracy' => (float) $evaluation['accuracy'],
            'test_rows' => $evaluation['test_rows'],
            'method' => 'held_out_test',
        ];
    }

    private function previousScore(AssessmentSubmission $submission): ?int
    {
        $subject = $submission->assessment?->subject;
        $previous = AssessmentSubmission::query()
            ->with('assessment')
            ->where('user_id', $submission->user_id)
            ->where('id', '<', $submission->id)
            ->when($subject, fn ($query) => $query->whereHas('assessment', fn ($assessment) => $assessment->where('subject', $subject)))
            ->latest('submitted_at')
            ->get()
            ->first(fn (AssessmentSubmission $attempt): bool => AssessmentScores::percentage($attempt) !== null);

        return $previous ? AssessmentScores::percentage($previous) : null;
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
