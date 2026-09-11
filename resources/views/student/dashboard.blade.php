<x-app-layout>
    @php
        $student = Auth::user();
        $firstName = str($student->name)->before(' ')->title();
        $pendingAssessments = $pendingAssessments ?? collect();
        $recentSubmissions = $recentSubmissions ?? collect();
        $studentMetrics = $studentMetrics ?? ['pending_count' => 0, 'completed_count' => 0, 'average_accuracy' => null, 'total_points' => 0];
        $pendingCount = $studentMetrics['pending_count'] ?? $pendingAssessments->count();
        $completedCount = $studentMetrics['completed_count'] ?? $recentSubmissions->count();
        $averageAccuracy = $studentMetrics['average_accuracy'] ?? null;
        $totalPoints = $studentMetrics['total_points'] ?? 0;
        $activityProgress = $pendingCount + $completedCount > 0 ? (int) round(($completedCount / ($pendingCount + $completedCount)) * 100) : 0;
    @endphp

    <div class="min-h-screen bg-background font-body text-on-surface">
        <x-student-nav active="home" />

        <main class="mx-auto max-w-7xl px-6 py-8 pb-32">
            <section class="relative mb-12 overflow-visible">
                <div class="flex flex-col items-center justify-between gap-8 rounded-lg bg-gradient-to-br from-primary to-primary-container p-8 text-on-primary shadow-xl md:flex-row md:p-12">
                    <div class="flex-1">
                        <h1 class="font-headline text-4xl font-extrabold tracking-tight md:text-5xl">
                            Welcome, {{ $firstName }}
                        </h1>
                        <p class="mb-8 mt-4 max-w-md text-lg text-on-primary/90">Your student dashboard is ready. Open assigned activities, check your account, and review your current status here.</p>

                        <div class="space-y-3">
                            <div class="flex items-end justify-between">
                                <span class="font-headline text-xl font-bold">Assigned Activities</span>
                                <span class="font-bold">{{ $pendingCount }} Available</span>
                            </div>
                            <div class="h-6 w-full overflow-hidden rounded-full border-2 border-white/20 bg-surface-container-highest/30">
                                <div class="relative h-full rounded-full bg-secondary shadow-[inset_0_2px_4px_rgba(255,255,255,0.4)]" style="width: {{ $activityProgress }}%">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/10 to-transparent"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full max-w-[220px] flex-shrink-0 md:w-[220px]">
                        <x-student-pixel-avatar :gender="$student->gender" :name="$student->name" size="lg" :show-card="true" class="bg-white/10" />
                    </div>
                </div>
            </section>

            <div class="mb-12 grid grid-cols-1 gap-6 md:grid-cols-12">                @forelse ($pendingAssessments->take(2) as $assessment)
                    <div class="group rounded-lg border-b-4 {{ $assessment->subject === 'literacy' ? 'border-primary-container' : 'border-secondary-container' }} bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,94,159,0.06)] transition-transform hover:-translate-y-1 md:col-span-6">
                        <div class="mb-6 flex items-start justify-between">
                            <div class="rounded-lg {{ $assessment->subject === 'literacy' ? 'bg-primary-container/10' : 'bg-secondary-container/10' }} p-4">
                                <span class="material-symbols-outlined text-4xl {{ $assessment->subject === 'literacy' ? 'text-primary' : 'text-secondary' }}">{{ $assessment->subject === 'literacy' ? 'menu_book' : 'calculate' }}</span>
                            </div>
                            <span class="rounded-full bg-tertiary-container px-4 py-1 text-sm font-bold tracking-wide text-on-tertiary-container">AVAILABLE</span>
                        </div>
                        <h3 class="font-headline text-2xl font-bold">{{ $assessment->title }}</h3>
                        <p class="mb-8 mt-2 text-on-surface-variant">{{ $assessment->instructions ?: 'Open the assigned assessment prepared by your teacher.' }}</p>
                        <a class="flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r {{ $assessment->subject === 'literacy' ? 'from-primary to-primary-container' : 'from-secondary to-secondary-dim' }} py-4 font-bold text-white shadow-lg transition-all" href="{{ route('student.assessments.show', $assessment) }}">
                            Open Activity
                            <span class="material-symbols-outlined">rocket_launch</span>
                        </a>
                    </div>
                @empty
                    <div class="rounded-lg border-b-4 border-outline-variant bg-surface-container-lowest p-8 text-center shadow-[0_20px_40px_rgba(0,94,159,0.06)] md:col-span-12">
                        <span class="material-symbols-outlined text-5xl text-outline-variant">assignment_late</span>
                        <h3 class="mt-4 font-headline text-2xl font-bold">No assigned activities</h3>
                        <p class="mt-2 text-on-surface-variant">Unlocked assessments for your section will appear here automatically.</p>
                    </div>
                @endforelse

                <div class="flex flex-col items-center justify-center rounded-lg bg-surface-container-low p-6 text-center md:col-span-4">
                    <span class="mb-2 text-xs font-bold uppercase tracking-widest text-on-surface-variant">Current Status</span>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-4xl text-primary">assignment</span>
                        <span class="font-headline text-3xl font-black text-on-surface">{{ $averageAccuracy === null ? 'No Summary Yet' : $averageAccuracy.'%' }}</span>
                    </div>
                    <p class="mt-4 text-sm text-on-surface-variant">Completed: {{ $completedCount }} | Points: {{ number_format($totalPoints) }}</p>
                </div>

                <div class="rounded-lg bg-surface-container-lowest p-6 shadow-sm md:col-span-8">
                    <div class="mb-6 flex items-center justify-between">
                        <h4 class="font-headline text-xl font-bold">Achievements</h4>
                    </div>
                    <div class="rounded-xl bg-surface-container-low p-6 text-center">
                        <span class="material-symbols-outlined text-5xl text-outline-variant">workspace_premium</span>
                        <p class="mt-4 font-headline text-xl font-bold text-on-surface">{{ $completedCount > 0 ? 'Progress saved' : 'No rewards available yet' }}</p>
                        <p class="mt-2 text-sm text-on-surface-variant">{{ $completedCount > 0 ? 'Your completed assessment results are now connected to this dashboard.' : 'This area will stay empty until real activity results are recorded by the system.' }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-surface-container-low p-8">
                <h3 class="flex items-center gap-2 font-headline text-xl font-bold">
                    <span class="material-symbols-outlined text-primary">analytics</span>
                    Activity Overview
                </h3>
                <div class="mt-6 rounded-xl bg-surface-container-lowest p-8 text-center">
                    <span class="material-symbols-outlined text-5xl text-outline-variant">bar_chart</span>
                    <p class="mt-4 font-headline text-xl font-bold text-on-surface">{{ $completedCount > 0 ? 'Real analytics connected' : 'No analytics yet' }}</p>
                    <p class="mt-2 text-sm text-on-surface-variant">{{ $completedCount > 0 ? 'Average accuracy and points are calculated from your saved assessment submissions.' : 'Charts and summaries will appear here after the system stores completed assessment data.' }}</p>
                </div>
            </div>
        </main>

    </div>
</x-app-layout>