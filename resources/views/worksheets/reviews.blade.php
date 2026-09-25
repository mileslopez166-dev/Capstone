<x-worksheet-layout teacher title="Worksheet Reviews" :back="route('assessments.index')">
    <section class="worksheet-section" aria-labelledby="worksheet-reviews-heading">
        <h2 id="worksheet-reviews-heading">Awaiting Review <span class="text-sm text-on-surface-variant">({{ $reviews->count() }})</span></h2>
        @forelse ($reviews as $attempt)
            <a class="worksheet-row" href="{{ route('worksheets.review', $attempt) }}"><div><strong>{{ $attempt->student->name }}</strong><p>{{ $attempt->assessment->title }}</p></div><span>{{ $attempt->created_at->format('M d, H:i') }}</span><span class="material-symbols-outlined" aria-hidden="true">rate_review</span></a>
        @empty
            <p class="worksheet-empty">No worksheets awaiting review.</p>
        @endforelse
    </section>
</x-worksheet-layout>
