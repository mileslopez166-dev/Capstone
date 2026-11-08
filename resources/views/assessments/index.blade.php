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
    <section class="mb-8" aria-labelledby="assessment-completions-heading">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase text-primary">Assessment Results</p>
                <h2 id="assessment-completions-heading" class="mt-1 text-2xl font-extrabold text-on-surface">Students Who Completed Assessments</h2>
                <p class="mt-1 text-sm text-on-surface-variant">Latest results from assessments you created. Retakes are grouped per student and assessment.</p>
            </div>
        </div>
        <div class="grid gap-5 xl:grid-cols-2">
            @foreach (['literacy' => ['Literacy', 'menu_book'], 'numeracy' => ['Numeracy', 'calculate']] as $rosterSubject => [$rosterLabel, $rosterIcon])
                @php
                    $roster = $completionRosters->get($rosterSubject, collect());
                    $learnerCount = $roster->pluck('student.id')->unique()->count();
                @endphp
                <article class="overflow-hidden rounded-lg border border-outline-variant/20 bg-surface-container-lowest shadow-[0_12px_28px_rgba(0,0,0,0.04)]">
                    <header class="flex items-center justify-between gap-4 border-b border-outline-variant/15 bg-surface-container-low/50 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-container/30 text-primary">
                                <span class="material-symbols-outlined" aria-hidden="true">{{ $rosterIcon }}</span>
                            </span>
                            <div>
                                <h3 class="font-headline text-lg font-extrabold text-on-surface">{{ $rosterLabel }}</h3>
                                <p class="text-xs text-on-surface-variant">{{ $learnerCount }} {{ Str::plural('student', $learnerCount) }} &middot; {{ $roster->count() }} completed {{ Str::plural('assessment', $roster->count()) }}</p>
                            </div>
                        </div>
                    </header>
                    @if ($roster->isEmpty())
                        <div class="px-5 py-10 text-center">
                            <span class="material-symbols-outlined text-4xl text-outline-variant" aria-hidden="true">pending_actions</span>
                            <p class="mt-3 font-bold text-on-surface">No {{ strtolower($rosterLabel) }} completions yet</p>
                            <p class="mt-1 text-sm text-on-surface-variant">Student results will appear here after submission.</p>
                        </div>
                    @else
                        <x-data-table-scroll :label="$rosterLabel.' assessment completion roster'">
                            <table class="w-full min-w-[700px] border-collapse text-left">
                                <thead>
                                    <tr class="bg-surface-container-low/30">
                                        <th class="px-5 py-3 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Student</th>
                                        <th class="px-5 py-3 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Assessment</th>
                                        <th class="px-5 py-3 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Latest Score</th>
                                        <th class="px-5 py-3 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Attempts</th>
                                        <th class="px-5 py-3 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Submitted</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/10">
                                    @foreach ($roster as $completion)
                                        <tr class="transition-colors hover:bg-surface-container-low/30">
                                            <td class="px-5 py-4">
                                                <a class="font-bold text-on-surface hover:text-primary" href="{{ route('students.show', $completion['student']) }}">{{ $completion['student']->name }}</a>
                                                <p class="mt-1 text-xs text-on-surface-variant">Section {{ $completion['student']->section ?: 'Not assigned' }}</p>
                                            </td>
                                            <td class="px-5 py-4">
                                                <a class="font-semibold text-on-surface hover:text-primary" href="{{ route('assessments.show', $completion['assessment']) }}">{{ $completion['assessment']->title }}</a>
                                            </td>
                                            <td class="px-5 py-4">
                                                @if ($completion['score'] === null)
                                                    <span class="ui-table-badge inline-flex rounded-full bg-surface-container-high px-3 py-1 text-xs font-bold text-on-surface-variant">Awaiting score</span>
                                                @else
                                                    <span class="ui-table-badge inline-flex rounded-full bg-secondary-container/30 px-3 py-1 text-sm font-extrabold text-on-secondary-container">{{ $completion['score'] }}%</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 font-semibold text-on-surface">{{ $completion['attempts'] }} {{ Str::plural('attempt', $completion['attempts']) }}</td>
                                            <td class="px-5 py-4 text-sm text-on-surface-variant">
                                                <time datetime="{{ $completion['submitted_at']?->toIso8601String() }}">{{ $completion['submitted_at']?->format('M d, Y h:i A') ?? 'Recently' }}</time>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </x-data-table-scroll>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
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
                            <div><dt class="text-on-surface-variant">Section</dt><dd class="mt-1 font-semibold">{{ \App\Support\SectionCatalog::displayTarget($assessment->target_section) }}</dd></div>
                            <div><dt class="text-on-surface-variant">{{ $assessment->worksheet_number ? 'Worksheet' : 'Questions' }}</dt><dd class="mt-1 font-semibold">{{ $assessment->worksheet_number ? '#'.$assessment->worksheet_number.' / '.$assessment->worksheet_total.' items' : ($assessment->question_selection === 'automatic' ? '8 per student' : count($assessment->manual_questions ?? [])) }}</dd></div>
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
