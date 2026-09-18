<x-worksheet-layout teacher title="Numeracy Worksheet Mission" :back="route('assessments.index')">
    <nav class="worksheet-subjects" aria-label="Assessment subject"><a href="{{ route('assessments.index') }}"><span class="material-symbols-outlined" aria-hidden="true">auto_stories</span>Literacy</a><a href="{{ route('worksheets.index') }}" aria-current="page"><span class="material-symbols-outlined" aria-hidden="true">menu_book</span>Numeracy</a></nav>
    <nav class="worksheet-tabs" aria-label="Worksheet sections"><a href="#library">Worksheet Library <span>35</span></a><a href="#review">Awaiting Review <span>{{ $reviews->count() }}</span></a><a href="#assigned">My Assignments <span>{{ $assignments->count() }}</span></a></nav>
    <section id="library" class="worksheet-section">
        <h2>ARAL Mathematics - Grade 6</h2>
        <div class="worksheet-library">
            @foreach ($worksheets as $worksheet)
                <a class="worksheet-tile" href="{{ route('worksheets.create', $worksheet['number']) }}">
                    <img loading="lazy" src="{{ route('worksheets.image', [$worksheet['number'], 1]) }}" alt="Preview of Worksheet {{ $worksheet['number'] }}" width="1432" height="1013">
                    <div><h3>Worksheet {{ $worksheet['number'] }}</h3><span>{{ count($worksheet['pages']) }} {{ Str::plural('part', count($worksheet['pages'])) }} <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></span></div>
                </a>
            @endforeach
        </div>
    </section>
    <section id="review" class="worksheet-section">
        <h2>Awaiting Review</h2>
        @forelse ($reviews as $attempt)
            <a class="worksheet-row" href="{{ route('worksheets.review', $attempt) }}"><div><strong>{{ $attempt->student->name }}</strong><p>{{ $attempt->assessment->title }}</p></div><span>{{ $attempt->created_at->format('M d, H:i') }}</span><span class="material-symbols-outlined" aria-hidden="true">rate_review</span></a>
        @empty <p class="worksheet-empty">No worksheets awaiting review.</p> @endforelse
    </section>
    <section id="assigned" class="worksheet-section">
        <h2>My Assignments</h2>
        @forelse ($assignments as $assessment)
            <a class="worksheet-row" href="{{ route('assessments.show', $assessment) }}"><div><strong>{{ $assessment->title }}</strong><p>{{ str($assessment->target_section)->replace('_', ' ')->title() }}</p></div><span>{{ $assessment->status === 'published' ? 'Published' : 'Locked' }}</span><span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a>
        @empty <p class="worksheet-empty">No worksheet assignments yet.</p> @endforelse
    </section>
</x-worksheet-layout>
