<x-app-layout>
    @php
        $teacherName = Auth::user()->name;
        $teacherInitials = collect(explode(' ', $teacherName))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
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
                <form id="assessment-builder-form" class="space-y-8" method="POST" action="{{ route('assessments.store') }}" enctype="multipart/form-data">
                    @csrf
                    <input id="assessment-status" name="status" type="hidden" value="{{ old('status', 'published') }}">
                    <input id="delivery-method" name="delivery_method" type="hidden" value="manual">

                    <header class="mb-10 flex flex-col justify-between gap-6 md:flex-row md:items-end">
                        <div>
                            <span class="mb-2 block text-xs font-bold uppercase tracking-[0.25em] text-primary">Curriculum Builder</span>
                            <h1 class="font-display text-4xl font-extrabold text-on-surface">Create New Assessment</h1>
                            <p class="mt-2 max-w-xl text-on-surface-variant">Configure a student-ready assessment. Choose the focus area, add instructions, then save it locked or publish it to the student activity queue.</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button class="rounded-full border border-outline-variant px-6 py-2.5 font-semibold text-on-surface-variant transition-all hover:border-primary hover:bg-primary/5 hover:text-primary" type="submit" data-submit-status="draft">
                                Save Locked
                            </button>
                            <button class="rounded-full bg-primary px-8 py-2.5 font-bold text-on-primary shadow-lg shadow-primary/20 transition-all hover:bg-primary-dim" type="submit" data-submit-status="published">
                                Publish Assessment
                            </button>
                        </div>
                    </header>

                    @if (session('status'))
                        <div class="rounded-lg border border-secondary/20 bg-secondary-container/30 px-4 py-3 text-sm font-medium text-on-surface">
                            {{ session('status') }}
                        </div>
                    @endif

                    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <div class="space-y-6 lg:col-span-2">
                            <div class="rounded-lg border border-outline-variant/10 bg-surface-container-lowest p-6 shadow-sm sm:p-8">
                                <h2 class="mb-6 flex items-center gap-2 font-headline text-xl font-bold">
                                    <span class="material-symbols-outlined text-primary">category</span>
                                    1. Assessment Foundation
                                </h2>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <label class="group relative cursor-pointer">
                                        <input class="peer sr-only" name="subject" type="radio" value="literacy" {{ old('subject', 'literacy') === 'literacy' ? 'checked' : '' }} data-subject-choice="literacy">
                                                <div class="rounded-lg border-2 border-transparent bg-surface-container-low p-6 transition-all group-hover:bg-surface-container-high peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:shadow-lg peer-checked:shadow-primary/10">
                                            <div class="mb-4 flex items-start justify-between">
                                                <div class="rounded-lg bg-white p-3 shadow-sm">
                                                    <span class="material-symbols-outlined text-3xl text-primary">auto_stories</span>
                                                </div>
                                                <span class="grid h-6 w-6 place-items-center rounded-full border-2 border-outline-variant peer-checked:border-primary peer-checked:bg-primary">
                                                    <span class="h-2 w-2 rounded-full bg-white"></span>
                                                </span>
                                            </div>
                                            <h3 class="mb-1 text-lg font-bold">Literacy</h3>
                                            <p class="text-sm leading-relaxed text-on-surface-variant">Reading, comprehension, vocabulary, and fluency development.</p>
                                        </div>
                                    </label>

                                    <label class="group relative cursor-pointer">
                                        <input class="peer sr-only" name="subject" type="radio" value="numeracy" {{ old('subject') === 'numeracy' ? 'checked' : '' }} data-subject-choice="numeracy">
                                                <div class="rounded-lg border-2 border-transparent bg-surface-container-low p-6 transition-all group-hover:bg-surface-container-high peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:shadow-lg peer-checked:shadow-primary/10">
                                            <div class="mb-4 flex items-start justify-between">
                                                <div class="rounded-lg bg-white p-3 shadow-sm">
                                                    <span class="material-symbols-outlined text-3xl text-primary">calculate</span>
                                                </div>
                                                <span class="grid h-6 w-6 place-items-center rounded-full border-2 border-outline-variant peer-checked:border-primary peer-checked:bg-primary">
                                                    <span class="h-2 w-2 rounded-full bg-white"></span>
                                                </span>
                                            </div>
                                            <h3 class="mb-1 text-lg font-bold">Numeracy</h3>
                                            <p class="text-sm leading-relaxed text-on-surface-variant">Arithmetic, number sense, spatial reasoning, and problem solving.</p>
                                        </div>
                                    </label>
                                </div>
                                @error('subject')
                                    <p class="mt-3 text-sm text-error">{{ $message }}</p>
                                @enderror

                                <div class="mt-8">
                                    <label class="mb-4 block text-sm font-bold text-on-surface-variant">Choose Quiz Type</label>
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                        @foreach ([
                                            ['value' => 'multiple_choice', 'icon' => 'checklist', 'label' => 'Multiple Choice'],
                                            ['value' => 'data_egg', 'icon' => 'egg_alt', 'label' => 'Interactive Egg'],
                                            ['value' => 'flashcards', 'icon' => 'pest_control', 'label' => 'Frog Flashcards'],
                                        ] as $quizType)
                                            <label class="group relative cursor-pointer">
                                                <input class="peer sr-only" name="quiz_type" type="radio" value="{{ $quizType['value'] }}" {{ old('quiz_type', 'multiple_choice') === $quizType['value'] ? 'checked' : '' }}>
                                                <div class="flex min-h-28 flex-col items-center justify-center rounded-lg border-2 border-transparent bg-surface-container-low p-4 text-center transition-all group-hover:bg-surface-container-high peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary peer-checked:shadow-lg peer-checked:shadow-primary/10">
                                                    <span class="material-symbols-outlined mb-2 text-primary">{{ $quizType['icon'] }}</span>
                                                    <span class="text-sm font-bold">{{ $quizType['label'] }}</span>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('quiz_type')
                                        <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="mt-8 space-y-4">
                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-on-surface-variant" for="title">Assessment Title</label>
                                        <input class="w-full rounded-sm border-none bg-surface-container-low px-4 py-3 text-lg font-medium focus:ring-2 focus:ring-primary" id="title" name="title" placeholder="e.g., Mid-Term Reading Fluency Diagnostic" type="text" value="{{ old('title') }}" required>
                                        @error('title')
                                            <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-on-surface-variant" for="instructions">Detailed Description</label>
                                        <textarea class="w-full rounded-sm border-none bg-surface-container-low px-4 py-3 focus:ring-2 focus:ring-primary" id="instructions" name="instructions" placeholder="Provide context or instructions for the students..." rows="4">{{ old('instructions') }}</textarea>
                                        @error('instructions')
                                            <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-lg border border-outline-variant/10 bg-surface-container-lowest p-6 shadow-sm sm:p-8">
                                <h2 class="mb-6 flex items-center gap-2 font-headline text-xl font-bold">
                                    <span class="material-symbols-outlined text-primary">edit_note</span>
                                    2. Story & Questions
                                </h2>



                                <div class="space-y-4" id="input-manual">
                                    @php
                                        $oldManualQuestions = old('manual_questions', [[
                                            'question' => '',
                                            'answers' => ['A' => '', 'B' => '', 'C' => '', 'D' => ''],
                                            'correct_answer' => 'A',
                                        ]]);
                                    @endphp

                                    <div class="rounded-lg border border-outline-variant/20 bg-surface p-6">
                                        <div class="mb-5 flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded bg-primary-container/20 text-primary">
                                                <span class="material-symbols-outlined">auto_stories</span>
                                            </div>
                                            <div>
                                                <h3 class="font-headline text-lg font-bold text-on-surface">Story Details</h3>
                                                <p class="text-sm text-on-surface-variant">Add the story students will read before answering.</p>
                                            </div>
                                        </div>
                                        <div class="space-y-4">

                                            <div>
                                                <label class="mb-3 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Assessment Type</label>
                                                <div class="grid gap-3 md:grid-cols-2">
                                                    @foreach ([
                                                        'silent_reading' => 'Silent Reading',
                                                        'oral_reading' => 'Oral Reading Assessment',
                                                        'listening_comprehension' => 'Listening Comprehension Assessment',
                                                        'group_screening' => 'Group Screening Test',
                                                    ] as $typeValue => $typeLabel)
                                                        <label class="flex cursor-pointer items-center gap-3 rounded-lg bg-white p-4 text-sm font-bold text-on-surface shadow-sm ring-1 ring-outline-variant/20 transition-all hover:ring-primary/40 has-[:checked]:bg-primary/10 has-[:checked]:text-primary has-[:checked]:ring-primary">
                                                            <input class="text-primary focus:ring-primary" name="assessment_type" type="radio" value="{{ $typeValue }}" @checked(old('assessment_type', 'silent_reading') === $typeValue)>
                                                            <span>{{ $typeLabel }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                @error('assessment_type')
                                                    <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div class="overflow-hidden rounded-lg bg-surface-container-low text-xs shadow-sm ring-1 ring-outline-variant/15">
                                                <div class="grid grid-cols-4 gap-0 bg-surface-container-high px-3 py-2 font-black uppercase tracking-wider text-on-surface-variant">
                                                    <span>Assessment Type</span>
                                                    <span>Reading Mode</span>
                                                    <span>Main Purpose</span>
                                                    <span>Output</span>
                                                </div>
                                                @foreach ([
                                                    ['Oral Reading Assessment', 'Read aloud', 'Measure fluency + accuracy', 'Reading errors, WCPM, comprehension'],
                                                    ['Silent Reading Assessment', 'Read silently', 'Measure comprehension', 'Score and reading level'],
                                                    ['Listening Comprehension Assessment', 'Listen only', 'Measure understanding', 'Listening score'],
                                                    ['Group Screening Test', 'Class activity', 'Identify struggling readers', 'Students for further testing'],
                                                ] as $row)
                                                    <div class="grid grid-cols-4 gap-0 px-3 py-2 text-on-surface-variant odd:bg-white even:bg-surface-container-lowest">
                                                        @foreach ($row as $cell)
                                                            <span class="pr-3">{{ $cell }}</span>
                                                        @endforeach
                                                    </div>
                                                @endforeach
                                            </div>

                                            <div>
                                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="story_title">Story Title</label>
                                                <input class="w-full rounded-sm border-outline-variant/20 bg-white px-4 py-3 text-sm focus:border-primary focus:ring-primary" id="story_title" name="story_title" type="text" value="{{ old('story_title') }}" placeholder="Enter story title">
                                                @error('story_title')
                                                    <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div>
                                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="story_description">Story Description</label>
                                                <textarea class="w-full rounded-sm border-outline-variant/20 bg-white px-4 py-3 text-sm focus:border-primary focus:ring-primary" id="story_description" name="story_description" rows="7" placeholder="Write the story or reading passage here...">{{ old('story_description') }}</textarea>
                                                @error('story_description')
                                                    <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-lg border border-dashed border-outline-variant/30 bg-surface p-5" id="question-upload-panel">
                                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                            <div>
                                                <h3 class="font-headline text-lg font-bold text-on-surface">Import Questions from TXT</h3>
                                                <p class="text-sm text-on-surface-variant">Use Question:, A-D answers, and Correct Answer: A. Imported questions stay editable.</p>
                                            </div>
                                            <label class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-full bg-surface-container-high px-5 py-2.5 text-sm font-bold text-on-surface-variant transition-colors hover:bg-primary/10 hover:text-primary">
                                                <span class="material-symbols-outlined text-lg">upload_file</span>
                                                Upload TXT
                                                <input class="sr-only" id="question-text-file" type="file" accept=".txt,text/plain">
                                            </label>
                                        </div>
                                        <p class="mt-3 hidden text-sm font-medium" id="question-import-status"></p>
                                    </div>

                                    <div id="manual-questions" class="space-y-4">
                                        @foreach ($oldManualQuestions as $questionIndex => $manualQuestion)
                                            <div class="manual-question-card rounded-lg border border-outline-variant/20 bg-surface p-6" data-question-card>
                                                <div class="mb-4 flex items-center gap-4">
                                                    <div class="question-number flex h-10 w-10 shrink-0 items-center justify-center rounded border border-outline-variant/30 bg-white font-bold">Q{{ $questionIndex + 1 }}</div>
                                                    <div class="min-w-0 flex-1">
                                                        <p class="font-bold">Manual Question</p>
                                                        <p class="text-xs text-on-surface-variant">This question will be saved with the assessment.</p>
                                                    </div>
                                                    <button class="remove-question rounded-full p-2 text-on-surface-variant transition-colors hover:bg-error-container/20 hover:text-error" type="button" aria-label="Remove question">
                                                        <span class="material-symbols-outlined">delete</span>
                                                    </button>
                                                </div>
                                                <div class="space-y-4">
                                                    <div>
                                                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Question</label>
                                                        <textarea class="w-full rounded-sm border-outline-variant/20 bg-white px-4 py-3 text-sm focus:border-primary focus:ring-primary" name="manual_questions[{{ $questionIndex }}][question]" rows="3" placeholder="Type the question here...">{{ $manualQuestion['question'] ?? '' }}</textarea>
                                                        @error("manual_questions.{$questionIndex}.question")
                                                            <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                                        @enderror
                                                    </div>
                                                    <div class="grid gap-3 sm:grid-cols-2">
                                                        @foreach (['A', 'B', 'C', 'D'] as $answer)
                                                            <div>
                                                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Answer {{ $answer }}</label>
                                                                <input class="w-full rounded-sm border-outline-variant/20 bg-white px-4 py-3 text-sm focus:border-primary focus:ring-primary" name="manual_questions[{{ $questionIndex }}][answers][{{ $answer }}]" type="text" value="{{ $manualQuestion['answers'][$answer] ?? '' }}" placeholder="Option {{ $answer }}">
                                                                @error("manual_questions.{$questionIndex}.answers.{$answer}")
                                                                    <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                                                @enderror
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <div>
                                                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Correct Answer</label>
                                                        <select class="w-full rounded-sm border-outline-variant/20 bg-white px-4 py-3 text-sm focus:border-primary focus:ring-primary" name="manual_questions[{{ $questionIndex }}][correct_answer]">
                                                            @foreach (['A', 'B', 'C', 'D'] as $answer)
                                                                <option value="{{ $answer }}" @selected(($manualQuestion['correct_answer'] ?? 'A') === $answer)>Answer {{ $answer }}</option>
                                                            @endforeach
                                                        </select>
                                                        @error("manual_questions.{$questionIndex}.correct_answer")
                                                            <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    @error('manual_questions')
                                        <p class="text-sm text-error">{{ $message }}</p>
                                    @enderror

                                    <button id="add-question-button" class="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-dashed border-outline-variant py-4 font-bold text-on-surface-variant transition-all hover:bg-surface-container-high" type="button">
                                        <span class="material-symbols-outlined">add_circle</span>
                                        Add Question
                                    </button>
                                </div>
                                @error('delivery_method')
                                    <p class="mt-3 text-sm text-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <aside class="space-y-6">
                            <div class="relative overflow-hidden rounded-lg border border-outline-variant/10 bg-surface-container-low p-6">
                                <div class="absolute right-0 top-0 p-4 opacity-10">
                                    <span class="material-symbols-outlined text-7xl text-primary" id="bg-icon">menu_book</span>
                                </div>
                                <h2 class="relative z-10 mb-4 font-headline text-lg font-bold">Advanced Specs</h2>
                                <div class="relative z-10 space-y-5">

                                    <div>
                                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Target Section</label>
                                        <select class="w-full rounded-sm border border-outline-variant/20 bg-white p-3 font-medium focus:ring-primary" name="target_section">
                                            <option value="all" @selected(old('target_section', 'all') === 'all')>All Sections</option>
                                            <option value="section_a" @selected(old('target_section') === 'section_a')>Section A</option>
                                            <option value="section_b" @selected(old('target_section') === 'section_b')>Section B</option>
                                            <option value="section_c" @selected(old('target_section') === 'section_c')>Section C</option>
                                        </select>
                                        @error('target_section')
                                            <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <hr class="border-outline-variant/20">

                                    <div class="space-y-3" id="literacy-options">
                                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Literacy Focus</label>
                                        @foreach (['Reading Fluency', 'Comprehension Depth', 'Spelling & Vocabulary'] as $focus)
                                            <label class="flex cursor-pointer items-center gap-3 rounded-sm border border-transparent bg-white p-3 transition-all hover:border-primary/20 hover:bg-primary/5" data-focus-choice>
                                                <input class="rounded-sm text-primary focus:ring-primary" name="focus_areas[]" value="{{ $focus }}" type="checkbox" data-focus-input="literacy" @checked(in_array($focus, old('focus_areas', ['Reading Fluency']), true))>
                                                <span class="text-sm font-medium">{{ $focus }}</span>
                                            </label>
                                        @endforeach
                                    </div>

                                    <div class="hidden space-y-3" id="numeracy-options">
                                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Numeracy Focus</label>
                                        @foreach (['Mental Arithmetic', 'Problem Solving', 'Data Interpretation'] as $focus)
                                            <label class="flex cursor-pointer items-center gap-3 rounded-sm border border-transparent bg-white p-3 transition-all hover:border-primary/20 hover:bg-primary/5" data-focus-choice>
                                                <input class="rounded-sm text-primary focus:ring-primary" name="focus_areas[]" value="{{ $focus }}" type="checkbox" data-focus-input="numeracy" @checked(in_array($focus, old('focus_areas', []), true))>
                                                <span class="text-sm font-medium">{{ $focus }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('focus_areas')
                                        <p class="text-sm text-error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-lg shadow-lg">
                                <div class="relative h-48 bg-gradient-to-br from-primary to-primary-container">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
                                    <div class="absolute inset-x-0 bottom-0 p-6">
                                        <span class="mb-1 block text-[10px] font-black uppercase tracking-[0.2em] text-white/60">Student Preview</span>
                                        <p class="font-bold leading-tight text-white">Students will see a friendly, focused activity screen built from this assessment.</p>
                                    </div>
                                </div>
                            </div>
                        </aside>
                    </section>
                </form>

                <section class="mt-8 rounded-lg border border-outline-variant/10 bg-surface-container-lowest p-6 shadow-sm sm:p-8">
                    <div class="mb-6">
                        <h2 class="font-headline text-2xl font-extrabold text-on-surface">Created Assessments</h2>
                        <p class="mt-2 text-sm text-on-surface-variant">Your prepared assessments stay here for quick review.</p>
                    </div>

                    @if ($assessments->isEmpty())
                        <div class="flex min-h-[16rem] flex-col items-center justify-center rounded-xl bg-surface-container-low p-8 text-center">
                            <span class="material-symbols-outlined text-6xl text-outline-variant">note_add</span>
                            <h3 class="mt-4 font-headline text-2xl font-bold text-on-surface">No assessments yet</h3>
                            <p class="mt-2 max-w-xl text-sm text-on-surface-variant">Create your first literacy or numeracy assessment to start filling the student activity queue.</p>
                        </div>
                    @else
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach ($assessments as $assessment)
                                @php
                                    $isPublished = $assessment->status === 'published';
                                    $availabilityLabel = $isPublished ? 'Unlocked' : 'Locked';
                                    $nextAvailability = $isPublished ? 'draft' : 'published';
                                    $availabilityButtonLabel = $isPublished ? 'Lock' : 'Unlock';
                                    $availabilityIcon = $isPublished ? 'lock' : 'lock_open';
                                    $availabilityConfirm = $isPublished
                                        ? 'Lock this assessment? Students will no longer be able to answer it.'
                                        : 'Unlock this assessment? Students will be able to answer it.';
                                @endphp
                                <article class="rounded-xl border border-outline-variant/15 bg-surface-container-low p-5">
                                    <div class="mb-3 flex flex-wrap gap-2">
                                        <span class="rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest {{ $assessment->subject === 'literacy' ? 'bg-primary-container/20 text-primary' : 'bg-secondary-container/30 text-secondary-dim' }}">
                                            {{ $assessment->subject }}
                                        </span>
                                        <span class="rounded-full bg-surface-container-high px-3 py-1 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">
                                            {{ str($assessment->quiz_type ?? 'multiple_choice')->replace('_', ' ')->title() }}
                                        </span>
                                        <span class="rounded-full bg-primary-container/10 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-primary">
                                            Story Assessment
                                        </span>
                                        <span class="rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest {{ $isPublished ? 'bg-secondary-container/30 text-secondary-dim' : 'bg-error-container/30 text-error' }}">
                                            {{ $availabilityLabel }}
                                        </span>
                                    </div>
                                    <h3 class="font-headline text-xl font-bold text-on-surface">{{ $assessment->title }}</h3>
                                    <p class="mt-2 text-sm text-on-surface-variant">{{ $assessment->instructions ?: 'No instructions added yet.' }}</p>
                                    <dl class="mt-4 grid gap-3 text-xs text-on-surface-variant sm:grid-cols-2">
                                        <div>
                                            <dt class="font-black uppercase tracking-widest">Target</dt>
                                            <dd class="mt-1 font-medium text-on-surface">{{ str($assessment->target_section ?? 'all')->replace('_', ' ')->title() }}</dd>
                                        </div>
                                        <div>
                                            <dt class="font-black uppercase tracking-widest">Focus</dt>
                                            <dd class="mt-1 font-medium text-on-surface">{{ collect($assessment->focus_areas ?? [])->join(', ') ?: 'None selected' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="font-black uppercase tracking-widest">Asset</dt>
                                            <dd class="mt-1 font-medium text-on-surface">{{ $assessment->asset_path ? basename($assessment->asset_path) : 'No file uploaded' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="font-black uppercase tracking-widest">Manual Questions</dt>
                                            <dd class="mt-1 font-medium text-on-surface">{{ count($assessment->manual_questions ?? []) }}</dd>
                                        </div>
                                    </dl>
                                    <div class="mt-5 flex flex-col gap-3 border-t border-outline-variant/15 pt-4 sm:flex-row sm:items-center sm:justify-between">
                                        <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">{{ $assessment->created_at->format('M d, Y') }}</p>
                                        <div class="flex flex-wrap gap-2">
                                            <a class="inline-flex items-center justify-center gap-2 rounded-full bg-primary px-4 py-2 text-xs font-black uppercase tracking-widest text-on-primary transition-colors hover:bg-primary-dim" href="{{ route('assessments.show', $assessment) }}">
                                                View Details
                                                <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                            </a>
                                            <form method="POST" action="{{ route('assessments.availability', $assessment) }}" data-confirm-message="{{ $availabilityConfirm }}" onsubmit="return confirm(this.dataset.confirmMessage);">
                                                @csrf
                                                @method('PATCH')
                                                <input name="status" type="hidden" value="{{ $nextAvailability }}">
                                                <button class="inline-flex items-center justify-center gap-2 rounded-full {{ $isPublished ? 'bg-surface-container-high text-on-surface-variant hover:bg-surface-container-highest' : 'bg-secondary px-4 text-on-secondary hover:bg-secondary-dim' }} px-4 py-2 text-xs font-black uppercase tracking-widest transition-colors" type="submit">
                                                    {{ $availabilityButtonLabel }}
                                                    <span class="material-symbols-outlined text-sm">{{ $availabilityIcon }}</span>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('assessments.destroy', $assessment) }}" onsubmit="return confirm('Delete this assessment? Student submissions for this assessment will also be removed.');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="inline-flex items-center justify-center gap-2 rounded-full bg-error px-4 py-2 text-xs font-black uppercase tracking-widest text-on-error transition-colors hover:bg-red-700" type="submit">
                                                    Delete
                                                    <span class="material-symbols-outlined text-sm">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        </main>
    </div>

    <script>
        const subjectChoices = document.querySelectorAll('[data-subject-choice]');
        const literacyOptions = document.getElementById('literacy-options');
        const numeracyOptions = document.getElementById('numeracy-options');
        const bgIcon = document.getElementById('bg-icon');
        const statusInput = document.getElementById('assessment-status');
        const statusButtons = document.querySelectorAll('[data-submit-status]');
        const focusInputs = document.querySelectorAll('[data-focus-input]');
        const focusChoices = document.querySelectorAll('[data-focus-choice]');
        const manualQuestions = document.getElementById('manual-questions');
        const addQuestionButton = document.getElementById('add-question-button');
        const questionUploadPanel = document.getElementById('question-upload-panel');
        const questionTextFile = document.getElementById('question-text-file');
        const questionImportStatus = document.getElementById('question-import-status');
        const assessmentTypeInputs = document.querySelectorAll('input[name="assessment_type"]');
        const activeButtonClasses = ['bg-primary', 'text-on-primary', 'shadow-lg', 'shadow-primary/20'];
        const inactiveButtonClasses = ['bg-surface-container-high', 'text-on-surface-variant'];
        const activeChoiceClasses = ['border-primary', 'bg-primary/10', 'text-primary', 'shadow-sm'];
        const inactiveChoiceClasses = ['border-transparent', 'bg-white'];

        function toggleSpecifics(type) {
            const isLiteracy = type === 'literacy';
            literacyOptions.classList.toggle('hidden', !isLiteracy);
            numeracyOptions.classList.toggle('hidden', isLiteracy);
            bgIcon.textContent = isLiteracy ? 'menu_book' : 'grid_view';
            bgIcon.classList.toggle('text-primary', isLiteracy);
            bgIcon.classList.toggle('text-secondary', !isLiteracy);
            focusInputs.forEach((input) => {
                input.disabled = input.dataset.focusInput !== type;
            });
            updateFocusHighlights();
        }


        function setButtonActive(button, isActive) {
            button.classList.toggle('hover:bg-primary-dim', isActive);
            button.classList.toggle('hover:text-primary', !isActive);
            button.classList.toggle('hover:bg-primary/5', !isActive);
            activeButtonClasses.forEach((className) => button.classList.toggle(className, isActive));
            inactiveButtonClasses.forEach((className) => button.classList.toggle(className, !isActive));
        }

        function updateFocusHighlights() {
            focusChoices.forEach((choice) => {
                const input = choice.querySelector('[data-focus-input]');
                const isActive = input.checked && ! input.disabled;
                activeChoiceClasses.forEach((className) => choice.classList.toggle(className, isActive));
                inactiveChoiceClasses.forEach((className) => choice.classList.toggle(className, !isActive));
            });
        }

        function updateSubmitHighlights(status) {
            statusButtons.forEach((button) => {
                const isActive = button.dataset.submitStatus === status;
                setButtonActive(button, isActive);
                button.classList.toggle('border-primary', isActive);
                button.classList.toggle('border-outline-variant', !isActive);
            });
        }

        function escapeHtml(value) {
            const element = document.createElement('textarea');
            element.textContent = value ?? '';
            return element.innerHTML;
        }

        function questionTemplate(index, question = {}) {
            const answers = question.answers || {};
            const correctAnswer = ['A', 'B', 'C', 'D'].includes(question.correct_answer) ? question.correct_answer : 'A';

            return `
                <div class="manual-question-card rounded-lg border border-outline-variant/20 bg-surface p-6" data-question-card>
                    <div class="mb-4 flex items-center gap-4">
                        <div class="question-number flex h-10 w-10 shrink-0 items-center justify-center rounded border border-outline-variant/30 bg-white font-bold">Q${index + 1}</div>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold">Manual Question</p>
                            <p class="text-xs text-on-surface-variant">This question will be saved with the assessment.</p>
                        </div>
                        <button class="remove-question rounded-full p-2 text-on-surface-variant transition-colors hover:bg-error-container/20 hover:text-error" type="button" aria-label="Remove question">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Question</label>
                            <textarea class="w-full rounded-sm border-outline-variant/20 bg-white px-4 py-3 text-sm focus:border-primary focus:ring-primary" name="manual_questions[${index}][question]" rows="3" placeholder="Type the question here...">${escapeHtml(question.question || '')}</textarea>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            ${['A', 'B', 'C', 'D'].map((answer) => `
                                <div>
                                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Answer ${answer}</label>
                                    <input class="w-full rounded-sm border-outline-variant/20 bg-white px-4 py-3 text-sm focus:border-primary focus:ring-primary" name="manual_questions[${index}][answers][${answer}]" type="text" value="${escapeHtml(answers[answer] || '')}" placeholder="Option ${answer}">
                                </div>
                            `).join('')}
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Correct Answer</label>
                            <select class="w-full rounded-sm border-outline-variant/20 bg-white px-4 py-3 text-sm focus:border-primary focus:ring-primary" name="manual_questions[${index}][correct_answer]">
                                ${['A', 'B', 'C', 'D'].map((answer) => `<option value="${answer}" ${correctAnswer === answer ? 'selected' : ''}>Answer ${answer}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                </div>
            `;
        }
        function toggleQuestionBuilder() {
            const selectedType = document.querySelector('input[name="assessment_type"]:checked')?.value || 'silent_reading';
            const isOralReading = selectedType === 'oral_reading';
            manualQuestions.classList.toggle('hidden', isOralReading);
            addQuestionButton.classList.toggle('hidden', isOralReading);
            questionUploadPanel.classList.toggle('hidden', isOralReading);
            questionTextFile.disabled = isOralReading;
            manualQuestions.querySelectorAll('input, textarea, select, button').forEach((field) => {
                field.disabled = isOralReading;
            });
        }

        function setQuestionImportStatus(message, isError = false) {
            questionImportStatus.textContent = message;
            questionImportStatus.classList.remove('hidden', 'text-error', 'text-secondary-dim');
            questionImportStatus.classList.add(isError ? 'text-error' : 'text-secondary-dim');
        }

        function isCompleteImportedQuestion(question) {
            return question.question
                && ['A', 'B', 'C', 'D'].every((answer) => question.answers[answer])
                && ['A', 'B', 'C', 'D'].includes(question.correct_answer);
        }

        function parseImportedQuestions(content) {
            const questions = [];
            let currentQuestion = null;

            const pushCurrentQuestion = () => {
                if (currentQuestion && isCompleteImportedQuestion(currentQuestion)) {
                    questions.push(currentQuestion);
                }
            };

            content
                .replace(/\r\n/g, '\n')
                .replace(/\r/g, '\n')
                .split('\n')
                .map((line) => line.trim())
                .filter(Boolean)
                .forEach((line) => {
                    const questionMatch = line.match(/^question\s*(?:\d+)?\s*[:.-]\s*(.+)$/i);
                    const answerMatch = line.match(/^([A-D])[\).:-]\s*(.+)$/i);
                    const correctAnswerMatch = line.match(/^(?:correct\s+)?answer\s*[:.-]\s*([A-D])\b/i);

                    if (questionMatch) {
                        pushCurrentQuestion();
                        currentQuestion = {
                            question: questionMatch[1].trim(),
                            answers: {},
                            correct_answer: 'A',
                        };
                        return;
                    }

                    if (! currentQuestion) {
                        currentQuestion = {
                            question: line,
                            answers: {},
                            correct_answer: 'A',
                        };
                        return;
                    }

                    if (answerMatch) {
                        currentQuestion.answers[answerMatch[1].toUpperCase()] = answerMatch[2].trim();
                        return;
                    }

                    if (correctAnswerMatch) {
                        currentQuestion.correct_answer = correctAnswerMatch[1].toUpperCase();
                    }
                });

            pushCurrentQuestion();

            return questions;
        }
        function renumberQuestions() {
            manualQuestions.querySelectorAll('[data-question-card]').forEach((card, index) => {
                card.querySelector('.question-number').textContent = `Q${index + 1}`;
                card.querySelectorAll('[name]').forEach((field) => {
                    field.name = field.name.replace(/manual_questions\[\d+\]/, `manual_questions[${index}]`);
                });
                const removeButton = card.querySelector('.remove-question');
                removeButton.classList.toggle('hidden', manualQuestions.querySelectorAll('[data-question-card]').length === 1);
            });
        }

        subjectChoices.forEach((choice) => {
            choice.addEventListener('change', () => toggleSpecifics(choice.value));
        });

        statusButtons.forEach((button) => {
            button.addEventListener('click', () => {
                statusInput.value = button.dataset.submitStatus;
                updateSubmitHighlights(button.dataset.submitStatus);
            });
        });
        focusInputs.forEach((input) => {
            input.addEventListener('change', updateFocusHighlights);
        });
        assessmentTypeInputs.forEach((input) => {
            input.addEventListener('change', toggleQuestionBuilder);
        });
        addQuestionButton.addEventListener('click', () => {
            const index = manualQuestions.querySelectorAll('[data-question-card]').length;
            manualQuestions.insertAdjacentHTML('beforeend', questionTemplate(index));
            renumberQuestions();
        });
        questionTextFile.addEventListener('change', async () => {
            const file = questionTextFile.files?.[0];

            if (! file) {
                return;
            }

            if (! file.name.toLowerCase().endsWith('.txt') && file.type !== 'text/plain') {
                setQuestionImportStatus('Please upload a plain .txt file.', true);
                questionTextFile.value = '';
                return;
            }

            const importedQuestions = parseImportedQuestions(await file.text());

            if (importedQuestions.length === 0) {
                setQuestionImportStatus('No complete questions found. Use Question:, A-D options, and Correct Answer: A.', true);
                questionTextFile.value = '';
                return;
            }

            manualQuestions.innerHTML = importedQuestions
                .map((question, index) => questionTemplate(index, question))
                .join('');
            renumberQuestions();
            toggleQuestionBuilder();
            setQuestionImportStatus(`${importedQuestions.length} question${importedQuestions.length === 1 ? '' : 's'} imported. You can still edit them before saving.`);
            questionTextFile.value = '';
        });
        manualQuestions.addEventListener('click', (event) => {
            const button = event.target.closest('.remove-question');

            if (! button) {
                return;
            }

            if (manualQuestions.querySelectorAll('[data-question-card]').length === 1) {
                return;
            }

            button.closest('[data-question-card]').remove();
            renumberQuestions();
        });
        toggleSpecifics(document.querySelector('[data-subject-choice]:checked')?.value || 'literacy');
        updateSubmitHighlights(statusInput.value || 'published');
        updateFocusHighlights();
        renumberQuestions();
        toggleQuestionBuilder();
    </script>
</x-app-layout>
