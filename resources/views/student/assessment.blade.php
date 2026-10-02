<x-app-layout>
    @php
        $assetPath = $assessment->asset_path;
        $assetExtension = $assetPath ? strtolower(pathinfo($assetPath, PATHINFO_EXTENSION)) : null;
        $assetUrl = $assetPath ? \Illuminate\Support\Facades\Storage::url($assetPath) : null;
        $student = $student ?? Auth::user();
        $assisted = $assisted ?? false;
        $assessmentBackUrl = $assisted ? route('assessments.show', $assessment) : route('student.activities', ['subject' => $assessment->subject]);
        $assessmentBackLabel = $assisted ? 'Back to Assessment' : 'Back to Activities';
        $attemptSubmitUrl = $assisted ? route('teacher.assessments.submit', [$assessment, $student]) : route('student.assessments.submit', $assessment);
        $attemptProgressUrl = $assisted ? route('teacher.assessments.progress', [$assessment, $student]) : route('student.assessments.progress', $assessment);
        $studentName = $student?->name ?? 'Student';
        $studentInitials = collect(explode(' ', $studentName))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');
        $manualQuestions = collect($attemptQuestions ?? $assessment->manual_questions ?? [])->values();
        $storyTitle = $assessment->story_title;
        $assessmentTypeLabels = [
            'silent_reading' => 'Silent Reading',
            'oral_reading' => 'Oral Reading Assessment',
            'listening_comprehension' => 'Listening Comprehension Assessment',
            'group_screening' => 'Group Screening Test',
        ];
        $assessmentType = $assessment->assessment_type ?? 'silent_reading';
        $assessmentTypeLabel = $assessmentTypeLabels[$assessmentType] ?? 'Silent Reading';
        $isOralReading = $assessmentType === 'oral_reading';
        $isSilentReading = $assessmentType === 'silent_reading';
        $isListeningComprehension = $assessmentType === 'listening_comprehension';
        $isFlashcards = ($assessment->quiz_type ?? 'multiple_choice') === 'flashcards';
        $isTreasureQuest = ($assessment->quiz_type ?? 'multiple_choice') === 'treasure_quest';
        $isFishing = ! $isOralReading && ! $isFlashcards && ! $isTreasureQuest;
        $storyDescription = $assessment->story_description;
        $hasReadingStage = ($storyTitle || $storyDescription) && ! $isListeningComprehension;
        $storyText = $storyDescription ?: $assessment->instructions;
        $storyParagraphs = collect(preg_split('/\R{2,}/u', trim($storyText ?? '')))
            ->filter(fn (string $paragraph): bool => trim($paragraph) !== '');
        $storyReadOnlyHtml = $storyParagraphs
            ->map(function (string $paragraph): string {
                $words = collect(preg_split('/(\s+)/u', $paragraph, -1, PREG_SPLIT_DELIM_CAPTURE))
                    ->map(fn (string $part): string => e($part))
                    ->implode('');

                return '<p class="story-paragraph">'.$words.'</p>';
            })
            ->implode('');
        $storyMarkingHtml = $storyParagraphs
            ->map(function (string $paragraph): string {
                preg_match_all('/[^.!?]+[.!?]+|[^.!?]+$/u', $paragraph, $matches);

                $sentences = collect($matches[0] ?: [$paragraph])
                    ->map(fn (string $sentence): string => trim($sentence))
                    ->filter(fn (string $sentence): bool => $sentence !== '');

                $words = $sentences
                    ->map(function (string $sentence): string {
                        $sentenceWords = collect(preg_split('/(\s+)/u', $sentence, -1, PREG_SPLIT_DELIM_CAPTURE))
                            ->map(fn (string $part): string => trim($part) === ''
                                ? e($part)
                                : '<button class="story-word" type="button" data-mark="0" aria-pressed="false"'.(preg_match('/[\p{L}\p{N}]/u', $part) ? '' : ' disabled').'>'.e($part).'</button>')
                            ->implode('');

                        return '<span class="story-sentence" data-sentence-mark="0">'.$sentenceWords.'</span>';
                    })
                    ->implode(' ');

                return '<p class="story-paragraph">'.$words.'</p>';
            })
            ->implode('');
        $storyHtml = $isOralReading ? $storyMarkingHtml : $storyReadOnlyHtml;
        $gameQuestions = $manualQuestions
            ->map(function (array $question, int $index): array {
                $answers = collect($question['answers'] ?? [])
                    ->only(['A', 'B', 'C', 'D'])
                    ->map(fn ($answer, $letter): array => ['l' => (string) $letter, 't' => (string) $answer])
                    ->values()
                    ->all();

                return [
                    'node' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'text' => (string) ($question['question'] ?? 'Untitled question'),
                    'options' => $answers,
                    'correct' => (string) ($question['correct_answer'] ?? 'A'),
                ];
            })
            ->filter(fn (array $question): bool => filled($question['text']) && count($question['options']) > 0)
            ->values();
        $questionCount = $gameQuestions->count();
        $firstQuestion = $gameQuestions->first();
        // The random attempt key keeps one school design stable across resumes and devices.
        $fishSpecies = ['pufferfish', 'shark', 'yellowfin', 'jellyfish'][
            hexdec(substr(hash('sha256', $progress->attempt_key ?? $assessment->id.':'.$student->id), 0, 2)) % 4
        ];
        $missionTitle = str($assessment->title)->upper()->limit(28, '');
        $assessmentTutorConfig = [
            'available' => (bool) config('tutor.enabled') && filled(config('tutor.key')) && ! $assisted,
            'sendUrl' => route('student.assessments.tutor', $assessment),
        ];
    @endphp

    <style>
        .answer-fish { position: absolute; left: 0; top: 0; width: 132px; height: 80px; max-width: none; padding: 0; border: 0; background: transparent; cursor: pointer; touch-action: manipulation; will-change: transform; }
        .fish-visual { display: block; width: 100%; height: 100%; overflow: visible; transform: scaleX(var(--fish-direction, 1)); filter: drop-shadow(0 5px 3px rgba(11,81,94,.15)); }
        .fish-tail { transform-origin: 33px 40px; animation: fish-tail-beat .55s ease-in-out infinite alternate; }
        .fish-fin { transform-origin: 72px 48px; animation: fish-fin-beat .8s ease-in-out infinite alternate; }
        .fish-letter { position: absolute; inset: 0; display: grid; place-items: center; padding-left: 7px; font-size: 22px; font-weight: 900; color: #143847; text-shadow: 0 1px 0 rgba(255,255,255,.7); pointer-events: none; }
        .answer-fish:focus-visible { outline: 3px solid #005e9f; outline-offset: 4px; border-radius: 50%; }
        .answer-fish:hover .fish-visual { filter: drop-shadow(0 0 5px rgba(0,94,159,.5)); }
        .answer-fish.is-caught { z-index: 45; pointer-events: none; }
        .answer-fish.is-caught .fish-visual { transform: rotate(-90deg); }
        .answer-fish.is-caught .fish-tail { animation-duration: .18s; }
        @keyframes fish-tail-beat { from { transform: scaleX(.72) skewY(-8deg); } to { transform: scaleX(1) skewY(8deg); } }
        @keyframes fish-fin-beat { from { transform: rotate(-14deg); } to { transform: rotate(12deg); } }
        .hook-game { display: grid; grid-template-columns: 288px minmax(0,1fr); grid-template-rows: auto minmax(260px,1fr); gap: 20px 24px; padding: 20px 20px 180px; min-height: 700px; }
        .hook-game #hook-hud { position: relative; inset: auto; grid-column: 1; grid-row: 1 / 3; width: auto; max-width: none; }
        .hook-game #mission-info { position: relative; inset: auto; grid-column: 2; grid-row: 1; max-width: none; }
        .hook-game #question-node { max-height: 400px; overflow-y: auto; border-radius: 8px; }
        .hook-game #fish-container { position: relative; inset: auto; grid-column: 2; grid-row: 2; min-height: 320px; margin-top: 36px; overflow: hidden; border-top: 3px solid #8cdce3; background: linear-gradient(180deg, rgba(213,247,249,.6), rgba(166,224,233,.3)); }
        .hook-game #fish-container::after { content: ''; position: absolute; inset: auto 0 0; height: 2px; background: #c1e8dd; pointer-events: none; }
        .hook-head { position: relative; width: 40px; height: 48px; flex: none; filter: drop-shadow(1px 2px 1px rgba(27,65,79,.2)); }
        @media (max-width: 760px) {
            .hook-game { grid-template-columns: minmax(0,1fr); grid-template-rows: auto auto minmax(280px,1fr); gap: 12px; padding: 12px 12px 110px; height: auto; min-height: calc(100dvh - 64px); }
            .hook-game #mission-info { grid-column: 1; grid-row: 1; text-align: left; }
            .hook-game #mission-info h1 { font-size: 17px; }
            .hook-game #hook-hud { grid-column: 1; grid-row: 2; gap: 8px; }
            .hook-game #hook-hud > div:first-child { flex-direction: row; gap: 8px; }
            .hook-game #hook-hud > div:first-child > div { flex: 1; padding: 8px; }
            .hook-game #question-node { padding: 12px; max-height: 250px; }
            .hook-game #question-node h2 { font-size: 16px; margin-bottom: 10px; }
            .hook-game .hook-options { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 6px; }
            .hook-game .hook-options > div { margin: 0; padding: 6px; gap: 7px; font-size: 13px; }
            .hook-game .hook-options > div > span:first-child { flex-shrink: 0; }
            .hook-game #fish-container { grid-column: 1; grid-row: 3; }
            .hook-game #egg-wrapper { transform: translateX(-50%) scale(.55); transform-origin: center bottom; bottom: 4px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .fish-tail, .fish-fin { animation: none; }
        }
        .particle { position: absolute; pointer-events: none; z-index: 100; }
        .glass-hud { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); }
        .liquid-track { box-shadow: inset 0 2px 4px rgba(0,0,0,0.1); }
        .liquid-fill { box-shadow: 0 0 15px rgba(145, 247, 142, 0.6), inset 0 2px 4px rgba(255,255,255,0.4); transition: width 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .hook-cable { width: 2px; background: #728c99; height: 0; flex: none; }
        .pulse-bag { animation: bag-pulse 2s infinite ease-in-out; transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), background-color 0.4s ease; }
        @keyframes bag-pulse { 0%, 100% { transform: scale(var(--base-scale, 1)) translateY(0); } 50% { transform: scale(calc(var(--base-scale, 1) * 1.05)) translateY(-5px); } }
        .mission-canvas { cursor: crosshair; }
        .data-egg-container { width: 118px; height: 148px; position: relative; perspective: 1000px; }
        .data-egg { width: 100%; height: 100%; background: radial-gradient(circle at 30% 30%, #ffffff 0%, #eef1f4 50%, #d9dde1 100%); border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%; box-shadow: 0 20px 40px rgba(0, 94, 159, 0.15), inset -10px -10px 30px rgba(0, 0, 0, 0.05), inset 10px 10px 30px rgba(255, 255, 255, 0.8); position: relative; overflow: hidden; border: 2px solid rgba(255, 255, 255, 0.5); transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease; }
        .egg-glow { position: absolute; inset: -28px; background: radial-gradient(circle, rgba(68, 165, 255, 0.3) 0%, transparent 70%); pointer-events: none; opacity: 0.6; animation: glow-pulse 3s infinite ease-in-out; z-index: 5; }
        @keyframes glow-pulse { 0%, 100% { opacity: 0.4; transform: scale(1); } 50% { opacity: 0.8; transform: scale(1.2); } }
        .crack { position: absolute; background: #005e9f; width: 2px; height: 0; opacity: 0; transition: height 0.5s ease-out, opacity 0.5s ease; transform-origin: top; filter: blur(0.5px); z-index: 20; }
        .crack-visible { opacity: 0.4; height: 30px; }
        .shell-piece { position: absolute; background: radial-gradient(circle at 30% 30%, #ffffff 0%, #eef1f4 50%, #d9dde1 100%); width: 58px; height: 72px; border: 2px solid rgba(255, 255, 255, 0.5); pointer-events: none; z-index: 30; opacity: 0; }
        .hatch-core { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; z-index: 10; opacity: 0; transform: scale(0); transition: all 0.8s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .hatch-active .hatch-core { opacity: 1; transform: scale(1); animation: core-pop 1.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }
        @keyframes core-pop { 0% { transform: scale(0); opacity: 0; } 30% { transform: scale(1.3); opacity: 1; } 100% { transform: scale(1); opacity: 1; } }
        .hatch-active .data-egg { opacity: 0; pointer-events: none; }
        .hatch-light { position: absolute; inset: -110px; background: radial-gradient(circle, #ffffff 0%, #44a5ff 30%, transparent 70%); opacity: 0; pointer-events: none; z-index: 15; }
        @keyframes explode-out { 0% { transform: translate(0, 0) rotate(0) scale(1); opacity: 1; } 100% { transform: translate(var(--tx), var(--ty)) rotate(var(--tr)) scale(0.5); opacity: 0; } }
        .shell-exploded { animation: explode-out 1.2s forwards cubic-bezier(0.165, 0.84, 0.44, 1); }
        #story-reader-text { user-select: none; -webkit-user-select: none; }
        .story-paragraph { margin: 0 0 1rem; text-align: left; }
        .story-paragraph:last-child { margin-bottom: 0; }
        .story-sentence { border-radius: 0.55rem; box-decoration-break: clone; -webkit-box-decoration-break: clone; cursor: pointer; padding: 0.08rem 0.12rem; transition: background-color 0.15s ease, box-shadow 0.15s ease; }
        .story-sentence:hover { background: rgba(0, 94, 159, 0.06); }
        .story-sentence-mark-2 { background: rgba(248, 113, 113, 0.2); box-shadow: 0 0 0 1px rgba(220, 38, 38, 0.18); }
        .story-word { appearance: none; display: inline; cursor: pointer; border: 0; border-radius: 0.25rem; background: transparent; margin: 0; padding: 0.03rem 0.1rem; color: inherit; font: inherit; line-height: inherit; text-align: inherit; vertical-align: baseline; transition: background-color 0.15s ease, color 0.15s ease; user-select: none; -webkit-user-select: none; touch-action: manipulation; }
        .story-word:hover { background: rgba(0, 94, 159, 0.08); }
        .story-word.story-word-mark-2 { background: rgba(248, 113, 113, 0.28); color: #991b1b; }
        .mark-mode-button { transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease; }
        .mark-mode-button:hover { transform: translateY(-1px); }
        .mark-mode-button.is-active { background: #005e9f; color: #ffffff; box-shadow: 0 12px 24px rgba(0, 94, 159, 0.18); }
        .mark-mode-button.mark-mode-red.is-active { background: #ef4444; color: #ffffff; box-shadow: 0 12px 24px rgba(220, 38, 38, 0.18); }
        .result-pattern { background-image: radial-gradient(circle at 10px 10px, rgba(68, 165, 255, 0.45) 1px, transparent 1px), radial-gradient(circle at 30px 30px, rgba(145, 247, 142, 0.45) 1px, transparent 1px); background-size: 40px 40px; background-position: 0 0, 20px 20px; opacity: 0.2; }
    </style>

    <div class="student-assessment-page {{ $assisted ? '' : 'assessment-focus-mode' }} min-h-screen font-body text-on-surface" x-data="{ mobileMenuOpen: false }">
        @if ($assisted)
            @php
                $teacherName = auth()->user()->name;
                $teacherInitials = collect(explode(' ', $teacherName))->take(2)->map(fn ($part) => substr($part, 0, 1))->join('');
            @endphp
            <div class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full lg:translate-x-0" :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'">
                <x-teacher-sidebar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" active="assessments" />
            </div>
            <button x-show="mobileMenuOpen" @click="mobileMenuOpen = false" class="fixed inset-0 z-30 bg-black/40 lg:hidden" aria-label="Close menu"></button>
            <div class="lg:ml-72"><x-teacher-topbar :teacher-name="$teacherName" :teacher-initials="$teacherInitials"><x-slot:mobileTrigger><button class="lg:hidden" @click="mobileMenuOpen = true" aria-label="Open menu"><span class="material-symbols-outlined">menu</span></button></x-slot:mobileTrigger></x-teacher-topbar></div>

        @endif

        <main class="assessment-workspace {{ $assisted ? 'lg:ml-72' : '' }}" aria-labelledby="assessment-title">
            @if ($assisted)<x-assisted-assessment-banner :student="$student" :assessment="$assessment" />@endif
            @if ($assisted)
                <x-teacher-answer-key :questions="$manualQuestions->all()" :context="$progress->selection_context ?? []" />
            @endif
            <header class="assessment-heading">
                <a class="assessment-back" href="{{ $assessmentBackUrl }}" aria-label="{{ $assessmentBackLabel }}" title="{{ $assessmentBackLabel }}">
                    <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
                </a>
                <div class="assessment-heading-copy">
                    <p>{{ $assessmentTypeLabel }}</p>
                    <h1 id="assessment-title">{{ $assessment->title }}</h1>
                    <x-teacher-identity :teacher="$assessment->teacher" />
                </div>
                <x-assessment-text-settings />
                @unless ($assisted)
                    <div class="assessment-word-helper" x-data="assessmentWordHelp(@js($assessmentTutorConfig))" @keydown.escape.window="open = false">
                        <button class="assessment-helper-toggle" type="button" @click="toggle()" :aria-expanded="open.toString()" aria-controls="assessment-word-helper-panel">
                            <span class="material-symbols-outlined" aria-hidden="true">support_agent</span>
                            <span>Ask for help</span>
                        </button>
                        <section id="assessment-word-helper-panel" class="assessment-helper-panel" x-show="open" x-transition x-cloak aria-label="Assessment tutor">
                            <div class="assessment-helper-heading">
                                <div>
                                    <strong>Ask Tutor</strong>
                                    <p>For the story, questions, words, or directions you do not understand.</p>
                                </div>
                                <button type="button" @click="open = false" aria-label="Close assessment tutor"><span class="material-symbols-outlined" aria-hidden="true">close</span></button>
                            </div>
                            <div class="assessment-helper-messages" x-ref="messages" role="log" aria-live="polite">
                                <p class="assessment-helper-empty" x-show="turns.length === 0">Ask about the story, a question, a word, or an instruction. I can guide you, but I will not answer the test for you.</p>
                                <template x-for="turn in turns" :key="turn.id">
                                    <article class="assessment-helper-turn">
                                        <p><strong>You:</strong> <span x-text="turn.question"></span></p>
                                        <p><strong>Tutor:</strong> <span x-text="turn.answer"></span></p>
                                    </article>
                                </template>
                            </div>
                            <p class="assessment-helper-status" role="status" x-show="busy" x-cloak>Thinking...</p>
                            <p class="assessment-helper-error" role="alert" x-show="error" x-text="error" x-cloak></p>
                            <form class="assessment-helper-form" @submit.prevent="send()" :aria-busy="busy">
                                <label for="assessment-helper-question">Question</label>
                                <textarea id="assessment-helper-question" x-ref="question" x-model="draft" :disabled="!available || busy" maxlength="1500" rows="2" placeholder="What part of the story or question is confusing?" required></textarea>
                                <div>
                                    <small x-text="draft.length + ' / 1500'"></small>
                                    <button type="submit" :disabled="!available || busy || !draft.trim()">
                                        <span>Send</span>
                                        <span class="material-symbols-outlined" aria-hidden="true">send</span>
                                    </button>
                                </div>
                            </form>
                            <p class="assessment-helper-disclosure" x-show="available">Do not share passwords or personal details. Ask your teacher for important help.</p>
                            <p class="assessment-helper-error" x-show="!available">Ask Tutor is not connected yet. Please ask your teacher for help.</p>
                        </section>
                    </div>
                @endunless
                <div class="assessment-attempt">
                    <span class="material-symbols-outlined" aria-hidden="true">cloud_done</span>
                    <span id="assessment-save-status" role="status">Attempt {{ $progress->attempt_number ?? 1 }}</span>
                </div>
            </header>

        @if ($questionCount > 0 || $isOralReading)
            <audio id="assessment-game-music" src="{{ asset('audio/assessment-game-music.mp3') }}" preload="auto" loop></audio>
            <audio id="multiple-choice-hook-sound" src="{{ asset('audio/multiple-choice-hook-reel.mp3') }}" preload="auto"></audio>
            <audio id="frog-wrong-answer-sound" src="{{ asset('audio/frog-wrong-answer.mp3') }}" preload="auto"></audio>
            <audio id="frog-correct-answer-sound" src="{{ asset('audio/frog-correct-answer.mp3') }}" preload="auto"></audio>
            <section class="mission-canvas relative app-game-screen w-full overflow-hidden {{ $hasReadingStage ? 'assessment-reading' : '' }} {{ ($isFlashcards && ! $isOralReading) ? 'frog-pond-game' : '' }} {{ $isFishing ? 'hook-game ocean-game' : '' }} {{ ($isTreasureQuest && ! $isOralReading) ? 'treasure-game' : '' }}" id="mission-canvas" data-fish-species="{{ $fishSpecies }}" aria-label="Assessment activity">
                @if ($isFishing)
                    <div id="fishing-sea-scene" aria-hidden="true"></div>
                    <div class="ocean-catch-status" id="ocean-catch-status" role="status" aria-live="polite"></div>

                @endif
                <div id="hook-hud" class="pointer-events-none absolute left-3 right-3 top-3 z-30 flex max-w-sm flex-col gap-3 sm:left-5 sm:right-auto sm:top-5 sm:w-72 {{ ! $isFishing ? 'hidden' : '' }}">
                    <div class="flex flex-col gap-4">
                        <div class="glass-hud pointer-events-auto flex items-center gap-4 rounded-lg border border-white/40 p-3 shadow-sm">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-tertiary-container text-tertiary-dim shadow-sm"><span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">stars</span></div>
                            <div><p class="font-label text-[10px] font-bold uppercase tracking-widest text-slate-500">Progress</p><p class="font-headline text-xl font-black leading-none text-on-surface" id="score">0/{{ $questionCount }}</p></div>
                        </div>
                        <div class="glass-hud pointer-events-auto rounded-lg border border-white/40 p-3 shadow-sm">
                            <p class="font-label mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-500">Catch Progress</p>
                            <div class="liquid-track h-3 w-full overflow-hidden rounded-full bg-surface-container-highest"><div class="liquid-fill h-full w-0 rounded-full bg-secondary" id="progress-bar"></div></div>
                        </div>
                    </div>


                    <div class="glass-hud pointer-events-auto rounded-2xl border border-white/60 p-4 shadow-xl transition-opacity duration-300" id="question-node">
                        @if ($firstQuestion)
                            <div class="mb-3 flex items-center gap-2"><span class="material-symbols-outlined text-sm text-primary">terminal</span><h3 class="font-label text-[10px] font-black uppercase tracking-[0.2em] text-primary-dim">Question Node {{ $firstQuestion['node'] }}</h3></div>
                            <h2 class="font-headline mb-4 text-lg font-extrabold leading-tight text-on-surface">{{ $firstQuestion['text'] }}</h2>
                            <div class="hook-options space-y-2">
                                @foreach ($firstQuestion['options'] as $option)
                                    <div class="group flex cursor-default items-center gap-3 rounded-xl border border-white bg-white/50 p-2.5 transition-colors hover:bg-white">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-container/20 font-bold text-primary">{{ $option['l'] }}</span>
                                        <span class="font-medium text-on-surface-variant">{{ $option['t'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="pointer-events-none absolute right-3 top-3 z-30 max-w-[calc(100%-1.5rem)] text-right sm:right-5 sm:top-5 {{ ! $isFishing ? 'hidden' : '' }}" id="mission-info">
                    <div class="ocean-eyebrow"><span class="material-symbols-outlined" aria-hidden="true">sailing</span> Ocean Expedition</div>
                    <h1 class="font-headline mb-1 text-xl font-black italic leading-none tracking-tighter text-primary-dim">{{ $assessment->title }}</h1>
                    <p class="font-body font-medium text-slate-500">{{ $questionCount }} questions</p>
                </div>

                <div class="pointer-events-none absolute left-1/2 top-0 z-40 flex -translate-x-1/2 flex-col items-center {{ ! $isFishing ? 'hidden' : '' }}" id="hook-assembly" aria-hidden="true">
                    <svg class="fishing-boat-fallback" viewBox="0 0 140 100" aria-hidden="true">
                        <path d="M7 62h115c-10 21-22 26-39 26H37C22 88 14 77 7 62Z" fill="#f3ead9" stroke="#316c79" stroke-width="3"/>
                        <path d="M35 35h33v27H35Z" fill="#fff9e5" stroke="#316c79" stroke-width="2"/>
                        <path d="M40 41h21v13H40Z" fill="#397186"/><path d="M30 33h44" stroke="#ea9e64" stroke-width="4"/>
                        <path d="M94 62c8-36 15-47 27-45l15 6" fill="none" stroke="#274750" stroke-width="2"/>
                        <path d="m136 23-7 70" stroke="#e4f9ee" stroke-width="1.5"/>
                    </svg>
                    <div class="hook-cable" id="hook-cable"></div>
                    <div class="hook-head" id="hook-head">
                        <svg width="40" height="48" viewBox="0 0 40 48" fill="none"><path d="M20 0v29c0 20 18 20 18 0v-8l-7 7" stroke="#4c6675" stroke-width="4" stroke-linejoin="round"/><path d="M20 0v29c0 17 16 17 16 0v-5" stroke="#deebef" stroke-width="1.5"/></svg>
                    </div>
                </div>

                <div class="absolute inset-0 z-20 {{ ! $isFishing ? 'hidden' : '' }}" id="fish-container" role="group" aria-label="Answer fish"></div>
                <template id="answer-fish-template">
                    <button type="button" class="answer-fish">
                        <svg class="fish-visual" viewBox="0 0 132 80" aria-hidden="true">
                            <g class="fish-design-yellowfin">
                            <g class="fish-tail"><path d="M38 40 6 15Q1 40 6 65Z" fill="var(--fish-dark)" stroke="var(--fish-outline)" stroke-width="2"/><path d="m9 25 22 15L9 55M7 40h24" fill="none" stroke="var(--fish-light)" stroke-width="2"/></g>
                            <path d="M48 24Q57 1 79 12L88 27M48 56Q63 79 82 64L87 52" fill="var(--fish-dark)" stroke="var(--fish-outline)" stroke-width="2"/>
                            <path d="M27 40C35 8 102 5 120 39 103 75 39 73 27 40Z" fill="var(--fish-color)" stroke="var(--fish-outline)" stroke-width="2"/>
                            <path d="M35 44Q74 73 115 43C96 66 51 68 35 44Z" fill="var(--fish-light)"/>
                            <path d="M42 29Q68 13 91 23" fill="none" stroke="var(--fish-light)" stroke-width="5" stroke-linecap="round"/>
                            <path class="fish-fin" d="M76 45Q70 48 69 59 83 59 89 46" fill="var(--fish-dark)" stroke="var(--fish-outline)" stroke-width="1.5"/>
                            <path d="M93 31q-6 10 0 20" fill="none" stroke="var(--fish-outline)" stroke-width="1.5" opacity=".5"/>
                            <circle cx="103" cy="31" r="7" fill="white"/><circle cx="105" cy="32" r="3.6" fill="#153440"/><circle cx="106" cy="30" r="1.3" fill="white"/>
                            <path d="m115 42 5-3" stroke="var(--fish-outline)" stroke-width="2" stroke-linecap="round"/>
                            </g>
                            <g class="fish-design-pufferfish">
                                <path class="fish-tail" d="m48 35-26-16q-7 16 0 32Z" fill="#dea035" stroke="#93743b" stroke-width="2"/>
                                <path d="m52 17-4-10 12 5m9-3 5-8 5 9m13 4 10-5-1 12m-52 8-10 5 11 5m2 12-5 9 12-3m35-4 10 5-2-12" fill="#ffe2a0" stroke="#b69650" stroke-width="1.5"/>
                                <ellipse cx="75" cy="35" rx="31" ry="27" fill="#e6be52" stroke="#93743b" stroke-width="2"/>
                                <path d="M47 41q28 12 57-1c-3 30-51 30-57 1Z" fill="#fff0bc"/>
                                <g fill="#93743b"><circle cx="58" cy="26" r="3"/><circle cx="69" cy="17" r="3"/><circle cx="78" cy="22" r="2.5"/><circle cx="53" cy="36" r="2.5"/><circle cx="65" cy="34" r="2.5"/></g>
                                <path class="fish-fin" d="M65 36q-22-10-16 10 8 3 16-10Z" fill="#dea035" stroke="#93743b"/>
                                <ellipse cx="91" cy="27" rx="8" ry="9" fill="#fffbea"/><ellipse cx="94" cy="28" rx="4" ry="5" fill="#153440"/><circle cx="95" cy="25" r="1.7" fill="white"/>
                                <ellipse cx="107" cy="37" rx="6" ry="4" fill="#fff0bc"/>
                                <path d="m108 37 4 1" stroke="#93743b" stroke-width="2" stroke-linecap="round"/>
                            </g>
                            <g class="fish-design-shark">
                                <path class="fish-tail" d="M35 38 9 10l6 26-7 21 24-13Z" fill="#587e98" stroke="#365e78" stroke-width="2"/>
                                <path d="M54 27 67 3 77 28" fill="#587e98" stroke="#365e78" stroke-width="2"/>
                                <path d="M29 39c19-24 64-22 93-4q4 5-1 7C87 60 51 59 29 39Z" fill="#7199b1" stroke="#365e78" stroke-width="2"/>
                                <path d="M37 43q35 4 84-3c-29 21-62 16-84 3Z" fill="#e6f0ed"/>
                                <path class="fish-fin" d="m73 39-18 24 1-23" fill="#587e98" stroke="#365e78" stroke-width="1.5"/>
                                <path d="m79 29-2 10m-4-11-2 10m-4-10-2 10" fill="none" stroke="#365e78" stroke-width="1.7" stroke-linecap="round"/>
                                <circle cx="99" cy="31" r="5" fill="#e6f0ed"/><circle cx="100" cy="31" r="3" fill="#153440"/><circle cx="101" cy="30" r="1" fill="white"/>
                                <path d="M97 42q11 5 19-3" fill="none" stroke="#365e78" stroke-width="1.7" stroke-linecap="round"/>
                            </g>
                            <g class="fish-design-jellyfish">
                                <g class="jelly-tentacles" fill="none" stroke-linecap="round">
                                    <path d="M51 33q-9 8 1 14t-4 16m20-30q10 8-1 16t5 15m14-31q-9 8 0 14t-5 16" stroke="#bc77bb" stroke-width="2.5"/>
                                    <path d="M59 34q10 8 0 17m17-17q-10 9 0 18" stroke="#f2b5df" stroke-width="5"/>
                                </g>
                                <path d="M39 33C39 1 95 1 96 33q-5 7-10 0-6 8-12 0-7 8-13 0-6 8-12 0-5 6-10 0Z" fill="#e6b4df" fill-opacity=".88" stroke="#ad75b9" stroke-width="1.5"/>
                                <path d="M46 23q4-12 17-12" fill="none" stroke="#fff1fd" stroke-width="3" stroke-linecap="round"/>
                                <ellipse cx="59" cy="25" rx="2.5" ry="3.5" fill="#493b66"/><ellipse cx="78" cy="25" rx="2.5" ry="3.5" fill="#493b66"/>
                                <path d="M65 28q4 4 8 0" fill="none" stroke="#79538a" stroke-width="1.5" stroke-linecap="round"/>
                            </g>
                        </svg>
                        <span class="fish-letter"></span>
                    </button>
                </template>

                <div class="absolute bottom-3 left-1/2 z-40 -translate-x-1/2 {{ ! $isFishing ? 'hidden' : '' }}" id="egg-wrapper">
                    <div class="egg-glow"></div><div class="hatch-light" id="hatch-flash"></div>
                    <div class="data-egg-container pulse-bag" id="power-core" style="--base-scale: 1;">
                        <div class="data-egg" id="shell-main">
                            <div class="crack left-1/4 top-1/3 rotate-[15deg]" id="crack-1"></div><div class="crack right-1/4 top-1/4 -rotate-[25deg]" id="crack-2"></div><div class="crack left-1/2 bottom-1/4 rotate-[180deg]" id="crack-3"></div><div class="crack left-[20%] bottom-[40%] rotate-[45deg]" id="crack-4"></div><div class="crack right-[15%] bottom-[30%] rotate-[-60deg]" id="crack-5"></div>
                            <div class="relative z-10 flex flex-col items-center pt-14 text-primary"><span class="material-symbols-outlined mb-1 text-4xl opacity-80" id="bag-icon" style="font-variation-settings: 'FILL' 1;">dataset</span><span class="font-label text-[9px] font-bold uppercase tracking-widest">Data Egg</span></div>
                        </div>
                        <div class="hatch-core" id="inside-core"><div class="flex flex-col items-center"><div class="flex h-20 w-20 items-center justify-center rounded-full border-4 border-primary bg-white shadow-[0_0_50px_rgba(68,165,255,0.4)]"><span class="material-symbols-outlined text-5xl text-primary" style="font-variation-settings: 'FILL' 1;">stars</span></div><div class="mt-4 rounded-full bg-primary px-4 py-2 text-xs font-black uppercase tracking-widest text-white shadow-lg">Unlocked</div></div></div>
                    </div>
                </div>

                @if ($isFlashcards && ! $isOralReading)
                    <x-frog-pond :question="$firstQuestion" :question-count="$questionCount" />
                @endif
                @if ($isTreasureQuest && ! $isOralReading)
                    <x-treasure-quest :student="$student" :question-count="$questionCount" />
                @endif
                @if ($hasReadingStage)
                    <div id="story-gate" class="assessment-story-gate">
                        <section class="assessment-reader {{ $isOralReading ? 'assessment-reader-oral' : '' }}" aria-labelledby="story-title">
                            <div class="assessment-reader-heading">
                                <div class="assessment-reader-icon">
                                    <span class="material-symbols-outlined">auto_stories</span>
                                </div>
                                <div>
                                    <p class="assessment-eyebrow">{{ $isOralReading ? 'Reading Studio' : 'Read First' }}</p>
                                    <h2 id="story-title">{{ $storyTitle ?: $assessment->title }}</h2>
                                </div>
                            </div>

                            @if ($isOralReading)
                                <div class="assessment-reading-panes">
                                    <div class="assessment-reading-pane">
                                        <div class="border-b border-primary/10 bg-primary/5 px-5 py-3">
                                            <p class="font-label text-[10px] font-black uppercase tracking-[0.22em] text-primary-dim">Reading</p>
                                        </div>
                                        <div class="min-h-0 flex-1 overflow-y-auto p-4 text-sm sm:p-5 sm:text-base leading-relaxed text-on-surface-variant" data-sync-scroll="oral-story">
                                            {!! $storyReadOnlyHtml !!}
                                        </div>
                                    </div>
                                    <div class="assessment-reading-pane assessment-teacher-pane">
                                        <div class="border-b border-secondary/10 bg-secondary/5 px-5 py-3">
                                            <p class="font-label text-[10px] font-black uppercase tracking-[0.22em] text-primary-dim">Teacher Check</p>
                                        </div>
                                        <div class="min-h-0 flex-1 overflow-y-auto p-4 text-sm sm:p-5 sm:text-base leading-relaxed text-on-surface-variant" id="story-reader-text" data-sync-scroll="oral-story">
                                            {!! $storyMarkingHtml !!}
                                        </div>
                                    </div>
                                </div>
                                <div class="assessment-legend mt-4 flex flex-wrap items-center gap-3 p-3 text-sm font-bold text-on-surface-variant" aria-label="Oral reading marking legend">
                                    <span class="font-label text-[10px] font-black uppercase tracking-[0.22em] text-primary-dim">Legend</span>
                                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-2 shadow-sm"><span class="h-4 w-4 rounded bg-red-400/70 ring-1 ring-red-500/30"></span>Mispronounced</span>
                                    <span id="oral-mark-count" role="status" aria-live="polite">0 red-marked words</span>
                                </div>
                                <div class="assessment-mark-tools mt-3 flex flex-wrap items-center gap-2 p-3 text-sm font-bold text-on-surface-variant" aria-label="Oral reading mark mode">
                                    <span class="font-label text-[10px] font-black uppercase tracking-[0.22em] text-primary-dim">Mark Mode</span>
                                    <button class="mark-mode-button is-active inline-flex items-center gap-2 rounded-full bg-surface-container-low px-3 py-2" type="button" data-mark-mode="word" aria-pressed="true">
                                        <span class="material-symbols-outlined text-lg">touch_app</span>
                                        Word
                                    </button>
                                    <button class="mark-mode-button mark-mode-red inline-flex items-center gap-2 rounded-full bg-surface-container-low px-3 py-2" type="button" data-mark-mode="sentence-2" aria-pressed="false">
                                        <span class="material-symbols-outlined text-lg">format_color_fill</span>
                                        Sentence: Mispronounced
                                    </button>
                                </div>
                            @else
                                <div class="assessment-passage overflow-y-auto p-4 text-sm sm:p-5 sm:text-base leading-relaxed text-on-surface-variant">
                                    <div id="story-reader-text">
                                        {!! $storyHtml !!}
                                    </div>
                                </div>
                            @endif

                            @if ($isSilentReading)
                                <div class="assessment-timer mt-5 p-4">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="font-label text-[10px] font-black uppercase tracking-[0.22em] text-primary-dim">Silent Reading Timer</p>
                                            <p class="mt-1 font-display text-3xl font-black text-on-surface" id="reading-timer-display">00:00</p>
                                        </div>
                                        <div class="flex flex-wrap gap-3">
                                            <button class="inline-flex items-center gap-2 rounded-lg bg-secondary px-5 py-3 text-sm font-black uppercase tracking-widest text-on-secondary transition-colors hover:bg-secondary-dim" id="start-timer-button" type="button">
                                                <span class="material-symbols-outlined text-lg">timer</span>
                                                Start Timer
                                            </button>
                                            <button class="inline-flex items-center gap-2 rounded-lg bg-error px-5 py-3 text-sm font-black uppercase tracking-widest text-on-error opacity-60 transition-colors" id="end-timer-button" type="button" disabled>
                                                <span class="material-symbols-outlined text-lg">timer_off</span>
                                                End Timer
                                            </button>
                                        </div>
                                    </div>
                                    <p class="mt-3 text-sm font-medium text-on-surface-variant" id="reading-timer-status">Start the timer when the reader begins. End it when reading is done.</p>
                                </div>
                            @endif

                            <div class="assessment-reader-actions mt-6 flex justify-end">
                                <button class="inline-flex items-center gap-2 rounded-lg bg-primary px-6 py-3 text-sm font-black uppercase tracking-widest text-on-primary shadow-lg shadow-primary/20 transition-colors hover:bg-primary-dim disabled:cursor-not-allowed disabled:opacity-50" id="start-questions-button" type="button" @if ($isSilentReading) disabled @endif>
                                    {{ $isOralReading ? 'Finish Assessment' : 'Start Questions' }}
                                    <span class="material-symbols-outlined text-lg">{{ $isOralReading ? 'check_circle' : 'arrow_forward' }}</span>
                                </button>
                            </div>
                        </section>
                    </div>
                @endif
            </section>

            <section class="assessment-results hidden opacity-0 transition-opacity duration-300" id="mission-modal" tabindex="-1" aria-labelledby="assessment-result-title">
                <header class="assessment-result-heading">
                    <div class="assessment-result-avatar"><x-student-character :user="$student" variant="portrait" /></div>
                    <div>
                        <p class="assessment-eyebrow">Assessment Complete</p>
                        <h2 id="assessment-result-title">Great job, {{ str($studentName)->before(' ') }}!</h2>
                        <p id="result-summary" role="status">Checking your real score...</p>
                    </div>
                </header>

                <div class="assessment-result-metrics">
                    <article class="assessment-result-metric">
                        <p><span class="material-symbols-outlined" aria-hidden="true">target</span> {{ $isOralReading ? 'Word Reading' : 'Accuracy Score' }}</p>
                        <div class="assessment-result-value"><strong id="result-accuracy">0</strong><span id="result-accuracy-unit">%</span></div>
                        <div class="assessment-result-track"><div id="result-progress" style="width: 0%"></div></div>
                        @if ($isOralReading)<p>Calculated from red-marked words</p>@endif
                    </article>
                    <article class="assessment-result-metric assessment-metric-xp">
                        <p><span class="material-symbols-outlined" aria-hidden="true">stars</span> Experience Earned</p>
                        <div class="assessment-result-value"><strong id="result-points">Saving...</strong></div>
                        <p>Assessment points</p>
                    </article>
                    <article class="assessment-result-metric assessment-metric-correct">
                        <p><span class="material-symbols-outlined" aria-hidden="true">task_alt</span> {{ $isOralReading ? 'Red-marked Words' : 'Correct Answers' }}</p>
                        <div class="assessment-result-value"><strong id="result-correct">0/{{ $questionCount }}</strong></div>
                        <p>{{ $assessmentTypeLabel }}</p>
                    </article>
                </div>

                @if ($assessment->subject === 'literacy')
                    <x-phil-iri-result :live="true" />
                @endif
                <x-ml-prediction-result :live="true" />
                <p class="assessment-coin-reward" role="status"><span class="material-symbols-outlined" aria-hidden="true">toll</span><strong id="result-coins">Saving coin reward...</strong></p>
                <div class="assessment-result-detail">
                    <section class="assessment-breakdown" aria-labelledby="breakdown-title">
                        <h3 id="breakdown-title">Performance Breakdown</h3>
                        <dl>
                            <div><dt>Passage</dt><dd>{{ $storyTitle ?: $assessment->title }}</dd></div>
                            <div><dt>Assessment Type</dt><dd>{{ $assessmentTypeLabel }}</dd></div>
                            <div class="hidden" id="result-reading-time-row"><dt>Reading Time</dt><dd id="result-reading-time">00:00</dd></div>
                        </dl>
                        @if ($isOralReading)
                            <div class="assessment-pronunciation">
                                <h4><span class="assessment-color-red"></span> Mispronounced</h4>
                                <div class="flex flex-wrap gap-2" id="result-wrong-pronunciation"><span>No red words marked.</span></div>
                            </div>
                        @else
                            <div id="result-wrong-pronunciation" hidden></div>
                        @endif
                    </section>
                    <aside class="assessment-achievement">
                        <span class="material-symbols-outlined" aria-hidden="true">workspace_premium</span>
                        <h3 id="result-badge-title">Your Achievement</h3>
                        <p id="result-badge">Calculating your achievement...</p>
                    </aside>
                </div>

                <footer class="assessment-result-actions">
                    <a class="assessment-primary-action" href="{{ $assessmentBackUrl }}"><span class="material-symbols-outlined" aria-hidden="true">arrow_back</span> {{ $assessmentBackLabel }}</a>
                    <button class="assessment-secondary-action" id="assessment-result-action" type="button">Retry Saving</button>
                </footer>
            </section>

            <script>
                (() => {
                    const canvas = document.getElementById('mission-canvas');
                    const hookAssembly = document.getElementById('hook-assembly');
                    const hookCable = document.getElementById('hook-cable');
                    const hookHead = document.getElementById('hook-head');
                    const container = document.getElementById('fish-container');
                    const fishTemplate = document.getElementById('answer-fish-template');
                    const catchStatus = document.getElementById('ocean-catch-status');

                    const reducedMotion = () => window.PgaalsPreferences?.reducedMotion ?? window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    const scoreEl = document.getElementById('score');
                    const progressEl = document.getElementById('progress-bar');
                    const bag = document.getElementById('power-core');
                    const modal = document.getElementById('mission-modal');
                    const eggWrapper = document.getElementById('egg-wrapper');
                    const shellMain = document.getElementById('shell-main');
                    const hatchFlash = document.getElementById('hatch-flash');
                    const missionInfo = document.getElementById('mission-info');
                    const questionNode = document.getElementById('question-node');
                    const resultSummary = document.getElementById('result-summary');
                    const resultAccuracy = document.getElementById('result-accuracy');
                    const resultProgress = document.getElementById('result-progress');
                    const resultAccuracyUnit = document.getElementById('result-accuracy-unit');
                    const resultPoints = document.getElementById('result-points');
                    const resultCoins = document.getElementById('result-coins');
                    const resultCorrect = document.getElementById('result-correct');
                    const resultBadgeTitle = document.getElementById('result-badge-title');
                    const resultBadge = document.getElementById('result-badge');
                    const resultWrongPronunciation = document.getElementById('result-wrong-pronunciation');
                    const storyGate = document.getElementById('story-gate');
                    const storyReaderText = document.getElementById('story-reader-text');
                    const markModeButtons = Array.from(document.querySelectorAll('[data-mark-mode]'));
                    const syncedStoryScrollers = Array.from(document.querySelectorAll('[data-sync-scroll="oral-story"]'));
                    const progressScrollers = syncedStoryScrollers.length ? syncedStoryScrollers : (storyReaderText ? [storyReaderText.parentElement] : []);
                    const startQuestionsButton = document.getElementById('start-questions-button');
                    const startTimerButton = document.getElementById('start-timer-button');
                    const endTimerButton = document.getElementById('end-timer-button');
                    const readingTimerDisplay = document.getElementById('reading-timer-display');
                    const readingTimerStatus = document.getElementById('reading-timer-status');
                    const resultReadingTimeRow = document.getElementById('result-reading-time-row');
                    const resultReadingTime = document.getElementById('result-reading-time');
                    const gameMusic = document.getElementById('assessment-game-music');
                    const hookReelSound = document.getElementById('multiple-choice-hook-sound');
                    const frogWrongAnswerSound = document.getElementById('frog-wrong-answer-sound');
                    const frogCorrectAnswerSound = document.getElementById('frog-correct-answer-sound');
                    const submitUrl = @json($attemptSubmitUrl);
                    const progressUrl = @json($attemptProgressUrl);
                    const attemptKey = @json($progress->attempt_key ?? null);
                    const initialProgress = @json(['revision' => $progress->revision ?? 0, 'state' => $progress->state ?? []]);
                    const storageKey = @json('assessment-progress:'.$student->id.':'.$assessment->id.':'.($progress->attempt_key ?? 'preview'));
                    const saveStatus = document.getElementById('assessment-save-status');
                    const resultAction = document.getElementById('assessment-result-action');
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    const assessmentType = @json($assessmentType);
                    const isFlashcards = @json($isFlashcards);
                    const isTreasureQuest = @json($isTreasureQuest);
                    const isFishing = @json($isFishing);
                    const treasureGame = document.querySelector('.treasure-quest');
                    const treasureButtons = Array.from(document.querySelectorAll('[data-treasure-answer]'));
                    const treasureNext = document.getElementById('treasure-next');
                    const isOralReading = assessmentType === 'oral_reading';
                    const isSilentReading = assessmentType === 'silent_reading';
                    const canTrackPronunciation = isOralReading;
                    const frogGame = document.getElementById('frog-flashcards-game');
                    const frogScore = document.getElementById('frog-score');
                    const frogProgressBar = document.getElementById('frog-progress-bar');
                    const frogLevelText = document.getElementById('frog-level-text');
                    const frogQuestionText = document.getElementById('frog-question-text');
                    const frogQuestionCard = document.getElementById('frog-question-card');
                    const frogAnswerButtons = Array.from(document.querySelectorAll('[data-frog-answer]'));
                    const questions = @json($gameQuestions->values());
                    const targetsNeeded = questions.length;
                    const capturedAnswers = {};
                    let caughtCount = 0;
                    let totalScore = 0;
                    let isHooking = false;
                    let isHatching = false;
                    let isProcessingCapture = false;
                    let currentQuestionIndex = 0;
                    let currentHookX = canvas.clientWidth / 2;
                    let missionStarted = !storyGate;
                    let fishSchool = [];
                    let fishFrame = null;
                    let hookFrame = null;
                    let lastFishFrame = null;
                    let readingTimer = null;
                    let readingStartedAt = null;
                    let readingElapsedSeconds = 0;
                    let oralMarkMode = 'word';
                    let gameMusicFadeFrame = null;
                    let hookReelFadeTimer = null;
                    let timerStatus = 'idle';
                    let missionFinished = false;
                    let submissionSaved = false;
                    let submissionInFlight = false;
                    let progressRevision = Number(initialProgress.revision || 0);
                    let saveTimeout = null;
                    let frogRoundEnding = false;
                    let treasureRoundEnding = false;

                    function updateTreasureProgress() {
                        if (!treasureGame) return;
                        treasureGame.dataset.answered = String(caughtCount);
                        treasureGame.style.setProperty('--treasure-route', String(targetsNeeded ? caughtCount / targetsNeeded : 0));
                        document.getElementById('treasure-gems').textContent = String(totalScore);
                        document.getElementById('treasure-progress').value = caughtCount;
                        document.getElementById('treasure-progress-text').textContent = `${caughtCount} / ${targetsNeeded} explored`;
                    }

                    function renderTreasureQuestion(focus = false) {
                        if (!treasureGame || !questions[currentQuestionIndex]) return;
                        const question = questions[currentQuestionIndex];
                        treasureGame.dataset.selected = '';
                        treasureGame.dataset.outcome = '';
                        treasureNext.hidden = true;
                        document.getElementById('treasure-feedback').textContent = '';
                        document.getElementById('treasure-question-number').textContent = `Question ${currentQuestionIndex + 1} / ${targetsNeeded}`;
                        const heading = document.getElementById('treasure-question-text');
                        heading.textContent = question.text;
                        treasureButtons.forEach(button => {
                            const option = question.options.find(item => item.l === button.dataset.treasureAnswer);
                            button.hidden = !option;
                            button.disabled = !option || !missionStarted;
                            delete button.dataset.result;
                            button.querySelector('[data-treasure-answer-label]').textContent = option?.t || '';
                            button.querySelector('.treasure-verdict').textContent = '';
                            const label = treasureGame.querySelector(`[data-chest-label="${button.dataset.treasureAnswer}"]`);
                            delete label.dataset.result;
                        });
                        updateTreasureProgress();
                        if (focus) heading.focus({ preventScroll: true });
                    }

                    function answerTreasure(letter, button) {
                        if (!treasureGame || !missionStarted || missionFinished || isProcessingCapture) return;
                        const question = questions[currentQuestionIndex];
                        if (!question || capturedAnswers[currentQuestionIndex] || !question.options.some(option => option.l === letter)) return;
                        isProcessingCapture = true;
                        playGameMusic();
                        capturedAnswers[currentQuestionIndex] = letter;
                        caughtCount++;
                        const correct = question.correct === letter;
                        if (correct) totalScore++;
                        treasureButtons.forEach(item => item.disabled = true);
                        button.dataset.result = correct ? 'correct' : 'incorrect';
                        button.querySelector('.treasure-verdict').textContent = correct ? 'check_circle' : 'cancel';
                        treasureGame.dataset.selected = letter;
                        treasureGame.dataset.outcome = correct ? 'correct' : 'incorrect';
                        treasureGame.querySelector(`[data-chest-label="${letter}"]`).dataset.result = button.dataset.result;
                        document.getElementById('treasure-feedback').textContent = correct
                            ? 'Correct! You found a gem.'
                            : 'Not quite. This chest holds sand. Keep exploring!';
                        updateTreasureProgress();
                        persistProgress(true);
                        treasureNext.hidden = false;
                        treasureNext.disabled = true;
                        document.getElementById('treasure-next-label').textContent = caughtCount >= targetsNeeded ? 'Open the vault' : 'Continue';
                        setTimeout(() => {
                            if (missionFinished) return;
                            treasureNext.disabled = false;
                            treasureNext.focus({ preventScroll: true });
                        }, reducedMotion() ? 150 : 1000);
                    }

                    function endTreasureRound() {
                        if (!treasureGame || treasureRoundEnding) return;
                        treasureRoundEnding = true;
                        isProcessingCapture = true;
                        canvas.classList.remove('assessment-reading');
                        storyGate?.classList.add('hidden');
                        treasureGame.dataset.finished = 'true';
                        treasureNext.hidden = true;
                        treasureButtons.forEach(button => button.disabled = true);
                        updateTreasureProgress();
                        document.getElementById('treasure-feedback').textContent = `Trail complete! ${totalScore} of ${targetsNeeded} answers correct.`;
                        treasureGame.querySelector('.treasure-map').scrollIntoView({ block: 'center', behavior: reducedMotion() ? 'auto' : 'smooth' });
                        setTimeout(victory, reducedMotion() ? 200 : 1600);
                    }

                    function progressSnapshot() {
                        if (readingTimer) updateReadingTimer();
                        const scroller = progressScrollers[0];
                        const allAnswered = targetsNeeded > 0 && Object.keys(capturedAnswers).length === targetsNeeded;
                        return {
                            answers: { ...capturedAnswers },
                            phase: missionFinished || allAnswered ? 'finished' : (missionStarted && !isOralReading ? 'questions' : 'reading'),
                            reading_seconds: readingElapsedSeconds,
                            timer_status: timerStatus,
                            word_marks: Array.from(storyReaderText?.querySelectorAll('.story-word') || [], word => Number(word.dataset.mark || 0)),
                            sentence_marks: Array.from(storyReaderText?.querySelectorAll('.story-sentence') || [], sentence => Number(sentence.dataset.sentenceMark || 0)),
                            mark_mode: oralMarkMode,
                            scroll_ratio: scroller ? scroller.scrollTop / Math.max(1, scroller.scrollHeight - scroller.clientHeight) : 0,
                        };
                    }

                    function persistProgress(leaving = false) {
                        if (!attemptKey || submissionSaved) return;
                        saveStatus.textContent = 'Saving progress...';
                        progressRevision = Math.max(Date.now(), progressRevision + 1);
                        const snapshot = { revision: progressRevision, state: progressSnapshot() };
                        let backedUp = false;
                        try {
                            localStorage.setItem(storageKey, JSON.stringify(snapshot));
                            backedUp = true;
                        } catch (_) {}
                        clearTimeout(saveTimeout);
                        const send = async () => {
                            if (submissionSaved) return;
                            saveStatus.textContent = 'Saving progress...';
                            try {
                                const response = await fetch(progressUrl, {
                                    method: 'POST',
                                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                                    body: JSON.stringify({ attempt_key: attemptKey, ...snapshot }),
                                    keepalive: leaving,
                                });
                                if (!response.ok) throw new Error('Progress not saved');
                                if (!submissionSaved && snapshot.revision === progressRevision) saveStatus.textContent = 'Progress saved';
                            } catch (_) {
                                if (!submissionSaved && snapshot.revision === progressRevision) {
                                    saveStatus.textContent = backedUp ? 'Saved on this device' : 'Progress not saved';
                                }
                            }
                        };
                        if (leaving) send(); else saveTimeout = setTimeout(send, 250);
                    }

                    function restoreProgress() {
                        let snapshot = initialProgress;
                        try {
                            const backup = JSON.parse(localStorage.getItem(storageKey) || 'null');
                            if (backup?.revision > Number(snapshot.revision || 0)) snapshot = backup;
                        } catch (_) {}
                        const state = snapshot.state || {};
                        progressRevision = Number(snapshot.revision || 0);
                        Object.entries(state.answers || {}).forEach(([index, answer]) => {
                            if (questions[index]?.options.some(option => option.l === answer)) capturedAnswers[index] = answer;
                        });
                        caughtCount = Object.keys(capturedAnswers).length;
                        currentQuestionIndex = questions.findIndex((_, index) => !capturedAnswers[index]);
                        if (currentQuestionIndex < 0) currentQuestionIndex = targetsNeeded;
                        totalScore = questions.filter((question, index) => question.correct === capturedAnswers[index]).length;
                        const savedPhase = typeof state.phase === 'string' ? state.phase : null;
                        const hasQuestionProgress = Object.keys(capturedAnswers).length > 0;
                        missionFinished = savedPhase === 'finished' || (targetsNeeded > 0 && caughtCount === targetsNeeded);
                        missionStarted = !storyGate;
                        if (storyGate && savedPhase === 'questions') {
                            // Starting questions without answering should not skip the story on the next open.
                            missionStarted = hasQuestionProgress || missionFinished;
                        } else if (savedPhase) {
                            missionStarted = savedPhase !== 'reading';
                        }
                        const savedReadingSeconds = Number(state.reading_seconds || 0);
                        readingElapsedSeconds = Number.isFinite(savedReadingSeconds) ? Math.max(0, savedReadingSeconds) : 0;
                        timerStatus = state.timer_status === 'running' ? 'paused' : (state.timer_status || 'idle');
                        storyReaderText?.querySelectorAll('.story-sentence').forEach((sentence, index) => setSentenceMark(sentence, Number(state.sentence_marks?.[index] || 0)));
                        storyReaderText?.querySelectorAll('.story-word').forEach((word, index) => setWordMark(word, Number(state.word_marks?.[index] || 0)));
                        storyReaderText?.querySelectorAll('.story-sentence').forEach(syncSentenceMark);
                        updateOralMarkCount();
                        setOralMarkMode(state.mark_mode || 'word');
                        requestAnimationFrame(() => progressScrollers.forEach(scroller => {
                            scroller.scrollTop = Number(state.scroll_ratio || 0) * Math.max(0, scroller.scrollHeight - scroller.clientHeight);
                        }));
                        if (readingTimerDisplay) readingTimerDisplay.textContent = formatElapsedTime(readingElapsedSeconds);
                        if (isSilentReading && startTimerButton) {
                            startTimerButton.disabled = timerStatus === 'finished';
                            startTimerButton.classList.toggle('opacity-60', timerStatus === 'finished');
                            if (timerStatus === 'paused') startTimerButton.lastChild.textContent = ' Resume Timer';
                            endTimerButton.disabled = timerStatus !== 'paused';
                            endTimerButton.classList.toggle('opacity-60', timerStatus !== 'paused');
                            startQuestionsButton.disabled = timerStatus !== 'finished';
                            if (timerStatus === 'finished') readingTimerStatus.textContent = `Reading finished in ${formatElapsedTime(readingElapsedSeconds)}.`;
                            if (timerStatus === 'paused') readingTimerStatus.textContent = `Timer paused at ${formatElapsedTime(readingElapsedSeconds)}.`;
                        }
                        if (progressRevision > 0) saveStatus.textContent = 'Progress restored';
                        if (isFlashcards) updateFrogProgress();
                        else if (isTreasureQuest) updateTreasureProgress();
                        else if (!isOralReading && targetsNeeded > 0) {
                            scoreEl.textContent = `${caughtCount}/${targetsNeeded}`;
                            progressEl.style.width = `${caughtCount / targetsNeeded * 100}%`;
                            for (let index = 1; index <= Math.ceil(caughtCount / targetsNeeded * 5); index++) document.getElementById(`crack-${index}`)?.classList.add('crack-visible');
                            bag.style.setProperty('--base-scale', 1 + caughtCount / targetsNeeded * .4);
                        }
                    }

                    function resumeReadingTicker() {
                        if (!isSilentReading || timerStatus !== 'running' || readingTimer || missionFinished) return;
                        updateReadingTimer();
                        readingTimer = window.setInterval(updateReadingTimer, 1000);
                    }

                    function pauseAndSave() {
                        if (readingTimer || timerStatus === 'running') {
                            updateReadingTimer();
                            clearInterval(readingTimer);
                            readingTimer = null;
                            timerStatus = 'paused';
                            startTimerButton.disabled = false;
                            startTimerButton.classList.remove('opacity-60');
                            startTimerButton.lastChild.textContent = ' Resume Timer';
                            readingTimerStatus.textContent = `Timer paused at ${formatElapsedTime(readingElapsedSeconds)}.`;
                        }
                        persistProgress(true);
                        stopGameMusic();
                        fadeHookReelSound();
                    }

                    function formatElapsedTime(totalSeconds) {
                        const minutes = Math.floor(totalSeconds / 60).toString().padStart(2, '0');
                        const seconds = Math.floor(totalSeconds % 60).toString().padStart(2, '0');
                        return `${minutes}:${seconds}`;
                    }

                    function playGameMusic() {
                        if (!gameMusic || isOralReading || missionFinished) return;
                        gameMusic.volume = 0.28;
                        gameMusic.play().catch(() => {});
                    }

                    function stopGameMusic() {
                        cancelAnimationFrame(gameMusicFadeFrame);
                        gameMusicFadeFrame = null;
                        if (!gameMusic) return;
                        gameMusic.pause();
                        gameMusic.currentTime = 0;
                    }

                    function fadeOutGameMusic() {
                        if (!gameMusic || gameMusic.paused) {
                            stopGameMusic();
                            return;
                        }
                        cancelAnimationFrame(gameMusicFadeFrame);
                        const startingVolume = gameMusic.volume;
                        const startedAt = performance.now();
                        const duration = 2000;

                        function fade(now) {
                            const progress = Math.min(1, (now - startedAt) / duration);
                            const easedProgress = progress * progress * (3 - 2 * progress);
                            gameMusic.volume = startingVolume * (1 - easedProgress);
                            if (progress < 1) {
                                gameMusicFadeFrame = requestAnimationFrame(fade);
                            } else {
                                stopGameMusic();
                            }
                        }

                        gameMusicFadeFrame = requestAnimationFrame(fade);
                    }

                    function playHookReelSound() {
                        if (!hookReelSound || !isFishing) return;
                        clearInterval(hookReelFadeTimer);
                        hookReelSound.pause();
                        hookReelSound.currentTime = 0;
                        hookReelSound.volume = 0.75;
                        hookReelSound.play().catch(() => {});
                    }

                    function fadeHookReelSound() {
                        if (!hookReelSound || hookReelSound.paused) return;
                        clearInterval(hookReelFadeTimer);
                        hookReelFadeTimer = setInterval(() => {
                            const nextVolume = Math.max(0, hookReelSound.volume - 0.08);
                            hookReelSound.volume = nextVolume;

                            if (nextVolume <= 0) {
                                clearInterval(hookReelFadeTimer);
                                hookReelSound.pause();
                                hookReelSound.currentTime = 0;
                                hookReelSound.volume = 0.75;
                            }
                        }, 35);
                    }

                    function playFrogWrongAnswerSound() {
                        if (!frogWrongAnswerSound) return;
                        frogWrongAnswerSound.pause();
                        frogWrongAnswerSound.currentTime = 0;
                        frogWrongAnswerSound.volume = 0.8;
                        frogWrongAnswerSound.play().catch(() => {});
                    }

                    function playFrogCorrectAnswerSound() {
                        if (!frogCorrectAnswerSound) return;
                        frogCorrectAnswerSound.pause();
                        frogCorrectAnswerSound.currentTime = 0;
                        frogCorrectAnswerSound.volume = 0.8;
                        frogCorrectAnswerSound.play().catch(() => {});
                    }

                    function updateReadingTimer() {
                        if (readingStartedAt === null || !Number.isFinite(readingStartedAt) || !readingTimerDisplay) return;
                        readingElapsedSeconds = Math.max(0, Math.floor((Date.now() - readingStartedAt) / 1000));
                        readingTimerDisplay.textContent = formatElapsedTime(readingElapsedSeconds);
                    }

                    function startReadingTimer() {
                        if (timerStatus === 'finished') return;
                        if (timerStatus === 'running') {
                            resumeReadingTicker();
                            return;
                        }

                        readingStartedAt = Date.now() - readingElapsedSeconds * 1000;
                        timerStatus = 'running';
                        updateReadingTimer();
                        resumeReadingTicker();
                        startTimerButton.disabled = true;
                        startTimerButton.classList.add('opacity-60');
                        endTimerButton.disabled = false;
                        endTimerButton.classList.remove('opacity-60');
                        readingTimerStatus.textContent = 'Timer is running. Click End Timer when the reader is done.';
                        persistProgress();
                    }

                    function endReadingTimer() {
                        if (timerStatus !== 'running' && timerStatus !== 'paused') return;
                        updateReadingTimer();
                        clearInterval(readingTimer);
                        readingTimer = null;
                        timerStatus = 'finished';
                        startTimerButton.disabled = true;
                        startTimerButton.classList.add('opacity-60');
                        endTimerButton.disabled = true;
                        endTimerButton.classList.add('opacity-60');
                        startQuestionsButton.disabled = false;
                        readingTimerStatus.innerText = `Reading finished in ${formatElapsedTime(readingElapsedSeconds)}. You can now start the questions.`;
                        persistProgress();
                    }

                    function updateReadingTimeResult() {
                        if (!isSilentReading || readingElapsedSeconds <= 0 || !resultReadingTimeRow || !resultReadingTime) return;
                        resultReadingTimeRow.classList.remove('hidden');
                        resultReadingTimeRow.classList.add('flex');
                        resultReadingTime.innerText = formatElapsedTime(readingElapsedSeconds);
                    }

                    function positionHook(clientX) {
                        const arena = container.getBoundingClientRect();
                        const canvasRect = canvas.getBoundingClientRect();
                        const tipX = Math.max(arena.left + 12, Math.min(arena.right - 12, clientX));
                        currentHookX = tipX - canvasRect.left - 18;
                        hookAssembly.style.left = `${currentHookX}px`;
                        hookAssembly.style.top = `${arena.top - canvasRect.top - 42}px`;
                    }

                    canvas.addEventListener('pointermove', (event) => {
                        if (isFishing && missionStarted && !isHooking && !isHatching && !isProcessingCapture && container.contains(event.target)) {
                            positionHook(event.clientX);
                        }
                    });

                    canvas.addEventListener('click', (event) => {
                        if (!missionStarted || !isFishing || !container.contains(event.target)) return;
                        if (isHooking || isHatching || isProcessingCapture) return;
                        const fish = event.target.closest('.answer-fish');
                        const fishRect = fish?.getBoundingClientRect();
                        positionHook(event.detail === 0 && fishRect ? fishRect.left + fishRect.width / 2 : event.clientX);
                        playGameMusic();
                        fireHook();
                    });

                    if (isFishing) {
                        new ResizeObserver(() => {
                            positionHook(canvas.getBoundingClientRect().left + currentHookX + 18);
                        }).observe(container);
                    }

                    function escapeHtml(value) {
                        return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                    }



                    let isSyncingStoryScroll = false;
                    syncedStoryScrollers.forEach((scroller) => {
                        scroller.addEventListener('scroll', () => {
                            if (isSyncingStoryScroll) return;
                            const maxScroll = Math.max(scroller.scrollHeight - scroller.clientHeight, 1);
                            const scrollRatio = scroller.scrollTop / maxScroll;
                            isSyncingStoryScroll = true;
                            syncedStoryScrollers.forEach((target) => {
                                if (target === scroller) return;
                                const targetMaxScroll = Math.max(target.scrollHeight - target.clientHeight, 1);
                                target.scrollTop = scrollRatio * targetMaxScroll;
                            });
                            requestAnimationFrame(() => {
                                isSyncingStoryScroll = false;
                            });
                        });
                    });
                    function setWordMark(word, mark) {
                        // Old yellow notes are not counted as mispronunciations.
                        mark = mark === 2 && !word.disabled ? 2 : 0;
                        word.dataset.mark = String(mark);
                        word.classList.toggle('story-word-mark-2', mark === 2);
                        word.setAttribute('aria-pressed', String(mark === 2));
                    }

                    function syncSentenceMark(sentence) {
                        const words = Array.from(sentence.querySelectorAll('.story-word:not(:disabled)'));
                        const marked = words.length > 0 && words.every(word => word.dataset.mark === '2');
                        sentence.dataset.sentenceMark = marked ? '2' : '0';
                        sentence.classList.toggle('story-sentence-mark-2', marked);
                    }

                    function updateOralMarkCount() {
                        const count = storyReaderText?.querySelectorAll('.story-word[data-mark="2"]').length || 0;
                        const counter = document.getElementById('oral-mark-count');
                        if (counter) counter.textContent = `${count} red-marked ${count === 1 ? 'word' : 'words'}`;
                    }

                    function setSentenceMark(sentence, mark) {
                        sentence.querySelectorAll('.story-word').forEach((word) => setWordMark(word, mark));
                        syncSentenceMark(sentence);
                    }

                    function setOralMarkMode(mode) {
                        mode = mode === 'sentence-2' ? mode : 'word';
                        oralMarkMode = mode;
                        markModeButtons.forEach((button) => {
                            const isActive = button.dataset.markMode === mode;
                            button.classList.toggle('is-active', isActive);
                            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                        });
                    }

                    markModeButtons.forEach((button) => {
                        button.addEventListener('click', () => setOralMarkMode(button.dataset.markMode || 'word'));
                    });

                    storyReaderText?.addEventListener('pointerdown', (event) => {
                        if (!canTrackPronunciation) return;
                        if (event.target.closest('.story-word, .story-sentence')) event.preventDefault();
                    });

                    storyReaderText?.addEventListener('click', (event) => {
                        if (!canTrackPronunciation) return;
                        const word = event.target.closest('.story-word');
                        const sentence = event.target.closest('.story-sentence');

                        if (oralMarkMode.startsWith('sentence-')) {
                            if (!sentence) return;
                            event.preventDefault();
                            const nextMark = sentence.dataset.sentenceMark === '2' ? 0 : 2;
                            setSentenceMark(sentence, nextMark);
                            updateOralMarkCount();
                            return;
                        }

                        if (!word) {
                            if (sentence) {
                                event.preventDefault();
                                const nextMark = sentence.dataset.sentenceMark === '2' ? 0 : 2;
                                setSentenceMark(sentence, nextMark);
                                updateOralMarkCount();
                            }

                            return;
                        }

                        event.preventDefault();
                        const nextMark = word.dataset.mark === '2' ? 0 : 2;
                        setWordMark(word, nextMark);
                        if (sentence) syncSentenceMark(sentence);
                        updateOralMarkCount();
                    });
                    function renderFlashcardQuestion(index = currentQuestionIndex) {
                        if (!isFlashcards || isOralReading || !frogQuestionText || !questions[index]) return;

                        const question = questions[index];
                        frogQuestionText.innerText = question.text;
                        frogLevelText.innerText = `Question ${index + 1}/${targetsNeeded}`;
                        delete canvas.dataset.frogOutcome;
                        delete canvas.dataset.frogJumpStart;
                        delete canvas.dataset.frogJumpDuration;
                        delete frogQuestionCard.dataset.result;
                        document.getElementById('frog-feedback').textContent = '';
                        frogAnswerButtons.forEach((button) => {
                            const letter = button.dataset.frogAnswer;
                            const option = (question.options || []).find((item) => item.l === letter);
                            const label = button.querySelector('[data-frog-answer-label]');
                            button.disabled = !option;
                            button.hidden = !option;
                            delete button.dataset.result;
                            button.setAttribute('aria-label', `${letter}: ${option?.t || ''}`);
                            if (label) label.innerText = option?.t || '';
                            button.querySelector('.frog-answer-verdict').textContent = '';
                        });
                        canvas.dispatchEvent(new CustomEvent('frog:question'));
                    }

                    function updateFrogProgress(correct) {
                        const progress = targetsNeeded > 0 ? (totalScore / targetsNeeded) * 100 : 0;
                        canvas.dataset.frogCorrect = String(totalScore);
                        canvas.dataset.frogAnswered = String(caughtCount);
                        canvas.dataset.frogTotal = String(targetsNeeded);
                        if (frogScore) frogScore.innerText = String(totalScore);
                        if (frogProgressBar) frogProgressBar.style.width = `${Math.min(progress, 100)}%`;
                        if (frogQuestionCard && typeof correct === 'boolean') frogQuestionCard.dataset.result = correct ? 'correct' : 'incorrect';
                        document.getElementById('frog-progress-track')?.setAttribute('aria-valuenow', totalScore);
                        const count = document.getElementById('frog-progress-count');
                        if (count) count.textContent = `${totalScore} / ${targetsNeeded}`;
                    }

                    function endFrogRound() {
                        if (frogRoundEnding) return;
                        frogRoundEnding = true;
                        isProcessingCapture = true;
                        frogAnswerButtons.forEach(button => button.disabled = true);
                        canvas.classList.remove('assessment-reading');
                        storyGate?.classList.add('hidden');
                        updateFrogProgress();
                        const status = document.getElementById('frog-race-status');
                        if (targetsNeeded === 0 || totalScore !== targetsNeeded || caughtCount !== targetsNeeded) {
                            status.textContent = `Round complete: ${totalScore} / ${targetsNeeded} correct.`;
                            setTimeout(victory, 700);
                            return;
                        }

                        const startedAt = performance.now();
                        const duration = reducedMotion() ? 650 : 2200;
                        canvas.dataset.frogFinishing = 'true';
                        canvas.dataset.frogFinishStart = String(startedAt);
                        canvas.dataset.frogFinishDuration = String(duration);
                        frogLevelText.textContent = 'Perfect run';
                        status.textContent = 'Heading for the finish line!';
                        canvas.dispatchEvent(new CustomEvent('frog:finish-line', { detail: {
                            startedAt, duration, correct: totalScore, answered: caughtCount, total: targetsNeeded,
                        } }));
                        document.getElementById('frog-finish-line').scrollIntoView({ block: 'center', behavior: 'auto' });
                        setTimeout(victory, duration + 700);
                    }

                    function animateFrogJump(button, correct, duration) {
                        canvas.dataset.frogOutcome = correct ? 'correct' : 'incorrect';
                        const startedAt = performance.now();
                        canvas.dataset.frogJumpStart = String(startedAt);
                        canvas.dataset.frogJumpDuration = String(duration);
                        canvas.dispatchEvent(new CustomEvent('frog:jump', { detail: { letter: button.dataset.frogAnswer, correct, duration, startedAt } }));
                    }

                    function answerFlashcard(letter, button) {
                        if (!isFlashcards || isOralReading || !missionStarted || isProcessingCapture || !questions[currentQuestionIndex]) return;
                        const question = questions[currentQuestionIndex];
                        if (!question.options.some((option) => option.l === letter)) return;
                        playGameMusic();
                        isProcessingCapture = true;
                        frogAnswerButtons.forEach((item) => item.disabled = true);
                        const correct = question.correct === letter;
                        capturedAnswers[currentQuestionIndex] = letter;
                        persistProgress(true);
                        caughtCount++;
                        if (correct) {
                            totalScore++;
                            playFrogCorrectAnswerSound();
                        } else {
                            playFrogWrongAnswerSound();
                        }
                        button.dataset.result = correct ? 'correct' : 'incorrect';
                        button.querySelector('.frog-answer-verdict').textContent = correct ? 'check_circle' : 'cancel';
                        document.getElementById('frog-feedback').textContent = correct
                            ? 'Correct! A safe landing on the lily pad.'
                            : 'The frog and lily pad sank together. On to the next question.';
                        const jumpDuration = reducedMotion() ? 650 : (correct ? 1600 : 2600);
                        animateFrogJump(button, correct, jumpDuration);
                        updateFrogProgress(correct);

                        setTimeout(() => {
                            if (caughtCount >= targetsNeeded) {
                                endFrogRound();
                                return;
                            }

                            currentQuestionIndex++;
                            isProcessingCapture = false;
                            renderFlashcardQuestion();
                        }, jumpDuration + 80);
                    }

                    function fireHook() {
                        isHooking = true;
                        if (catchStatus) catchStatus.textContent = '';
                        playHookReelSound();
                        let depth = 0;
                        let lastFrame = performance.now();
                        let previousTipY = hookHead.getBoundingClientRect().top + 21;

                        function lowerHook(now) {
                            const elapsed = Math.min((now - lastFrame) / 1000, .04);
                            lastFrame = now;
                            depth = Math.min(depth + elapsed * 440, container.clientHeight + 20);
                            hookCable.style.height = `${depth}px`;
                            const hookRect = hookHead.getBoundingClientRect();
                            const tipX = hookRect.left + 38;
                            const tipY = hookRect.top + 21;

                            // Check the swept hook tip so a slow frame cannot skip a fish.
                            for (const { element } of fishSchool) {
                                if (element.dataset.caught) continue;
                                const fishRect = element.getBoundingClientRect();
                                if (tipX >= fishRect.left + 22 && tipX <= fishRect.right - 14 && tipY >= fishRect.top + 24 && previousTipY <= fishRect.bottom - 20) {
                                    catchFish(element);
                                    return;
                                }
                            }

                            previousTipY = tipY;
                            if (depth >= container.clientHeight + 20) {
                                reelInFish();
                                return;
                            }
                            hookFrame = requestAnimationFrame(lowerHook);
                        }

                        hookFrame = requestAnimationFrame(lowerHook);
                    }

                    function catchFish(element) {
                        fadeHookReelSound();
                        const correct = questions[currentQuestionIndex].correct === element.dataset.letter;
                        canvas.dataset.catchResult = correct ? 'correct' : 'incorrect';
                        questionNode.dataset.catchResult = canvas.dataset.catchResult;
                        element.dataset.result = canvas.dataset.catchResult;
                        if (catchStatus) catchStatus.textContent = correct
                            ? `Correct! Caught ${element.dataset.letter}. Reeling in...`
                            : 'Not quite! The fish is pulling the boat under!';
                        isProcessingCapture = true;
                        capturedAnswers[currentQuestionIndex] = element.dataset.letter;
                        persistProgress(true);
                        const previousRect = element.getBoundingClientRect();
                        const visual = element.querySelector('.fish-visual');
                        const swimmingTransform = getComputedStyle(visual).transform;
                        element.dataset.caught = 'true';
                        fishSchool.forEach((fish) => { fish.element.disabled = true; });
                        element.classList.add('is-caught');

                        // Parenting the catch to the hook keeps both on the same fishing line.
                        hookHead.appendChild(element);
                        element.style.left = '38px';
                        element.style.top = '35px';
                        element.style.transform = 'translateX(-50%)';
                        const attachedRect = element.getBoundingClientRect();
                        element.animate([
                            { transform: `translate(calc(-50% + ${previousRect.left - attachedRect.left}px), ${previousRect.top - attachedRect.top}px)` },
                            { transform: 'translate(-50%, 0)' },
                        ], { duration: 180, easing: 'ease-out' });
                        visual.animate([{ transform: swimmingTransform }, { transform: correct ? 'rotate(-90deg)' : 'rotate(90deg)' }], { duration: 180, easing: 'ease-out' });
                        if (correct) reelInFish(element);
                        else sinkBoat(element);
                    }

                    async function sinkBoat(element) {
                        const depth = parseFloat(hookCable.style.height) || 0;
                        const maxDepth = Math.max(depth, canvas.getBoundingClientRect().bottom - hookAssembly.getBoundingClientRect().top - 30);
                        const diveDepth = Math.min(depth + 120, maxDepth);
                        const duration = reducedMotion() ? 700 : 2600;
                        const startedAt = performance.now();
                        canvas.style.setProperty('--boat-pull-direction', currentHookX > canvas.clientWidth * .7 ? '-1' : '1');

                        // One timeline drives the line, 3D boat and fallback before the next answer unlocks.
                        await new Promise((resolve) => {
                            function pullUnder(now) {
                                const progress = Math.min((now - startedAt) / duration, 1);
                                const dive = Math.min(progress / .62, 1);
                                const recovery = Math.max(0, (progress - .8) / .2);
                                const pull = (dive * dive * (3 - 2 * dive)) * (1 - recovery * recovery * (3 - 2 * recovery));
                                canvas.style.setProperty('--boat-pull', pull.toFixed(4));
                                canvas.dataset.catchPhase = progress < .62 ? 'pulling' : (progress < .8 ? 'submerged' : 'recovering');
                                hookCable.style.height = `${(depth + (diveDepth - depth) * dive) * (1 - recovery)}px`;
                                if (progress >= .8) element.remove();
                                if (progress < 1) hookFrame = requestAnimationFrame(pullUnder);
                                else {
                                    hookFrame = null;
                                    resolve();
                                }
                            }
                            hookFrame = requestAnimationFrame(pullUnder);
                        });

                        element.remove();
                        canvas.style.removeProperty('--boat-pull');
                        canvas.style.removeProperty('--boat-pull-direction');
                        delete canvas.dataset.catchPhase;
                        hookCable.style.height = '0px';
                        updateProgress();
                        if (caughtCount < targetsNeeded) nextQuestion();
                        isHooking = false;
                    }

                    async function reelInFish(element = null) {
                        fadeHookReelSound();
                        const depth = parseFloat(hookCable.style.height) || 0;
                        const duration = element ? 900 : 500;
                        const startedAt = performance.now();
                        await new Promise((resolve) => {
                            function lift(now) {
                                const progress = Math.min((now - startedAt) / duration, 1);
                                const eased = (1 - Math.cos(Math.PI * progress)) / 2;
                                hookCable.style.height = `${depth * (1 - eased)}px`;
                                if (progress < 1) {
                                    hookFrame = requestAnimationFrame(lift);
                                } else {
                                    hookFrame = null;
                                    resolve();
                                }
                            }
                            hookFrame = requestAnimationFrame(lift);
                        });

                        if (element) {
                            await element.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 220, delay: 160, fill: 'forwards' }).finished;
                            element.remove();
                            updateProgress();
                            if (caughtCount < targetsNeeded) nextQuestion();
                        }
                        isHooking = false;
                        if (!element && catchStatus) catchStatus.textContent = 'No catch this time. Cast again!';
                    }

                    function nextQuestion() {

                        currentQuestionIndex++;
                        questionNode.style.opacity = '0';
                        setTimeout(() => {
                            renderHookQuestion();
                            questionNode.style.opacity = '1';
                            isProcessingCapture = false;
                            spawnFishSchool();
                        }, 300);
                    }


                    function renderHookQuestion() {
                        const question = questions[currentQuestionIndex];
                        if (!question || !questionNode) return;
                        delete canvas.dataset.catchResult;
                        delete questionNode.dataset.catchResult;
                        if (catchStatus) catchStatus.textContent = '';
                        questionNode.innerHTML = `<div class="mb-3 flex items-center gap-2"><span class="material-symbols-outlined text-sm text-primary">terminal</span><h3 class="font-label text-[10px] font-black uppercase tracking-[0.2em] text-primary-dim">Question Node ${escapeHtml(question.node)}</h3></div><h2 class="font-headline mb-4 text-lg font-extrabold leading-tight text-on-surface">${escapeHtml(question.text)}</h2><div class="hook-options space-y-2">${question.options.map((option) => `<div class="group flex cursor-default items-center gap-3 rounded-xl border border-white bg-white/50 p-2.5 transition-colors hover:bg-white"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-container/20 font-bold text-primary">${escapeHtml(option.l)}</span><span class="font-medium text-on-surface-variant">${escapeHtml(option.t)}</span></div>`).join('')}</div>`;
                    }

                    function updateProgress() {
                        caughtCount++;
                        scoreEl.innerText = `${caughtCount}/${targetsNeeded}`;
                        progressEl.style.width = `${Math.min((caughtCount / targetsNeeded) * 100, 100)}%`;
                        const visibleCracks = Math.min(5, Math.ceil((caughtCount / targetsNeeded) * 5));
                        for (let index = 1; index <= visibleCracks; index++) document.getElementById(`crack-${index}`)?.classList.add('crack-visible');
                        const baseScale = 1 + (caughtCount / targetsNeeded) * 0.4;
                        bag.style.setProperty('--base-scale', baseScale);
                        bag.animate([{ transform: `scale(${baseScale})` }, { transform: `scale(${baseScale * 1.15})` }, { transform: `scale(${baseScale})` }], { duration: 500, easing: 'ease-out' });
                        if (caughtCount >= targetsNeeded) hatchEgg();
                    }

                    function hatchEgg() {
                        isHatching = true;
                        cancelAnimationFrame(fishFrame);
                        fishFrame = null;
                        cancelAnimationFrame(hookFrame);
                        container.replaceChildren();
                        fishSchool = [];
                        hookAssembly.style.opacity = '0';
                        if (canvas.classList.contains('ocean-game')) {
                            if (catchStatus) catchStatus.textContent = 'All answers caught!';
                            setTimeout(victory, 450);
                            return;
                        }
                        missionInfo.style.opacity = '0';
                        questionNode.style.opacity = '0.3';
                        bag.style.animation = 'none';
                        bag.animate([{ transform: 'scale(1.4) rotate(0)' }, { transform: 'scale(1.8) rotate(3deg)' }, { transform: 'scale(1.8) rotate(-3deg)' }, { transform: 'scale(2.2) rotate(0)' }], { duration: 800, easing: 'ease-in-out' });
                        setTimeout(() => {
                            createShellExplosion();
                            eggWrapper.classList.add('hatch-active');
                            const shockwave = document.createElement('div');
                            Object.assign(shockwave.style, { position: 'absolute', left: '50%', top: '50%', transform: 'translate(-50%, -50%) scale(0)', width: '100px', height: '100px', borderRadius: '50%', border: '10px solid #44a5ff', boxShadow: '0 0 60px #44a5ff, inset 0 0 40px #ffeb3b', opacity: '1', zIndex: '5' });
                            eggWrapper.appendChild(shockwave);
                            shockwave.animate([{ transform: 'translate(-50%, -50%) scale(0.2)', opacity: 1, borderWidth: '20px' }, { transform: 'translate(-50%, -50%) scale(30)', opacity: 0, borderWidth: '1px' }], { duration: 1800, easing: 'cubic-bezier(0.1, 0.8, 0.2, 1)' });
                            hatchFlash.animate([{ opacity: 0, transform: 'scale(0.5)' }, { opacity: 1, transform: 'scale(3)' }, { opacity: 0, transform: 'scale(4)' }], { duration: 1200, easing: 'ease-out' });
                            createCelebrationParticles();
                            setTimeout(victory, 1500);
                        }, 800);
                    }

                    function createShellExplosion() {
                        const shellRect = shellMain.getBoundingClientRect();
                        const centerX = shellRect.left + shellRect.width / 2;
                        const centerY = shellRect.top + shellRect.height / 2;
                        for (let index = 0; index < 15; index++) {
                            const shard = document.createElement('div');
                            shard.className = 'shell-piece';
                            shard.style.borderRadius = `${Math.random() * 50}%`;
                            shard.style.width = `${30 + Math.random() * 60}px`;
                            shard.style.height = `${30 + Math.random() * 60}px`;
                            shard.style.left = `${centerX - 40}px`;
                            shard.style.top = `${centerY - 40}px`;
                            const angle = (index / 15) * Math.PI * 2 + (Math.random() * 0.5);
                            const distance = 500 + Math.random() * 400;
                            shard.style.setProperty('--tx', `${Math.cos(angle) * distance}px`);
                            shard.style.setProperty('--ty', `${Math.sin(angle) * distance}px`);
                            shard.style.setProperty('--tr', `${Math.random() * 1080}deg`);
                            document.body.appendChild(shard);
                            shard.classList.add('shell-exploded');
                            setTimeout(() => shard.remove(), 1500);
                        }
                    }

                    function createCelebrationParticles() {
                        const shellRect = shellMain.getBoundingClientRect();
                        const centerX = shellRect.left + shellRect.width / 2;
                        const centerY = shellRect.top + shellRect.height / 2;
                        const colors = ['#005e9f', '#44a5ff', '#2498f5', '#91f78e', '#ffeb3b', '#ffffff'];
                        for (let index = 0; index < 400; index++) {
                            const particle = document.createElement('div');
                            particle.className = 'particle';
                            const size = Math.random() * 8 + 3;
                            particle.style.width = `${size}px`;
                            particle.style.height = `${size}px`;
                            particle.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                            particle.style.left = `${centerX}px`;
                            particle.style.top = `${centerY}px`;
                            particle.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
                            document.body.appendChild(particle);
                            const angle = Math.random() * Math.PI * 2;
                            const velocity = Math.random() * 50 + 20;
                            let vx = Math.cos(angle) * velocity;
                            let vy = Math.sin(angle) * velocity;
                            let opacity = 1;
                            let posX = centerX;
                            let posY = centerY;
                            function updateParticle() {
                                posX += vx; posY += vy; vx *= 0.94; vy *= 0.94; vy += 0.15; opacity -= 0.01;
                                particle.style.left = `${posX}px`; particle.style.top = `${posY}px`; particle.style.opacity = opacity;
                                if (opacity > 0) requestAnimationFrame(updateParticle); else particle.remove();
                            }
                            requestAnimationFrame(updateParticle);
                        }
                    }

                    function uniqueMarkedWords(mark) {
                        const seen = new Set();
                        return Array.from(document.querySelectorAll(`.story-word[data-mark="${mark}"]`))
                            .map((word) => word.textContent.trim())
                            .filter(Boolean)
                            .filter((word) => {
                                const key = word.toLowerCase();
                                if (seen.has(key)) return false;
                                seen.add(key);
                                return true;
                            });
                    }

                    function renderWordList(container, words, className, emptyText) {
                        if (!container) return;
                        if (words.length === 0) {
                            container.innerHTML = `<span class="text-sm text-on-surface-variant">${emptyText}</span>`;
                            return;
                        }

                        container.innerHTML = words
                            .map((word) => `<span class="rounded-full px-3 py-1.5 text-sm font-bold ${className}">${escapeHtml(word)}</span>`)
                            .join('');
                    }

                    function updatePronunciationResults() {
                        renderWordList(
                            resultWrongPronunciation,
                            uniqueMarkedWords('2'),
                            'bg-error-container/40 text-error',
                            'No red words marked.'
                        );
                    }

                    function setResultBadge(accuracy) {
                        if (accuracy >= 90) {
                            resultBadgeTitle.innerText = 'Mastery Badge Earned!';
                            resultBadge.innerText = 'You unlocked Mastery Level status.';
                            return;
                        }

                        if (accuracy >= 75) {
                            resultBadgeTitle.innerText = 'Independent Badge Earned!';
                            resultBadge.innerText = 'You unlocked Independent Level status.';
                            return;
                        }

                        resultBadgeTitle.innerText = 'Keep Growing!';
                        resultBadge.innerText = 'Review the story and try again to unlock a higher badge.';
                    }

                    async function submitAttempt() {
                        if (submissionInFlight || submissionSaved) return;
                        submissionInFlight = true;
                        resultAction.disabled = true;
                        resultPoints.innerText = 'Saving...';
                        resultCoins.textContent = 'Saving coin reward...';
                        resultSummary.innerText = 'Checking your real score...';

                        try {
                            const response = await fetch(submitUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                },
                                body: JSON.stringify({ answers: capturedAnswers, attempt_key: attemptKey, state: progressSnapshot(), revision: Math.max(1, progressRevision) }),
                                keepalive: true,
                            });

                            if (!response.ok) {
                                throw new Error('Score sync failed');
                            }

                            const result = await response.json();
                            document.dispatchEvent(new CustomEvent('assessment:graded', { detail: result }));
                            submissionSaved = true;
                            clearTimeout(saveTimeout);
                            try { localStorage.removeItem(storageKey); } catch (_) {}
                            saveStatus.textContent = 'Assessment saved';
                            resultAction.textContent = 'Take Again';
                            const accuracy = Number(result.accuracy || 0);
                            scoreEl.innerText = result.points.toLocaleString();
                            resultAccuracy.innerText = accuracy.toLocaleString();
                            resultProgress.style.width = `${Math.min(accuracy, 100)}%`;
                            resultPoints.innerText = result.points.toLocaleString();
                            resultCoins.textContent = result.coins_pending
                                ? 'Coins awaiting teacher score'
                                : `${Number(result.coins_earned || 0).toLocaleString()} coins earned`;
                            resultCorrect.innerText = `${result.correct_count}/${result.question_count}`;
                            resultSummary.innerText = `${result.correct_count} of ${result.question_count} correct. You earned ${result.points.toLocaleString()} of ${result.possible_points.toLocaleString()} EXP.`;
                            if (result.phil_iri) {
                                resultBadgeTitle.innerText = 'Literacy Assessment';
                                resultBadge.innerText = result.phil_iri.status === 'complete'
                                    ? 'Your score was calculated from the assessment formula.'
                                    : 'Keep reading and building your skills.';
                                if (result.question_count === 0) {
                                    resultAccuracy.innerText = 'N/A';
                                    resultAccuracyUnit.innerText = '';
                                    resultCorrect.innerText = 'Not assessed';
                                    resultSummary.innerText = 'Reading activity saved. The score will appear after the reading marks are recorded.';
                                }
                                if (isOralReading) {
                                    const wordAccuracy = result.phil_iri.word_reading_percent;
                                    resultAccuracy.innerText = wordAccuracy == null ? 'N/A' : wordAccuracy.toLocaleString();
                                    resultAccuracyUnit.innerText = wordAccuracy == null ? '' : '%';
                                    resultProgress.style.width = `${wordAccuracy ?? 0}%`;
                                    resultCorrect.innerText = result.phil_iri.marked_miscues == null ? 'Not recorded' : result.phil_iri.marked_miscues.toLocaleString();
                                    if (wordAccuracy != null) resultSummary.innerText = 'Word-reading score calculated from the red-marked words.';
                                }
                            } else {
                                setResultBadge(accuracy);
                            }
                            if (isFlashcards && !isOralReading && result.question_count > 0 && result.correct_count === result.question_count) {
                                resultBadgeTitle.innerText = 'Finish Line Crossed!';
                                resultBadge.innerText = 'A perfect run. Every answer was correct!';
                            }
                        } catch (error) {
                            resultPoints.innerText = 'Sync failed';
                            resultCoins.textContent = 'Coin reward not confirmed. Retry saving.';
                            resultAccuracy.innerText = '0';
                            resultProgress.style.width = '0%';
                            resultSummary.innerText = 'Your answers were captured, but the score could not be saved. Please try again.';
                            resultBadgeTitle.innerText = 'Score Sync Needed';
                            resultBadge.innerText = 'Try again when the connection is stable.';
                            resultAction.textContent = 'Retry Saving';
                        } finally {
                            submissionInFlight = false;
                            resultAction.disabled = false;
                        }
                    }

                    function victory() {
                        canvas.dataset.assessmentFinished = 'true';
                        missionFinished = true;
                        missionStarted = false;
                        isProcessingCapture = true;
                        persistProgress();
                        storyGate?.classList.add('hidden');
                        frogAnswerButtons.forEach(button => button.disabled = true);
                        canvas.dispatchEvent(new CustomEvent('frog:finished'));
                        canvas.dispatchEvent(new CustomEvent('fishing:finished'));
                        treasureGame?.dispatchEvent(new CustomEvent('treasure:finished'));
                        fadeOutGameMusic();
                        fadeHookReelSound();
                        updatePronunciationResults();
                        updateReadingTimeResult();
                        modal.classList.remove('hidden');
                        canvas.hidden = true;
                        modal.focus({ preventScroll: true });
                        document.querySelector('.assessment-heading').scrollIntoView({ block: 'start' });
                        setTimeout(() => modal.classList.add('opacity-100'), 50);
                        submitAttempt();
                    }

                    function spawnFishSchool() {
                        container.replaceChildren();
                        const colors = ['#71aeb7', '#ecc347', '#d7eee2', '#365e78'];
                        fishSchool = questions[currentQuestionIndex].options.map((option, index) => {
                            const element = fishTemplate.content.firstElementChild.cloneNode(true);
                            element.dataset.letter = option.l;
                            element.dataset.fishSpecies = canvas.dataset.fishSpecies;
                            element.setAttribute('aria-label', `${option.l}: ${option.t}`);
                            element.querySelector('.fish-letter').textContent = option.l;
                            ['color', 'dark', 'light', 'outline'].forEach((name, colorIndex) => {
                                element.style.setProperty(`--fish-${name}`, colors[colorIndex]);
                            });
                            container.appendChild(element);
                            return { element, x: Math.max(0, container.clientWidth - element.offsetWidth - 16) * ((index * .29 + .1) % 1) + 8, direction: index % 2 ? -1 : 1, speed: 32 + index * 4 };
                        });
                        positionFishSchool(0, performance.now());
                    }

                    function positionFishSchool(elapsed, now) {
                        const fishWidth = fishSchool[0]?.element.offsetWidth || 150;
                        const fishHeight = fishSchool[0]?.element.offsetHeight || 88;
                        const maxX = Math.max(8, container.clientWidth - fishWidth - 8);
                        const laneHeight = Math.max(0, container.clientHeight - fishHeight - 24) / Math.max(1, fishSchool.length - 1);
                        fishSchool.forEach((fish, index) => {
                            if (fish.element.dataset.caught) return;
                            if (!reducedMotion()) fish.x += fish.direction * fish.speed * elapsed;
                            if (fish.x >= maxX) { fish.x = maxX; fish.direction = -1; }
                            if (fish.x <= 8) { fish.x = 8; fish.direction = 1; }
                            const bob = reducedMotion() ? 0 : Math.sin(now / 650 + index * 2) * 3;
                            const y = 12 + index * laneHeight + bob;
                            fish.element.style.setProperty('--fish-direction', fish.direction);
                            fish.element.style.transform = `translate(${fish.x}px, ${y}px)`;
                        });
                    }

                    function swimFish(now) {
                        if (!missionStarted || isHatching) { fishFrame = null; return; }
                        const elapsed = lastFishFrame === null ? 0 : Math.min((now - lastFishFrame) / 1000, .04);
                        lastFishFrame = now;
                        positionFishSchool(elapsed, now);
                        fishFrame = requestAnimationFrame(swimFish);
                    }

                    function startMission() {
                        if (isOralReading || missionFinished || !questions[currentQuestionIndex]) return;
                        if (missionStarted && (fishFrame || frogGame?.dataset.started === 'true' || treasureGame?.dataset.started === 'true')) return;
                        missionStarted = true;
                        persistProgress();
                        playGameMusic();
                        canvas.classList.remove('assessment-reading');
                        storyGate?.classList.add('hidden');
                        if (isFlashcards) {
                            if (frogGame) frogGame.dataset.started = 'true';
                            renderFlashcardQuestion();
                            return;
                        }
                        if (isTreasureQuest) {
                            treasureGame.dataset.started = 'true';
                            renderTreasureQuestion();
                            return;
                        }
                        renderHookQuestion();
                        spawnFishSchool();
                        const arena = container.getBoundingClientRect();
                        positionHook(arena.left + arena.width / 2);
                        fishFrame = requestAnimationFrame(swimFish);
                    }

                    startTimerButton?.addEventListener('click', startReadingTimer);
                    endTimerButton?.addEventListener('click', endReadingTimer);

                    frogAnswerButtons.forEach((button) => {
                        button.addEventListener('click', () => answerFlashcard(button.dataset.frogAnswer, button));
                    });
                    treasureButtons.forEach(button => button.addEventListener('click', () => answerTreasure(button.dataset.treasureAnswer, button)));
                    treasureNext?.addEventListener('click', () => {
                        if (treasureNext.disabled || !isProcessingCapture || treasureRoundEnding) return;
                        if (caughtCount >= targetsNeeded) { endTreasureRound(); return; }
                        currentQuestionIndex = questions.findIndex((_, index) => !capturedAnswers[index]);
                        isProcessingCapture = false;
                        renderTreasureQuestion(true);
                    });
                    startQuestionsButton?.addEventListener('click', () => {
                        if (isOralReading) {
                            victory();
                            return;
                        }

                        startMission();
                    });
                    storyReaderText?.addEventListener('click', () => persistProgress());
                    markModeButtons.forEach(button => button.addEventListener('click', () => persistProgress()));
                    progressScrollers.forEach(scroller => scroller.addEventListener('scroll', () => persistProgress(), { passive: true }));
                    function handleReadingPageHide(event) {
                        if (event.persisted) {
                            if (timerStatus === 'running') {
                                updateReadingTimer();
                                persistProgress(true);
                            }
                            return;
                        }

                        pauseAndSave();
                    }

                    window.addEventListener('pagehide', handleReadingPageHide);
                    window.addEventListener('pageshow', () => {
                        if (timerStatus === 'running') {
                            updateReadingTimer();
                            resumeReadingTicker();
                            readingTimerStatus.innerText = 'Timer is running. Click End Timer when the reader is done.';
                        }
                    });
                    document.addEventListener('visibilitychange', () => {
                        if (document.hidden) {
                            if (timerStatus === 'running') {
                                updateReadingTimer();
                                persistProgress(true);
                            }
                            return;
                        }

                        if (timerStatus === 'running') {
                            updateReadingTimer();
                            resumeReadingTicker();
                            readingTimerStatus.innerText = 'Timer is running. Click End Timer when the reader is done.';
                        }
                    });
                    window.addEventListener('online', () => {
                        if (missionFinished) submitAttempt(); else persistProgress();
                    });
                    resultAction.addEventListener('click', () => {
                        if (submissionSaved) window.location.reload(); else submitAttempt();
                    });
                    setInterval(() => {
                        if (!document.hidden && !submissionSaved) persistProgress();
                    }, 5000);
                    restoreProgress();
                    if (missionFinished) {
                        if (isFlashcards && !isOralReading) {
                            renderFlashcardQuestion(Math.max(0, targetsNeeded - 1));
                            endFrogRound();
                        } else if (isTreasureQuest && !isOralReading) {
                            currentQuestionIndex = Math.max(0, targetsNeeded - 1);
                            renderTreasureQuestion();
                            endTreasureRound();
                        }
                        else victory();
                    }
                    else {
                        if (isFlashcards) renderFlashcardQuestion();
                        if (isTreasureQuest) renderTreasureQuestion();
                        if (missionStarted && !isOralReading) startMission();
                    }
                })();
            </script>
        @elseif ($assetPath)
            <section class="assessment-empty flex w-full items-center justify-center p-4 sm:p-8"><div class="glass-hud w-full max-w-xl rounded-2xl border border-white/60 p-6 text-center shadow-xl sm:p-10"><div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-primary text-on-primary"><span class="material-symbols-outlined text-6xl">file_open</span></div><h1 class="font-headline text-3xl font-black text-on-surface sm:text-4xl">Uploaded Mission</h1><p class="mt-3 font-medium text-slate-500">{{ $assessment->instructions ?: 'Open the uploaded assessment material from your teacher.' }}</p><a class="mt-8 inline-flex rounded-lg bg-primary px-8 py-3 text-base font-bold text-white shadow-lg transition-colors hover:bg-primary-dim" href="{{ $assetUrl }}" target="_blank" rel="noopener">Open File</a></div></section>
        @else
            <section class="assessment-empty flex w-full items-center justify-center p-4 sm:p-8"><div class="glass-hud w-full max-w-xl rounded-2xl border border-white/60 p-6 text-center shadow-xl sm:p-10"><div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-surface-container text-primary"><span class="material-symbols-outlined text-6xl">pending_actions</span></div><h1 class="font-headline text-3xl font-black text-on-surface sm:text-4xl">No Mission Data</h1><p class="mt-3 font-medium text-slate-500">Your teacher has published this assessment, but no manual questions or uploaded file were attached.</p></div></section>
        @endif
        </main>
    </div>
</x-app-layout>
