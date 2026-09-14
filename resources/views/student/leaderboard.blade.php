<x-app-layout>
    @php
        $scope = $scope ?? 'all';
        $scopeLabel = $scopeLabel ?? 'All Sections';
        $leaderboard = $leaderboard ?? collect();
        $currentStudentRank = $currentStudentRank ?? null;
        $scopes = [
            'all' => 'All',
            'section_a' => 'Section A',
            'section_b' => 'Section B',
            'section_c' => 'Section C',
        ];
    @endphp

    <style>
        @keyframes leaderboardFlame {
            0%, 100% { box-shadow: 0 0 18px rgba(239, 68, 68, 0.35), 0 0 32px rgba(245, 158, 11, 0.28); transform: translateY(0); }
            50% { box-shadow: 0 0 28px rgba(239, 68, 68, 0.55), 0 0 52px rgba(245, 158, 11, 0.38); transform: translateY(-1px); }
        }

        @keyframes leaderboardShine {
            0% { transform: translateX(-120%); }
            100% { transform: translateX(140%); }
        }

        .leaderboard-flaming {
            animation: leaderboardFlame 1.8s ease-in-out infinite;
        }

        .leaderboard-shine::after {
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.45), transparent);
            content: "";
            inset: 0;
            pointer-events: none;
            position: absolute;
            transform: translateX(-120%);
        }

        .leaderboard-shine:hover::after {
            animation: leaderboardShine 0.9s ease-out;
        }
    </style>

    <div class="min-h-screen bg-background font-body text-on-surface">
        <x-student-nav active="leaderboard" />

        <main class="min-h-screen px-4 py-8 pb-32 sm:px-8 lg:ml-72 lg:px-12">
            <div class="mx-auto max-w-6xl space-y-6">
                <section class="rounded-lg bg-gradient-to-br from-primary to-primary-container p-7 text-on-primary shadow-xl sm:p-9">
                    <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.25em] text-on-primary/80">Leaderboard</p>
                            <h1 class="mt-2 font-headline text-4xl font-black tracking-tight">{{ $scopeLabel }} Rankings</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-on-primary/85">Rankings are based on real saved assessment points from completed student submissions.</p>
                        </div>
                        <div class="rounded-lg bg-white/15 px-5 py-4 text-right ring-1 ring-white/20">
                            <p class="text-xs font-bold uppercase tracking-widest text-on-primary/75">Your Rank</p>
                            <p class="mt-1 font-headline text-3xl font-black">{{ $currentStudentRank ? '#'.$currentStudentRank : 'No Rank' }}</p>
                        </div>
                    </div>
                </section>

                <nav class="grid grid-cols-2 gap-3 rounded-lg bg-surface-container-low p-3 sm:grid-cols-4">
                    @foreach ($scopes as $scopeKey => $label)
                        <a class="inline-flex items-center justify-center rounded-lg px-4 py-3 text-sm font-bold transition-colors {{ $scope === $scopeKey ? 'bg-primary text-on-primary shadow-sm' : 'bg-white text-on-surface-variant hover:text-primary' }}" href="{{ route('student.leaderboard', ['scope' => $scopeKey]) }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </nav>

                <section class="overflow-hidden rounded-lg bg-surface-container-lowest shadow-[0_20px_50px_rgba(0,94,159,0.06)]">
                    <div class="border-b border-outline-variant/15 bg-surface-container-low px-5 py-4 sm:px-6">
                        <h2 class="font-headline text-2xl font-bold text-on-surface">Top Students</h2>
                        <p class="mt-1 text-sm text-on-surface-variant">Completed assessments, total points, and average accuracy.</p>
                    </div>

                    <div class="divide-y divide-outline-variant/15">
                        @forelse ($leaderboard as $entry)
                            @php
                                $isCurrentStudent = Auth::id() === $entry['student']->id;
                                $tier = match ($entry['rank']) {
                                    1 => [
                                        'label' => 'Flaming',
                                        'icon' => 'local_fire_department',
                                        'row' => 'leaderboard-flaming border-l-4 border-red-500 bg-gradient-to-r from-red-50 via-amber-50 to-white',
                                        'rank' => 'bg-gradient-to-br from-red-500 via-orange-400 to-yellow-300 text-white shadow-lg shadow-orange-300/40',
                                        'avatar' => 'bg-gradient-to-br from-red-500 via-orange-400 to-yellow-300 text-white ring-4 ring-orange-200',
                                        'badge' => 'bg-red-500 text-white',
                                    ],
                                    2 => [
                                        'label' => 'Diamond',
                                        'icon' => 'diamond',
                                        'row' => 'leaderboard-shine border-l-4 border-cyan-400 bg-gradient-to-r from-cyan-50 via-white to-sky-50',
                                        'rank' => 'bg-gradient-to-br from-cyan-300 to-blue-500 text-white shadow-md shadow-cyan-200',
                                        'avatar' => 'bg-gradient-to-br from-cyan-300 to-blue-500 text-white ring-4 ring-cyan-100',
                                        'badge' => 'bg-cyan-500 text-white',
                                    ],
                                    3 => [
                                        'label' => 'Platinum',
                                        'icon' => 'workspace_premium',
                                        'row' => 'leaderboard-shine border-l-4 border-slate-300 bg-gradient-to-r from-slate-50 via-white to-blue-50',
                                        'rank' => 'bg-gradient-to-br from-slate-200 to-slate-500 text-white shadow-md shadow-slate-200',
                                        'avatar' => 'bg-gradient-to-br from-slate-200 to-slate-500 text-white ring-4 ring-slate-100',
                                        'badge' => 'bg-slate-600 text-white',
                                    ],
                                    4 => [
                                        'label' => 'Gold',
                                        'icon' => 'military_tech',
                                        'row' => 'border-l-4 border-yellow-400 bg-yellow-50/70',
                                        'rank' => 'bg-gradient-to-br from-yellow-300 to-amber-500 text-amber-950 shadow-md shadow-yellow-200',
                                        'avatar' => 'bg-gradient-to-br from-yellow-300 to-amber-500 text-amber-950 ring-4 ring-yellow-100',
                                        'badge' => 'bg-yellow-400 text-amber-950',
                                    ],
                                    5 => [
                                        'label' => 'Silver',
                                        'icon' => 'shield',
                                        'row' => 'border-l-4 border-zinc-300 bg-zinc-50/80',
                                        'rank' => 'bg-gradient-to-br from-zinc-200 to-zinc-400 text-zinc-900 shadow-md shadow-zinc-200',
                                        'avatar' => 'bg-gradient-to-br from-zinc-200 to-zinc-400 text-zinc-900 ring-4 ring-zinc-100',
                                        'badge' => 'bg-zinc-300 text-zinc-900',
                                    ],
                                    6 => [
                                        'label' => 'Bronze',
                                        'icon' => 'editor_choice',
                                        'row' => 'border-l-4 border-orange-700 bg-orange-50/70',
                                        'rank' => 'bg-gradient-to-br from-orange-300 to-orange-700 text-white shadow-md shadow-orange-200',
                                        'avatar' => 'bg-gradient-to-br from-orange-300 to-orange-700 text-white ring-4 ring-orange-100',
                                        'badge' => 'bg-orange-700 text-white',
                                    ],
                                    default => [
                                        'label' => 'Bronze',
                                        'icon' => 'editor_choice',
                                        'row' => 'border-l-4 border-orange-700/40 bg-orange-50/40',
                                        'rank' => 'bg-gradient-to-br from-orange-200 to-orange-500 text-white shadow-sm shadow-orange-100',
                                        'avatar' => 'bg-gradient-to-br from-orange-200 to-orange-500 text-white ring-4 ring-orange-100',
                                        'badge' => 'bg-orange-700 text-white',
                                    ],
                                };

                            @endphp
                            <div class="relative overflow-hidden flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6 {{ $tier['row'] }} {{ $isCurrentStudent ? 'bg-primary/5' : '' }}">
                                <div class="flex min-w-0 items-center gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg font-headline text-lg font-black {{ $tier['rank'] }}">
                                        #{{ $entry['rank'] }}
                                    </div>
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full {{ $tier['avatar'] }}" aria-hidden="true">
                                        <x-student-character :gender="$entry['student']->gender" variant="portrait" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-headline text-base font-black text-on-surface">
                                            {{ $entry['student']->name }}
                                            @if ($isCurrentStudent)
                                                <span class="ml-2 rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-on-primary">You</span>
                                            @endif
                                        </p>
                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            <p class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ $entry['student']->section ?: 'No Section' }}</p>
                                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-black uppercase tracking-wider {{ $tier['badge'] }}">
                                                <span class="material-symbols-outlined text-[13px]">{{ $tier['icon'] }}</span>
                                                {{ $tier['label'] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-3 text-center sm:min-w-[22rem]">
                                    <div class="rounded-lg bg-surface-container-low px-3 py-2">
                                        <p class="font-headline text-lg font-black text-on-surface">{{ number_format($entry['points']) }}</p>
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Points</p>
                                    </div>
                                    <div class="rounded-lg bg-surface-container-low px-3 py-2">
                                        <p class="font-headline text-lg font-black text-on-surface">{{ $entry['completed'] }}</p>
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Done</p>
                                    </div>
                                    <div class="rounded-lg bg-surface-container-low px-3 py-2">
                                        <p class="font-headline text-lg font-black text-on-surface">{{ $entry['accuracy'] === null ? '-' : $entry['accuracy'].'%' }}</p>
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Avg</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-14 text-center">
                                <span class="material-symbols-outlined text-5xl text-outline-variant">leaderboard</span>
                                <p class="mt-4 font-headline text-xl font-bold text-on-surface">No rankings yet</p>
                                <p class="mt-2 text-sm text-on-surface-variant">Students will appear here after assessment points are saved.</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </main>
    </div>
</x-app-layout>