<?php

namespace App\Support;

use App\Models\AiActiveConfiguration;
use App\Models\AiModelActivationLog;
use App\Models\AiModelEvaluation;
use App\Models\AiModelVersion;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AlgorithmEvaluationService
{
    public const ALGORITHMS = ['decision_tree', 'random_forest', 'svm'];

    public function records(string $subject): array
    {
        return AssessmentSubmission::query()
            ->with(['assessment', 'student', 'interventionPlan'])
            ->whereHas('assessment', fn ($query) => $query->where('subject', $subject))
            ->whereNotNull('submitted_at')
            ->orderBy('user_id')->orderBy('submitted_at')->get()
            ->map(function (AssessmentSubmission $submission) use ($subject): ?array {
                $features = app(MLPredictionService::class)->features($submission);
                $label = $subject === 'literacy' ? (PhilIri::forSubmission($submission)['level'] ?? null) : null;

                if (! $label) {
                    return null;
                }

                return [
                    'student_code' => hash_hmac('sha256', (string) $submission->user_id, (string) config('app.key')),
                    'proficiency_label' => $label,
                    ...$features,
                ];
            })->filter()->values()->all();
    }

    public function evaluate(AiModelEvaluation $evaluation): void
    {
        $evaluation->update(['status' => 'training', 'started_at' => now(), 'error_message' => null]);
        $records = $this->records($evaluation->subject);

        try {
            $response = Http::acceptJson()->asJson()
                ->withHeaders(['X-ML-Admin-Token' => (string) config('ai_models.admin_token')])
                ->timeout((int) config('ai_models.training_timeout', 900))
                ->post(rtrim((string) config('ai_models.endpoint'), '/').'/admin/evaluate', [
                    'task' => $evaluation->subject,
                    'records' => $records,
                    'minimum_rows' => (int) config('ai_models.minimum_training_rows', 30),
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException('ML service returned HTTP '.$response->status().'.');
            }

            $result = $response->json();
            if (($result['status'] ?? null) === 'insufficient_data') {
                $evaluation->update([
                    'status' => 'insufficient_data',
                    'dataset_info' => ['rows_sent' => count($records)],
                    'error_message' => $result['message'] ?? 'Insufficient labeled data.',
                    'completed_at' => now(),
                ]);

                return;
            }
            if (($result['status'] ?? null) !== 'evaluated' || ! is_array($result['models'] ?? null)) {
                throw new \RuntimeException('ML service returned an invalid evaluation payload.');
            }

            DB::transaction(function () use ($evaluation, $result): void {
                $evaluation->update([
                    'status' => 'evaluated',
                    'dataset_info' => $result['dataset'] ?? null,
                    'comparison_metrics' => collect($result['models'])->map(fn ($model) => $model['validation_metrics'] ?? [])->all(),
                    'recommended_algorithm' => $result['recommended_algorithm'] ?? null,
                    'final_test_metrics' => $result['final_test_metrics'] ?? null,
                    'completed_at' => now(),
                ]);

                foreach ($result['models'] as $algorithm => $model) {
                    if (! in_array($algorithm, self::ALGORITHMS, true)) {
                        continue;
                    }
                    AiModelVersion::updateOrCreate([
                        'subject' => $evaluation->subject,
                        'model_name' => $model['model_name'],
                        'version' => $model['version'],
                    ], [
                        'evaluation_id' => $evaluation->id,
                        'algorithm' => $algorithm,
                        'status' => $model['status'] === 'validated' ? 'validated' : 'failed',
                        'artifact_path' => $model['artifact_path'] ?? null,
                        'metrics' => $model['validation_metrics'] ?? null,
                        'provenance' => [
                            'dataset' => $result['dataset'] ?? null,
                            'protocol' => $result['evaluation_protocol'] ?? null,
                            'random_seed' => $result['random_seed'] ?? null,
                            'training_date' => $result['training_date'] ?? null,
                        ],
                        'validated_at' => $model['status'] === 'validated' ? now() : null,
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            $evaluation->update([
                'status' => 'failed',
                'dataset_info' => ['rows_sent' => count($records)],
                'error_message' => (string) str($exception->getMessage())->limit(1000),
                'completed_at' => now(),
            ]);
            report($exception);
        }
    }

    public function activate(User $admin, AiModelVersion $version): void
    {
        if ($version->status !== 'validated' || ! $version->validated_at) {
            throw ValidationException::withMessages(['model' => 'Only a validated model can be activated.']);
        }

        $response = Http::acceptJson()->asJson()
            ->withHeaders(['X-ML-Admin-Token' => (string) config('ai_models.admin_token')])
            ->timeout(30)
            ->post(rtrim((string) config('ai_models.endpoint'), '/').'/admin/activate', [
                'task' => $version->subject,
                'algorithm' => $version->algorithm,
                'model_name' => $version->model_name,
                'version' => $version->version,
            ]);

        if (! $response->successful() || $response->json('status') !== 'activated') {
            $detail = $response->json('detail');
            throw ValidationException::withMessages([
                'model' => is_string($detail) ? $detail : 'The ML service could not activate this model.',
            ]);
        }

        if ($response->json('model_name') !== $version->model_name) {
            throw ValidationException::withMessages(['model' => 'The ML service activated a different model version. No configuration was changed.']);
        }

        DB::transaction(function () use ($admin, $version, $response): void {
            $configuration = AiActiveConfiguration::firstOrNew([
                'configuration_type' => 'classification', 'subject' => $version->subject,
            ]);
            $previous = $configuration->active_key;
            $configuration->fill([
                'active_key' => $version->model_name,
                'previous_key' => $previous,
                'activated_by' => $admin->id,
                'activated_at' => now(),
            ])->save();
            AiModelActivationLog::create([
                'configuration_type' => 'classification', 'subject' => $version->subject,
                'previous_key' => $previous, 'new_key' => $version->model_name,
                'activated_by' => $admin->id, 'details' => $response->json(),
            ]);
        });
    }
}
