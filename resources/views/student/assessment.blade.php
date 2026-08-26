<x-app-layout>
    @php
        $student = Auth::user();
        $assetPath = $assessment->asset_path;
        $assetExtension = $assetPath ? strtolower(pathinfo($assetPath, PATHINFO_EXTENSION)) : null;
        $isImageAsset = in_array($assetExtension, ['jpg', 'jpeg', 'png'], true);
        $assetUrl = $assetPath ? \Illuminate\Support\Facades\Storage::url($assetPath) : null;
        $manualQuestions = $assessment->manual_questions ?? [];
    @endphp

    <div class="min-h-screen overflow-x-hidden bg-background font-body text-on-surface">
        <x-student-nav active="activities" />

        <main class="mx-auto max-w-6xl px-4 pb-32 pt-6 sm:px-6 lg:px-8">
            <a class="mb-6 inline-flex items-center gap-2 font-bold text-primary transition-colors hover:text-primary-dim" href="{{ route('student.activities') }}">
                <span class="material-symbols-outlined text-lg">arrow_back</span>
                Back to Activities
            </a>

            <section class="overflow-hidden rounded-2xl bg-white shadow-[0_24px_70px_rgba(0,94,159,0.08)]">
                <div class="grid lg:grid-cols-[20rem_1fr]">
                    <div class="relative min-h-72 overflow-hidden bg-gradient-to-br from-primary to-primary-container">
                        @if ($isImageAsset)
                            <img class="absolute inset-0 h-full w-full object-cover" src="{{ $assetUrl }}" alt="{{ $assessment->title }} assessment image">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-transparent"></div>
                        @else
                            <div class="absolute inset-0" style="background: radial-gradient(circle at top left, rgba(255,255,255,0.35), transparent 32%), linear-gradient(135deg, #005e9f, #44a5ff);"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="rounded-2xl bg-white/15 p-8 text-white backdrop-blur-sm">
                                    <span class="material-symbols-outlined text-7xl">{{ $assessment->subject === 'literacy' ? 'auto_stories' : 'calculate' }}</span>
                                </div>
                            </div>
                        @endif

                        <div class="absolute bottom-0 left-0 right-0 p-7 text-white">
                            <p class="text-[10px] font-black uppercase tracking-[0.25em] text-white/70">Published Assessment</p>
                            <p class="mt-1 font-headline text-3xl font-black leading-tight">{{ ucfirst($assessment->subject) }}</p>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8">
                        <div class="mb-4 flex flex-wrap gap-2">
                            <span class="rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest {{ $assessment->subject === 'literacy' ? 'bg-primary-container/20 text-primary' : 'bg-secondary-container/30 text-secondary-dim' }}">
                                {{ $assessment->subject }}
                            </span>
                            <span class="rounded-full bg-surface-container-low px-3 py-1 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">
                                {{ str($assessment->quiz_type ?? 'multiple_choice')->replace('_', ' ')->title() }}
                            </span>
                            <span class="rounded-full bg-secondary-container/30 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-secondary-dim">
                                Ready
                            </span>
                        </div>

                        <h2 class="font-headline text-3xl font-extrabold leading-tight text-on-surface">{{ $assessment->title }}</h2>
                        <p class="mt-4 max-w-3xl leading-relaxed text-on-surface-variant">
                            {{ $assessment->instructions ?: 'Read each item carefully and answer the questions prepared by your teacher.' }}
                        </p>

                        <dl class="mt-8 grid gap-4 text-sm sm:grid-cols-3">
                            <div class="rounded-xl bg-surface-container-low p-4">
                                <dt class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Target</dt>
                                <dd class="mt-1 font-bold text-on-surface">{{ str($assessment->target_section ?? 'all')->replace('_', ' ')->title() }}</dd>
                            </div>
                            <div class="rounded-xl bg-surface-container-low p-4">
                                <dt class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Focus</dt>
                                <dd class="mt-1 font-bold text-on-surface">{{ collect($assessment->focus_areas ?? [])->join(', ') ?: 'General Skills' }}</dd>
                            </div>
                            <div class="rounded-xl bg-surface-container-low p-4">
                                <dt class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Questions</dt>
                                <dd class="mt-1 font-bold text-on-surface">{{ count($manualQuestions) ?: ($assetPath ? 'File Based' : 'Teacher Guided') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </section>

            <section class="mt-8 rounded-2xl bg-white p-6 shadow-[0_20px_55px_rgba(0,94,159,0.06)] sm:p-8">
                <h3 class="font-headline text-2xl font-extrabold text-on-surface">Assessment Items</h3>

                @if ($manualQuestions)
                    <form class="mt-6 space-y-6">
                        @foreach ($manualQuestions as $index => $question)
                            <fieldset class="rounded-2xl border border-outline-variant/15 bg-surface-container-low p-5">
                                <legend class="rounded-full bg-primary px-4 py-2 text-xs font-black uppercase tracking-widest text-on-primary">Question {{ $index + 1 }}</legend>
                                <h4 class="mt-4 font-headline text-xl font-bold text-on-surface">{{ $question['question'] ?? 'Untitled question' }}</h4>
                                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                    @foreach (($question['answers'] ?? []) as $letter => $answer)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-xl bg-white p-4 transition-colors hover:bg-primary/5">
                                            <input class="text-primary focus:ring-primary" type="radio" name="answers[{{ $index }}]" value="{{ $letter }}">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-container/20 text-sm font-black text-primary">{{ $letter }}</span>
                                            <span class="font-medium text-on-surface">{{ $answer }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach

                        <button class="inline-flex items-center gap-2 rounded-full bg-primary px-6 py-3 font-black text-on-primary transition-colors hover:bg-primary-dim" type="button">
                            Submit Answers
                            <span class="material-symbols-outlined text-lg">check_circle</span>
                        </button>
                    </form>
                @elseif ($assetPath)
                    <div class="mt-6 rounded-2xl bg-surface-container-low p-6">
                        <p class="text-on-surface-variant">This assessment uses an uploaded file from your teacher.</p>
                        <a class="mt-4 inline-flex items-center gap-2 rounded-full bg-primary px-6 py-3 font-black text-on-primary transition-colors hover:bg-primary-dim" href="{{ $assetUrl }}" target="_blank" rel="noopener">
                            Open Assessment File
                            <span class="material-symbols-outlined text-lg">open_in_new</span>
                        </a>
                    </div>
                @else
                    <div class="mt-6 rounded-2xl bg-surface-container-low p-6 text-on-surface-variant">
                        Your teacher has published this assessment, but no manual questions or uploaded file were attached.
                    </div>
                @endif
            </section>
        </main>
    </div>
</x-app-layout>
