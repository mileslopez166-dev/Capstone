<?php

namespace App\Jobs;

use App\Models\AiModelEvaluation;
use App\Support\AlgorithmEvaluationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateClassificationModels implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200;

    public int $tries = 1;

    public function __construct(public int $evaluationId)
    {
        $this->onQueue('ai');
    }

    public function handle(AlgorithmEvaluationService $service): void
    {
        $service->evaluate(AiModelEvaluation::findOrFail($this->evaluationId));
    }
}
