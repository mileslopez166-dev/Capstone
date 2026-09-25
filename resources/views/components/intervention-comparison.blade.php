@props(['plan', 'submission', 'available' => false])
@php
    $comparison = \App\Support\InterventionFollowUp::comparison($plan, $submission);
    $followUp = $plan->followUpSubmission;
    $before = $comparison['before'];
    $after = $comparison['after'];
    $difference = $comparison['difference'];
    $format = fn ($number) => rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    $percent = fn ($number) => $number === null ? 'Not recorded' : $format($number).'%';
    $change = $difference === null ? null : ($difference > 0 ? '+' : '').$format($difference).' percentage points';
@endphp
<section class="intervention-comparison" aria-label="Follow-up comparison">
    <header>
        <h3>Follow-up Comparison</h3>
        @if ($followUp && $comparison['comparable'])<span class="ui-status ui-status-{{ $difference === null ? 'gray' : ($difference < 0 ? 'amber' : 'green') }}">{{ $comparison['change_label'] }}</span>@endif
    </header>
    <p class="intervention-comparison-target">{{ $plan->followUpAssessment?->title ?? 'Linked assessment unavailable' }}</p>
    <p class="intervention-comparison-meta">Linked {{ $plan->follow_up_linked_at->format('M d, Y, g:i A') }}</p>
    @if ($plan->comparison_basis)<p class="intervention-comparison-basis"><strong>Comparison basis:</strong> {{ $plan->comparison_basis }}</p>@endif
    @if ($followUp && !$comparison['comparable'])
        <p class="intervention-errors">Assessment details have changed. These results cannot currently be compared.</p>
    @else
        <div class="intervention-score-pair">
            <div><span>Original</span><strong>{{ $percent($before['score']) }}</strong><small>{{ $before['score_label'] }} &middot; Attempt {{ $submission->attempt_number }}</small><small>{{ $submission->submitted_at?->format('M d, Y') }}</small></div>
            <div><span>Follow-up</span><strong>{{ $after ? $percent($after['score']) : 'Pending' }}</strong><small>{{ $after ? $after['score_label'].' - Attempt '.$followUp->attempt_number : 'Next result after linking' }}</small><small>{{ $followUp?->submitted_at?->format('M d, Y') }}</small></div>
        </div>
        @if ($change)<p class="intervention-score-change">{{ $change }}</p>@endif
        @if ($before['type'] !== 'numeracy' && $before['type'] !== 'group_screening')
            <dl class="intervention-comparison-details">
                <div><dt>Reading level</dt><dd>Original: {{ $before['level'] ?? 'Not recorded' }}<br>Follow-up: {{ $after['level'] ?? 'Not recorded' }}</dd></div>
                @if ($before['type'] === 'oral_reading')
                    <div><dt>Pronunciation errors</dt><dd>Original: {{ $before['miscues'] === null ? 'Not recorded' : $before['miscues'].' / '.$before['word_count'].' words' }}<br>Follow-up: {{ ($after['miscues'] ?? null) === null ? 'Not recorded' : $after['miscues'].' / '.$after['word_count'].' words' }}</dd></div>
                    <div><dt>Comprehension</dt><dd>Original: {{ $percent($before['comprehension']) }}<br>Follow-up: {{ $percent($after['comprehension'] ?? null) }}</dd></div>
                @endif
            </dl>
        @endif
    @endif
    <div class="intervention-follow-up-actions">
        @if ($followUp)
            <a class="practice-link" href="{{ route('reports.student', $submission->user_id).'#submission-'.$followUp->id }}"><span class="material-symbols-outlined" aria-hidden="true">fact_check</span>View Follow-up Result</a>
        @elseif ($available && $plan->followUpAssessment?->status === 'published')
            <a class="practice-link" href="{{ route('teacher.assessments.take', [$plan->followUpAssessment, $submission->user_id]) }}"><span class="material-symbols-outlined" aria-hidden="true">play_arrow</span>Take Together</a>
        @endif
    </div>
</section>
