<x-app-layout>
    @php
        $student = Auth::user();
    @endphp

    <div class="min-h-screen overflow-x-hidden bg-background font-body text-on-surface selection:bg-primary-container/30">
        <x-student-nav active="activities" />

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

                            <div class="space-y-5">
                                @foreach ($pendingAssessments as $assessment)
                                    @php
                                        $assetPath = $assessment->asset_path;
                                        $assetExtension = $assetPath ? strtolower(pathinfo($assetPath, PATHINFO_EXTENSION)) : null;
                                        $isImageAsset = in_array($assetExtension, ['jpg', 'jpeg', 'png'], true);
                                        $assetUrl = $assetPath ? \Illuminate\Support\Facades\Storage::url($assetPath) : null;
                                        $targetLabel = str($assessment->target_section ?? 'all')->replace('_', ' ')->title();
                                        $quizLabel = str($assessment->quiz_type ?? 'multiple_choice')->replace('_', ' ')->title();
                                        $focusLabel = collect($assessment->focus_areas ?? [])->join(', ');
                                    @endphp

                                    <a class="group block overflow-hidden rounded-2xl border border-outline-variant/15 bg-white text-left shadow-[0_20px_55px_rgba(0,94,159,0.08)] transition-all hover:-translate-y-1 hover:border-primary-container hover:shadow-[0_24px_70px_rgba(0,94,159,0.14)]" href="{{ route('student.assessments.show', $assessment) }}">
                                        <div class="grid gap-0 lg:grid-cols-[18rem_1fr]">
                                            <div class="relative min-h-56 overflow-hidden bg-gradient-to-br from-primary to-primary-container lg:min-h-full">
                                                @if ($isImageAsset)
                                                    <img class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" src="{{ $assetUrl }}" alt="{{ $assessment->title }} assessment image">
                                                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/15 to-transparent"></div>
                                                @else
                                                    <div class="absolute inset-0" style="background: radial-gradient(circle at top left, rgba(255,255,255,0.35), transparent 32%), linear-gradient(135deg, #005e9f, #44a5ff);"></div>
                                                    <div class="absolute inset-0 flex items-center justify-center">
                                                        <div class="rounded-2xl bg-white/15 p-6 text-white backdrop-blur-sm">
                                                            <span class="material-symbols-outlined text-6xl">{{ $assessment->subject === 'literacy' ? 'auto_stories' : 'calculate' }}</span>
                                                        </div>
                                                    </div>
                                                @endif

                                                <div class="absolute bottom-0 left-0 right-0 p-5 text-white">
                                                    <p class="text-[10px] font-black uppercase tracking-[0.25em] text-white/70">Published Assessment</p>
                                                    <p class="mt-1 font-headline text-2xl font-black leading-tight">{{ ucfirst($assessment->subject) }}</p>
                                                </div>
                                            </div>

                                            <div class="flex flex-col justify-between p-6 lg:p-8">
                                                <div>
                                                    <div class="mb-4 flex flex-wrap gap-2">
                                                        <span class="rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest {{ $assessment->subject === 'literacy' ? 'bg-primary-container/20 text-primary' : 'bg-secondary-container/30 text-secondary-dim' }}">
                                                            {{ $assessment->subject }}
                                                        </span>
                                                        <span class="rounded-full bg-surface-container-low px-3 py-1 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">
                                                            {{ $quizLabel }}
                                                        </span>
                                                        <span class="rounded-full bg-secondary-container/30 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-secondary-dim">
                                                            Published
                                                        </span>
                                                    </div>

                                                    <h4 class="font-headline text-2xl font-extrabold leading-tight text-on-surface">{{ $assessment->title }}</h4>
                                                    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-on-surface-variant">
                                                        {{ $assessment->instructions ?: 'Your teacher has prepared this assessment. Review the details, then open it when you are ready to begin.' }}
                                                    </p>

                                                    <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-3">
                                                        <div class="rounded-xl bg-surface-container-low p-4">
                                                            <dt class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Target</dt>
                                                            <dd class="mt-1 font-bold text-on-surface">{{ $targetLabel }}</dd>
                                                        </div>
                                                        <div class="rounded-xl bg-surface-container-low p-4">
                                                            <dt class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Focus</dt>
                                                            <dd class="mt-1 font-bold text-on-surface">{{ $focusLabel ?: 'General Skills' }}</dd>
                                                        </div>
                                                        <div class="rounded-xl bg-surface-container-low p-4">
                                                            <dt class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Material</dt>
                                                            <dd class="mt-1 font-bold text-on-surface">{{ $assetPath ? strtoupper($assetExtension) : 'Activity Screen' }}</dd>
                                                        </div>
                                                    </dl>
                                                </div>

                                                <div class="mt-7 flex flex-col gap-3 border-t border-outline-variant/15 pt-5 sm:flex-row sm:items-center sm:justify-between">
                                                    <span class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">
                                                        Prepared {{ $assessment->created_at?->format('M d, Y') }}
                                                    </span>
                                                    <span class="inline-flex items-center justify-center gap-2 rounded-full bg-primary px-5 py-3 text-sm font-black text-on-primary transition-colors group-hover:bg-primary-dim">
                                                        Open Assessment
                                                        <span class="material-symbols-outlined text-lg">arrow_forward</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
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

    </div>
</x-app-layout>
