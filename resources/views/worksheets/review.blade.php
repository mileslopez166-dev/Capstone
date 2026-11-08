<x-worksheet-layout :teacher="$teacher" :title="$assessment->title" :back="$teacher ? route('worksheets.reviews') : route('worksheets.mission')">
    @php
        $automaticReview = \App\Support\NumeracyWorksheetGrader::grade($worksheet, $attempt->pages ?? []);
        $studentResultsOnly = ! $teacher && $attempt->reviewed_at && $automaticReview;
        $resultsGuidePages = collect($worksheet['pages'])->filter(fn ($page) => collect($page['reading']['sections'] ?? [])
            ->contains(fn ($section) => isset($section['heading']) || ! empty($section['paragraphs'])));
    @endphp
    @unless ($teacher)<x-teacher-identity :teacher="$assessment->teacher" />@endunless
    <section class="worksheet-review-summary">
        <div><h2>{{ $teacher ? $attempt->student->name : 'Your Worksheet' }}</h2><p>Attempt {{ $attempt->progress->attempt_number }} &middot; Submitted {{ $attempt->created_at->format('M d, Y H:i') }}</p></div>
        @if ($attempt->reviewed_at)<strong class="worksheet-score">{{ $attempt->score }} / {{ $attempt->total }} <small>{{ $attempt->total > 0 ? round($attempt->score / $attempt->total * 100) : 0 }}%</small></strong>@else<strong class="worksheet-pending">Awaiting Teacher Review</strong>@endif
    </section>
    @if ($attempt->progress->submission)
        <x-assessment-coin-reward :submission="$attempt->progress->submission" />
    @else
        <p class="assessment-coin-reward"><span class="material-symbols-outlined" aria-hidden="true">toll</span>Coins awaiting teacher score</p>
    @endif
    @if ($attempt->progress->administrator)
        <p class="worksheet-notice">Teacher-assisted: {{ $attempt->progress->administrator->name }}</p>
    @endif
    @unless ($teacher)
        <div class="worksheet-actions"><a class="ui-button" href="{{ route('worksheets.mission') }}"><span class="material-symbols-outlined" aria-hidden="true">flag</span>Continue Mission</a></div>
    @endunless
    @if ($attempt->feedback)<div class="worksheet-feedback"><h2>Teacher Feedback</h2><p>{{ $attempt->feedback }}</p></div>@endif
    @if ($studentResultsOnly && $resultsGuidePages->isNotEmpty())
        <section class="numeracy-guide" data-results-learning-guide aria-labelledby="worksheet-results-guide-title">
            <article class="numeracy-guide-page">
                <header class="numeracy-guide-heading">
                    <span class="material-symbols-outlined" aria-hidden="true">school</span>
                    <div><p>Learning guide</p><h2 id="worksheet-results-guide-title">Rules &amp; Examples</h2></div>
                </header>
                @foreach ($resultsGuidePages as $guidePage)
                    <x-numeracy-guide :page="$guidePage" :show-instructions="false" />
                @endforeach
            </article>
        </section>
    @endif
    @if ($attempt->reviewed_at && $automaticReview)
        <section class="worksheet-answer-review" aria-labelledby="worksheet-answer-review-title">
            <header class="worksheet-answer-review-heading">
                <div>
                    <p>Automatic checking</p>
                    <h2 id="worksheet-answer-review-title">Complete Answer Review</h2>
                    <span>{{ $automaticReview['correct'] }} correct &middot; {{ $automaticReview['incorrect'] }} incorrect</span>
                </div>
                <dl>
                    <div><dt>Correct</dt><dd>{{ $automaticReview['correct'] }}</dd></div>
                    <div><dt>Wrong</dt><dd>{{ $automaticReview['incorrect'] }}</dd></div>
                </dl>
            </header>
            <div class="worksheet-answer-list">
                @foreach ($automaticReview['items'] as $item)
                    <article class="worksheet-answer-item {{ $item['is_correct'] ? 'is-correct' : 'is-wrong' }}">
                        <span class="material-symbols-outlined" aria-hidden="true">{{ $item['is_correct'] ? 'check_circle' : 'cancel' }}</span>
                        <div>
                            <p class="worksheet-answer-question"><strong>Item {{ $item['label'] }}</strong>{{ $item['question'] }}</p>
                            <dl>
                                <div><dt>Your answer</dt><dd>{{ $item['student_answer'] }}</dd></div>
                                <div><dt>Correct answer</dt><dd>{{ $item['correct_answer'] }}</dd></div>
                            </dl>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
    @unless ($studentResultsOnly)
        @php $config = ['pages' => \App\Support\NumeracyWorksheets::pageConfig($worksheet), 'state' => ['pages' => $attempt->pages, 'page' => 0], 'revision' => 0]; @endphp
        <x-worksheet-reader :worksheet="$worksheet" :config="$config" readonly />
    @endunless
    @if ($teacher && ! $attempt->reviewed_at)
        <form class="worksheet-grade" method="POST" action="{{ route('worksheets.grade', $attempt) }}">@csrf<h2>Teacher Review</h2><div class="worksheet-fields"><label>Correct items (out of {{ $attempt->total }})<input name="score" type="number" min="0" max="{{ $attempt->total }}" value="{{ old('score') }}" required></label><label class="worksheet-wide">Feedback &amp; Corrections<textarea name="feedback" rows="4" maxlength="5000" required>{{ old('feedback') }}</textarea></label></div><button class="ui-button" type="submit"><span class="material-symbols-outlined">check_circle</span>Save Score</button></form>
    @endif
</x-worksheet-layout>
