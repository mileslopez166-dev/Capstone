<x-practice-layout :title="$mission->title" :back="route('student.practice.index')">
    @php
        $answers = (array) old('answers', $mission->progress['answers'] ?? []);
        $practiced = array_map('intval', (array) old('practiced_words', $mission->progress['practiced_words'] ?? []));
        $reviewLabels = ['clear' => 'Read clearly', 'getting_closer' => 'Getting Closer', 'mispronounced' => 'Mispronounced'];
    @endphp
    <div class="practice-summary"><div><p>{{ $mission->source_title }} &middot; {{ $mission->teacher?->name ?? 'Your teacher' }}</p><x-status-badge :status="$mission->displayStatus(false)" /></div><span class="practice-coins"><span class="material-symbols-outlined" aria-hidden="true">toll</span>{{ $mission->reward_coins }} coins{{ $mission->status === 'completed' ? ' earned' : '' }}</span></div>
    @if ($mission->instructions)<p class="practice-note">{{ $mission->instructions }}</p>@endif
    @if ($mission->status === 'completed')
        <section class="practice-complete"><span class="material-symbols-outlined" aria-hidden="true">workspace_premium</span><div><h2>Mission complete!</h2><p>{{ $mission->words && !$mission->reviewed_at ? 'Words practiced. Waiting for your teacher to check your reading.' : 'Nice work finishing your practice.' }}</p><a class="practice-link" href="{{ route('student.wardrobe.edit') }}">Visit your wardrobe <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a></div></section>
    @elseif ($mission->status === 'cancelled')
        <p class="practice-note">Your teacher cancelled this mission.</p>
    @endif
    @if ($mission->reviewed_at)
        <section class="practice-section"><h2>Teacher feedback</h2><p class="practice-note">{{ $mission->feedback ?: 'Reviewed by your teacher.' }}</p>
            @foreach ($mission->words as $index => $word)<p class="practice-review-word"><strong>{{ $word['word'] }}</strong><span>{{ $reviewLabels[$mission->word_reviews[$index] ?? ''] ?? 'Not reviewed' }}</span></p>@endforeach
        </section>
    @endif
    @if ($mission->story)<details class="practice-story"><summary>Reading passage <span class="material-symbols-outlined" aria-hidden="true">expand_more</span></summary><p>{{ $mission->story }}</p></details>@endif
    <form method="POST" action="{{ route('student.practice.submit', $mission) }}" class="practice-form">
        @csrf<input type="hidden" name="attempt_key" value="{{ $attemptKey }}">
        @if ($mission->words)
            <section class="practice-section"><h2>Reading practice</h2>
                @foreach ($mission->words as $index => $word)
                    <article class="practice-word"><div><span class="practice-mark mark-{{ $word['mark'] }}">{{ $word['mark'] === 2 ? 'Mispronounced' : 'Getting Closer' }}</span><h3>{{ $word['word'] }}</h3><p>{{ $word['context'] }}</p></div>
                        <label class="practice-check"><input type="checkbox" name="practiced_words[]" value="{{ $index }}" @checked(in_array($index, $practiced)) @disabled($mission->status !== 'assigned')> Practiced</label>
                    </article>
                @endforeach
            </section>
        @endif
        @foreach ($mission->questions as $index => $question)
            <fieldset class="practice-question" @disabled($mission->status !== 'assigned')><legend><span>Question {{ $index + 1 }}</span>{{ $question['question'] }}</legend>
                @foreach ($question['answers'] as $letter => $answer)
                    <label class="practice-answer"><input type="radio" name="answers[{{ $index }}]" value="{{ $letter }}" @checked(($answers[$index] ?? null) === $letter)><strong>{{ $letter }}</strong><span>{{ $answer }}</span></label>
                @endforeach
            </fieldset>
            @if ($latestAttempt)
                @php $wasCorrect = ($latestAttempt->answers[$index] ?? null) === $question['correct_answer']; @endphp
                <p class="practice-answer-feedback {{ $wasCorrect ? 'is-correct' : '' }}"><span class="material-symbols-outlined" aria-hidden="true">{{ $wasCorrect ? 'check_circle' : 'info' }}</span> Last check: {{ $wasCorrect ? 'Correct' : 'Try again. Correct answer: '.$question['correct_answer'].'. '.$question['answers'][$question['correct_answer']] }}</p>
            @endif
        @endforeach
        @if ($mission->status === 'assigned')
            <footer class="practice-actions"><button class="practice-button practice-secondary" type="submit" name="action" value="save"><span class="material-symbols-outlined" aria-hidden="true">save</span> Save progress</button><button class="practice-button" type="submit" name="action" value="check"><span class="material-symbols-outlined" aria-hidden="true">task_alt</span> Check practice</button></footer>
        @endif
    </form>
</x-practice-layout>
