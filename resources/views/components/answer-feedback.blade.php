@props(['submission', 'index'])
<div class="answer-feedback" x-data="answerFeedback({ url: @js(route('student.answers.help', ['submission' => $submission->id, 'question' => $index])) })">
    <button type="button" class="ui-button ui-button-secondary" @click="toggle()" :disabled="busy"
        :aria-expanded="open" aria-controls="answer-feedback-{{ $submission->id }}-{{ $index }}">
        <span class="material-symbols-outlined" aria-hidden="true">lightbulb</span>
        <span x-text="busy ? 'Preparing help...' : error ? 'Try again' : open ? 'Hide explanation' : 'Help me understand'">Help me understand</span>
    </button>
    <div id="answer-feedback-{{ $submission->id }}-{{ $index }}" x-show="open" x-cloak :aria-busy="busy" class="answer-feedback__panel" role="region" aria-label="Answer explanation">
        <p x-show="busy" role="status">Thinking about this question...</p>
        <p x-show="error" x-text="error" role="alert"></p>
        <div x-show="answer" aria-live="polite">
            <p class="answer-feedback__label">AI learning help</p>
            <p class="answer-feedback__answer" x-text="answer"></p>
            <p class="answer-feedback__note">Practice is unscored. AI can make mistakes; ask your teacher if something seems unclear.</p>
        </div>
    </div>
</div>
