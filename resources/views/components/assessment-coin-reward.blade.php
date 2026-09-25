@props(['submission'])
@php
    $reward = $submission?->coinReward;
    $pending = $submission && ! $reward && \App\Support\AssessmentCoinRewards::percentage($submission) === null;
@endphp
@if ($reward || $pending)
    <p class="assessment-coin-reward">
        <span class="material-symbols-outlined" aria-hidden="true">toll</span>
        @if ($reward)<strong>{{ $reward->amount }} coins earned</strong>@else<span>Coins awaiting teacher score</span>@endif
    </p>
@endif
