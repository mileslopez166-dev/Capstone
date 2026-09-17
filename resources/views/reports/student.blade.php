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

                        <div class="teacher-report-actions flex gap-4">
                            <button class="flex items-center gap-2 rounded-sm border-2 border-outline-variant/20 bg-surface-container-lowest px-6 py-3 font-bold text-on-surface transition-all hover:bg-surface-container" type="button">
                                <span class="material-symbols-outlined">print</span>
                                Print
                            </button>
                            <button class="flex items-center gap-2 rounded-sm bg-primary px-6 py-3 font-bold text-on-primary shadow-lg transition-all hover:shadow-primary/20" type="button">
                                <span class="material-symbols-outlined">ios_share</span>
                                Export PDF
                            </button>
                        </div>
                    </section>

                    <section class="teacher-summary-metrics grid grid-cols-1 gap-6 md:grid-cols-4 lg:grid-cols-6">
                        @foreach ([
                            ['title' => 'Literacy', 'value' => 'No Data', 'note' => 'No completed literacy assessments', 'icon' => 'auto_stories', 'tone' => 'primary'],
                            ['title' => 'Numeracy', 'value' => 'No Data', 'note' => 'No completed numeracy assessments', 'icon' => 'calculate', 'tone' => 'error'],
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

                    <div class="grid grid-cols-1 gap-12 lg:grid-cols-2">
                        <div class="space-y-8">
                            <div>
                                <h2 class="mb-6 font-headline text-2xl font-bold">Skill Breakdown</h2>
                                <div class="rounded-lg bg-surface-container-lowest p-8 text-center shadow-sm">
                                    <span class="material-symbols-outlined text-5xl text-outline-variant">insights</span>
                                    <p class="mt-4 font-headline text-xl font-bold text-on-surface">No skill breakdown yet</p>
                                    <p class="mt-2 text-sm text-on-surface-variant">Skill strengths and growth areas will appear after real student assessment results are stored.</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="flex items-center justify-between">
                                <h2 class="font-headline text-2xl font-bold">Completed Activities</h2>
                            </div>
                            @if ($submissions->isEmpty())
                                <div class="rounded-sm bg-surface-container-lowest p-8 text-center shadow-sm">
                                    <span class="material-symbols-outlined text-5xl text-outline-variant">assignment</span>
                                    <p class="mt-4 font-headline text-xl font-bold text-on-surface">No completed activities yet</p>
                                    <p class="mt-2 text-sm text-on-surface-variant">This report page updates from saved assessment submissions.</p>
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach ($submissions as $submission)
                                        @php
                                            $accuracy = $submission->question_count > 0 ? (int) round(($submission->correct_count / $submission->question_count) * 100) : 0;
                                        @endphp
                                        <div class="rounded-sm bg-surface-container-lowest p-5 shadow-sm">
                                            <div class="flex items-center justify-between gap-4">
                                                <div>
                                                    <p class="font-headline text-lg font-bold text-on-surface">{{ $submission->assessment?->title ?? 'Assessment' }}</p>
                                                    <p class="text-sm text-on-surface-variant">{{ $submission->submitted_at?->format('M d, Y') ?? 'Saved result' }}</p>
                                                    <a class="practice-link" href="{{ route('teacher.practice.create', $submission) }}"><span class="material-symbols-outlined" aria-hidden="true">flag</span> Assign practice</a>
                                                </div>
                                                <span class="rounded-full bg-secondary-container/40 px-3 py-1 text-xs font-black text-secondary-dim">{{ $accuracy }}%</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
