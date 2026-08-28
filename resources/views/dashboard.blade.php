<x-app-layout>
    @php
        $teacherName = Auth::user()->name;
        $teacherInitials = collect(explode(' ', $teacherName))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
        $dashboardMetrics = $dashboardMetrics ?? ['average_accuracy' => null, 'students_with_results' => 0, 'needs_attention' => 0, 'total_points' => 0];
        $focusAreas = $focusAreas ?? collect();
        $recentSubmissions = $recentSubmissions ?? collect();
    @endphp

    <div class="min-h-screen lg:flex" x-data="{ mobileMenuOpen: false }">
        <div class="fixed inset-y-0 left-0 z-40 w-72 max-w-[85vw] -translate-x-full transition-transform duration-300 lg:translate-x-0" :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'">
            <x-teacher-sidebar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" active="dashboard" />
        </div>

        <div
            class="fixed inset-0 z-30 bg-slate-950/40 transition-opacity lg:hidden"
            x-show="mobileMenuOpen"
            x-transition.opacity
            @click="mobileMenuOpen = false"
        ></div>

        <main class="min-h-screen flex-1 lg:ml-72">
            <x-teacher-topbar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" search-placeholder="Search student by name...">
                <x-slot:mobileTrigger>
                    <button class="rounded-full bg-surface-container-low p-2 text-on-surface lg:hidden" type="button" @click="mobileMenuOpen = true">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                </x-slot:mobileTrigger>
            </x-teacher-topbar>

            <div class="mx-auto max-w-7xl space-y-8 p-5 sm:p-8">
                <section class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ([
                        ['title' => 'Average Accuracy', 'value' => $dashboardMetrics['average_accuracy'] === null ? 'No Data' : $dashboardMetrics['average_accuracy'].'%', 'note' => 'From completed student submissions', 'icon' => 'trending_up', 'tone' => 'primary'],
                        ['title' => 'Students With Results', 'value' => number_format($dashboardMetrics['students_with_results']), 'note' => 'Students with saved assessment attempts', 'icon' => 'group', 'tone' => 'secondary'],
                        ['title' => 'Needs Attention', 'value' => number_format($dashboardMetrics['needs_attention']), 'note' => 'Students averaging below 75%', 'icon' => 'warning', 'tone' => 'tertiary'],
                        ['title' => 'Total Points', 'value' => number_format($dashboardMetrics['total_points']), 'note' => 'Real EXP from saved results', 'icon' => 'stars', 'tone' => 'on-surface'],
                    ] as $card)
                        <div class="rounded-lg border-l-4 border-{{ $card['tone'] }} bg-surface-container-lowest p-6 shadow-[0_10px_30px_rgba(0,0,0,0.02)]">
                            <div class="mb-4 flex items-start justify-between">
                                <span class="rounded-md bg-surface-container-low p-2 text-{{ $card['tone'] }}">
                                    <span class="material-symbols-outlined">{{ $card['icon'] }}</span>
                                </span>
                            </div>
                            <p class="mb-1 text-xs font-bold uppercase tracking-wider text-on-surface-variant">{{ $card['title'] }}</p>
                            <h3 class="font-headline text-3xl font-bold text-on-surface">{{ $card['value'] }}</h3>
                            <p class="mt-2 text-sm text-on-surface-variant">{{ $card['note'] }}</p>
                        </div>
                    @endforeach
                </section>

                <section class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                    <div class="overflow-hidden rounded-lg bg-surface-container-lowest p-8 shadow-[0_10px_30px_rgba(0,0,0,0.02)] lg:col-span-8">
                        <div class="mb-6">
                            <h4 class="font-headline text-lg font-bold">Student Performance Overview</h4>
                            <p class="text-sm text-on-surface-variant">Real progress updates here when students submit your assessments.</p>
                        </div>

                        @if ($recentSubmissions->isEmpty())
                            <div class="flex min-h-[18rem] flex-col items-center justify-center rounded-xl bg-surface-container-low p-8 text-center">
                                <span class="material-symbols-outlined text-6xl text-outline-variant">bar_chart</span>
                                <h5 class="mt-4 font-headline text-2xl font-bold text-on-surface">No student progress yet</h5>
                                <p class="mt-2 max-w-xl text-sm text-on-surface-variant">This dashboard updates after students complete teacher-prepared assessments.</p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach ($recentSubmissions as $submission)
                                    @php
                                        $accuracy = $submission->question_count > 0 ? (int) round(($submission->correct_count / $submission->question_count) * 100) : 0;
                                    @endphp
                                    <div class="rounded-xl bg-surface-container-low p-4">
                                        <div class="flex items-center justify-between gap-4">
                                            <div>
                                                <p class="font-bold text-on-surface">{{ $submission->student?->name ?? 'Student' }}</p>
                                                <p class="text-xs text-on-surface-variant">{{ $submission->assessment?->title ?? 'Assessment' }}</p>
                                            </div>
                                            <span class="rounded-full bg-secondary-container/40 px-3 py-1 text-xs font-black text-secondary-dim">{{ $accuracy }}%</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="rounded-lg bg-primary p-8 text-on-primary lg:col-span-4">
                        <h4 class="mb-2 font-headline text-lg font-bold">Focus Areas</h4>
                        <p class="mb-8 text-sm opacity-80">No gaps can be identified until student results are recorded.</p>

                        <div class="space-y-5">
                            @forelse ($focusAreas as $area)
                                <div>
                                    <div class="mb-2 flex justify-between text-xs font-bold uppercase tracking-widest">
                                        <span>{{ $area['label'] }}</span>
                                        <span>{{ $area['accuracy'] === null ? 'No Data' : $area['accuracy'].'%' }}</span>
                                    </div>
                                    <div class="h-3 w-full overflow-hidden rounded-full bg-on-primary/20">
                                        <div class="h-full rounded-full bg-white/40" style="width: {{ $area['accuracy'] ?? 0 }}%"></div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-on-primary/80">No focus-area data yet.</p>
                            @endforelse
                        </div>

                        <div class="mt-10 rounded-md bg-white/10 p-4 backdrop-blur-md">
                            <p class="text-sm font-medium leading-relaxed">{{ $recentSubmissions->isEmpty() ? 'Assign assessments first to unlock recommendations and class-wide focus analysis.' : 'Focus analysis is based on saved student submissions.' }}</p>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-lg bg-surface-container-lowest shadow-[0_20px_40px_rgba(0,0,0,0.03)]">
                    <div class="flex flex-col items-start justify-between gap-4 border-b border-outline-variant/10 p-8 md:flex-row md:items-center">
                        <div>
                            <h4 class="font-headline text-xl font-extrabold text-on-surface">Student Roster</h4>
                            <p class="text-sm font-medium text-on-surface-variant">Real-time student accounts from the database. Progress stays empty until assessments are completed.</p>
                        </div>
                        <a class="rounded-md bg-primary px-6 py-2.5 text-xs font-bold text-on-primary transition-all hover:bg-primary-dim" href="{{ route('students.index') }}">
                            OPEN ROSTER
                        </a>
                    </div>

                    @if ($students->isEmpty())
                        <div class="flex min-h-[22rem] flex-col items-center justify-center p-8 text-center">
                            <span class="material-symbols-outlined text-6xl text-outline-variant">school</span>
                            <h5 class="mt-4 font-headline text-2xl font-bold text-on-surface">No student accounts yet</h5>
                            <p class="mt-2 max-w-2xl text-sm text-on-surface-variant">When students create accounts, they will appear here automatically from the database.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse text-left">
                                <thead>
                                    <tr class="bg-surface-container-low/50">
                                        <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Student Name</th>
                                        <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Email</th>
                                        <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Progress</th>
                                        <th class="px-8 py-4 text-right text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/5">
                                    @foreach ($students as $student)
                                        @php
                                            $initials = collect(explode(' ', $student->name))
                                                ->filter()
                                                ->take(2)
                                                ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                                                ->implode('');
                                        @endphp
                                        <tr class="group transition-colors hover:bg-surface-container-low/30">
                                            <td class="px-8 py-5">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-container/20 font-bold text-primary">{{ $initials }}</div>
                                                    <div>
                                                        <a class="font-bold text-on-surface transition-colors hover:text-primary" href="{{ route('students.show', $student) }}">{{ $student->name }}</a>
                                                        <p class="text-xs text-on-surface-variant">Student Account</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-8 py-5 text-sm text-on-surface">{{ $student->email }}</td>
                                            <td class="px-8 py-5">
                                                <span class="rounded-full bg-surface-container-high px-3 py-1 text-[10px] font-black uppercase text-on-surface-variant">{{ $student->average_accuracy === null ? 'No Data Yet' : $student->average_accuracy.'% Avg' }}</span>
                                            </td>
                                            <td class="px-8 py-5 text-right">
                                                <a class="inline-flex p-2 text-on-surface-variant transition-colors hover:text-primary" href="{{ route('students.show', $student) }}">
                                                    <span class="material-symbols-outlined">open_in_new</span>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                <section class="grid grid-cols-1 gap-6 pb-12 md:grid-cols-2">
                    <div class="rounded-lg border border-outline-variant/20 bg-surface-container-high p-8">
                        <div class="flex items-start gap-4">
                            <div class="rounded-lg bg-primary p-4 text-on-primary">
                                <span class="material-symbols-outlined">assignment</span>
                            </div>
                            <div>
                            <div class="flex items-center justify-between gap-4">
                                <h5 class="font-headline font-bold">Assessment Workflow</h5>
                                <span class="rounded-full bg-primary/10 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-primary">{{ $assessmentCount }} Created</span>
                            </div>
                            <p class="mt-2 text-sm text-on-surface-variant">Build literacy or numeracy assessments here first. Published assessments will appear in the student activity queue.</p>
                            <a class="mt-4 inline-flex rounded-md bg-primary px-4 py-2 text-xs font-bold uppercase tracking-widest text-on-primary transition-colors hover:bg-primary-dim" href="{{ route('assessments.index') }}">
                                Open Assessment Maker
                            </a>
                        </div>
                    </div>
                    </div>

                    <div class="rounded-lg bg-secondary p-8 text-white shadow-lg shadow-secondary/20">
                        <div class="flex items-start gap-4">
                            <div class="rounded-lg bg-white/20 p-4 text-white">
                                <span class="material-symbols-outlined">info</span>
                            </div>
                            <div>
                                <h5 class="font-headline font-bold">System Status</h5>
                                <p class="mt-2 text-sm opacity-90">{{ $recentSubmissions->isEmpty() ? 'The teacher side is waiting for real student progress records.' : 'Teacher dashboard is connected to live student assessment submissions.' }}</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</x-app-layout>
