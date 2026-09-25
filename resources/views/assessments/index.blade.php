<x-teacher-assessment-layout>
    <header class="teacher-workspace-heading mb-6 flex flex-wrap items-center justify-between gap-4">
        <div><p class="mb-2 text-xs font-bold uppercase text-primary">Assessment Library</p><h1 class="text-3xl font-extrabold">Created Assessments</h1><p class="mt-2 text-sm text-on-surface-variant">{{ $assessments->total() }} {{ Str::plural('assessment', $assessments->total()) }}</p></div>
        <a class="ui-button" href="{{ route('assessments.create') }}"><span class="material-symbols-outlined" aria-hidden="true">add</span>Create Assessment</a>
    </header>
    @if (session('status'))<p class="mb-6 border-l-4 border-secondary bg-secondary-container/30 p-4" role="status">{{ session('status') }}</p>@endif
    <div class="assessment-list-tools">
        <nav class="worksheet-subjects" aria-label="Assessment subject">
            @foreach (['' => 'All', 'literacy' => 'Literacy', 'numeracy' => 'Numeracy'] as $value => $label)
                <a href="{{ route('assessments.index', array_filter(['subject' => $value, 'q' => $search])) }}" @if (($subject ?? '') === $value) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <form method="GET" action="{{ route('assessments.index') }}" class="assessment-list-search">
            @if ($subject)<input type="hidden" name="subject" value="{{ $subject }}">@endif
            <input type="search" name="q" value="{{ $search }}" placeholder="Search titles..." aria-label="Search created assessments" maxlength="255">
            <button class="worksheet-icon" type="submit" title="Search" aria-label="Search"><span class="material-symbols-outlined" aria-hidden="true">search</span></button>
            @if ($search !== '')<a class="worksheet-icon" href="{{ route('assessments.index', array_filter(['subject' => $subject])) }}" title="Clear search" aria-label="Clear search"><span class="material-symbols-outlined" aria-hidden="true">close</span></a>@endif
        </form>
    </div>
    <section aria-label="Created assessment list">
        @if ($assessments->isEmpty())
            <div class="py-16 text-center">
                <span class="material-symbols-outlined text-5xl text-outline-variant" aria-hidden="true">assignment</span>
                <h2 class="mt-4 text-xl font-bold">{{ $search !== '' ? 'No matching assessments' : 'No assessments yet' }}</h2>
                <a class="ui-button mt-5" href="{{ route('assessments.create') }}"><span class="material-symbols-outlined" aria-hidden="true">add</span>Create Assessment</a>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($assessments as $assessment)
                    @php $isPublished = $assessment->status === 'published'; @endphp
                    <article class="assessment-list-item">
                        <div class="mb-3 flex flex-wrap items-center gap-2">
                            <span class="text-xs font-bold uppercase text-primary">{{ $assessment->subject }}</span>
                            <x-assessment-type-label :assessment="$assessment" />
                            <x-status-badge :status="$isPublished ? 'unlocked' : 'draft'" />
                        </div>
                        <h2 class="text-xl font-bold"><a href="{{ route('assessments.show', $assessment) }}">{{ $assessment->title }}</a></h2>
                        <dl class="my-4 grid grid-cols-2 gap-4 text-sm">
                            <div><dt class="text-on-surface-variant">Section</dt><dd class="mt-1 font-semibold">{{ str($assessment->target_section ?? 'all')->replace('_', ' ')->title() }}</dd></div>
                            <div><dt class="text-on-surface-variant">{{ $assessment->worksheet_number ? 'Worksheet' : 'Questions' }}</dt><dd class="mt-1 font-semibold">{{ $assessment->worksheet_number ? '#'.$assessment->worksheet_number.' / '.$assessment->worksheet_total.' items' : count($assessment->manual_questions ?? []) }}</dd></div>
                        </dl>
                        <div class="assessment-list-item-actions">
                            <a class="ui-button" href="{{ route('assessments.show', $assessment) }}"><span class="material-symbols-outlined" aria-hidden="true">open_in_new</span>View Assessment</a>
                            <form method="POST" action="{{ route('assessments.availability', $assessment) }}" onsubmit="return confirm(this.dataset.confirm)" data-confirm="{{ $isPublished ? 'Lock this assessment for students?' : 'Unlock this assessment for students?' }}">
                                @csrf @method('PATCH')<input type="hidden" name="status" value="{{ $isPublished ? 'draft' : 'published' }}">
                                <button class="worksheet-icon" type="submit" title="{{ $isPublished ? 'Lock assessment' : 'Unlock assessment' }}" aria-label="{{ $isPublished ? 'Lock' : 'Unlock' }} {{ $assessment->title }}"><span class="material-symbols-outlined" aria-hidden="true">{{ $isPublished ? 'lock' : 'lock_open' }}</span></button>
                            </form>
                            <form method="POST" action="{{ route('assessments.destroy', $assessment) }}" onsubmit="return confirm('Delete this assessment and its student submissions?')">
                                @csrf @method('DELETE')<button class="worksheet-icon text-error" type="submit" title="Delete assessment" aria-label="Delete {{ $assessment->title }}"><span class="material-symbols-outlined" aria-hidden="true">delete</span></button>
                            </form>
                            <time class="text-xs text-on-surface-variant" datetime="{{ $assessment->created_at->toDateString() }}">{{ $assessment->created_at->format('M d, Y') }}</time>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-6">{{ $assessments->links() }}</div>
        @endif
    </section>
</x-teacher-assessment-layout>
