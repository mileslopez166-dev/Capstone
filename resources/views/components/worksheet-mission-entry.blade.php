@php $mission = \App\Support\WorksheetMission::forStudent(auth()->user()); @endphp
<section class="worksheet-mission-entry" aria-label="Numeracy worksheet mission">
    <span class="material-symbols-outlined worksheet-entry-icon" aria-hidden="true">{{ $mission['complete'] ? 'emoji_events' : 'menu_book' }}</span>
    <div><h2>{{ $mission['complete'] ? 'Numeracy Mission Complete!' : 'Your Numeracy Mission' }}</h2><p>{{ $mission['finished'] }} / {{ $mission['total'] }} worksheets submitted</p><progress value="{{ $mission['finished'] }}" max="{{ $mission['total'] }}" aria-label="Worksheets submitted"></progress></div>
    <a class="ui-button" href="{{ route('worksheets.mission') }}"><span class="material-symbols-outlined" aria-hidden="true">{{ $mission['complete'] ? 'visibility' : 'flag' }}</span>{{ $mission['complete'] ? 'View Mission' : 'Open Mission' }}</a>
</section>
