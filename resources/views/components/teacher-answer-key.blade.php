@props(['questions' => [], 'context' => [], 'answers' => null])
@if (auth()->user()?->isTeacher() && count($questions))
<details class="teacher-answer-key">
    <summary><span class="material-symbols-outlined" aria-hidden="true">fact_check</span>Teacher answer key <span>{{ count($questions) }} questions</span></summary>
    @if (($context['mode'] ?? '') === 'automatic')
        <div class="answer-key-context">
            <strong>{{ ['balanced' => 'Balanced starter', 'support' => 'Support focus', 'developing' => 'Moderate focus', 'challenge' => 'Challenge focus'][$context['plan']] ?? 'Automatic selection' }}</strong>
            <p>{{ $context['evidence_assessments'] ?? 0 }} recent comparable assessments. Provisional rule-based placement, not an official Phil-IRI level or ML prediction.</p>
            <p>Selected: {{ $context['mix']['frustration'] ?? 0 }} easier, {{ $context['mix']['instructional'] ?? 0 }} moderate, {{ $context['mix']['independent'] ?? 0 }} challenging.</p>
            @foreach (array_intersect_key(\App\Support\AdaptiveQuestions::LABELS, $context['bands'] ?? []) as $level => $label)
                @php $band = $context['bands'][$level]; @endphp
                <span>{{ \App\Support\AdaptiveQuestions::LABELS[$level] ?? $level }} evidence: {{ $band['correct'] }}/{{ $band['total'] }}</span>
            @endforeach
        </div>
    @endif
    <ol>
        @foreach ($questions as $index => $question)
            @php $correct = $question['correct_answer'] ?? ''; @endphp
            <li>
                <div class="answer-key-question"><strong>{{ $index + 1 }}. {{ $question['question'] ?? '' }}</strong><span>{{ \App\Support\AdaptiveQuestions::LABELS[$question['difficulty'] ?? ''] ?? 'Unspecified difficulty' }}</span></div>
                <p><strong>Correct answer: {{ $correct }}</strong> &ndash; {{ $question['answers'][$correct] ?? '' }}</p>
                @if ($answers !== null)
                    @php $chosen = $answers[$index] ?? ''; @endphp
                    <p>Student answer: <strong>{{ $chosen ?: 'Unanswered' }}</strong> {{ $question['answers'][$chosen] ?? '' }} ({{ $chosen === $correct ? 'Correct' : 'Incorrect' }})</p>
                @endif
            </li>
        @endforeach
    </ol>
</details>
@endif
