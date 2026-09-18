<x-app-layout>
    @php $student = Auth::user(); @endphp
    <x-student-nav active="home" />
    <main class="student-home lg:ml-72">
        <header class="home-heading">
            <div><p>YOUR LEARNING SPACE</p><h1>Hi, {{ str($student->name)->before(' ')->title() }}!</h1></div>
            <a class="ui-button ui-button-secondary" href="{{ route('student.wardrobe.edit') }}"><span class="material-symbols-outlined" aria-hidden="true">checkroom</span>Wardrobe</a>
        </header>
        @if (session('status'))<p class="wardrobe-notice" role="status">{{ session('status') }}</p>@endif
        <section class="home-next" aria-labelledby="next-task-heading">
            <div class="home-next-content">
                <p class="home-eyebrow">UP NEXT{{ $nextTask ? ' / '.$nextTask['kind'] : '' }}</p>
                @if ($nextTask)
                    <x-status-badge :status="$nextTask['status']" />
                    <h2 id="next-task-heading">{{ $nextTask['title'] }}</h2>
                    <a class="ui-button" href="{{ $nextTask['url'] }}"><span class="material-symbols-outlined" aria-hidden="true">{{ $nextTask['status'] === 'in_progress' ? 'play_arrow' : 'arrow_forward' }}</span>{{ $nextTask['action'] }}</a>
                @else
                    <h2 id="next-task-heading">You're all caught up!</h2><p>Your next assignment will appear here.</p>
                    <a class="ui-button" href="{{ route('student.activities') }}"><span class="material-symbols-outlined" aria-hidden="true">history</span>View Results</a>
                @endif
            </div>
            <a class="home-avatar" href="{{ route('student.wardrobe.edit') }}" aria-label="Customize your avatar"><x-student-character :user="$student" /></a>
        </section>
        <x-worksheet-pending />
        <x-worksheet-mission-entry />
        <section class="home-stats" aria-label="Your progress">
            <a href="{{ route('student.practice.index') }}"><span class="material-symbols-outlined" aria-hidden="true">toll</span><div><strong>{{ number_format($coinBalance) }}</strong><span>Practice coins</span></div></a>
            <a href="{{ route('student.activities') }}#recorded-outputs"><span class="material-symbols-outlined" aria-hidden="true">task_alt</span><div><strong>{{ $studentMetrics['completed_count'] }}</strong><span>Assessments done</span></div></a>
            <div><span class="material-symbols-outlined" aria-hidden="true">target</span><div><strong>{{ $studentMetrics['average_accuracy'] === null ? '--' : $studentMetrics['average_accuracy'].'%' }}</strong><span>Best-score accuracy</span></div></div>
            <a href="{{ route('student.leaderboard') }}"><span class="material-symbols-outlined" aria-hidden="true">stars</span><div><strong>{{ number_format($studentMetrics['total_points']) }}</strong><span>Best-score points</span></div></a>
        </section>
        <div class="home-columns">
            <section class="home-section" aria-labelledby="recent-results">
                <header><h2 id="recent-results">Recent Results</h2><a href="{{ route('student.activities') }}#recorded-outputs">View all<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a></header>
                @forelse ($recentSubmissions as $submission)
                    <a class="home-result" href="{{ route('student.activities') }}#result-{{ $submission->id }}"><span class="home-result-icon material-symbols-outlined" aria-hidden="true">assignment_turned_in</span><div><strong>{{ $submission->assessment?->title ?? 'Assessment' }}</strong><span>{{ $submission->submitted_at?->format('M j, Y') }} &middot; {{ $submission->correct_count }} / {{ $submission->question_count }} correct</span></div><strong>{{ number_format($submission->points) }}<small>points</small></strong><span class="material-symbols-outlined" aria-hidden="true">chevron_right</span></a>
                @empty
                    <div class="home-empty"><span class="material-symbols-outlined" aria-hidden="true">assignment</span><p>No results yet. Your completed assessments will appear here.</p></div>
                @endforelse
            </section>
            <section class="home-section" aria-labelledby="your-queue">
                <header><h2 id="your-queue">Your Queue</h2><a href="{{ route('student.activities') }}">Activities<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a></header>
                @forelse ($pendingAssessments->take(3) as $assessment)
                    <a class="home-queue-item" href="{{ route('student.assessments.show', $assessment) }}"><div><strong>{{ $assessment->title }}</strong><x-status-badge :status="$assessment->student_state" /></div><span class="material-symbols-outlined" aria-hidden="true">chevron_right</span></a>
                @empty
                    <p class="home-empty">No assessments waiting.</p>
                @endforelse
                <a class="home-practice-link" href="{{ route('student.practice.index') }}"><span class="material-symbols-outlined" aria-hidden="true">flag</span><strong>Practice Missions</strong><span>{{ $practiceCount }} to do</span><span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a>
            </section>
        </div>
    </main>
</x-app-layout>
