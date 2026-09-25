<?php

namespace App\Support;

use App\Models\AssessmentSubmission;
use App\Models\InterventionPlan;
use App\Models\MLPrediction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class MLPredictionService
{
    private const LABELS = ['Independent', 'Instructional', 'Frustration'];

    public function predictForSubmission(AssessmentSubmission $submission): ?MLPrediction
    {
        if (! config('ml.enabled') || blank(config('ml.endpoint'))) {
            return null;
        }

        $submission->loadMissing('assessment');
        $features = $this->features($submission);

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

        return MLPrediction::query()->updateOrCreate([
            'assessment_submission_id' => $submission->id,
            'model_name' => $modelName,
        ], [
            'student_id' => $submission->user_id,
            'prediction' => $prediction,
            'confidence_score' => $confidence,
            'input_data' => $features,
            'recommendation' => $this->recommendation($features, $prediction),
        ]);
    }

    public function features(AssessmentSubmission $submission): array
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
            'assessment_attempts' => (int) $submission->attempt_number,
            'previous_score' => $this->previousScore($submission),
            'completion_time' => $this->number($philIri['reading_seconds'] ?? null),
            'intervention_count' => InterventionPlan::query()
                ->whereHas('submission', fn ($query) => $query->where('user_id', $submission->user_id))
                ->count(),
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
