<x-practice-layout :teacher="true" :title="$mission->title" :back="route('teacher.practice.index')">
    @php $reviewLabels = ['clear' => 'Read clearly', 'getting_closer' => 'Getting Closer', 'mispronounced' => 'Mispronounced']; @endphp
    <div class="practice-summary"><div><h2>{{ $mission->student?->name ?? 'Unavailable student' }}</h2><p>{{ $mission->source_title }}</p></div><x-status-badge :status="$mission->displayStatus(true)" /></div>
    @if ($mission->instructions)<p class="practice-note">{{ $mission->instructions }}</p>@endif
    <section class="practice-section"><h2>Practice progress</h2>
        <div class="practice-stats"><div><span>First check</span><strong>{{ $firstAttempt && $firstAttempt->question_count ? $firstAttempt->correct_count.' / '.$firstAttempt->question_count : 'No quiz score' }}</strong></div><div><span>Checks submitted</span><strong>{{ $attempts->total() }}</strong></div><div><span>Practice coins</span><strong>{{ $mission->status === 'completed' ? $mission->reward_coins.' earned' : 'Not earned yet' }}</strong></div></div>
        @foreach ($mission->questions as $index => $question)<article class="practice-source"><div><h3>{{ $question['question'] }}</h3><p>Assessment answer: {{ $question['original_answer'] ?? 'Unanswered' }} &middot; Correct: {{ $question['correct_answer'] }}</p></div></article>@endforeach
        @foreach ($mission->words as $index => $word)<div class="practice-review-word"><strong>{{ $word['word'] }} <span class="practice-mark mark-{{ $word['mark'] }}">{{ $word['mark'] === 2 ? 'Mispronounced' : 'Getting Closer' }}</span></strong><span>{{ in_array($index, $mission->progress['practiced_words'] ?? [], true) ? 'Student marked as practiced' : 'Not practiced yet' }}</span></div>@endforeach
        @if ($mission->story)<details class="practice-story"><summary>Reading passage <span class="material-symbols-outlined" aria-hidden="true">expand_more</span></summary><p>{{ $mission->story }}</p></details>@endif
    </section>
    @if ($mission->status === 'completed')
        <section class="practice-section"><h2>Teacher review</h2><form method="POST" action="{{ route('teacher.practice.review', $mission) }}" class="practice-form">
            @csrf @method('PATCH')
            @foreach ($mission->words as $index => $word)<label class="practice-rating"><strong>{{ $word['word'] }}</strong><select name="word_reviews[{{ $index }}]" required><option value="">Select reading result</option>@foreach ($reviewLabels as $value => $label)<option value="{{ $value }}" @selected(old('word_reviews.'.$index, $mission->word_reviews[$index] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>@endforeach
            <div class="practice-fields"><label>Feedback <small>(optional)</small><textarea name="feedback" rows="3" maxlength="2000">{{ old('feedback', $mission->feedback) }}</textarea></label></div>
            <footer class="practice-actions">@if ($mission->reviewed_at)<span class="practice-muted">Reviewed {{ $mission->reviewed_at->format('M j, Y') }}</span>@endif<button class="practice-button" type="submit"><span class="material-symbols-outlined" aria-hidden="true">rate_review</span> Save review</button></footer>
        </form></section>
    @endif
    <section class="practice-section"><h2>Practice checks</h2>
        @forelse ($attempts as $attempt)
            <details class="practice-attempt"><summary><span>{{ $attempt->created_at->format('M j, Y g:i A') }}</span><strong>{{ $attempt->question_count ? $attempt->correct_count.' / '.$attempt->question_count.' correct' : 'Reading practiced' }}</strong></summary>
                @foreach ($mission->questions as $index => $question)<p>{{ $question['question'] }}<br><strong>Answer: {{ $attempt->answers[$index] ?? 'Unanswered' }}</strong> &middot; {{ ($attempt->answers[$index] ?? null) === $question['correct_answer'] ? 'Correct' : 'Incorrect' }}</p>@endforeach
                @if ($mission->words)<p>{{ count($attempt->practiced_words) }} words marked as practiced by student.</p>@endif
            </details>
        @empty<p class="practice-empty">No practice checks submitted yet.</p>@endforelse
        <div class="practice-pagination">{{ $attempts->links() }}</div>
    </section>
    @if ($mission->status === 'assigned')
        <details class="practice-cancel"><summary>Cancel mission</summary><form method="POST" action="{{ route('teacher.practice.cancel', $mission) }}">@csrf<p>This stops the mission without awarding coins.</p><button class="practice-button practice-danger" type="submit">Confirm cancellation</button></form></details>
    @endif
</x-practice-layout>
