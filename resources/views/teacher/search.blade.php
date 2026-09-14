<x-app-layout>
    @php
        $teacherName = Auth::user()->name;
        $teacherInitials = collect(explode(' ', $teacherName))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
        $hasQuery = filled($query);
        $hasResults = $quickLinks->isNotEmpty() || $students->isNotEmpty() || $assessments->isNotEmpty() || $submissions->isNotEmpty();
    @endphp

    <div class="min-h-screen bg-background lg:flex">
        <x-teacher-sidebar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" active="dashboard" />

        <main class="min-h-screen flex-1 lg:ml-72">
            <x-teacher-topbar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" search-placeholder="Search students, assessments, reports..." />

            <div class="mx-auto max-w-7xl space-y-8 p-5 sm:p-8">
                <section class="rounded-lg bg-surface-container-lowest p-6 shadow-[0_20px_40px_rgba(0,94,159,0.06)] sm:p-8">
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-primary">Teacher Search</p>
                    <h1 class="mt-2 font-headline text-3xl font-extrabold text-on-surface sm:text-4xl">{{ $hasQuery ? 'Results for "'.$query.'"' : 'Search your teacher workspace' }}</h1>
                    <form class="mt-6 flex flex-col gap-3 sm:flex-row" method="GET" action="{{ route('teacher.search') }}">
                        <label class="sr-only" for="teacher-search-page-input">Search</label>
                        <input id="teacher-search-page-input" class="min-w-0 flex-1 rounded-lg border-none bg-surface-container-low px-4 py-3 text-on-surface shadow-inner focus:ring-2 focus:ring-primary" name="q" type="search" value="{{ $query }}" placeholder="Try student name, assessment title, reports, or add student">
                        <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-3 font-bold text-on-primary shadow-lg shadow-primary/20 transition-colors hover:bg-primary-dim" type="submit">
                            <span class="material-symbols-outlined text-lg">search</span>
                            Search
                        </button>
                    </form>
                </section>

                @if (! $hasQuery)
                    <section class="rounded-lg bg-surface-container-low p-8 text-center">
                        <span class="material-symbols-outlined text-5xl text-primary">manage_search</span>
                        <h2 class="mt-4 font-headline text-2xl font-bold text-on-surface">Find records faster</h2>
                        <p class="mx-auto mt-2 max-w-2xl text-sm text-on-surface-variant">Search students, assessment titles, reports, sections, quiz types, or pages like leaderboard, support, and profile.</p>
                    </section>
                @elseif (! $hasResults)
                    <section class="rounded-lg bg-surface-container-low p-8 text-center">
                        <span class="material-symbols-outlined text-5xl text-outline-variant">search_off</span>
                        <h2 class="mt-4 font-headline text-2xl font-bold text-on-surface">No matches found</h2>
                        <p class="mt-2 text-sm text-on-surface-variant">Try a student name, email, assessment title, quiz type, section, or page name.</p>
                    </section>
                @else
                    <section class="grid gap-6 lg:grid-cols-2">
                        <div class="rounded-lg bg-surface-container-lowest p-6 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                            <h2 class="font-headline text-xl font-extrabold text-on-surface">Pages</h2>
                            <div class="mt-4 space-y-3">
                                @forelse ($quickLinks as $link)
                                    <a class="flex items-center justify-between gap-4 rounded-xl bg-surface-container-low p-4 transition-colors hover:bg-primary-container/15" href="{{ $link['href'] }}">
                                        <span>
                                            <span class="block font-bold text-on-surface">{{ $link['label'] }}</span>
                                            <span class="block text-sm text-on-surface-variant">{{ $link['description'] }}</span>
                                        </span>
                                        <span class="material-symbols-outlined text-primary">arrow_forward</span>
                                    </a>
                                @empty
                                    <p class="text-sm text-on-surface-variant">No matching pages.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="rounded-lg bg-surface-container-lowest p-6 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                            <h2 class="font-headline text-xl font-extrabold text-on-surface">Students</h2>
                            <div class="mt-4 space-y-3">
                                @forelse ($students as $student)
                                    <a class="flex items-center justify-between gap-4 rounded-xl bg-surface-container-low p-4 transition-colors hover:bg-primary-container/15" href="{{ route('students.show', $student) }}">
                                        <span>
                                            <span class="block font-bold text-on-surface">{{ $student->name }}</span>
                                            <span class="block text-sm text-on-surface-variant">{{ $student->email }}{{ $student->section ? ' | '.$student->section : '' }}</span>
                                        </span>
                                        <span class="material-symbols-outlined text-primary">open_in_new</span>
                                    </a>
                                @empty
                                    <p class="text-sm text-on-surface-variant">No matching students.</p>
                                @endforelse
                            </div>
                        </div>
                    </section>

                    <section class="grid gap-6 lg:grid-cols-2">
                        <div class="rounded-lg bg-surface-container-lowest p-6 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                            <h2 class="font-headline text-xl font-extrabold text-on-surface">Assessments</h2>
                            <div class="mt-4 space-y-3">
                                @forelse ($assessments as $assessment)
                                    <a class="flex items-center justify-between gap-4 rounded-xl bg-surface-container-low p-4 transition-colors hover:bg-primary-container/15" href="{{ route('assessments.show', $assessment) }}">
                                        <span>
                                            <span class="block font-bold text-on-surface">{{ $assessment->title }}</span>
                                            <span class="block text-sm text-on-surface-variant">{{ ucfirst($assessment->subject) }} | {{ str($assessment->quiz_type)->replace('_', ' ')->title() }} | {{ ucfirst($assessment->status) }}</span>
                                        </span>
                                        <span class="material-symbols-outlined text-primary">assignment</span>
                                    </a>
                                @empty
                                    <p class="text-sm text-on-surface-variant">No matching assessments.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="rounded-lg bg-surface-container-lowest p-6 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                            <h2 class="font-headline text-xl font-extrabold text-on-surface">Reports</h2>
                            <div class="mt-4 space-y-3">
                                @forelse ($submissions as $submission)
                                    <a class="flex items-center justify-between gap-4 rounded-xl bg-surface-container-low p-4 transition-colors hover:bg-primary-container/15" href="{{ route('reports.student', $submission->student) }}">
                                        <span>
                                            <span class="block font-bold text-on-surface">{{ $submission->student?->name ?? 'Student' }}</span>
                                            <span class="block text-sm text-on-surface-variant">{{ $submission->assessment?->title ?? 'Assessment' }} | {{ $submission->correct_count }}/{{ $submission->question_count }} correct</span>
                                        </span>
                                        <span class="material-symbols-outlined text-primary">bar_chart</span>
                                    </a>
                                @empty
                                    <p class="text-sm text-on-surface-variant">No matching reports.</p>
                                @endforelse
                            </div>
                        </div>
                    </section>
                @endif
            </div>
        </main>
    </div>
</x-app-layout>