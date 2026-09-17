<x-practice-layout :teacher="true" title="Practice Missions">
    <div class="practice-toolbar"><h2>Assigned missions</h2>@if ($studentId)<a class="practice-link" href="{{ route('teacher.practice.index') }}">All students</a>@endif</div>
    <div class="practice-list">
        @forelse ($missions as $mission)
            <a class="practice-mission" href="{{ route('teacher.practice.show', $mission) }}">
                <span class="practice-icon material-symbols-outlined" aria-hidden="true">{{ $mission->status === 'completed' ? 'task_alt' : 'flag' }}</span>
                <div><h3>{{ $mission->title }}</h3><p>{{ $mission->student?->name ?? 'Unavailable student' }} &middot; {{ $mission->attempts_count }} practice checks</p></div>
                <x-status-badge :status="$mission->displayStatus(true)" />
                <span class="material-symbols-outlined" aria-hidden="true">chevron_right</span>
            </a>
        @empty
            <p class="practice-empty">No practice missions assigned yet.</p>
        @endforelse
    </div>
    <div class="practice-pagination">{{ $missions->links() }}</div>
    <section class="practice-section">
        <div class="practice-toolbar"><h2>Assessment results</h2><span class="practice-muted">Choose a result for practice</span></div>
        <div class="practice-list">
            @forelse ($submissions as $submission)
                <article class="practice-source">
                    <div><h3>{{ $submission->student->name }}</h3><p>{{ $submission->assessment->title }}</p><small>Attempt {{ $submission->attempt_number }} &middot; {{ $submission->submitted_at?->format('M j, Y') }}</small></div>
                    <span class="practice-muted">{{ $submission->question_count ? $submission->correct_count.' / '.$submission->question_count.' correct' : 'Reading result' }}</span>
                    <a class="practice-button practice-secondary" href="{{ route('teacher.practice.create', $submission) }}"><span class="material-symbols-outlined" aria-hidden="true">playlist_add</span> Select practice</a>
                </article>
            @empty
                <p class="practice-empty">No submitted assessments yet.</p>
            @endforelse
        </div>
        <div class="practice-pagination">{{ $submissions->links() }}</div>
    </section>
</x-practice-layout>
