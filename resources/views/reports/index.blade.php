<x-app-layout>
    @php
        $teacherName = Auth::user()->name;
        $teacherInitials = collect(explode(' ', $teacherName))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
    @endphp

    <div class="min-h-screen bg-surface">
        <div class="flex min-h-screen">
            <x-teacher-sidebar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" active="reports" />

            <main class="min-h-screen flex-1 lg:ml-64">
                <x-teacher-topbar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" search-placeholder="Search reports..." />

                <section class="px-8 pb-8 pt-8">
                    <div class="teacher-workspace-heading mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-end">
                        <div class="space-y-1">
                            <h1 class="font-headline text-4xl font-extrabold tracking-tight text-on-surface">Reports</h1>
                            <a class="ui-button ui-button-secondary" href="{{ route('worksheets.index') }}#review"><span class="material-symbols-outlined">rate_review</span>Worksheet Reviews</a>
                            <p class="text-lg text-on-surface-variant">{{ $submissions->isEmpty() ? 'Reports will populate when student progress is available in the system.' : 'Reports are connected to saved student assessment submissions.' }}</p>
                        </div>
                    </div>

                    @if (session('status'))
                        <div class="mb-6 rounded-sm border border-primary/20 bg-primary-container/20 px-5 py-4 text-sm font-semibold text-primary">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="teacher-report-overview grid grid-cols-1 gap-6 lg:grid-cols-12">
                        <section class="rounded-sm border border-outline-variant/10 bg-surface-container-lowest p-6 lg:col-span-4">
                            <h3 class="mb-6 text-sm font-bold uppercase tracking-wider text-on-surface-variant">Student Distribution</h3>
                            <div class="flex min-h-[18rem] flex-col items-center justify-center rounded-xl bg-surface-container-low p-6 text-center">
                                <span class="material-symbols-outlined text-5xl text-outline-variant">donut_large</span>
                                <p class="mt-4 font-headline text-xl font-bold text-on-surface">{{ $reportMetrics['average_accuracy'] === null ? 'No report data yet' : $reportMetrics['average_accuracy'].'% average accuracy' }}</p>
                                <p class="mt-2 text-sm text-on-surface-variant">Students with results: {{ number_format($reportMetrics['students_with_results']) }}</p>
                            </div>
                        </section>

                        <section class="rounded-sm border border-outline-variant/10 bg-surface-container-lowest p-6 lg:col-span-8">
                            <h3 class="mb-6 text-sm font-bold uppercase tracking-wider text-on-surface-variant">Class Comparison</h3>
                            <div class="flex min-h-[18rem] flex-col items-center justify-center rounded-xl bg-surface-container-low p-6 text-center">
                                <span class="material-symbols-outlined text-5xl text-outline-variant">analytics</span>
                                <p class="mt-4 font-headline text-xl font-bold text-on-surface">{{ $reportMetrics['completed_count'] > 0 ? number_format($reportMetrics['completed_count']).' completed assessments' : 'No class performance yet' }}</p>
                                <p class="mt-2 text-sm text-on-surface-variant">{{ $submissions->isEmpty() ? 'Literacy and numeracy comparisons will stay empty until real student results exist.' : 'Class comparison is calculated from completed assessments.' }}</p>
                            </div>
                        </section>

                        <section class="rounded-sm border border-outline-variant/10 bg-surface-container-lowest p-6 lg:col-span-7">
                            <h3 class="mb-6 text-sm font-bold uppercase tracking-wider text-on-surface-variant">Trend Summary</h3>
                            <div class="flex min-h-[16rem] flex-col items-center justify-center rounded-xl bg-surface-container-low p-6 text-center">
                                <span class="material-symbols-outlined text-5xl text-outline-variant">show_chart</span>
                                <p class="mt-4 font-headline text-xl font-bold text-on-surface">{{ $submissions->isEmpty() ? 'No trends recorded' : 'Recent progress recorded' }}</p>
                                <p class="mt-2 text-sm text-on-surface-variant">{{ $submissions->isEmpty() ? 'Trend lines and monthly progress will correlate directly to completed student assessments.' : 'The latest submissions are now feeding this report page.' }}</p>
                            </div>
                        </section>

                        <section class="teacher-callout rounded-lg bg-primary p-8 shadow-2xl lg:col-span-5">
                            <div>
                                <div class="mb-6 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-2xl text-tertiary-fixed">auto_awesome</span>
                                    <h3 class="text-xl font-extrabold text-on-primary">System Insight</h3>
                                </div>
                                <div class="rounded-xl bg-white/10 p-6 backdrop-blur-md">
                                    <p class="text-sm leading-relaxed text-on-primary">{{ $submissions->isEmpty() ? 'No AI-generated insights yet. Recommendations only appear after student progress exists in the system.' : 'Report data is now live from saved student assessment results.' }}</p>
                                </div>
                            </div>
                            @if ($students->isNotEmpty())
                                <a class="mt-8 flex items-center justify-center gap-2 rounded-full bg-surface-container-lowest px-6 py-3 text-sm font-bold text-primary transition-colors hover:bg-on-primary" href="{{ route('reports.student', ['student' => $students->first()]) }}">
                                    Open Individual Student Report
                                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                </a>
                            @else
                                <div class="mt-8 flex items-center justify-center gap-2 rounded-full bg-surface-container-lowest/70 px-6 py-3 text-sm font-bold text-on-surface-variant">
                                    No answered student reports yet
                                    <span class="material-symbols-outlined text-sm">hourglass_empty</span>
                                </div>
                            @endif
                        </section>
                    </div>
                </section>
            </main>
        </div>
    </div>
</x-app-layout>
