<x-teacher-assessment-layout>
    <header class="teacher-workspace-heading mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="mb-2 text-xs font-bold uppercase text-primary">Assessment Results</p>
            <h1 class="text-3xl font-extrabold">Students Who Completed Assessments</h1>
            <p class="mt-2 text-sm text-on-surface-variant">Results from assessments you created. Retakes are grouped by student and assessment.</p>
        </div>
        <a class="ui-button ui-button-secondary" href="{{ route('assessments.index') }}"><span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>Back to Assessments</a>
    </header>

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
                            <h2 class="font-headline text-lg font-extrabold text-on-surface">{{ $rosterLabel }}</h2>
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
</x-teacher-assessment-layout>
