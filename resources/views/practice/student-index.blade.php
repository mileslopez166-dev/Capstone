<x-practice-layout title="My Practice Missions">
    <section class="practice-summary practice-student-summary">
        <div class="practice-mini-avatar"><x-student-character :user="auth()->user()" /></div>
        <div><h2>Your next small win</h2><p>{{ $missions->total() ? 'A little practice. A new adventure.' : 'Your teacher has not assigned a practice mission yet.' }}</p></div>
        <a class="practice-coins" href="{{ route('student.wardrobe.edit') }}"><span class="material-symbols-outlined" aria-hidden="true">toll</span> {{ $coinBalance }} coins <span class="material-symbols-outlined" aria-hidden="true">chevron_right</span></a>
    </section>
    <div class="practice-grid">
        @forelse ($missions as $mission)
            <a class="practice-mission-card" href="{{ route('student.practice.show', $mission) }}">
                <div class="practice-toolbar"><span class="practice-icon material-symbols-outlined" aria-hidden="true">{{ $mission->status === 'completed' ? 'task_alt' : 'flag' }}</span><x-status-badge :status="$mission->displayStatus(false)" /></div>
                <h2>{{ $mission->title }}</h2><p>{{ $mission->teacher?->name ?? 'Your teacher' }}</p>
                <div class="practice-mission-meta"><span>{{ count($mission->words) }} words &middot; {{ count($mission->questions) }} questions</span><strong>{{ $mission->reward_coins }} coins</strong></div>
                <span class="practice-link">{{ $mission->status === 'assigned' ? ($mission->progress ? 'Continue practice' : 'Start practice') : 'View mission' }} <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></span>
            </a>
        @empty
            <div class="practice-empty"><span class="material-symbols-outlined" aria-hidden="true">flag</span><h2>All clear for now</h2><a class="practice-link" href="{{ route('student.activities') }}">Back to activities</a></div>
        @endforelse
    </div>
    <div class="practice-pagination">{{ $missions->links() }}</div>
</x-practice-layout>
