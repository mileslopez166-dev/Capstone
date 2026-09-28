<x-app-layout>
    @php
        $teacher = Auth::user();
        $teacherName = $teacher->name;
        $teacherInitials = collect(explode(' ', $teacherName))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
    @endphp

    <div class="min-h-screen bg-background">
        <div class="flex min-h-screen">
            <x-teacher-sidebar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" active="students" />

            <main class="min-h-screen flex-1 lg:ml-72">
                <x-teacher-topbar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" search-placeholder="Search student accounts..." />

                <div class="mx-auto max-w-7xl space-y-8 p-5 sm:p-8">
                    <section class="teacher-workspace-heading flex flex-col justify-between gap-4 md:flex-row md:items-end">
                        <div>
                            <h1 class="font-headline text-4xl font-extrabold tracking-tight text-on-surface">Student List</h1>
                            <p class="mt-2 text-on-surface-variant">Student accounts and assessment progress.</p>
                        </div>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div class="rounded-lg bg-surface-container-low px-5 py-4">
                                <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Student Accounts</p>
                                <p class="mt-2 font-headline text-3xl font-black text-primary">{{ $students->count() }}</p>
                            </div>
                            <a class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-3 text-sm font-bold text-on-primary shadow-lg shadow-primary/20 transition-colors hover:bg-primary-dim" href="{{ route('students.create') }}">
                                <span class="material-symbols-outlined text-lg">person_add</span>
                                Add Student
                            </a>
                        </div>
                    </section>

                    @if (session('status'))
                        <div class="rounded-lg border border-secondary/20 bg-secondary-container/30 px-4 py-3 text-sm font-medium text-on-surface">
                            {{ session('status') }}
                        </div>
                    @endif


                    <section class="overflow-hidden rounded-lg bg-surface-container-lowest shadow-[0_20px_40px_rgba(0,0,0,0.03)]">
                        <div class="border-b border-outline-variant/10 p-8">
                            <h4 class="font-headline text-xl font-extrabold text-on-surface">Student Roster</h4>
                            <p class="mt-2 text-sm font-medium text-on-surface-variant">Real-time student accounts with progress from your assessment submissions.</p>
                            <p class="mt-2 text-sm text-on-surface-variant">Overall ML estimates use your assessments only. They are provisional teaching support, not official grades or a validated overall diagnosis.</p>
                        </div>

                        @if ($students->isEmpty())
                            <div class="flex min-h-[20rem] flex-col items-center justify-center p-8 text-center">
                                <span class="material-symbols-outlined text-6xl text-outline-variant">school</span>
                                <h5 class="mt-4 font-headline text-2xl font-bold text-on-surface">No student accounts yet</h5>
                                <p class="mt-2 max-w-xl text-sm text-on-surface-variant">As soon as students register in the system, they will appear here automatically.</p>
                            </div>
                        @else
                            <x-data-table-scroll label="Student roster">
                                <table class="w-full min-w-[640px] border-collapse text-left">
                                    <thead>
                                        <tr class="bg-surface-container-low/50">
                                            <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Student Name</th>
                                            <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Email</th>
                                            <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Status</th>
                                            <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Overall ML Estimate</th>
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
                                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-container/20 font-bold text-primary">{{ $initials }}</div>
                                                        <div>
                                                            <a class="font-bold text-on-surface transition-colors hover:text-primary" href="{{ route('students.show', $student) }}">{{ $student->name }}</a>
                                                            <p class="text-xs text-on-surface-variant">Student Account</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="ui-table-copy px-8 py-5 text-sm text-on-surface">{{ $student->email }}</td>
                                                <td class="px-8 py-5">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="ui-table-badge inline-flex items-center gap-2 rounded-full bg-surface-container-high px-3 py-1 text-[10px] font-black uppercase text-on-surface-variant">
                                                            <span class="h-2.5 w-2.5 rounded-full {{ $student->is_online ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                                            {{ $student->is_online ? 'Online' : 'Offline' }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="px-8 py-5"><x-teacher-ml-level :student="$student" /></td>
                                                <td class="px-8 py-5 text-right">
                                                    <a class="inline-flex p-2 text-on-surface-variant transition-colors hover:text-primary" href="{{ route('students.show', $student) }}">
                                                        <span class="material-symbols-outlined">open_in_new</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </x-data-table-scroll>
                        @endif
                    </section>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
