<x-app-layout>
    @php
        $student = Auth::user();
    @endphp

    <div class="min-h-screen overflow-x-hidden bg-background font-body text-on-surface selection:bg-primary-container/30">
        <x-student-nav active="activities" />

        <main class="campus-activities min-h-screen px-4 py-8 pb-32 sm:px-8 lg:ml-72 lg:px-12">
            <div class="mx-auto max-w-7xl space-y-8">
                <section class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                    <div class="campus-panel rounded-lg bg-surface-container-lowest p-6 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                        <p class="campus-queue-label text-sm font-bold uppercase tracking-[0.2em] text-primary-dim"><span class="material-symbols-outlined" aria-hidden="true">bookmark_star</span>Assessment Queue</p>
                        <h2 class="campus-queue-title mt-3 font-headline text-3xl font-extrabold text-on-surface">{{ $pendingAssessments->isEmpty() ? 'Ready when your teacher is' : 'Your next adventure awaits' }}</h2>
                        <p class="mt-3 text-on-surface-variant">This page shows assessments assigned by your teacher. Once an assessment is published, you can open it here and start answering questions.</p>
                    </div>

                    <div class="campus-stat rounded-lg bg-primary p-6 text-white shadow-xl">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/70">Pending Assessments</p>
                        <div class="mt-4 flex items-end justify-between">
                            <span class="campus-stat-count font-headline text-5xl font-black">{{ $pendingAssessments->count() }}</span>
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

                <section class="campus-panel rounded-lg bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                    @if ($pendingAssessments->isEmpty())
                        <div class="campus-empty flex flex-col items-center justify-center py-10 text-center">
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

                <section class="campus-results">
                    <div class="campus-results-heading mb-6">
                        <h3 class="font-headline text-3xl font-bold text-on-surface"><span class="material-symbols-outlined" aria-hidden="true">bar_chart</span>Recorded Outputs</h3>
                        <p class="mt-2 text-on-surface-variant">Review your saved assessment scores, request a retake token, or take again when your teacher has allowed more tries.</p>
                    </div>

                    @if (($completedSubmissions ?? collect())->isEmpty())
                        <div class="rounded-xl bg-surface-container-low p-6 text-center">
                            <span class="material-symbols-outlined text-5xl text-outline-variant">history</span>
                            <p class="mt-3 font-headline text-xl font-bold text-on-surface">No recorded outputs yet</p>
                            <p class="mt-2 text-sm text-on-surface-variant">Your completed assessment output will appear here after you submit your first activity.</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach ($completedSubmissions as $submission)
                                @php
                                    $assessment = $submission->assessment;
                                    $requestStatus = $submission->latest_retake_request?->status;
                                @endphp
                                <div class="rounded-2xl border border-outline-variant/15 bg-white p-5 shadow-[0_20px_55px_rgba(0,94,159,0.06)]">
                                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                                        <div>
                                            <div class="mb-3 flex flex-wrap gap-2">
                                                <span class="rounded-full bg-primary-container/20 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-primary">Recorded</span>
                                                <span class="rounded-full bg-surface-container-low px-3 py-1 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Attempt {{ $submission->attempt_number }}</span>
                                                @if ($assessment?->hasUnlimitedRetries())
                                                    <span class="rounded-full bg-secondary-container/40 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-secondary-dim">Unlimited retakes left</span>
                                                @elseif ($submission->remaining_retake_tries > 0)
                                                    <span class="rounded-full bg-secondary-container/40 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-secondary-dim">{{ $submission->remaining_retake_tries }} retake {{ Str::plural('try', $submission->remaining_retake_tries) }} left</span>
                                                @elseif ($requestStatus)
                                                    <span class="rounded-full bg-surface-container-high px-3 py-1 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Request {{ $requestStatus }}</span>
                                                @endif
                                            </div>
                                            <h4 class="font-headline text-2xl font-extrabold leading-tight text-on-surface">{{ $assessment?->title ?? 'Assessment' }}</h4>
                                            <p class="mt-2 text-sm text-on-surface-variant">Submitted {{ $submission->submitted_at?->format('M d, Y h:i A') ?? 'recently' }} | {{ $submission->attempts_count }} total {{ Str::plural('attempt', $submission->attempts_count) }}</p>
                                        </div>

                                        <div class="grid gap-3 sm:grid-cols-3 lg:min-w-[26rem]">
                                            <div class="rounded-xl bg-surface-container-low p-4 text-center">
                                                <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Score</p>
                                                <p class="mt-2 font-headline text-3xl font-black text-primary">{{ $submission->accuracy }}%</p>
                                            </div>
                                            <div class="rounded-xl bg-surface-container-low p-4 text-center">
                                                <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Points</p>
                                                <p class="mt-2 font-headline text-3xl font-black text-secondary-dim">{{ number_format($submission->points) }}</p>
                                            </div>
                                            <div class="rounded-xl bg-surface-container-low p-4 text-center">
                                                <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Correct</p>
                                                <p class="mt-2 font-headline text-3xl font-black text-on-surface">{{ $submission->correct_count }}/{{ $submission->question_count }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <details class="group mt-5 rounded-2xl border border-outline-variant/15 bg-surface-container-low p-5">
                                        <summary class="flex cursor-pointer list-none flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <h5 class="font-headline text-lg font-extrabold text-on-surface">Assessment Review</h5>
                                                <p class="mt-1 text-sm text-on-surface-variant">Open this only if you want to check the questions from your latest attempt.</p>
                                            </div>
                                            <span class="inline-flex w-fit items-center gap-2 rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest {{ ($submission->wrong_review_items ?? collect())->isEmpty() && ($submission->review_items ?? collect())->isNotEmpty() ? 'bg-secondary-container/40 text-secondary-dim' : 'bg-error/10 text-error' }}">
                                                <span class="material-symbols-outlined text-sm">{{ ($submission->wrong_review_items ?? collect())->isEmpty() && ($submission->review_items ?? collect())->isNotEmpty() ? 'verified' : 'rate_review' }}</span>
                                                {{ ($submission->wrong_review_items ?? collect())->count() }} Wrong
                                                <span class="material-symbols-outlined text-sm transition-transform group-open:rotate-180">expand_more</span>
                                            </span>
                                        </summary>

                                        <div class="mt-4 border-t border-outline-variant/10 pt-4">
                                            @if (($submission->review_items ?? collect())->isEmpty())
                                                <div class="rounded-xl bg-white p-4 text-sm text-on-surface-variant">
                                                    This assessment has no multiple-choice answers to review.
                                                </div>
                                            @elseif (($submission->wrong_review_items ?? collect())->isEmpty())
                                                <div class="rounded-xl bg-white p-4 text-sm font-bold text-secondary-dim">
                                                    Nice work. All checked answers were correct in this attempt.
                                                </div>
                                            @else
                                                <div class="space-y-3">
                                                    @foreach ($submission->wrong_review_items as $reviewItem)
                                                        <div class="rounded-xl bg-white p-4 shadow-sm">
                                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                                <div class="min-w-0">
                                                                    <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Question {{ $reviewItem['number'] }}</p>
                                                                    <p class="mt-1 font-bold text-on-surface">{{ $reviewItem['question'] }}</p>
                                                                </div>
                                                                <span class="inline-flex w-fit shrink-0 rounded-full bg-error/10 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-error">Needs Review</span>
                                                            </div>
                                                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                                                <div class="rounded-lg border border-error/20 bg-error/5 p-3">
                                                                    <p class="text-[10px] font-black uppercase tracking-widest text-error">Your Answer</p>
                                                                    <p class="mt-1 text-sm font-bold text-on-surface">{{ $reviewItem['selected_letter'] ?: '-' }}. {{ $reviewItem['selected_text'] }}</p>
                                                                </div>
                                                                <div class="rounded-lg border border-secondary/20 bg-secondary-container/20 p-3">
                                                                    <p class="text-[10px] font-black uppercase tracking-widest text-secondary-dim">Correct Answer</p>
                                                                    <p class="mt-1 text-sm font-bold text-on-surface">{{ $reviewItem['correct_letter'] ?: '-' }}. {{ $reviewItem['correct_text'] }}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </details>

                                    <div class="mt-5 flex flex-col gap-3 border-t border-outline-variant/15 pt-5 sm:flex-row sm:items-center sm:justify-between">
                                        @if ($assessment && ($assessment->hasUnlimitedRetries() || $submission->remaining_retake_tries > 0))
                                            <a class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-3 text-sm font-black text-on-primary shadow-lg shadow-primary/20" href="{{ route('student.assessments.show', $assessment) }}">
                                                Take Again
                                                <span class="material-symbols-outlined text-lg">replay</span>
                                            </a>
                                        @elseif ($assessment && $requestStatus === 'pending')
                                            <button class="cursor-not-allowed rounded-lg bg-surface-container-high px-5 py-3 text-sm font-bold text-on-surface-variant" type="button" disabled>Waiting for Teacher Approval</button>
                                        @elseif ($assessment)
                                            <form class="grid w-full gap-3 sm:grid-cols-[7rem_1fr_auto]" method="POST" action="{{ route('student.assessments.retake-request', $assessment) }}">
                                                @csrf
                                                <label class="sr-only" for="requested-tries-{{ $submission->id }}">Requested tries</label>
                                                <input class="rounded-sm border-none bg-surface-container-low px-3 py-3 text-sm font-bold text-on-surface shadow-inner focus:ring-2 focus:ring-primary" id="requested-tries-{{ $submission->id }}" name="requested_tries" type="number" min="1" max="10" value="1">
                                                <input class="rounded-sm border-none bg-surface-container-low px-3 py-3 text-sm text-on-surface shadow-inner focus:ring-2 focus:ring-primary" name="message" type="text" placeholder="Message to teacher (optional)">
                                                <button class="rounded-lg bg-surface-container-highest px-5 py-3 text-sm font-bold text-on-surface" type="submit">Request Retake Token</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
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
