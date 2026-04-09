<x-app-layout>
    @php
        $teacherName = Auth::user()->name;
        $teacherInitials = collect(explode(' ', $teacherName))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');

        $literacyCount = $assessments->where('subject', 'literacy')->count();
        $numeracyCount = $assessments->where('subject', 'numeracy')->count();
        $publishedCount = $assessments->where('status', 'published')->count();
    @endphp

    <div class="min-h-screen lg:flex" x-data="{ mobileMenuOpen: false }">
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

            <div class="mx-auto max-w-7xl space-y-8 p-5 sm:p-8">
                <section class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                    <div class="rounded-lg bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,0,0,0.03)]">
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-primary">Assessment Builder</p>
                        <h1 class="mt-3 font-headline text-4xl font-extrabold text-on-surface">Create literacy and numeracy assessments</h1>
                        <p class="mt-4 max-w-2xl text-on-surface-variant">Teachers can prepare assessment titles, set the subject area, and publish them to the student activity queue. Published items become visible on the student side immediately.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                        <div class="rounded-lg bg-surface-container-lowest p-6 shadow-[0_10px_30px_rgba(0,0,0,0.02)]">
                            <p class="text-xs font-black uppercase tracking-widest text-on-surface-variant">Total</p>
                            <p class="mt-3 font-headline text-4xl font-black text-on-surface">{{ $assessments->count() }}</p>
                        </div>
                        <div class="rounded-lg bg-surface-container-lowest p-6 shadow-[0_10px_30px_rgba(0,0,0,0.02)]">
                            <p class="text-xs font-black uppercase tracking-widest text-on-surface-variant">Literacy</p>
                            <p class="mt-3 font-headline text-4xl font-black text-primary">{{ $literacyCount }}</p>
                        </div>
                        <div class="rounded-lg bg-surface-container-lowest p-6 shadow-[0_10px_30px_rgba(0,0,0,0.02)]">
                            <p class="text-xs font-black uppercase tracking-widest text-on-surface-variant">Published</p>
                            <p class="mt-3 font-headline text-4xl font-black text-secondary">{{ $publishedCount }}</p>
                        </div>
                    </div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                    <div class="rounded-lg bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,0,0,0.03)]">
                        <div class="mb-6">
                            <h2 class="font-headline text-2xl font-extrabold text-on-surface">Make a new assessment</h2>
                            <p class="mt-2 text-sm text-on-surface-variant">Use this form to create a literacy or numeracy assessment. Draft stays teacher-only, while published makes it visible to students.</p>
                        </div>

                        @if (session('status'))
                            <div class="mb-6 rounded-lg border border-secondary/20 bg-secondary-container/30 px-4 py-3 text-sm font-medium text-on-surface">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form class="space-y-6" method="POST" action="{{ route('assessments.store') }}">
                            @csrf

                            <div>
                                <label class="mb-2 block text-xs font-black uppercase tracking-widest text-on-surface-variant" for="title">Assessment Title</label>
                                <input class="w-full rounded-lg border-outline-variant/20 bg-surface-container-low px-4 py-3 text-sm focus:border-primary focus:ring-primary/20" id="title" name="title" type="text" value="{{ old('title') }}" placeholder="Quarter 1 Reading Check" required>
                                @error('title')
                                    <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <p class="mb-3 text-xs font-black uppercase tracking-widest text-on-surface-variant">Subject Area</p>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach (['literacy' => 'Literacy', 'numeracy' => 'Numeracy'] as $value => $label)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-outline-variant/20 bg-surface-container-low px-4 py-4 transition-all hover:border-primary-container hover:bg-primary/5">
                                            <input class="h-4 w-4 border-outline text-primary focus:ring-primary/20" name="subject" type="radio" value="{{ $value }}" {{ old('subject', 'literacy') === $value ? 'checked' : '' }}>
                                            <div>
                                                <p class="font-headline font-bold text-on-surface">{{ $label }}</p>
                                                <p class="text-sm text-on-surface-variant">{{ $value === 'literacy' ? 'Reading, language, and comprehension tasks' : 'Math, number sense, and problem-solving tasks' }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                @error('subject')
                                    <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-xs font-black uppercase tracking-widest text-on-surface-variant" for="instructions">Instructions</label>
                                <textarea class="min-h-[10rem] w-full rounded-lg border-outline-variant/20 bg-surface-container-low px-4 py-3 text-sm focus:border-primary focus:ring-primary/20" id="instructions" name="instructions" placeholder="Add a short teacher note so students know what to expect.">{{ old('instructions') }}</textarea>
                                @error('instructions')
                                    <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-xs font-black uppercase tracking-widest text-on-surface-variant" for="status">Publish State</label>
                                <select class="w-full rounded-lg border-outline-variant/20 bg-surface-container-low px-4 py-3 text-sm focus:border-primary focus:ring-primary/20" id="status" name="status">
                                    <option value="published" {{ old('status', 'published') === 'published' ? 'selected' : '' }}>Published</option>
                                    <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                                </select>
                                @error('status')
                                    <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <button class="inline-flex items-center rounded-lg bg-primary px-6 py-3 text-sm font-black uppercase tracking-widest text-on-primary transition-colors hover:bg-primary-dim" type="submit">
                                Create Assessment
                            </button>
                        </form>
                    </div>

                    <div class="rounded-lg bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,0,0,0.03)]">
                        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <h2 class="font-headline text-2xl font-extrabold text-on-surface">Created assessments</h2>
                                <p class="mt-2 text-sm text-on-surface-variant">This list shows what you have already prepared for students.</p>
                            </div>
                            <span class="rounded-full bg-surface-container-low px-3 py-1 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">{{ $numeracyCount }} Numeracy</span>
                        </div>

                        @if ($assessments->isEmpty())
                            <div class="flex min-h-[24rem] flex-col items-center justify-center rounded-xl bg-surface-container-low p-8 text-center">
                                <span class="material-symbols-outlined text-6xl text-outline-variant">note_add</span>
                                <h3 class="mt-4 font-headline text-2xl font-bold text-on-surface">No assessments yet</h3>
                                <p class="mt-2 max-w-xl text-sm text-on-surface-variant">Your assessment maker is ready. Create your first literacy or numeracy assessment to start filling the student activity queue.</p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach ($assessments as $assessment)
                                    <div class="rounded-xl border border-outline-variant/15 bg-surface-container-low p-5">
                                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <div class="mb-3 flex flex-wrap gap-2">
                                                    <span class="rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest {{ $assessment->subject === 'literacy' ? 'bg-primary-container/20 text-primary' : 'bg-secondary-container/30 text-secondary-dim' }}">
                                                        {{ $assessment->subject }}
                                                    </span>
                                                    <span class="rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest {{ $assessment->status === 'published' ? 'bg-secondary-container/30 text-secondary-dim' : 'bg-surface-container-high text-on-surface-variant' }}">
                                                        {{ $assessment->status }}
                                                    </span>
                                                </div>
                                                <h3 class="font-headline text-xl font-bold text-on-surface">{{ $assessment->title }}</h3>
                                                <p class="mt-2 text-sm text-on-surface-variant">{{ $assessment->instructions ?: 'No instructions added yet.' }}</p>
                                            </div>
                                            <div class="text-sm text-on-surface-variant">
                                                {{ $assessment->created_at->format('M d, Y') }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        </main>
    </div>
</x-app-layout>
