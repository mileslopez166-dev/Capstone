<x-worksheet-layout teacher title="Create Numeracy Assessment" :back="route('assessments.create')">
    <nav class="worksheet-subjects" aria-label="Assessment subject"><a href="{{ route('assessments.create') }}"><span class="material-symbols-outlined" aria-hidden="true">auto_stories</span>Literacy</a><a href="{{ route('worksheets.index') }}" aria-current="page"><span class="material-symbols-outlined" aria-hidden="true">menu_book</span>Numeracy</a></nav>
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
</x-worksheet-layout>
