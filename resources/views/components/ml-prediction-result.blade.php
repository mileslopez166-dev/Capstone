@props(['prediction' => null, 'live' => false])
@php
    $percent = static fn ($value) => is_numeric($value) && $value >= 0 && $value <= 1
        ? rtrim(rtrim(number_format($value * 100, 2, '.', ''), '0'), '.').'%' : 'Not available';
    $evaluation = $prediction?->model_evaluation;
    $hasEvaluation = ($evaluation['method'] ?? null) === 'held_out_test' && ($evaluation['test_rows'] ?? 0) > 0;
@endphp
<section class="ml-result" aria-label="ML learning prediction" @if ($live) data-ml-live hidden @endif>
    <h3>ML learning prediction</h3>
    <p data-ml-unavailable @if ($prediction) hidden @endif>No ML prediction was saved for this attempt. Your assessment score is unchanged.</p>
    <div data-ml-details @if (!$prediction) hidden @endif>
        <dl>
            <div><dt>Predicted learning level</dt><dd data-ml-field="prediction">{{ $prediction?->prediction }}</dd></div>
            <div><dt>Prediction confidence</dt><dd data-ml-field="confidence">{{ $percent($prediction?->confidence_score) }}</dd></div>
            <div><dt>Model test accuracy</dt><dd data-ml-field="accuracy">{{ $hasEvaluation ? $percent($evaluation['accuracy'] ?? null) : 'Not available' }}</dd></div>
        </dl>
        <p data-ml-field="evaluation">{{ $hasEvaluation ? 'Held-out test: '.number_format($evaluation['test_rows']).' records.' : 'No held-out test result was saved with this prediction.' }}</p>
        <p data-ml-field="recommendation">{{ $prediction?->recommendation }}</p>
        <p class="ml-result-note">Confidence is the model's estimate for this prediction. Test accuracy measures correct predictions on held-out data, not your assessment score or guaranteed real-world accuracy. Training labels may be score-derived. Your formula-based score remains official; discuss learning support with your teacher.</p>
    </div>
</section>
