<x-app-layout>
    @php
        $student = Auth::user();
    @endphp

    <div class="min-h-screen overflow-x-hidden bg-background font-body text-on-surface selection:bg-primary-container/30">
        <nav class="sticky top-0 z-50 flex w-full items-center justify-between bg-white/80 px-6 py-4 shadow-[0_20px_40px_rgba(0,94,159,0.06)] backdrop-blur-xl">
            <div class="flex items-center gap-4">
                <span class="material-symbols-outlined text-3xl text-primary">rocket_launch</span>
                <h1 class="font-headline text-lg font-bold tracking-tight text-blue-600">AI-PGAALS</h1>
            </div>

            <div class="hidden items-center gap-8 md:flex">
                <a class="font-medium text-slate-500 transition-colors hover:text-blue-500" href="{{ route('student.dashboard') }}">Home</a>
                <a class="border-b-4 border-blue-500 font-bold text-blue-700 transition-colors hover:text-blue-500" href="{{ route('student.activities') }}">Activities</a>
                <a class="font-medium text-slate-500 transition-colors hover:text-blue-500" href="{{ route('student.rewards') }}">Rewards</a>
                <a class="font-medium text-slate-500 transition-colors hover:text-blue-500" href="{{ route('profile.edit') }}">Profile</a>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 rounded-full bg-surface-container-low px-4 py-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-primary">assignment</span>
                    <span class="font-headline font-bold text-on-surface">Student Portal</span>
                </div>
                <button class="text-on-surface-variant transition-colors hover:text-primary" type="button">
                    <span class="material-symbols-outlined text-2xl">notifications</span>
                </button>
                <a class="text-on-surface-variant transition-colors hover:text-primary" href="{{ route('profile.edit') }}">
                    <span class="material-symbols-outlined text-2xl">account_circle</span>
                </a>
            </div>
        </nav>

        <main class="min-h-screen px-4 pb-32 pt-6 md:px-0">
            <div class="mx-auto max-w-5xl space-y-8">
                <section class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                    <div class="rounded-lg bg-surface-container-lowest p-6 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                        <p class="text-sm font-bold uppercase tracking-[0.2em] text-primary-dim">Assessment Queue</p>
                        <h2 class="mt-3 font-headline text-3xl font-extrabold text-on-surface">Ready when your teacher is</h2>
                        <p class="mt-3 text-on-surface-variant">This page shows assessments assigned by your teacher. Once an assessment is published, you can open it here and start answering questions.</p>
                    </div>

                    <div class="rounded-lg bg-primary p-6 text-white shadow-xl">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/70">Pending Assessments</p>
                        <div class="mt-4 flex items-end justify-between">
                            <span class="font-headline text-5xl font-black">{{ $pendingAssessments->count() }}</span>
                            <span class="rounded-full bg-white/15 px-3 py-1 text-sm font-bold">{{ $pendingAssessments->isEmpty() ? 'Waiting for Teacher' : 'Ready to Open' }}</span>
                        </div>
                        <p class="mt-4 text-sm text-white/80">
                            @if ($pendingAssessments->isEmpty())
                                No assessments are available yet. Check back after your teacher assigns one.
                            @else
                                Your teacher has published assessments. Open one below to begin when you're ready.
                            @endif
                        </p>
                    </div>
                </section>

                <section class="rounded-lg bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                    @if ($pendingAssessments->isEmpty())
                        <div class="flex flex-col items-center justify-center py-10 text-center">
                            <div class="flex h-24 w-24 items-center justify-center rounded-full bg-surface-container-low text-primary">
                                <span class="material-symbols-outlined text-5xl">assignment_late</span>
                            </div>
                            <h3 class="mt-6 font-headline text-3xl font-bold text-on-surface">No pending assessment</h3>
                            <p class="mt-3 max-w-2xl text-on-surface-variant">You do not have an active assessment right now. When your teacher prepares and publishes one, it will appear in this queue first. You can then click it to begin answering questions.</p>
                            <button class="mt-8 cursor-not-allowed rounded-lg bg-surface-container-highest px-8 py-4 font-headline text-lg font-bold text-on-surface-variant opacity-80" type="button" disabled>
                                Waiting for Assessment
                            </button>
                        </div>
                    @else
                        <div>
                            <div class="mb-6">
                                <h3 class="font-headline text-3xl font-bold text-on-surface">Pending assessments</h3>
                                <p class="mt-2 text-on-surface-variant">Select an assessment below to begin. Questions will open after you choose one.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                @foreach ($pendingAssessments as $assessment)
                                    <button class="rounded-xl border border-outline-variant/20 bg-surface-container-low p-6 text-left transition-all hover:-translate-y-1 hover:border-primary-container hover:bg-primary/5" type="button">
                                        <div class="mb-4 flex items-start justify-between gap-4">
                                            <span class="rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest {{ $assessment->subject === 'literacy' ? 'bg-primary-container/20 text-primary' : 'bg-secondary-container/30 text-secondary-dim' }}">
                                                {{ $assessment->subject }}
                                            </span>
                                            <span class="rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">
                                                Published
                                            </span>
                                        </div>
                                        <h4 class="font-headline text-xl font-bold text-on-surface">{{ $assessment->title }}</h4>
                                        <p class="mt-3 text-sm text-on-surface-variant">
                                            {{ $assessment->instructions ?: 'Your teacher has prepared this assessment. Click to open and begin once the question flow is ready.' }}
                                        </p>
                                        <div class="mt-5 flex items-center justify-between text-sm font-bold">
                                            <span class="text-on-surface-variant">Prepared by teacher</span>
                                            <span class="text-primary">Open Assessment</span>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>

                <section class="grid gap-6 md:grid-cols-2">
                    <div class="rounded-lg bg-surface-container-low p-8">
                        <h4 class="flex items-center gap-2 font-headline text-xl font-bold">
                            <span class="material-symbols-outlined text-primary">info</span>
                            How It Works
                        </h4>
                        <div class="mt-6 space-y-4">
                            <div class="rounded-xl bg-surface-container-lowest p-4">
                                <p class="text-sm font-bold text-on-surface">1. Teacher prepares an assessment</p>
                                <p class="mt-2 text-sm text-on-surface-variant">Your teacher creates the assessment and publishes it to your queue.</p>
                            </div>
                            <div class="rounded-xl bg-surface-container-lowest p-4">
                                <p class="text-sm font-bold text-on-surface">2. You open the assessment</p>
                                <p class="mt-2 text-sm text-on-surface-variant">Once available, click the assessment card to start the questions.</p>
                            </div>
                            <div class="rounded-xl bg-surface-container-lowest p-4">
                                <p class="text-sm font-bold text-on-surface">3. Results are saved by the system</p>
                                <p class="mt-2 text-sm text-on-surface-variant">After submission, your activity and summary pages will update automatically.</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-surface-container-low p-8">
                        <h4 class="flex items-center gap-2 font-headline text-xl font-bold">
                            <span class="material-symbols-outlined text-secondary">notifications_active</span>
                            Status
                        </h4>
                        <div class="mt-6 rounded-xl bg-surface-container-lowest p-6">
                            <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Current State</p>
                            <p class="mt-3 font-headline text-2xl font-bold text-on-surface">{{ $pendingAssessments->isEmpty() ? 'Awaiting teacher assignment' : 'Assessment available to open' }}</p>
                            <p class="mt-3 text-sm text-on-surface-variant">
                                @if ($pendingAssessments->isEmpty())
                                    There are no questions to answer yet because no assessment has been posted for this student account.
                                @else
                                    A teacher has already posted assessments. The student still needs to click one first before answering questions.
                                @endif
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <div class="fixed bottom-0 left-0 z-50 flex w-full items-center justify-around bg-white/80 px-4 pb-6 pt-4 shadow-2xl backdrop-blur-2xl md:hidden dark:bg-slate-900/80">
            <a class="flex flex-col items-center justify-center px-4 py-2 text-slate-400 transition-transform hover:scale-105 dark:text-slate-500" href="{{ route('student.dashboard') }}">
                <span class="material-symbols-outlined text-2xl">home</span>
                <span class="text-[10px] font-bold lowercase">Home</span>
            </a>
            <div class="flex scale-110 flex-col items-center justify-center rounded-[2rem] bg-blue-100 px-6 py-2 text-blue-700 shadow-inner dark:bg-blue-900/40 dark:text-blue-300">
                <span class="material-symbols-outlined text-2xl">rocket_launch</span>
                <span class="text-[10px] font-bold lowercase">Activities</span>
            </div>
            <a class="flex flex-col items-center justify-center px-4 py-2 text-slate-400 transition-transform hover:scale-105 dark:text-slate-500" href="{{ route('student.rewards') }}">
                <span class="material-symbols-outlined text-2xl">backpack</span>
                <span class="text-[10px] font-bold lowercase">Rewards</span>
            </a>
            <a class="flex flex-col items-center justify-center px-4 py-2 text-slate-400 transition-transform hover:scale-105 dark:text-slate-500" href="{{ route('profile.edit') }}">
                <span class="material-symbols-outlined text-2xl">face</span>
                <span class="text-[10px] font-bold lowercase">Profile</span>
            </a>
        </div>
    </div>
</x-app-layout>
