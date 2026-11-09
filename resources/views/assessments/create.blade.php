<x-teacher-assessment-layout>
                <form id="assessment-builder-form" class="teacher-assessment-builder space-y-8" method="POST" action="{{ route('assessments.store') }}" enctype="multipart/form-data">
                    @csrf
                    <input id="assessment-status" name="status" type="hidden" value="{{ old('status', 'published') }}">
                    <input id="delivery-method" name="delivery_method" type="hidden" value="manual">

                    <header class="teacher-workspace-heading teacher-builder-hero mb-10 flex flex-col justify-between gap-6 md:flex-row md:items-end">
                        <div>
                            <span class="mb-2 block text-xs font-bold uppercase tracking-[0.25em] text-primary">Curriculum Builder</span>
                            <h1 class="font-display text-4xl font-extrabold text-on-surface">Create New Assessment</h1>
                            <a class="ui-button ui-button-secondary mt-4" href="{{ route('assessments.index') }}"><span class="material-symbols-outlined" aria-hidden="true">inventory_2</span>Created Assessments</a>
                        </div>
                    </header>

                    <nav class="teacher-builder-steps" aria-label="Assessment creation steps">
                        <span class="is-current"><b>01</b>Foundation</span>
                        <span><b>02</b>Story and questions</span>
                        <span><b>03</b>Audience and settings</span>
                    </nav>

                    @if (session('status'))
                        <div class="rounded-lg border border-secondary/20 bg-secondary-container/30 px-4 py-3 text-sm font-medium text-on-surface">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="rounded-lg border border-error/30 bg-error-container/25 px-5 py-4 text-sm text-on-error-container" role="alert" tabindex="-1" data-assessment-error-summary>
                            <p class="font-bold">The assessment was not created. Please correct the following:</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <div class="contents lg:col-span-2 lg:block lg:space-y-6">
                            <div class="order-1 rounded-lg border border-outline-variant/10 bg-surface-container-lowest p-6 shadow-sm sm:p-8 lg:order-none">
                                <h2 class="mb-6 flex items-center gap-2 font-headline text-xl font-bold">
                                    <span class="material-symbols-outlined text-primary">category</span>
                                    1. Assessment Foundation
                                </h2>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <label class="group relative cursor-pointer">
                                        <input class="peer sr-only" name="subject" type="radio" value="literacy" checked data-subject-choice="literacy">
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

                                    <a class="group relative block rounded-lg border-2 border-transparent bg-surface-container-low p-6 transition-colors hover:border-primary hover:bg-primary/10" href="{{ route('worksheets.index') }}" data-numeracy-mission>
                                        <div class="mb-4 flex items-start justify-between"><span class="material-symbols-outlined rounded-lg bg-white p-3 text-3xl text-primary" aria-hidden="true">menu_book</span><span class="material-symbols-outlined text-primary" aria-hidden="true">arrow_forward</span></div>
                                        <h3 class="mb-1 text-lg font-bold">Numeracy</h3>
                                        <p class="text-sm leading-relaxed text-on-surface-variant">Worksheet Mission / 35 ARAL worksheets</p>
                                    </a>
                                </div>
                                @error('subject')
                                    <p class="mt-3 text-sm text-error">{{ $message }}</p>
                                @enderror

                                <div class="mt-8">
                                    <label class="mb-4 block text-sm font-bold text-on-surface-variant">Choose Quiz Type</label>
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                        @foreach ([
                                            ['value' => 'multiple_choice', 'icon' => 'phishing', 'label' => 'Fishing Game'],
                                            ['value' => 'flashcards', 'icon' => 'sports_esports', 'label' => 'Frog Adventure'],
                                            ['value' => 'reading_rally', 'icon' => 'sports_motorsports', 'label' => 'Reading Rally'],
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

                            <div class="order-3 rounded-lg border border-outline-variant/10 bg-surface-container-lowest p-6 shadow-sm sm:p-8 lg:order-none">
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

                                            <div class="assessment-type-reference overflow-hidden rounded-lg bg-surface-container-low text-xs shadow-sm ring-1 ring-outline-variant/15">
                                                <div class="grid grid-cols-4 gap-0 bg-surface-container-high px-3 py-2 font-black uppercase tracking-wider text-on-surface-variant">
                                                    <span>Assessment Type</span>
                                                    <span>Reading Mode</span>
                                                    <span>Main Purpose</span>
                                                    <span>Output</span>
                                                </div>
                                                @foreach ([
                                                    ['Oral Reading Assessment', 'Read aloud', 'Measure fluency + accuracy', 'Reading errors, WCPM, comprehension'],
                                                    ['Silent Reading Assessment', 'Read silently', 'Measure comprehension', 'Score and reading level'],
                                                ] as $row)
                                                    <div class="grid grid-cols-4 gap-0 px-3 py-2 text-on-surface-variant odd:bg-white even:bg-surface-container-lowest">
                                                        @foreach ($row as $cell)
                                                            <span class="pr-3">{{ $cell }}</span>
                                                        @endforeach
                                                    </div>
                                                @endforeach
                                            </div>

                                            <x-literacy-story-library :stories="$storyTemplates" />

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
                                                <h3 class="font-headline text-lg font-bold text-on-surface">Import Story and Questions from TXT</h3>
                                                <p class="text-sm text-on-surface-variant">Use Story Title:, Story Description:, Question:, A-D answers, and Correct Answer: A. Imported content stays editable.</p>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <a class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-bold text-primary ring-1 ring-primary/20 transition-colors hover:bg-primary/10" href="{{ asset('samples/multiple-choice-upload-sample.txt') }}" download>
                                                    <span class="material-symbols-outlined text-lg">description</span>
                                                    Sample Format
                                                </a>
                                                <label class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-full bg-surface-container-high px-5 py-2.5 text-sm font-bold text-on-surface-variant transition-colors hover:bg-primary/10 hover:text-primary">
                                                    <span class="material-symbols-outlined text-lg">upload_file</span>
                                                    Upload TXT
                                                    <input class="sr-only" id="question-text-file" type="file" accept=".txt,text/plain">
                                                </label>
                                            </div>
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
                                                    <label class="block text-sm font-bold">Question difficulty
                                                        <select class="mt-2 w-full rounded border-outline-variant/20 bg-white px-4 py-3 text-sm" name="manual_questions[{{ $questionIndex }}][difficulty]">
                                                            <option value="">Unspecified</option>
                                                            @foreach (\App\Support\AdaptiveQuestions::LABELS as $value => $label)
                                                                <option value="{{ $value }}" @selected(($manualQuestion['difficulty'] ?? '') === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
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

                        <aside class="contents lg:block lg:space-y-6">
                            <div class="order-2 relative overflow-hidden rounded-lg border border-outline-variant/10 bg-surface-container-low p-6 lg:order-none">
                                <div class="absolute right-0 top-0 p-4 opacity-10">
                                    <span class="material-symbols-outlined text-7xl text-primary" id="bg-icon">menu_book</span>
                                </div>
                                <h2 class="relative z-10 mb-4 font-headline text-lg font-bold">Advanced Specs</h2>
                                <div class="relative z-10 space-y-5">

                                    <div>
                                        @php($assignedTargetSection = \App\Support\SectionCatalog::targetKeyForLabel(auth()->user()->section) ?? 'all')
                                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Target Section</label>
                                        <select class="w-full rounded-sm border border-outline-variant/20 bg-white p-3 font-medium focus:ring-primary" name="target_section">
                                            <option value="all" @selected(old('target_section', $assignedTargetSection) === 'all')>All Sections</option>
                                            @foreach (\App\Support\SectionCatalog::targets() as $value => $label)
                                                <option value="{{ $value }}" @selected(old('target_section', $assignedTargetSection) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('target_section')
                                            <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="retry_limit">Retries after first attempt</label>
                                        <select class="w-full rounded-sm border border-outline-variant/20 bg-white p-3 font-medium focus:ring-primary" id="retry_limit" name="retry_limit" required>
                                            @foreach (\App\Models\Assessment::retryLimitOptions() as $value => $label)
                                                <option value="{{ $value }}" @selected((string) old('retry_limit', '0') === (string) $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('retry_limit')
                                            <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <fieldset>
                                        <legend class="mb-2 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Time per question</legend>
                                        <div class="grid grid-cols-2 gap-3">
                                            <label class="text-sm font-semibold text-on-surface-variant" for="question_time_minutes">Minutes
                                                <input class="mt-1 w-full rounded-sm border border-outline-variant/20 bg-white p-3 font-medium focus:ring-primary" id="question_time_minutes" name="question_time_minutes" type="number" min="0" max="60" inputmode="numeric" value="{{ old('question_time_minutes', 2) }}" required>
                                            </label>
                                            <label class="text-sm font-semibold text-on-surface-variant" for="question_time_seconds">Seconds
                                                <input class="mt-1 w-full rounded-sm border border-outline-variant/20 bg-white p-3 font-medium focus:ring-primary" id="question_time_seconds" name="question_time_seconds" type="number" min="0" max="59" inputmode="numeric" value="{{ old('question_time_seconds', 0) }}" required>
                                            </label>
                                        </div>
                                        <p class="mt-2 text-xs text-on-surface-variant">Students move to the next question when time runs out. Choose 10 seconds to 60 minutes.</p>
                                        @error('question_time_minutes')<p class="mt-2 text-sm text-error">{{ $message }}</p>@enderror
                                        @error('question_time_seconds')<p class="mt-2 text-sm text-error">{{ $message }}</p>@enderror
                                    </fieldset>

                                    <fieldset>
                                        <legend class="mb-2 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Assessment expiration (optional)</legend>
                                        <div class="grid grid-cols-2 gap-3">
                                            <label class="text-sm font-semibold text-on-surface-variant" for="expiration_value">Duration
                                                <input class="mt-1 w-full rounded-sm border border-outline-variant/20 bg-white p-3 font-medium focus:ring-primary" id="expiration_value" name="expiration_value" type="number" min="1" max="3650" inputmode="numeric" value="{{ old('expiration_value') }}" placeholder="No limit">
                                            </label>
                                            <label class="text-sm font-semibold text-on-surface-variant" for="expiration_unit">Unit
                                                <select class="mt-1 w-full rounded-sm border border-outline-variant/20 bg-white p-3 font-medium focus:ring-primary" id="expiration_unit" name="expiration_unit">
                                                    <option value="hours" @selected(old('expiration_unit', 'hours') === 'hours')>Hours</option>
                                                    <option value="days" @selected(old('expiration_unit') === 'days')>Days</option>
                                                </select>
                                            </label>
                                        </div>
                                        <p class="mt-2 text-xs text-on-surface-variant">Starts when you publish. Leave the duration blank to keep the assessment available until you lock it.</p>
                                        @error('expiration_value')<p class="mt-2 text-sm text-error">{{ $message }}</p>@enderror
                                        @error('expiration_unit')<p class="mt-2 text-sm text-error">{{ $message }}</p>@enderror
                                    </fieldset>

                                    <hr class="border-outline-variant/20">

                                    <div class="space-y-3">
                                        <x-phil-iri-rubric />
                                    </div>

                                    <div class="teacher-builder-actions teacher-builder-actions-sidebar" aria-label="Save or publish assessment">
                                        <button class="teacher-builder-button teacher-builder-button-secondary" type="submit" data-submit-status="draft">
                                            <span class="material-symbols-outlined" aria-hidden="true">lock</span>Save locked
                                        </button>
                                        <button class="teacher-builder-button teacher-builder-button-primary" type="submit" data-submit-status="published">
                                            <span class="material-symbols-outlined" aria-hidden="true">publish</span>Publish assessment
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </aside>
                    </section>

                    <div class="teacher-builder-actions teacher-builder-actions-dock teacher-builder-actions-mobile" aria-label="Save or publish assessment">
                        <button class="teacher-builder-button teacher-builder-button-secondary" type="submit" data-submit-status="draft">
                            <span class="material-symbols-outlined" aria-hidden="true">lock</span>Save locked
                        </button>
                        <button class="teacher-builder-button teacher-builder-button-primary" type="submit" data-submit-status="published">
                            <span class="material-symbols-outlined" aria-hidden="true">publish</span>Publish assessment
                        </button>
                    </div>
                </form>


    <script>
        const subjectChoices = document.querySelectorAll('[data-subject-choice]');
        const bgIcon = document.getElementById('bg-icon');
        const statusInput = document.getElementById('assessment-status');
        const statusButtons = document.querySelectorAll('[data-submit-status]');
        const manualQuestions = document.getElementById('manual-questions');
        const addQuestionButton = document.getElementById('add-question-button');
        const questionUploadPanel = document.getElementById('question-upload-panel');
        const questionTextFile = document.getElementById('question-text-file');
        const questionImportStatus = document.getElementById('question-import-status');
        const storyTitleInput = document.getElementById('story_title');
        const storyDescriptionInput = document.getElementById('story_description');
        const storyTemplates = {{ \Illuminate\Support\Js::from($storyTemplates) }};
        const storyLibrary = document.getElementById('story-library');
        const storyLibrarySelect = document.getElementById('story-library-select');
        const difficultyLabels = { frustration: 'Easier', instructional: 'Moderate', independent: 'Challenging', advanced: 'Enrichment' };
        const questionSelection = document.getElementById('question-selection');
        const loadStoryTemplateButton = document.getElementById('load-story-template');
        const storyLibraryStatus = document.getElementById('story-library-status');
        const assessmentTitleInput = document.getElementById('title');
        let templateAssessmentTitle = null;
        const assessmentTypeInputs = document.querySelectorAll('input[name="assessment_type"]');
        const activeButtonClasses = ['bg-primary', 'text-on-primary', 'shadow-lg', 'shadow-primary/20'];
        const inactiveButtonClasses = ['bg-surface-container-high', 'text-on-surface-variant'];

        function toggleSpecifics(type) {
            const isLiteracy = type === 'literacy';
            storyLibrary.classList.toggle('hidden', !isLiteracy);
            storyLibrarySelect.disabled = !isLiteracy;
            loadStoryTemplateButton.disabled = !isLiteracy || !storyLibrarySelect.value;
            bgIcon.textContent = isLiteracy ? 'menu_book' : 'grid_view';
            bgIcon.classList.toggle('text-primary', isLiteracy);
            bgIcon.classList.toggle('text-secondary', !isLiteracy);
        }


        function setButtonActive(button, isActive) {
            button.classList.toggle('hover:bg-primary-dim', isActive);
            button.classList.toggle('hover:text-primary', !isActive);
            button.classList.toggle('hover:bg-primary/5', !isActive);
            activeButtonClasses.forEach((className) => button.classList.toggle(className, isActive));
            inactiveButtonClasses.forEach((className) => button.classList.toggle(className, !isActive));
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
                        <label class="block text-sm font-bold">Question difficulty
                            <select class="mt-2 w-full rounded border-outline-variant/20 bg-white px-4 py-3 text-sm" name="manual_questions[${index}][difficulty]">
                                <option value="">Unspecified</option>
                                ${Object.entries(difficultyLabels).map(([value, label]) => `<option value="${value}" ${question.difficulty === value ? 'selected' : ''}>${label}</option>`).join('')}
                            </select>
                        </label>
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
        function selectedAssessmentType() {
            return document.querySelector('input[name="assessment_type"]:checked')?.value || 'silent_reading';
        }

        function assessmentTypeRequiresQuestions(type = selectedAssessmentType()) {
            return type !== 'oral_reading';
        }

        function ensureManualQuestionCard() {
            if (! manualQuestions.querySelector('[data-question-card]')) {
                manualQuestions.insertAdjacentHTML('beforeend', questionTemplate(0));
                renumberQuestions();
            }
        }

        function toggleQuestionBuilder() {
            const requiresQuestions = assessmentTypeRequiresQuestions();
            const allowsAutomatic = selectedAssessmentType() === 'silent_reading';
            questionSelection.querySelector('[value="automatic"]').disabled = !allowsAutomatic;
            if (!allowsAutomatic) questionSelection.value = 'fixed';
            updateStorySetPreview();

            if (requiresQuestions) {
                ensureManualQuestionCard();
            }

            manualQuestions.classList.toggle('hidden', ! requiresQuestions);
            addQuestionButton.classList.toggle('hidden', ! requiresQuestions);
            manualQuestions.querySelectorAll('input, textarea, select, button').forEach((field) => {
                field.disabled = ! requiresQuestions;
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

        function parseImportedAssessment(content) {
            const questions = [];
            let currentQuestion = null;
            let storyTitle = '';
            const storyDescriptionLines = [];
            let readingStoryDescription = false;
            let reachedQuestions = false;

            const pushCurrentQuestion = () => {
                if (currentQuestion && isCompleteImportedQuestion(currentQuestion)) {
                    questions.push(currentQuestion);
                }
            };

            content
                .replace(/\r\n/g, '\n')
                .replace(/\r/g, '\n')
                .split('\n')
                .forEach((rawLine) => {
                    const line = rawLine.trim();
                    const storyTitleMatch = line.match(/^(?:story\s+)?title\s*[:.-]\s*(.+)$/i);
                    const storyDescriptionMatch = line.match(/^(?:story\s+description|reading\s+passage|passage|story)\s*[:.-]\s*(.*)$/i);
                    const questionMatch = line.match(/^question\s*(?:\d+)?\s*[:.-]\s*(.+)$/i);
                    const answerMatch = line.match(/^([A-D])[\).:-]\s*(.+)$/i);
                    const correctAnswerMatch = line.match(/^(?:correct\s+)?answer\s*[:.-]\s*([A-D])\b/i);

                    if (questionMatch) {
                        readingStoryDescription = false;
                        reachedQuestions = true;
                        pushCurrentQuestion();
                        currentQuestion = {
                            question: questionMatch[1].trim(),
                            answers: {},
                            correct_answer: 'A',
                        };
                        return;
                    }

                    if (! reachedQuestions && ! currentQuestion && storyTitleMatch) {
                        storyTitle = storyTitleMatch[1].trim();
                        readingStoryDescription = false;
                        return;
                    }

                    if (! reachedQuestions && ! currentQuestion && storyDescriptionMatch) {
                        readingStoryDescription = true;

                        if (storyDescriptionMatch[1].trim()) {
                            storyDescriptionLines.push(storyDescriptionMatch[1].trim());
                        }

                        return;
                    }

                    if (readingStoryDescription && ! reachedQuestions && ! currentQuestion) {
                        storyDescriptionLines.push(rawLine.trimEnd());
                        return;
                    }

                    if (! line) {
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

            return {
                questions,
                storyTitle,
                storyDescription: storyDescriptionLines.join('\n').trim(),
            };
        }

        function applyImportedStory(imported) {
            if (imported.storyTitle) {
                storyTitleInput.value = imported.storyTitle;
                storyTitleInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (imported.storyDescription) {
                storyDescriptionInput.value = imported.storyDescription;
                storyDescriptionInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
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
        assessmentTypeInputs.forEach((input) => {
            input.addEventListener('change', toggleQuestionBuilder);
        });
        function updateStorySetPreview() {
            const template = storyTemplates.find(story => story.id === storyLibrarySelect.value);
            const automatic = questionSelection.value === 'automatic';
            const questions = automatic ? Object.values(template?.question_sets || {}).flat() : template?.question_sets?.instructional || [];
            loadStoryTemplateButton.disabled = !template || storyLibrarySelect.disabled;
            document.getElementById('story-library-count').textContent = template ? `${automatic ? '8 per student' : questions.length + ' fixed questions'} / ${Object.values(template.question_sets).flat().length} in story bank` : '';
            document.getElementById('story-library-guide').classList.toggle('hidden', !template);
            document.getElementById('story-library-guide-items').innerHTML = (template?.answer_guide || []).filter(item => automatic || item.difficulty === 'instructional').map(item => `<li value="${item.number}"><strong>${escapeHtml(item.skill)} - Answer ${escapeHtml(item.correct_answer)}</strong><p>${escapeHtml(item.explanation)}</p></li>`).join('');
        }
        storyLibrarySelect.addEventListener('change', () => {
            updateStorySetPreview();
        });
        questionSelection.addEventListener('change', updateStorySetPreview);
        loadStoryTemplateButton.addEventListener('click', () => {
            if (storyLibrarySelect.disabled) return;
            const template = storyTemplates.find(story => story.id === storyLibrarySelect.value);
            if (!template) return;
            const automatic = questionSelection.value === 'automatic';
            const questions = automatic ? Object.values(template.question_sets).flat() : template.question_sets.instructional;
            if (!questions?.length) return;

            const hasContent = storyTitleInput.value.trim() || storyDescriptionInput.value.trim()
                || Array.from(manualQuestions.querySelectorAll('textarea, input')).some(field => field.value.trim());
            if (hasContent && !confirm(`Replace the current story and questions with "${template.story_title}"?`)) return;

            applyImportedStory({ storyTitle: template.story_title, storyDescription: template.story_description });
            if (!assessmentTitleInput.value.trim() || assessmentTitleInput.value === templateAssessmentTitle) {
                assessmentTitleInput.value = template.story_title;
                assessmentTitleInput.dispatchEvent(new Event('input', { bubbles: true }));
                templateAssessmentTitle = template.story_title;
            }
            manualQuestions.innerHTML = questions.map((question, index) => questionTemplate(index, question)).join('');
            renumberQuestions();
            // Oral reading keeps these cards disabled; switching back restores the comprehension questions.
            toggleQuestionBuilder();
            questionTextFile.value = '';
            questionImportStatus.classList.add('hidden');
            const summary = assessmentTypeRequiresQuestions()
                ? (automatic ? `${questions.length} bank questions loaded. Each student receives 8 questions based on their overall level.` : `${questions.length} fixed moderate questions loaded.`)
                : 'Passage loaded for oral reading.';
            storyLibraryStatus.textContent = `${template.story_title}: ${summary}`;
            storyLibraryStatus.classList.remove('hidden');
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

            const importedAssessment = parseImportedAssessment(await file.text());
            const importedQuestions = importedAssessment.questions;
            const requiresQuestions = assessmentTypeRequiresQuestions();
            const importedStoryParts = [
                importedAssessment.storyTitle ? 'story title' : null,
                importedAssessment.storyDescription ? 'story description' : null,
            ].filter(Boolean);

            if (importedQuestions.length === 0 && importedStoryParts.length === 0) {
                setQuestionImportStatus('No story or complete questions found. Use Story Title:, Story Description:, Question:, A-D options, and Correct Answer: A.', true);
                questionTextFile.value = '';
                return;
            }

            applyImportedStory(importedAssessment);

            if (! requiresQuestions) {
                manualQuestions.innerHTML = '';
                toggleQuestionBuilder();

                const ignoredQuestions = importedQuestions.length > 0
                    ? `${importedQuestions.length} question block${importedQuestions.length === 1 ? '' : 's'} ${importedQuestions.length === 1 ? 'was' : 'were'} ignored because this assessment type does not need questions.`
                    : 'This assessment type does not need question cards.';

                setQuestionImportStatus([
                    importedStoryParts.length ? `${importedStoryParts.join(' and ')} imported.` : null,
                    ignoredQuestions,
                ].filter(Boolean).join(' '));
                questionTextFile.value = '';
                return;
            }

            if (importedQuestions.length === 0) {
                toggleQuestionBuilder();
                setQuestionImportStatus(`${importedStoryParts.join(' and ')} imported, but this assessment type still needs questions. Add them below or upload a TXT with Question:, A-D options, and Correct Answer: A.`, true);
                questionTextFile.value = '';
                return;
            }

            questionSelection.value = 'fixed';
            manualQuestions.innerHTML = importedQuestions
                .map((question, index) => questionTemplate(index, question))
                .join('');
            renumberQuestions();
            toggleQuestionBuilder();
            setQuestionImportStatus([
                importedStoryParts.length ? importedStoryParts.join(' and ') : null,
                `${importedQuestions.length} question${importedQuestions.length === 1 ? '' : 's'}`,
                'imported. You can still edit everything before saving.',
            ].filter(Boolean).join(' '));
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
        renumberQuestions();
        toggleQuestionBuilder();
    </script>
</x-teacher-assessment-layout>
