<x-app-layout>
    @php
        $teacher = auth()->user();
        $initials = collect(explode(' ', $teacher->name))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');
        $oral = $result['assessment_type'] === 'oral_reading';
        $manualComprehension = (int) $submission->question_count === 0;
    @endphp
    <x-teacher-sidebar :teacher-name="$teacher->name" :teacher-initials="$initials" active="reports" />
    <div class="lg:ml-72"><x-teacher-topbar :teacher-name="$teacher->name" :teacher-initials="$initials" /></div>
    <main class="phil-page lg:ml-72">
        <div class="phil-content">
            <header class="teacher-workspace-heading">
                <a class="phil-back" href="{{ route('reports.student', $submission->user_id) }}"><span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>Student report</a>
                <h1>Literacy Scoring</h1>
                <p>{{ $submission->student?->name ?? 'Student' }} / {{ $submission->assessment->title }} / Attempt {{ $submission->attempt_number }}</p>
            </header>
            @if (session('status'))<p class="phil-notice" role="status">{{ session('status') }}</p>@endif
            @if ($errors->any())<div class="phil-errors" role="alert"><strong>Please check the scoring details.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <x-phil-iri-result :result="$result" />
            @if ($oral)
                <form class="phil-scoring-form" method="POST" action="{{ route('teacher.phil-iri.update', $submission) }}">
                    @csrf
                    @method('PATCH')
                    <h2>Teacher Observation</h2>
                    <div class="phil-form-fields">
                        <label for="phil-words">Passage word count<input id="phil-words" name="word_count" type="number" min="1" max="10000" value="{{ old('word_count', $result['word_count'] ?: '') }}" required></label>
                        <label for="phil-miscues">Counted miscues<input id="phil-miscues" name="miscues" type="number" min="0" max="10000" value="{{ old('miscues', $result['miscues'] ?? $result['marked_miscues'] ?? '') }}" required></label>
                        <label for="phil-seconds">Reading time in seconds (optional)<input id="phil-seconds" name="reading_seconds" type="number" min="1" max="604800" value="{{ old('reading_seconds', $result['reading_seconds']) }}"></label>
                    </div>
                    <p class="phil-note">Red-marked words provide the initial miscue count. Confirm counted miscues using the Phil-IRI guidelines; self-corrections and dialectal variations are not errors.</p>
                    @if ($manualComprehension)
                        <h3>Orally Administered Comprehension</h3>
                        <div class="phil-form-fields">
                            <label for="phil-correct">Correct answers<input id="phil-correct" name="comprehension_correct" type="number" min="0" max="1000" value="{{ old('comprehension_correct', $result['question_count'] ? $result['correct_count'] : '') }}"></label>
                            <label for="phil-questions">Total questions<input id="phil-questions" name="comprehension_questions" type="number" min="1" max="1000" value="{{ old('comprehension_questions', $result['question_count'] ?: '') }}"></label>
                        </div>
                    @endif
                    <button class="ui-button" type="submit"><span class="material-symbols-outlined" aria-hidden="true">save</span>Save scoring</button>
                </form>
                @if ($submission->assessment->story_description)
                    <details class="phil-passage"><summary>Reading passage</summary><p>{{ $submission->assessment->story_description }}</p></details>
                @endif
            @endif
            <x-phil-iri-rubric />
        </div>
    </main>
</x-app-layout>
