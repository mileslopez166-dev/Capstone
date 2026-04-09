<x-app-layout>
    @php
        $teacherName = Auth::user()->name;
        $teacherInitials = collect(explode(' ', $teacherName))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
    @endphp

    <div class="min-h-screen bg-background">
        <x-teacher-topbar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" search-placeholder="Search student by name..." />

        <div class="flex min-h-screen">
            <x-teacher-sidebar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" active="students" />

            <main class="flex-1 p-4 md:p-8 lg:ml-64 lg:p-12">
                <section class="mb-12 grid grid-cols-1 items-end gap-8 lg:grid-cols-12">
                    <div class="lg:col-span-8">
                        <a class="mb-4 flex items-center gap-2 font-bold text-primary" href="{{ route('dashboard') }}">
                            <span class="material-symbols-outlined">arrow_back</span>
                            <span class="font-headline text-sm uppercase tracking-widest">Student Directory</span>
                        </a>
                        <h1 class="mb-2 font-headline text-5xl font-extrabold tracking-tight text-on-surface md:text-6xl">{{ $student->name }}</h1>
                        <div class="mt-4 flex flex-wrap items-center gap-4">
                            <span class="rounded-full bg-primary-container px-4 py-1.5 text-sm font-bold text-on-primary-container">Student Account</span>
                            <span class="rounded-full bg-surface-container-high px-4 py-1.5 text-sm font-bold text-on-surface-variant">No activity data yet</span>
                            <span class="flex items-center gap-2 text-sm font-medium text-slate-400">
                                <span class="material-symbols-outlined text-sm">alternate_email</span>{{ $student->email }}
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-end lg:col-span-4">
                        <div class="w-full rounded-lg border border-outline-variant/10 bg-surface-container-lowest p-6 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                            <p class="mb-4 text-xs font-bold uppercase tracking-widest text-slate-400">Student Progress</p>
                            <div class="flex items-baseline gap-2">
                                <span class="font-headline text-4xl font-black text-primary">No Data</span>
                            </div>
                            <p class="mt-3 text-sm text-on-surface-variant">This student has not completed any recorded assessments yet.</p>
                        </div>
                    </div>
                </section>

                <section class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg border border-outline-variant/10 bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,94,159,0.06)] md:col-span-2">
                        <div class="mb-8 flex items-start justify-between">
                            <div>
                                <h3 class="font-headline text-xl font-bold">Score Trends</h3>
                                <p class="text-sm text-on-surface-variant">No completed activities have been recorded for this student.</p>
                            </div>
                            <span class="material-symbols-outlined text-primary">trending_up</span>
                        </div>
                        <div class="flex min-h-[12rem] flex-col items-center justify-center rounded-xl bg-surface-container-low p-6 text-center">
                            <span class="material-symbols-outlined text-5xl text-outline-variant">show_chart</span>
                            <p class="mt-4 font-headline text-xl font-bold text-on-surface">No score history yet</p>
                        </div>
                    </div>

                    <div class="flex flex-col justify-between rounded-lg border border-outline-variant/10 bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                        <div>
                            <h3 class="font-headline text-xl font-bold">Accuracy</h3>
                            <p class="text-sm text-on-surface-variant">Awaiting first submitted answers</p>
                        </div>
                        <div class="flex flex-col items-center py-4">
                            <div class="relative flex h-32 w-32 items-center justify-center">
                                <svg class="h-full w-full -rotate-90">
                                    <circle class="text-surface-container-highest" cx="64" cy="64" r="56" fill="transparent" stroke="currentColor" stroke-width="12"></circle>
                                    <circle class="text-secondary" cx="64" cy="64" r="56" fill="transparent" stroke="currentColor" stroke-width="12" stroke-dasharray="351.85" stroke-dashoffset="351.85"></circle>
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-3xl font-black text-on-surface">0%</span>
                                    <span class="text-[10px] font-bold uppercase text-on-surface-variant">No Data</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="relative flex flex-col justify-between overflow-hidden rounded-lg bg-primary p-8 text-on-primary shadow-xl">
                        <div>
                            <div class="mb-2 flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">bolt</span>
                                <span class="text-xs font-bold uppercase tracking-tighter">Teacher Action</span>
                            </div>
                            <h3 class="font-headline text-xl font-bold leading-tight">Assign an assessment first</h3>
                        </div>
                        <p class="mt-4 text-sm leading-relaxed text-on-primary/80">Recommendations will only become meaningful after this student completes teacher-prepared assessments.</p>
                        <a class="mt-6 block w-full rounded-sm bg-white px-4 py-2 text-center text-sm font-bold text-primary" href="{{ route('reports.index') }}">Open Reports</a>
                    </div>
                </section>

                <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <div class="space-y-6 lg:col-span-2">
                        <div class="mb-2 flex items-center justify-between">
                            <h2 class="font-headline text-2xl font-bold">Completed Activities</h2>
                        </div>

                        <div class="rounded-lg border border-outline-variant/10 bg-surface-container-lowest p-8 text-center shadow-sm">
                            <span class="material-symbols-outlined text-5xl text-outline-variant">assignment</span>
                            <p class="mt-4 font-headline text-xl font-bold text-on-surface">No completed activities yet</p>
                            <p class="mt-2 text-sm text-on-surface-variant">This student page will begin to reflect real progress after assessments are assigned and submitted.</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <h2 class="font-headline text-2xl font-bold">Skill Breakdown</h2>
                        <div class="rounded-lg border border-outline-variant/10 bg-surface-container-lowest p-8 text-center shadow-sm">
                            <span class="material-symbols-outlined text-5xl text-outline-variant">psychology</span>
                            <p class="mt-4 font-headline text-xl font-bold text-on-surface">No skill data yet</p>
                            <p class="mt-2 text-sm text-on-surface-variant">Skill progress will correlate to student submissions once the assessment system starts storing results.</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
