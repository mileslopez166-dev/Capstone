<x-app-layout>
    @php
        $teacherName = Auth::user()->name;
        $teacherInitials = collect(explode(' ', $teacherName))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
        $submissions = $submissions ?? collect();
        $studentMetrics = $studentMetrics ?? ['completed_count' => 0, 'average_accuracy' => null, 'total_points' => 0];
        $subjectBreakdown = $subjectBreakdown ?? collect();
        $literacyMetrics = $subjectBreakdown->firstWhere('label', 'Literacy') ?? ['accuracy' => null, 'count' => 0];
        $numeracyMetrics = $subjectBreakdown->firstWhere('label', 'Numeracy') ?? ['accuracy' => null, 'count' => 0];
        $subjectCard = function (array $metrics, string $subject): array {
            $count = (int) ($metrics['count'] ?? 0);
            $accuracy = $metrics['accuracy'] ?? null;

            return [
                'value' => $count === 0 ? 'No Data' : ($accuracy === null ? 'Awaiting Score' : $accuracy.'%'),
                'note' => $count === 0
                    ? "No completed {$subject} assessments"
                    : $count.' completed '.$subject.' '.($count === 1 ? 'assessment' : 'assessments'),
            ];
        };
        $literacyCard = $subjectCard($literacyMetrics, 'literacy');
        $numeracyCard = $subjectCard($numeracyMetrics, 'numeracy');
        $studentInitials = collect(explode(' ', $student->name))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');
    @endphp

    <div class="min-h-screen bg-surface">
        <div class="flex min-h-screen">
            <x-teacher-sidebar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" active="reports" />

            <main class="min-h-screen flex-1 lg:ml-72">
                <x-teacher-topbar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" search-placeholder="Search reports..." />

                <div class="mx-auto max-w-7xl space-y-12 p-8 lg:p-12">
                    <section class="teacher-workspace-heading flex flex-col justify-between gap-8 md:flex-row md:items-end">
                        <div class="space-y-4">
                            <nav class="flex items-center gap-2 text-sm font-medium text-on-surface-variant">
                                <a class="hover:text-primary" href="{{ route('reports.index') }}">Reports</a>
                                <span class="material-symbols-outlined text-sm">chevron_right</span>
                                <span class="text-primary">{{ $student->name }}</span>
                            </nav>
                            <div class="teacher-report-identity flex items-center gap-6">
                                <div class="flex h-24 w-24 items-center justify-center rounded-lg border-4 border-surface-container-lowest bg-primary-container/20 shadow-xl">
                                    <span class="font-headline text-2xl font-black text-on-primary-container">{{ $studentInitials ?: 'S' }}</span>
                                </div>
                                <div>
                                    <h1 class="mb-1 font-headline text-5xl font-extrabold tracking-tight text-on-surface">{{ $student->name }}</h1>
                                    <p class="text-xl font-medium text-on-surface-variant">Grade 6{{ $student->section ? ' - '.$student->section : '' }} | ID: #{{ str_pad($student->id, 4, '0', STR_PAD_LEFT) }}</p>
                                </div>
                            </div>
                        </div>

                    </section>

                    <section class="teacher-summary-metrics grid grid-cols-1 gap-6 md:grid-cols-4 lg:grid-cols-6">
                        @foreach ([
                            ['title' => 'Literacy', 'value' => $literacyCard['value'], 'note' => $literacyCard['note'], 'icon' => 'auto_stories', 'tone' => 'primary'],
                            ['title' => 'Numeracy', 'value' => $numeracyCard['value'], 'note' => $numeracyCard['note'], 'icon' => 'calculate', 'tone' => 'error'],
                            ['title' => 'Average Accuracy', 'value' => $studentMetrics['average_accuracy'] === null ? '0%' : $studentMetrics['average_accuracy'].'%', 'note' => $studentMetrics['completed_count'].' completed assessments', 'icon' => 'target', 'tone' => 'secondary'],
                            ['title' => 'Total Points', 'value' => number_format($studentMetrics['total_points']), 'note' => 'Saved assessment points', 'icon' => 'stars', 'tone' => 'tertiary'],
                        ] as $card)
                            <div class="rounded-sm bg-surface-container-lowest p-8 shadow-sm md:col-span-2 lg:col-span-3">
                                <div class="mb-6 flex items-center gap-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-surface-container-low text-{{ $card['tone'] }}">
                                        <span class="material-symbols-outlined">{{ $card['icon'] }}</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold uppercase tracking-widest text-on-surface-variant">{{ $card['title'] }}</div>
                                        <div class="text-2xl font-bold">{{ $card['value'] }}</div>
                                    </div>
                                </div>
                                <p class="text-sm text-on-surface-variant">{{ $card['note'] }}</p>
                            </div>
                        @endforeach
                    </section>

                    <x-reading-progress-card :progress="$readingProgress" audience="teacher" />
                    <x-reading-progress-card :progress="$numeracyProgress" audience="teacher" subject="numeracy" />

                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                        <div class="teacher-callout relative overflow-hidden rounded-sm bg-on-primary-container p-10 text-on-primary lg:col-span-2">
                            <div class="relative z-10">
                                <div class="mb-6 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary-fixed">psychology</span>
                                    <h2 class="font-headline text-2xl font-bold tracking-tight">Student Report Status</h2>
                                </div>
                                <div class="rounded-sm bg-white/10 p-6 backdrop-blur-md">
                                    <p class="text-sm leading-relaxed text-on-primary/90">{{ $submissions->isEmpty() ? 'No individual recommendations yet. This report will update after this student completes your assessments.' : 'This report is using saved assessment submissions for this student.' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-sm bg-surface-container-low p-8">
                            <h2 class="mb-6 font-headline text-xl font-bold">Achievements</h2>
                            <div class="rounded-xl bg-surface-container-lowest p-6 text-center">
                                <span class="material-symbols-outlined text-5xl text-outline-variant">workspace_premium</span>
                                <p class="mt-4 font-headline text-xl font-bold text-on-surface">No student achievements yet</p>
                                <p class="mt-2 text-sm text-on-surface-variant">This will remain empty until the student completes activities that generate reportable milestones.</p>
                            </div>
                        </div>
                    </div>

                    <section class="space-y-6" aria-labelledby="completed-activities-heading">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 id="completed-activities-heading" class="font-headline text-2xl font-bold">Completed Activities</h2>
                                <p class="mt-1 text-sm text-on-surface-variant">Select an activity to view its full result and teacher action plan.</p>
                            </div>
                            <span class="rounded-full bg-primary-container/30 px-3 py-1 text-xs font-bold text-primary">{{ $submissions->count() }} completed</span>
                        </div>
                        @if ($submissions->isEmpty())
                            <div class="rounded-sm bg-surface-container-lowest p-8 text-center shadow-sm">
                                <span class="material-symbols-outlined text-5xl text-outline-variant">assignment</span>
                                <p class="mt-4 font-headline text-xl font-bold text-on-surface">No completed activities yet</p>
                                <p class="mt-2 text-sm text-on-surface-variant">This report page updates from saved assessment submissions.</p>
                            </div>
                        @else
                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-completed-activities-grid>
                                @foreach ($submissions as $submission)
                                    @php
                                        $accuracy = $submission->scorePercentage();
                                        $activityShouldOpen = (int) session('intervention_saved') === $submission->id
                                            || $errors->getBag('intervention-'.$submission->id)->any();
                                    @endphp
                                    <details
                                        id="submission-{{ $submission->id }}"
                                        class="group overflow-hidden rounded-lg border border-outline-variant/20 bg-surface-container-lowest shadow-sm transition-shadow open:shadow-lg [&[open]]:col-span-full"
                                        data-completed-activity
                                        x-data
                                        x-init="$nextTick(() => { if (window.location.hash) { const target = document.querySelector(window.location.hash); if (target && $el.contains(target)) $el.open = true; } })"
                                        @if ($activityShouldOpen) open @endif
                                    >
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 marker:content-none [&::-webkit-details-marker]:hidden">
                                            <div class="min-w-0">
                                                <p class="truncate font-headline text-lg font-bold text-on-surface">{{ $submission->assessment?->title ?? 'Assessment' }}</p>
                                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-on-surface-variant">
                                                    <span>{{ $submission->submitted_at?->format('M d, Y') ?? 'Saved result' }}</span>
                                                    <span class="font-bold uppercase tracking-wide">{{ ucfirst($submission->assessment?->subject ?? 'Assessment') }}</span>
                                                    <span>Attempt {{ $submission->attempt_number }}</span>
                                                </div>
                                            </div>
                                            <div class="flex shrink-0 items-center gap-2">
                                                <span class="rounded-full bg-secondary-container/40 px-3 py-1 text-xs font-black text-secondary-dim">{{ $accuracy === null ? 'Awaiting score' : $accuracy.'%' }}</span>
                                                <span class="material-symbols-outlined text-on-surface-variant transition-transform group-open:rotate-180" aria-hidden="true">expand_more</span>
                                            </div>
                                        </summary>
                                        <div class="border-t border-outline-variant/15 p-5">
                                            <div class="mb-4 flex flex-wrap gap-3">
                                                <a class="practice-link" href="{{ route('teacher.practice.create', $submission) }}"><span class="material-symbols-outlined" aria-hidden="true">flag</span> Assign practice</a>
                                                @if ($submission->assessment?->subject === 'literacy')
                                                    <a class="practice-link" href="{{ route('teacher.phil-iri.show', $submission) }}"><span class="material-symbols-outlined" aria-hidden="true">rule</span>Phil-IRI scoring</a>
                                                @endif
                                            </div>
                                            <x-phil-iri-result :result="\App\Support\PhilIri::forSubmission($submission)" :submission="$submission" />
                                            <x-intervention-plan :submission="$submission" :assessments="$followUpAssessments" return-to="report" />
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                        @endif
                    </section>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
