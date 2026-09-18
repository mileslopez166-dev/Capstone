<x-worksheet-layout :teacher="$teacher" :title="$assessment->title" :back="$teacher ? route('worksheets.index').'#review' : route('worksheets.mission')">
    <section class="worksheet-review-summary">
        <div><h2>{{ $teacher ? $attempt->student->name : 'Your Worksheet' }}</h2><p>Attempt {{ $attempt->progress->attempt_number }} &middot; Submitted {{ $attempt->created_at->format('M d, Y H:i') }}</p></div>
        @if ($attempt->reviewed_at)<strong class="worksheet-score">{{ $attempt->score }} / {{ $attempt->total }} <small>{{ round($attempt->score / $attempt->total * 100) }}%</small></strong>@else<strong class="worksheet-pending">Awaiting Teacher Review</strong>@endif
    </section>
    @if ($attempt->progress->administrator)
        <p class="worksheet-notice">Teacher-assisted: {{ $attempt->progress->administrator->name }}</p>
    @endif
    @unless ($teacher)
        <div class="worksheet-actions"><a class="ui-button" href="{{ route('worksheets.mission') }}"><span class="material-symbols-outlined" aria-hidden="true">flag</span>Continue Mission</a></div>
    @endunless
    @if ($attempt->feedback)<div class="worksheet-feedback"><h2>Teacher Feedback</h2><p>{{ $attempt->feedback }}</p></div>@endif
    @php $config = ['pages' => \App\Support\NumeracyWorksheets::pageConfig($worksheet), 'state' => ['pages' => $attempt->pages, 'page' => 0], 'revision' => 0]; @endphp
    <x-worksheet-reader :worksheet="$worksheet" :config="$config" readonly />
    @if ($teacher && ! $attempt->reviewed_at)
        <form class="worksheet-grade" method="POST" action="{{ route('worksheets.grade', $attempt) }}">@csrf<h2>Teacher Review</h2><div class="worksheet-fields"><label>Correct items (out of {{ $attempt->total }})<input name="score" type="number" min="0" max="{{ $attempt->total }}" value="{{ old('score') }}" required></label><label class="worksheet-wide">Feedback &amp; Corrections<textarea name="feedback" rows="4" maxlength="5000" required>{{ old('feedback') }}</textarea></label></div><button class="ui-button" type="submit"><span class="material-symbols-outlined">check_circle</span>Save Score</button></form>
    @endif
</x-worksheet-layout>
