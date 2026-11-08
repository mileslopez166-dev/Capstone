<x-worksheet-layout teacher title="Create Numeracy Assessment" :back="route('assessments.create')">
    <nav class="worksheet-subjects" aria-label="Assessment subject"><a href="{{ route('assessments.create') }}"><span class="material-symbols-outlined" aria-hidden="true">auto_stories</span>Literacy</a><a href="{{ route('worksheets.index') }}" aria-current="page"><span class="material-symbols-outlined" aria-hidden="true">calculate</span>Numeracy</a></nav>

    <nav class="teacher-builder-steps" aria-label="Assessment creation steps">
        <span class="is-current"><b>01</b>Foundation</span>
        <span><b>02</b>Worksheet and questions</span>
        <span><b>03</b>Audience and settings</span>
    </nav>

    <form method="GET" action="{{ route('worksheets.index') }}" class="worksheet-builder-form">
        <div class="worksheet-builder-layout worksheet-builder-layout-single">
            <div class="worksheet-builder-main">
                <section class="worksheet-builder-panel" aria-labelledby="numeracy-foundation-heading">
                    <div class="worksheet-builder-panel-heading">
                        <div>
                            <p class="worksheet-builder-kicker">Step 1</p>
                            <h2 id="numeracy-foundation-heading">Assessment foundation</h2>
                        </div>
                        <span class="material-symbols-outlined" aria-hidden="true">calculate</span>
                    </div>
                    <div class="worksheet-subject-choice">
                        <a href="{{ route('assessments.create') }}" class="worksheet-subject-card">
                            <span class="material-symbols-outlined" aria-hidden="true">auto_stories</span>
                            <strong>Literacy</strong>
                            <small>Reading stories, questions, and fluency activities.</small>
                        </a>
                        <div class="worksheet-subject-card is-selected" aria-current="page">
                            <span class="material-symbols-outlined" aria-hidden="true">calculate</span>
                            <strong>Numeracy</strong>
                            <small>Choose an ARAL Mathematics worksheet and turn it into a game-based assessment.</small>
                            <span class="worksheet-selected-mark material-symbols-outlined" aria-hidden="true">check_circle</span>
                        </div>
                    </div>
                    <label class="worksheet-builder-label" for="numeracy-title">Assessment title</label>
                    <input class="worksheet-builder-input" id="numeracy-title" name="title" type="text" maxlength="255" value="{{ request('title') }}" placeholder="e.g., Numeracy skills practice">
                    <p class="worksheet-builder-help">You can use the worksheet title automatically, or give this assignment a class-specific name.</p>
                </section>

                <section class="worksheet-builder-panel" aria-labelledby="worksheet-library-heading">
                    <div class="worksheet-builder-panel-heading">
                        <div>
                            <p class="worksheet-builder-kicker">Step 2</p>
                            <h2 id="worksheet-library-heading">Choose a worksheet</h2>
                            <p>Students will see the worksheet as an interactive numeracy game.</p>
                        </div>
                        <span class="material-symbols-outlined" aria-hidden="true">menu_book</span>
                    </div>
                    <div class="worksheet-library-heading-row">
                        <div>
                            <h3>ARAL Mathematics - Grade 6</h3>
                            <p>{{ count($worksheets) }} game-ready worksheets available</p>
                        </div>
                        <span class="worksheet-library-tip"><span class="material-symbols-outlined" aria-hidden="true">touch_app</span>Select a card to continue</span>
                    </div>
                    <div class="worksheet-library">
                        @foreach ($worksheets as $worksheet)
                            <button class="worksheet-tile" type="submit" name="worksheet_number" value="{{ $worksheet['number'] }}" aria-label="Choose Worksheet {{ $worksheet['number'] }}">
                                <img loading="lazy" src="{{ route('worksheets.image', [$worksheet['number'], 1]) }}" alt="Preview of Worksheet {{ $worksheet['number'] }}" width="1432" height="1013">
                                <span class="worksheet-tile-details"><strong>{{ $worksheet['title'] }}</strong><span>{{ count($worksheet['pages']) }} {{ Str::plural('part', count($worksheet['pages'])) }} <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></span></span>
                            </button>
                        @endforeach
                    </div>
                </section>
            </div>

        </div>
    </form>
</x-worksheet-layout>
