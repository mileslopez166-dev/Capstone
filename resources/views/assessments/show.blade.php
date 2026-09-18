<x-app-layout>
    @php
        $teacherName = Auth::user()->name;
        $teacherInitials = collect(explode(' ', $teacherName))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
        $assetPath = $assessment->asset_path;
        $assetExtension = $assetPath ? strtolower(pathinfo($assetPath, PATHINFO_EXTENSION)) : null;
        $isImageAsset = in_array($assetExtension, ['jpg', 'jpeg', 'png'], true);
        $assetUrl = $assetPath ? \Illuminate\Support\Facades\Storage::url($assetPath) : null;
        $manualQuestions = $assessment->manual_questions ?? [];
        $storyTitle = $assessment->story_title;
        $storyDescription = $assessment->story_description;
        $assessmentTypeLabels = [
            'silent_reading' => 'Silent Reading',
            'oral_reading' => 'Oral Reading Assessment',
            'listening_comprehension' => 'Listening Comprehension Assessment',
            'group_screening' => 'Group Screening Test',
        ];
        $assessmentTypeLabel = $assessmentTypeLabels[$assessment->assessment_type ?? 'silent_reading'] ?? 'Silent Reading';
        $isPublished = $assessment->status === 'published';
        $availabilityLabel = $isPublished ? 'Unlocked' : 'Locked';
        $nextAvailability = $isPublished ? 'draft' : 'published';
        $availabilityButtonLabel = $isPublished ? 'Lock Assessment' : 'Unlock Assessment';
        $availabilityIcon = $isPublished ? 'lock' : 'lock_open';
        $availabilityConfirm = $isPublished
            ? 'Lock this assessment? Students will no longer be able to answer it.'
            : 'Unlock this assessment? Students will be able to answer it.';
        $retryLimitFormValue = old('retry_limit', $assessment->retryLimitFormValue());
        $totalIncludedAttempts = 1 + (int) $assessment->retry_limit;
    @endphp

    <div class="min-h-screen bg-surface lg:flex" x-data="{ mobileMenuOpen: false }">
        <div class="fixed inset-y-0 left-0 z-40 w-72 max-w-[85vw] -translate-x-full transition-transform duration-300 lg:translate-x-0" :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'">
            <x-teacher-sidebar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" active="assessments" />
        </div>

        <div
            class="fixed inset-0 z-30 bg-slate-950/40 transition-opacity lg:hidden"
            x-show="mobileMenuOpen"
            x-transition.opacity
            @click="mobileMenuOpen = false"
        ></div>

        <main class="min-h-screen flex-1 lg:ml-72">
            <x-teacher-topbar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" search-placeholder="Search assessment title...">
                <x-slot:mobileTrigger>
                    <button class="rounded-full bg-surface-container-low p-2 text-on-surface lg:hidden" type="button" @click="mobileMenuOpen = true">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                </x-slot:mobileTrigger>
            </x-teacher-topbar>

            <div class="mx-auto max-w-6xl p-5 sm:p-8">
                <div class="teacher-workspace-heading mb-8 flex flex-wrap items-center justify-between gap-4">
                    <a class="inline-flex items-center gap-2 font-bold text-primary transition-colors hover:text-primary-dim" href="{{ route('assessments.index') }}">
                        <span class="material-symbols-outlined text-lg">arrow_back</span>
                        Back to Assessment Builder
                    </a>
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('assessments.availability', $assessment) }}" data-confirm-message="{{ $availabilityConfirm }}" onsubmit="return confirm(this.dataset.confirmMessage);">
                            @csrf
                            @method('PATCH')
                            <input name="status" type="hidden" value="{{ $nextAvailability }}">
                            <button class="inline-flex items-center justify-center gap-2 rounded-full {{ $isPublished ? 'bg-surface-container-high text-on-surface-variant hover:bg-surface-container-highest' : 'bg-secondary text-on-secondary hover:bg-secondary-dim' }} px-4 py-2 text-xs font-black uppercase tracking-widest transition-colors" type="submit">
                                {{ $availabilityButtonLabel }}
                                <span class="material-symbols-outlined text-sm">{{ $availabilityIcon }}</span>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('assessments.destroy', $assessment) }}" onsubmit="return confirm('Delete this assessment? Student submissions for this assessment will also be removed.');">
                        @csrf
                        @method('DELETE')
                        <button class="inline-flex items-center justify-center gap-2 rounded-full bg-error px-4 py-2 text-xs font-black uppercase tracking-widest text-on-error transition-colors hover:bg-red-700" type="submit">
                            Delete Assessment
                            <span class="material-symbols-outlined text-sm">delete</span>
                        </button>
                        </form>
                    </div>
                </div>

                @if (session('status'))
                    <p class="mb-6 rounded-lg bg-secondary-container/30 p-4 text-secondary-dim" role="status">{{ session('status') }}</p>
                @endif

                <x-assessment-student-picker :assessment="$assessment" :students="$eligibleStudents" />

                <form class="mb-8 flex flex-wrap items-end gap-4 border-b border-outline-variant/20 pb-6" method="POST" action="{{ route('assessments.retries', $assessment) }}">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="mb-2 block text-sm font-bold" for="retry_limit">Retries after first attempt</label>
                        <select class="w-48 rounded-lg border-outline-variant/30 bg-white p-3" id="retry_limit" name="retry_limit" required>
                            @foreach (\App\Models\Assessment::retryLimitOptions() as $value => $label)
                                <option value="{{ $value }}" @selected((string) $retryLimitFormValue === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-3 font-bold text-on-primary" type="submit">
                        <span class="material-symbols-outlined text-lg">save</span>Save Retry Limit
                    </button>
                    <p class="py-3 text-sm text-on-surface-variant">
                        @if ($assessment->hasUnlimitedRetries())
                            Unlimited attempts per student
                        @else
                            {{ $totalIncludedAttempts }} total {{ Str::plural('attempt', $totalIncludedAttempts) }} per student
                        @endif
                    </p>
                    @error('retry_limit')
                        <p class="w-full text-sm text-error">{{ $message }}</p>
                    @enderror
                </form>

                <section class="overflow-hidden rounded-2xl border border-outline-variant/10 bg-surface-container-lowest shadow-[0_24px_70px_rgba(0,94,159,0.08)]">
                    <div class="grid lg:grid-cols-[22rem_1fr]">
                        <div class="relative min-h-80 overflow-hidden bg-gradient-to-br from-primary to-primary-container">
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

                            <div class="absolute bottom-0 left-0 right-0 p-8 text-white">
                                <p class="text-xs font-black uppercase tracking-[0.25em] text-white/70">Assessment Detail</p>
                                <h1 class="mt-2 font-headline text-4xl font-black leading-tight">{{ ucfirst($assessment->subject) }}</h1>
                                <p class="mt-2 text-sm font-semibold text-white/80">{{ $assessment->created_at?->format('F d, Y') }}</p>
                            </div>
                        </div>

                        <div class="p-6 sm:p-8">
                            <div class="mb-5 flex flex-wrap gap-2">
                                <span class="rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest {{ $assessment->subject === 'literacy' ? 'bg-primary-container/20 text-primary' : 'bg-secondary-container/30 text-secondary-dim' }}">
                                    {{ $assessment->subject }}
                                </span>
                                <span class="rounded-full bg-surface-container-low px-3 py-1 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">
                                    {{ str($assessment->quiz_type ?? 'multiple_choice')->replace('_', ' ')->title() }}
                                </span>
                                <span class="rounded-full bg-primary-container/10 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-primary">
                                    Story Assessment
                                </span>
                                <span class="rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest {{ $isPublished ? 'bg-secondary-container/30 text-secondary-dim' : 'bg-error-container/30 text-error' }}">
                                    {{ $availabilityLabel }}
                                </span>
                            </div>

                            <h2 class="font-headline text-3xl font-extrabold leading-tight text-on-surface">{{ $assessment->title }}</h2>
                            <p class="mt-4 max-w-3xl leading-relaxed text-on-surface-variant">
                                {{ $assessment->instructions ?: 'No detailed instructions were added for this assessment.' }}
                            </p>

                            <dl class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                <div class="rounded-xl bg-surface-container-low p-4">
                                    <dt class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Target Section</dt>
                                    <dd class="mt-2 font-bold text-on-surface">{{ str($assessment->target_section ?? 'all')->replace('_', ' ')->title() }}</dd>
                                </div>
                                <div class="rounded-xl bg-surface-container-low p-4">
                                    <dt class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Focus Areas</dt>
                                    <dd class="mt-2 font-bold text-on-surface">{{ collect($assessment->focus_areas ?? [])->join(', ') ?: 'None selected' }}</dd>
                                </div>
                                <div class="rounded-xl bg-surface-container-low p-4">
                                    <dt class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Material</dt>
                                    <dd class="mt-2 font-bold text-on-surface">{{ $assetPath ? strtoupper($assetExtension) : 'Manual / Activity' }}</dd>
                                </div>
                                <div class="rounded-xl bg-surface-container-low p-4">
                                    <dt class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Questions</dt>
                                    <dd class="mt-2 font-bold text-on-surface">{{ count($manualQuestions) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </section>

                <section class="mt-8 grid gap-6 lg:grid-cols-[1fr_0.8fr]">
                    <div class="rounded-2xl border border-outline-variant/10 bg-surface-container-lowest p-6 shadow-sm sm:p-8">
                        <h2 class="mb-5 font-headline text-2xl font-extrabold text-on-surface">Assessment Content</h2>

                        @if ($storyTitle || $storyDescription)
                            <article class="mb-6 rounded-xl bg-surface-container-low p-5">
                                <p class="text-xs font-black uppercase tracking-widest text-primary">Story</p>
                                <p class="mt-2 text-sm font-bold text-on-surface-variant">{{ $assessmentTypeLabel }}</p>
                                @if ($storyTitle)
                                    <h3 class="mt-2 font-headline text-2xl font-bold text-on-surface">{{ $storyTitle }}</h3>
                                @endif
                                @if ($storyDescription)
                                    <p class="mt-3 whitespace-pre-line leading-relaxed text-on-surface-variant">{{ $storyDescription }}</p>
                                @endif
                            </article>
                        @endif

                        @if ($manualQuestions)
                            <div class="space-y-5">
                                @foreach ($manualQuestions as $index => $question)
                                    <article class="rounded-xl bg-surface-container-low p-5">
                                        <p class="text-xs font-black uppercase tracking-widest text-primary">Question {{ $index + 1 }}</p>
                                        <h3 class="mt-2 font-headline text-xl font-bold text-on-surface">{{ $question['question'] ?? 'Untitled question' }}</h3>
                                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                            @foreach (($question['answers'] ?? []) as $letter => $answer)
                                                <div class="rounded-lg bg-white p-4 {{ ($question['correct_answer'] ?? null) === $letter ? 'ring-2 ring-secondary-container' : '' }}">
                                                    <p class="text-xs font-black uppercase tracking-widest text-on-surface-variant">Answer {{ $letter }}</p>
                                                    <p class="mt-1 font-medium text-on-surface">{{ $answer }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @elseif ($assetPath)
                            <div class="rounded-xl bg-surface-container-low p-6">
                                <p class="text-sm text-on-surface-variant">This assessment uses an uploaded asset.</p>
                                <a class="mt-4 inline-flex items-center gap-2 rounded-full bg-primary px-5 py-3 text-sm font-black text-on-primary transition-colors hover:bg-primary-dim" href="{{ $assetUrl }}" target="_blank" rel="noopener">
                                    Open Uploaded File
                                    <span class="material-symbols-outlined text-lg">open_in_new</span>
                                </a>
                            </div>
                        @else
                            <div class="rounded-xl bg-surface-container-low p-6 text-sm text-on-surface-variant">
                                No manual question or uploaded asset is attached to this assessment.
                            </div>
                        @endif
                    </div>

                    <aside class="rounded-2xl border border-outline-variant/10 bg-surface-container-low p-6 shadow-sm sm:p-8">
                        <h2 class="font-headline text-2xl font-extrabold text-on-surface">Publishing Summary</h2>
                        <div class="mt-6 space-y-4">
                            <div class="rounded-xl bg-white p-4">
                                <p class="text-xs font-black uppercase tracking-widest text-on-surface-variant">Visibility</p>
                                <p class="mt-1 font-bold text-on-surface">{{ $isPublished ? 'Unlocked for students' : 'Locked from student answering' }}</p>
                            </div>
                            <div class="rounded-xl bg-white p-4">
                                <p class="text-xs font-black uppercase tracking-widest text-on-surface-variant">Delivery</p>
                                <p class="mt-1 font-bold text-on-surface">Story Assessment</p>
                            </div>
                            <div class="rounded-xl bg-white p-4">
                                <p class="text-xs font-black uppercase tracking-widest text-on-surface-variant">Prepared By</p>
                                <p class="mt-1 font-bold text-on-surface">{{ $teacherName }}</p>
                            </div>
                        </div>
                    </aside>
                </section>
            </div>
        </main>
    </div>
</x-app-layout>
