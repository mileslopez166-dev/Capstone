<x-worksheet-layout title="My Numeracy Mission" :back="route('student.activities', ['subject' => 'numeracy'])">
    <section class="worksheet-mission-overview" aria-labelledby="mission-title">
        <div class="worksheet-mission-title"><span class="material-symbols-outlined" aria-hidden="true">{{ $mission['complete'] ? 'emoji_events' : 'flag' }}</span><div><p>ARAL MATHEMATICS</p><h2 id="mission-title">{{ $mission['complete'] ? 'Mission Complete!' : '35 Worksheets. One Mission.' }}</h2></div></div>
        <div class="worksheet-mission-meter"><strong>{{ $mission['finished'] }} / {{ $mission['total'] }} submitted</strong><progress value="{{ $mission['finished'] }}" max="{{ $mission['total'] }}" aria-label="Worksheets submitted"></progress></div>
        <div class="worksheet-mission-stats"><span>{{ $mission['reviewed'] }} reviewed</span><span>{{ $mission['finished'] - $mission['reviewed'] }} awaiting review</span><span>{{ $mission['ready'] }} ready to finish</span></div>
        @if ($mission['next'])<a class="ui-button" href="{{ $mission['next']['url'] }}"><span class="material-symbols-outlined" aria-hidden="true">play_arrow</span>{{ $mission['next']['action'] }} {{ $mission['next']['number'] }}</a>@elseif (!$mission['complete'])<p class="worksheet-mission-waiting">Waiting for your teacher's next worksheet.</p>@endif
    </section>
    <section class="worksheet-mission-path" aria-label="Worksheet mission path">
        @foreach ($mission['steps'] as $step)
            <article class="worksheet-mission-step" data-mission-number="{{ $step['number'] }}" data-mission-state="{{ $step['state'] }}">
                <header><strong class="worksheet-step-number">{{ $step['number'] }}</strong><span>{{ $step['label'] }}</span><span class="material-symbols-outlined" aria-hidden="true">{{ $step['finished'] ? 'check_circle' : ($step['state'] === 'locked' ? 'lock' : 'menu_book') }}</span></header>
                @if ($step['image'])<img loading="lazy" src="{{ $step['image'] }}" alt="Worksheet {{ $step['number'] }} preview" width="1432" height="1013">@else<div class="worksheet-step-locked" aria-hidden="true"><span class="material-symbols-outlined">lock</span></div>@endif
                <div class="worksheet-step-content"><h2>Worksheet {{ $step['number'] }}</h2><p>{{ $step['parts'] }} {{ Str::plural('part', $step['parts']) }}@if ($step['score'])<strong>{{ $step['score'] }}</strong>@endif</p>
                    @if ($step['url'])<a class="ui-button {{ $step['finished'] ? 'ui-button-secondary' : '' }}" href="{{ $step['url'] }}"><span class="material-symbols-outlined" aria-hidden="true">{{ $step['finished'] ? 'visibility' : 'arrow_forward' }}</span>{{ $step['action'] }}<span class="sr-only"> {{ $step['number'] }}</span></a>@else<span class="worksheet-step-unavailable">Waiting for Teacher</span>@endif
                </div>
            </article>
        @endforeach
    </section>
</x-worksheet-layout>
