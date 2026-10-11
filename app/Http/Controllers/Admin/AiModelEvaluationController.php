<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\EvaluateClassificationModels;
use App\Jobs\EvaluateRecommendations;
use App\Models\AiActiveConfiguration;
use App\Models\AiModelActivationLog;
use App\Models\AiModelEvaluation;
use App\Models\AiModelVersion;
use App\Models\AiRecommendationEvaluation;
use App\Models\LearningRecommendation;
use App\Support\AlgorithmEvaluationService;
use App\Support\LearningRecommender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AiModelEvaluationController extends Controller
{
    public function index(Request $request): View
    {
        $admin = $this->admin($request);
        $evaluations = AiModelEvaluation::with(['versions', 'requester'])->latest()->take(20)->get();
        $recommendations = AiRecommendationEvaluation::with('requester')->latest('evaluated_at')->take(30)->get();

        return view('admin.ai-models', [
            'adminUser' => $admin,
            'adminInitials' => collect(explode(' ', $admin->name))->filter()->take(2)
                ->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode(''),
            'evaluations' => $evaluations,
            'latestBySubject' => $evaluations->unique('subject')->keyBy('subject'),
            'recommendationEvaluations' => $recommendations,
            'latestRecommendations' => $recommendations->unique('approach')->keyBy('approach'),
            'activeConfigurations' => AiActiveConfiguration::all()->keyBy(fn ($item) => $item->configuration_type.':'.$item->subject),
            'activationLogs' => AiModelActivationLog::latest()->take(15)->get(),
            'recentLearningRecommendations' => LearningRecommendation::with(['student', 'assessment'])
                ->latest()->take(20)->get(),
        ]);
    }

    public function evaluateClassification(Request $request): RedirectResponse
    {
        $admin = $this->admin($request);
        $data = $request->validate(['subject' => ['required', Rule::in(['literacy', 'numeracy'])]]);
        $evaluation = AiModelEvaluation::create([
            'requested_by' => $admin->id, 'subject' => $data['subject'], 'status' => 'queued',
        ]);
        EvaluateClassificationModels::dispatch($evaluation->id);

        return back()->with('status', ucfirst($data['subject']).' model comparison was queued.');
    }

    public function activateClassification(Request $request, AiModelVersion $version, AlgorithmEvaluationService $service): RedirectResponse
    {
        $admin = $this->admin($request);
        $service->activate($admin, $version);

        return back()->with('status', str($version->algorithm)->replace('_', ' ')->title().' is now active for '.ucfirst($version->subject).'.');
    }

    public function evaluateRecommendations(Request $request): RedirectResponse
    {
        $admin = $this->admin($request);
        EvaluateRecommendations::dispatch($admin->id);

        return back()->with('status', 'Recommendation comparison was queued.');
    }

    public function activateRecommendation(Request $request, LearningRecommender $recommender): RedirectResponse
    {
        $admin = $this->admin($request);
        $data = $request->validate([
            'approach' => ['required', Rule::in(LearningRecommender::APPROACHES)],
            'alpha' => ['nullable', 'numeric', 'between:0,1'],
        ]);
        try {
            $recommender->activate($admin, $data['approach'], isset($data['alpha']) ? (float) $data['alpha'] : null);
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['recommendation' => $exception->getMessage()]);
        }

        return back()->with('status', str($data['approach'])->replace('_', ' ')->title().' is now the active recommendation approach.');
    }

    public function rollback(Request $request, AlgorithmEvaluationService $models, LearningRecommender $recommendations): RedirectResponse
    {
        $admin = $this->admin($request);
        $data = $request->validate([
            'type' => ['required', Rule::in(['classification', 'recommendation'])],
            'subject' => ['required', Rule::in(['literacy', 'numeracy', 'all'])],
        ]);
        $configuration = AiActiveConfiguration::where('configuration_type', $data['type'])
            ->where('subject', $data['subject'])->firstOrFail();
        if (! $configuration->previous_key) {
            return back()->withErrors(['rollback' => 'No previous configuration is available.']);
        }

        if ($data['type'] === 'classification') {
            $version = AiModelVersion::where('model_name', $configuration->previous_key)->first();
            if (! $version) {
                return back()->withErrors(['rollback' => 'The previous model artifact is no longer registered.']);
            }
            $models->activate($admin, $version);
        } else {
            [$approach, $alpha] = array_pad(explode(':', $configuration->previous_key, 2), 2, null);
            try {
                $recommendations->activate($admin, $approach, is_numeric($alpha) ? (float) $alpha : null);
            } catch (\InvalidArgumentException $exception) {
                return back()->withErrors(['rollback' => $exception->getMessage()]);
            }
        }

        return back()->with('status', 'The previous AI configuration has been restored.');
    }

    private function admin(Request $request): \App\Models\User
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return $request->user();
    }
}
