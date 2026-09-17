<x-practice-layout :teacher="true" title="Assign Practice" :back="route('teacher.practice.index')">
    <div class="practice-summary"><div><h2>{{ $submission->student->name }}</h2><p>{{ $submission->assessment->title }} &middot; Attempt {{ $submission->attempt_number }}</p></div><span class="practice-coins"><span class="material-symbols-outlined" aria-hidden="true">toll</span> 25 coin reward</span></div>
    @if (!$suggestions)
        <section class="practice-empty"><span class="material-symbols-outlined" aria-hidden="true">task_alt</span><h2>No practice items in this result</h2><p>No missed questions or marked reading words were saved for this attempt.</p></section>
    @else
        <form method="POST" action="{{ route('teacher.practice.store', $submission) }}" class="practice-form">
            @csrf
            <div class="practice-fields">
                <label>Mission title<input name="title" maxlength="160" required value="{{ old('title', 'Practice: '.\Illuminate\Support\Str::limit($submission->assessment->title, 140)) }}"></label>
                <label>Teacher note <small>(optional)</small><textarea name="instructions" rows="3" maxlength="2000">{{ old('instructions') }}</textarea></label>
            </div>
            <section class="practice-section"><div class="practice-toolbar"><h2>Suggested practice</h2><span class="practice-muted">Select up to 10 items</span></div>
                @foreach ($suggestions as $key => $item)
                    <label class="practice-selection">
                        <input type="checkbox" name="items[]" value="{{ $key }}" @checked(in_array($key, (array) old('items', array_slice(array_keys($suggestions), 0, 5))))>
                        <span>
                            @if (isset($item['question']))
                                <strong>{{ $item['question'] }}</strong><small>Student answer: {{ $item['original_answer'] ?? 'Unanswered' }} &middot; Correct: {{ $item['correct_answer'] }}. {{ $item['answers'][$item['correct_answer']] }}</small>
                            @else
                                <strong>{{ $item['word'] }} <span class="practice-mark mark-{{ $item['mark'] }}">{{ $item['mark'] === 2 ? 'Mispronounced' : 'Getting Closer' }}</span></strong><small>{{ $item['context'] }}</small>
                            @endif
                        </span>
                    </label>
                @endforeach
            </section>
            <footer class="practice-actions"><a class="practice-button practice-secondary" href="{{ route('teacher.practice.index') }}">Cancel</a><button class="practice-button" type="submit"><span class="material-symbols-outlined" aria-hidden="true">send</span> Assign mission</button></footer>
        </form>
    @endif
</x-practice-layout>
