<x-app-layout>
    @php
        $hasQuery = filled($query);
        $hasResults = $quickLinks->isNotEmpty() || $assessments->isNotEmpty() || $submissions->isNotEmpty();
    @endphp

    <div class="min-h-screen overflow-x-hidden bg-background font-body text-on-surface selection:bg-primary-container/30">
        <x-student-nav active="home" />

        <main class="min-h-screen px-4 py-8 pb-32 sm:px-8 lg:ml-72 lg:px-12">
            <div class="mx-auto max-w-7xl space-y-8">
                <section class="rounded-lg bg-surface-container-lowest p-6 shadow-[0_20px_40px_rgba(0,94,159,0.06)] sm:p-8">
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-primary">Student Search</p>
                    <h1 class="mt-2 font-headline text-3xl font-extrabold text-on-surface sm:text-4xl">{{ $hasQuery ? 'Results for "'.$query.'"' : 'Search your student space' }}</h1>
                    <form class="mt-6 flex flex-col gap-3 sm:flex-row" method="GET" action="{{ route('student.search') }}">
                        <label class="sr-only" for="student-search-page-input">Search</label>
                        <input id="student-search-page-input" class="min-w-0 flex-1 rounded-lg border-none bg-surface-container-low px-4 py-3 text-on-surface shadow-inner focus:ring-2 focus:ring-primary" name="q" type="search" value="{{ $query }}" placeholder="Try assessment title, leaderboard, rewards, or profile">
                        <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-3 font-bold text-on-primary shadow-lg shadow-primary/20 transition-colors hover:bg-primary-dim" type="submit">
                            <span class="material-symbols-outlined text-lg">search</span>
                            Search
                        </button>
                    </form>
                </section>

                @if (! $hasQuery)
                    <section class="rounded-lg bg-surface-container-low p-8 text-center">
                        <span class="material-symbols-outlined text-5xl text-primary">travel_explore</span>
                        <h2 class="mt-4 font-headline text-2xl font-bold text-on-surface">Search your activities</h2>
                        <p class="mx-auto mt-2 max-w-2xl text-sm text-on-surface-variant">Find assessments, recorded outputs, leaderboard, rewards, profile, and support pages.</p>
                    </section>
                @elseif (! $hasResults)
                    <section class="rounded-lg bg-surface-container-low p-8 text-center">
                        <span class="material-symbols-outlined text-5xl text-outline-variant">search_off</span>
                        <h2 class="mt-4 font-headline text-2xl font-bold text-on-surface">No matches found</h2>
                        <p class="mt-2 text-sm text-on-surface-variant">Try an assessment title, quiz type, subject, or page name.</p>
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
                            <h2 class="font-headline text-xl font-extrabold text-on-surface">Available Assessments</h2>
                            <div class="mt-4 space-y-3">
                                @forelse ($assessments as $assessment)
                                    <a class="flex items-center justify-between gap-4 rounded-xl bg-surface-container-low p-4 transition-colors hover:bg-primary-container/15" href="{{ $assessment->can_open ? route('student.assessments.show', $assessment) : route('student.activities') }}">
                                        <span>
                                            <span class="block font-bold text-on-surface">{{ $assessment->title }}</span>
                                            <span class="block text-sm text-on-surface-variant">{{ ucfirst($assessment->subject) }} | {{ str($assessment->quiz_type)->replace('_', ' ')->title() }} | {{ $assessment->can_open ? 'Ready to open' : 'Already answered' }}</span>
                                        </span>
                                        <span class="material-symbols-outlined text-primary">assignment</span>
                                    </a>
                                @empty
                                    <p class="text-sm text-on-surface-variant">No matching assessments.</p>
                                @endforelse
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg bg-surface-container-lowest p-6 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                        <h2 class="font-headline text-xl font-extrabold text-on-surface">Recorded Outputs</h2>
                        <div class="mt-4 space-y-3">
                            @forelse ($submissions as $submission)
                                <a class="flex items-center justify-between gap-4 rounded-xl bg-surface-container-low p-4 transition-colors hover:bg-primary-container/15" href="{{ route('student.activities') }}">
                                    <span>
                                        <span class="block font-bold text-on-surface">{{ $submission->assessment?->title ?? 'Assessment' }}</span>
                                        <span class="block text-sm text-on-surface-variant">{{ $submission->correct_count }}/{{ $submission->question_count }} correct | {{ number_format($submission->points) }} points</span>
                                    </span>
                                    <span class="material-symbols-outlined text-primary">history</span>
                                </a>
                            @empty
                                <p class="text-sm text-on-surface-variant">No matching recorded outputs.</p>
                            @endforelse
                        </div>
                    </section>
                @endif
            </div>
        </main>
    </div>
</x-app-layout>