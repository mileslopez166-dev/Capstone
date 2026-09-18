@php $worksheetPending = \App\Models\WorksheetAttempt::with('assessment')->where('user_id', auth()->id())->whereNull('reviewed_at')->latest()->get(); @endphp
@if ($worksheetPending->isNotEmpty())
    <section class="worksheet-pending-list"><h2>Worksheets Awaiting Review</h2>@foreach ($worksheetPending as $attempt)<a class="worksheet-row" href="{{ route('worksheets.review', $attempt) }}"><strong>{{ $attempt->assessment->title }}</strong><span>View Submitted Work</span><span class="material-symbols-outlined">open_in_new</span></a>@endforeach</section>
@endif
