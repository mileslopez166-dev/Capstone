<?php

namespace App\Jobs;

use App\Support\LearningRecommender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateRecommendations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200;

    public int $tries = 1;

    public function __construct(public int $requestedBy)
    {
        $this->onQueue('ai');
    }

    public function handle(LearningRecommender $recommender): void
    {
        $recommender->evaluate($this->requestedBy);
    }
}
