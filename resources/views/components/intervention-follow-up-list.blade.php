@props(['plans'])
<section class="intervention-queue" aria-labelledby="follow-up-queue-title">
    <header><h2 id="follow-up-queue-title">Intervention Follow-ups</h2><span>{{ $plans->total() }} {{ Str::plural('plan', $plans->total()) }}</span></header>
    @forelse ($plans as $plan)
        @php
            $followUpStatus = \App\Support\InterventionFollowUp::status($plan);
            $source = $plan->submission;
        @endphp
        <a class="intervention-queue-row" href="{{ route('reports.student', $source->user_id).'#intervention-'.$source->id }}">
            <div><strong>{{ $source->student->name }}</strong><span>{{ $source->assessment->title }} &middot; Attempt {{ $source->attempt_number }}</span><small>{{ $plan->followUpAssessment?->title ?? 'No follow-up assessment linked' }}</small></div>
            <div class="intervention-queue-status"><span class="ui-status ui-status-{{ $followUpStatus['tone'] }}">{{ $followUpStatus['label'] }}</span><time>{{ $plan->follow_up_date?->format('M d, Y') ?? 'No date set' }}</time></div>
            <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
        </a>
    @empty
        <p class="intervention-queue-empty">No intervention follow-ups yet.</p>
    @endforelse
    @if ($plans->hasPages())<div class="intervention-queue-pagination">{{ $plans->fragment('follow-up-queue-title')->links() }}</div>@endif
</section>
