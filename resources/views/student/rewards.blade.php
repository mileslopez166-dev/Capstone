<x-app-layout>
    @php
        $student = Auth::user();
        $practiceCoins = $student->practiceCoinBalance();
        $unlockedRewards = count($student->unlockedAvatarItems());
        $breakdown = [
            ['label' => 'Completed Assessments', 'value' => number_format($studentMetrics['completed_count']), 'tone' => 'text-secondary'],
            ['label' => 'Saved Results', 'value' => number_format($studentMetrics['saved_results']), 'tone' => 'text-primary'],
            ['label' => 'Coins', 'value' => number_format($practiceCoins), 'tone' => 'text-tertiary'],
            ['label' => 'Wardrobe Rewards', 'value' => number_format($unlockedRewards), 'tone' => 'text-secondary'],
        ];
    @endphp

    <div class="min-h-screen overflow-x-clip bg-background font-body text-on-surface">
        <x-student-nav active="rewards" />

        <main class="relative min-h-screen px-4 py-8 pb-32 sm:px-8 lg:ml-72 lg:px-12">
            <div class="mx-auto max-w-7xl">
                <x-practice-entry />
            <div class="pointer-events-none absolute inset-0 opacity-15" style="background-image: radial-gradient(circle, #44a5ff 10%, transparent 10.5%), radial-gradient(circle, #ffeb3b 10%, transparent 10.5%), radial-gradient(circle, #91f78e 10%, transparent 10.5%); background-size: 40px 40px, 60px 60px, 50px 50px; background-position: 0 0, 20px 30px, 40px 10px;"></div>

            <section class="relative z-10 grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
                <div class="mb-4 text-center lg:col-span-12">
                    <h1 class="mb-2 font-headline text-5xl font-extrabold tracking-tight text-primary md:text-7xl">Rewards</h1>
                    <p class="text-xl font-medium text-on-surface-variant md:text-2xl">Your coins and wardrobe collection</p>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:col-span-7 md:grid-cols-2">
                    <div class="group relative overflow-hidden rounded-lg bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                        <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-primary-container/20 transition-transform duration-500 group-hover:scale-125"></div>
                        <div class="relative">
                            <span class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Accuracy Score</span>
                            <div class="mt-4 flex items-baseline gap-2">
                                <span class="font-headline text-6xl font-black text-primary">{{ $studentMetrics['average_accuracy'] ?? 0 }}</span>
                                <span class="font-headline text-2xl font-bold text-primary-dim">%</span>
                            </div>
                            <div class="mt-6 h-4 w-full overflow-hidden rounded-full bg-surface-container-highest">
                                <div class="relative h-full rounded-full bg-secondary shadow-[inset_0_2px_4px_rgba(0,0,0,0.1)]" style="width: {{ $studentMetrics['average_accuracy'] ?? 0 }}%">
                                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="group relative overflow-hidden rounded-lg bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                        <div class="absolute -bottom-4 -left-4 h-20 w-20 rounded-full bg-tertiary-container/30 transition-transform duration-500 group-hover:scale-110"></div>
                        <div class="relative">
                            <span class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Saved Results</span>
                            <div class="mt-4 flex items-center gap-3">
                                <span class="material-symbols-outlined text-4xl text-tertiary">folder</span>
                                <span class="font-headline text-6xl font-black text-on-surface">{{ number_format($studentMetrics['saved_results']) }}</span>
                            </div>
                            <p class="mt-4 text-sm font-medium text-on-surface-variant">{{ $studentMetrics['saved_results'] > 0 ? number_format($studentMetrics['total_points']).' total points saved.' : 'No assessment results saved yet.' }}</p>
                        </div>
                    </div>

                    <div class="relative overflow-hidden rounded-lg bg-gradient-to-br from-primary to-primary-dim p-10 text-on-primary shadow-2xl md:col-span-2">
                        <img class="absolute inset-0 h-full w-full object-cover opacity-30 mix-blend-overlay" alt="" src="{{ asset('images/campus/pixel-world.png') }}">
                        <div class="relative flex flex-col items-center justify-between gap-8 md:flex-row">
                            <div class="text-center md:text-left">
                                <h3 class="mb-2 font-headline text-3xl font-bold">Wardrobe</h3>
                                <p class="max-w-sm text-lg text-on-primary/80">{{ number_format($practiceCoins) }} coins &middot; {{ $unlockedRewards }} rewards unlocked</p>
                                <a class="practice-button practice-secondary mt-4" href="{{ route('student.wardrobe.edit') }}"><span class="material-symbols-outlined" aria-hidden="true">checkroom</span> Open wardrobe</a>
                            </div>
                            <div class="relative flex h-40 w-40 items-center justify-center">
                                <div class="absolute inset-0 rounded-full bg-tertiary-container opacity-40 blur-2xl"></div>
                                <div class="relative flex h-32 w-32 items-center justify-center rounded-full border-4 border-tertiary-container bg-white/10 shadow-inner backdrop-blur-md">
                                    <span class="material-symbols-outlined text-7xl text-tertiary-container">backpack</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6 lg:col-span-5">
                    <section class="overflow-hidden rounded-lg border border-primary/20 bg-surface-container-lowest shadow-[0_20px_40px_rgba(0,94,159,0.06)]" aria-labelledby="leaderboard-status-heading">
                        <div class="flex items-start justify-between gap-4 border-b border-outline-variant/15 bg-surface-container-low px-6 py-5">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-widest text-primary">Your Standing</p>
                                <h2 id="leaderboard-status-heading" class="mt-1 font-headline text-xl font-bold text-on-surface">Leaderboard Status</h2>
                            </div>
                            <a class="inline-flex min-h-11 items-center gap-1 text-sm font-bold text-primary" href="{{ route('student.leaderboard') }}">Full rankings<span class="material-symbols-outlined text-lg" aria-hidden="true">arrow_forward</span></a>
                        </div>
                        <div class="p-6">
                            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="grid h-16 w-16 shrink-0 place-items-center rounded-lg bg-primary font-headline text-2xl font-black text-on-primary shadow-md">{{ $leaderboardStatus['rank'] ? '#'.$leaderboardStatus['rank'] : '--' }}</div>
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Overall Rank</p>
                                        <p class="mt-1 flex items-center gap-2 font-headline text-xl font-black text-on-surface"><span class="material-symbols-outlined text-tertiary" aria-hidden="true">{{ $leaderboardStatus['tier']['icon'] }}</span>{{ $leaderboardStatus['tier']['label'] }} Tier</p>
                                    </div>
                                </div>
                                <div class="border-t border-outline-variant/20 pt-4 sm:border-l sm:border-t-0 sm:pl-5 sm:pt-0 sm:text-right">
                                    <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">{{ $student->section ?: 'No Section' }}</p>
                                    <p class="mt-1 font-headline text-lg font-black text-secondary">Section Rank {{ $leaderboardStatus['section_rank'] ? '#'.$leaderboardStatus['section_rank'] : '--' }}</p>
                                </div>
                            </div>
                            <dl class="mt-6 grid grid-cols-3 divide-x divide-outline-variant/20 border-y border-outline-variant/20 py-4 text-center">
                                <div class="px-2"><dt class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Points</dt><dd class="mt-1 font-headline text-lg font-black text-on-surface">{{ number_format($leaderboardStatus['points']) }}</dd></div>
                                <div class="px-2"><dt class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Completed</dt><dd class="mt-1 font-headline text-lg font-black text-on-surface">{{ $leaderboardStatus['completed'] }}</dd></div>
                                <div class="px-2"><dt class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Accuracy</dt><dd class="mt-1 font-headline text-lg font-black text-on-surface">{{ $leaderboardStatus['accuracy'] === null ? '--' : $leaderboardStatus['accuracy'].'%' }}</dd></div>
                            </dl>
                            @if (! $leaderboardStatus['rank'])<p class="mt-4 text-sm text-on-surface-variant">Complete an assessment to enter the leaderboard.</p>@endif
                        </div>
                    </section>

                    <div class="rounded-lg bg-surface-container-low p-8">
                        <h4 class="mb-6 flex items-center gap-2 font-headline text-xl font-bold">
                            <span class="material-symbols-outlined text-secondary">assessment</span>
                            Performance Breakdown
                        </h4>
                        <div class="space-y-4">
                            @foreach ($breakdown as $item)
                                <div class="flex items-center justify-between rounded-md bg-surface-container-lowest p-4">
                                    <span class="font-medium text-on-surface-variant">{{ $item['label'] }}</span>
                                    <span class="font-headline font-bold {{ $item['tone'] }}">{{ $item['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-col gap-4">
                        <a class="w-full rounded-lg bg-gradient-to-r from-primary to-primary-container py-5 text-center font-headline text-xl font-extrabold text-on-primary shadow-lg transition-transform duration-200 hover:scale-[1.02] active:scale-95" href="{{ route('student.activities') }}">
                            View Activities
                        </a>
                        <a class="practice-button" href="{{ route('student.practice.index') }}"><span class="material-symbols-outlined" aria-hidden="true">flag</span> Practice Missions</a>
                    </div>
                </div>
            </section>
            </div>
        </main>

    </div>
</x-app-layout>
