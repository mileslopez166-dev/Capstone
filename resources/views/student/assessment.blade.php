<x-app-layout>
    @php
        $assetPath = $assessment->asset_path;
        $assetExtension = $assetPath ? strtolower(pathinfo($assetPath, PATHINFO_EXTENSION)) : null;
        $assetUrl = $assetPath ? \Illuminate\Support\Facades\Storage::url($assetPath) : null;
        $student = Auth::user();
        $studentName = $student?->name ?? 'Student';
        $studentInitials = collect(explode(' ', $studentName))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');
        $manualQuestions = collect($assessment->manual_questions ?? [])->values();
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
                                : '<button class="story-word" type="button" data-mark="0">'.e($part).'</button>')
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
        $missionTitle = str($assessment->title)->upper()->limit(28, '');
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
        .story-sentence-mark-1 { background: rgba(250, 204, 21, 0.2); box-shadow: 0 0 0 1px rgba(202, 138, 4, 0.18); }
        .story-sentence-mark-2 { background: rgba(248, 113, 113, 0.2); box-shadow: 0 0 0 1px rgba(220, 38, 38, 0.18); }
        .story-word { appearance: none; display: inline; cursor: pointer; border: 0; border-radius: 0.25rem; background: transparent; margin: 0; padding: 0.03rem 0.1rem; color: inherit; font: inherit; line-height: inherit; text-align: inherit; vertical-align: baseline; transition: background-color 0.15s ease, color 0.15s ease; user-select: none; -webkit-user-select: none; touch-action: manipulation; }
        .story-word:hover { background: rgba(0, 94, 159, 0.08); }
        .story-word-mark-1 { background: rgba(250, 204, 21, 0.32); color: #854d0e; }
        .story-word-mark-2 { background: rgba(248, 113, 113, 0.28); color: #991b1b; }
        .mark-mode-button { transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease; }
        .mark-mode-button:hover { transform: translateY(-1px); }
        .mark-mode-button.is-active { background: #005e9f; color: #ffffff; box-shadow: 0 12px 24px rgba(0, 94, 159, 0.18); }
        .mark-mode-button.mark-mode-yellow.is-active { background: #facc15; color: #713f12; box-shadow: 0 12px 24px rgba(202, 138, 4, 0.18); }
        .mark-mode-button.mark-mode-red.is-active { background: #ef4444; color: #ffffff; box-shadow: 0 12px 24px rgba(220, 38, 38, 0.18); }
        .result-pattern { background-image: radial-gradient(circle at 10px 10px, rgba(68, 165, 255, 0.45) 1px, transparent 1px), radial-gradient(circle at 30px 30px, rgba(145, 247, 142, 0.45) 1px, transparent 1px); background-size: 40px 40px; background-position: 0 0, 20px 20px; opacity: 0.2; }
    </style>

    <div class="student-assessment-page min-h-screen font-body text-on-surface">
        <x-student-nav active="activities" />

        <main class="assessment-workspace lg:ml-72" aria-labelledby="assessment-title">
            <header class="assessment-heading">
                <a class="assessment-back" href="{{ route('student.activities') }}" aria-label="Back to activities" title="Back to activities">
                    <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
                </a>
                <div class="assessment-heading-copy">
                    <p>{{ $assessmentTypeLabel }}</p>
                    <h1 id="assessment-title">{{ $assessment->title }}</h1>
                </div>
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
            <section class="mission-canvas relative app-game-screen w-full overflow-hidden {{ $hasReadingStage ? 'assessment-reading' : '' }} {{ ($isFlashcards && ! $isOralReading) ? 'frog-pond-game' : '' }} {{ (! $isOralReading && ! $isFlashcards) ? 'hook-game ocean-game' : '' }}" id="mission-canvas" aria-label="Assessment activity">
                @if (! $isOralReading && ! $isFlashcards)
                    <div id="fishing-sea-scene" aria-hidden="true"></div>
                    <div class="ocean-catch-status" id="ocean-catch-status" role="status" aria-live="polite"></div>
                @endif
                <div id="hook-hud" class="pointer-events-none absolute left-3 right-3 top-3 z-30 flex max-w-sm flex-col gap-3 sm:left-5 sm:right-auto sm:top-5 sm:w-72 {{ ($isOralReading || $isFlashcards) ? 'hidden' : '' }}">
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

                <div class="pointer-events-none absolute right-3 top-3 z-30 max-w-[calc(100%-1.5rem)] text-right sm:right-5 sm:top-5 {{ ($isOralReading || $isFlashcards) ? 'hidden' : '' }}" id="mission-info">
                    <div class="ocean-eyebrow"><span class="material-symbols-outlined" aria-hidden="true">sailing</span> Ocean Expedition</div>
                    <h1 class="font-headline mb-1 text-xl font-black italic leading-none tracking-tighter text-primary-dim">{{ $assessment->title }}</h1>
                    <p class="font-body font-medium text-slate-500">{{ $questionCount }} questions</p>
                </div>

                <div class="pointer-events-none absolute left-1/2 top-0 z-40 flex -translate-x-1/2 flex-col items-center {{ ($isOralReading || $isFlashcards) ? 'hidden' : '' }}" id="hook-assembly" aria-hidden="true">
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

                <div class="absolute inset-0 z-20 {{ ($isOralReading || $isFlashcards) ? 'hidden' : '' }}" id="fish-container" role="group" aria-label="Answer fish"></div>
                <template id="answer-fish-template">
                    <button type="button" class="answer-fish">
                        <svg class="fish-visual" viewBox="0 0 132 80" aria-hidden="true">
                            <g class="fish-tail"><path d="M38 40 6 15Q1 40 6 65Z" fill="var(--fish-dark)" stroke="var(--fish-outline)" stroke-width="2"/><path d="m9 25 22 15L9 55M7 40h24" fill="none" stroke="var(--fish-light)" stroke-width="2"/></g>
                            <path d="M48 24Q57 1 79 12L88 27M48 56Q63 79 82 64L87 52" fill="var(--fish-dark)" stroke="var(--fish-outline)" stroke-width="2"/>
                            <path d="M27 40C35 8 102 5 120 39 103 75 39 73 27 40Z" fill="var(--fish-color)" stroke="var(--fish-outline)" stroke-width="2"/>
                            <path d="M35 44Q74 73 115 43C96 66 51 68 35 44Z" fill="var(--fish-light)"/>
                            <path d="M42 29Q68 13 91 23" fill="none" stroke="var(--fish-light)" stroke-width="5" stroke-linecap="round"/>
                            <path class="fish-fin" d="M76 45Q70 48 69 59 83 59 89 46" fill="var(--fish-dark)" stroke="var(--fish-outline)" stroke-width="1.5"/>
                            <path d="M93 31q-6 10 0 20" fill="none" stroke="var(--fish-outline)" stroke-width="1.5" opacity=".5"/>
                            <circle cx="103" cy="31" r="7" fill="white"/><circle cx="105" cy="32" r="3.6" fill="#153440"/><circle cx="106" cy="30" r="1.3" fill="white"/>
                            <path d="m115 42 5-3" stroke="var(--fish-outline)" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span class="fish-letter"></span>
                    </button>
                </template>

                <div class="absolute bottom-3 left-1/2 z-40 -translate-x-1/2 {{ ($isOralReading || $isFlashcards) ? 'hidden' : '' }}" id="egg-wrapper">
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
                                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-2 shadow-sm"><span class="h-4 w-4 rounded bg-yellow-300/80 ring-1 ring-yellow-500/30"></span>Getting Closer</span>
                                </div>
                                <div class="assessment-mark-tools mt-3 flex flex-wrap items-center gap-2 p-3 text-sm font-bold text-on-surface-variant" aria-label="Oral reading mark mode">
                                    <span class="font-label text-[10px] font-black uppercase tracking-[0.22em] text-primary-dim">Mark Mode</span>
                                    <button class="mark-mode-button is-active inline-flex items-center gap-2 rounded-full bg-surface-container-low px-3 py-2" type="button" data-mark-mode="word" aria-pressed="true">
                                        <span class="material-symbols-outlined text-lg">touch_app</span>
                                        Word
                                    </button>
                                    <button class="mark-mode-button mark-mode-yellow inline-flex items-center gap-2 rounded-full bg-surface-container-low px-3 py-2" type="button" data-mark-mode="sentence-1" aria-pressed="false">
                                        <span class="material-symbols-outlined text-lg">format_color_fill</span>
                                        Sentence: Getting Closer
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
                    <div class="assessment-result-avatar"><x-student-character :gender="$student?->gender" variant="portrait" /></div>
                    <div>
                        <p class="assessment-eyebrow">Assessment Complete</p>
                        <h2 id="assessment-result-title">Great job, {{ str($studentName)->before(' ') }}!</h2>
                        <p id="result-summary" role="status">Checking your real score...</p>
                    </div>
                </header>

                <div class="assessment-result-metrics">
                    <article class="assessment-result-metric">
                        <p><span class="material-symbols-outlined" aria-hidden="true">target</span> Accuracy Score</p>
                        <div class="assessment-result-value"><strong id="result-accuracy">0</strong><span id="result-accuracy-unit">%</span></div>
                        <div class="assessment-result-track"><div id="result-progress" style="width: 0%"></div></div>
                    </article>
                    <article class="assessment-result-metric assessment-metric-xp">
                        <p><span class="material-symbols-outlined" aria-hidden="true">stars</span> Experience Earned</p>
                        <div class="assessment-result-value"><strong id="result-points">Saving...</strong></div>
                        <p>Assessment points</p>
                    </article>
                    <article class="assessment-result-metric assessment-metric-correct">
                        <p><span class="material-symbols-outlined" aria-hidden="true">task_alt</span> Correct Answers</p>
                        <div class="assessment-result-value"><strong id="result-correct">0/{{ $questionCount }}</strong></div>
                        <p>{{ $assessmentTypeLabel }}</p>
                    </article>
                </div>

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
                                <h4><span class="assessment-color-yellow"></span> Getting Closer</h4>
                                <div class="flex flex-wrap gap-2" id="result-needs-improvement"><span>No yellow words marked.</span></div>
                            </div>
                            <div class="assessment-pronunciation">
                                <h4><span class="assessment-color-red"></span> Mispronounced</h4>
                                <div class="flex flex-wrap gap-2" id="result-wrong-pronunciation"><span>No red words marked.</span></div>
                            </div>
                        @else
                            <div id="result-needs-improvement" hidden></div>
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
                    <a class="assessment-primary-action" href="{{ route('student.activities') }}"><span class="material-symbols-outlined" aria-hidden="true">arrow_back</span> Back to Activities</a>
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
                    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
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
                    const resultCorrect = document.getElementById('result-correct');
                    const resultBadgeTitle = document.getElementById('result-badge-title');
                    const resultBadge = document.getElementById('result-badge');
                    const resultNeedsImprovement = document.getElementById('result-needs-improvement');
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
                    const submitUrl = @json(route('student.assessments.submit', $assessment));
                    const progressUrl = @json(route('student.assessments.progress', $assessment));
                    const attemptKey = @json($progress->attempt_key ?? null);
                    const initialProgress = @json(['revision' => $progress->revision ?? 0, 'state' => $progress->state ?? []]);
                    const storageKey = @json('assessment-progress:'.auth()->id().':'.$assessment->id.':'.($progress->attempt_key ?? 'preview'));
                    const saveStatus = document.getElementById('assessment-save-status');
                    const resultAction = document.getElementById('assessment-result-action');
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    const assessmentType = @json($assessmentType);
                    const isFlashcards = @json($isFlashcards);
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
                    const frogTongue = document.getElementById('frog-tongue');
                    const frogCharacter = document.getElementById('frog-character');
                    const frogOutcomeAnimations = [];
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
                        missionStarted = state.phase ? state.phase !== 'reading' : !storyGate;
                        missionFinished = state.phase === 'finished' || (targetsNeeded > 0 && caughtCount === targetsNeeded);
                        readingElapsedSeconds = Math.max(0, Number(state.reading_seconds || 0));
                        timerStatus = state.timer_status === 'running' ? 'paused' : (state.timer_status || 'idle');
                        storyReaderText?.querySelectorAll('.story-sentence').forEach((sentence, index) => setSentenceMark(sentence, Number(state.sentence_marks?.[index] || 0)));
                        storyReaderText?.querySelectorAll('.story-word').forEach((word, index) => setWordMark(word, Number(state.word_marks?.[index] || 0)));
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
                        else if (!isOralReading && targetsNeeded > 0) {
                            scoreEl.textContent = `${caughtCount}/${targetsNeeded}`;
                            progressEl.style.width = `${caughtCount / targetsNeeded * 100}%`;
                            for (let index = 1; index <= Math.ceil(caughtCount / targetsNeeded * 5); index++) document.getElementById(`crack-${index}`)?.classList.add('crack-visible');
                            bag.style.setProperty('--base-scale', 1 + caughtCount / targetsNeeded * .4);
                        }
                    }

                    function pauseAndSave() {
                        if (readingTimer) {
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
                        if (!hookReelSound || isOralReading || isFlashcards) return;
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
                        if (!readingStartedAt || !readingTimerDisplay) return;
                        readingElapsedSeconds = Math.floor((Date.now() - readingStartedAt) / 1000);
                        readingTimerDisplay.innerText = formatElapsedTime(readingElapsedSeconds);
                    }

                    function startReadingTimer() {
                        if (readingTimer || timerStatus === 'finished') return;
                        readingStartedAt = Date.now() - readingElapsedSeconds * 1000;
                        timerStatus = 'running';
                        updateReadingTimer();
                        readingTimer = setInterval(updateReadingTimer, 1000);
                        startTimerButton.disabled = true;
                        startTimerButton.classList.add('opacity-60');
                        endTimerButton.disabled = false;
                        endTimerButton.classList.remove('opacity-60');
                        readingTimerStatus.innerText = 'Timer is running. Click End Timer when the reader is done.';
                        persistProgress();
                    }

                    function endReadingTimer() {
                        if (!readingTimer && timerStatus !== 'paused') return;
                        if (readingTimer) updateReadingTimer();
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
                        if (!isOralReading && !isFlashcards && missionStarted && !isHooking && !isHatching && !isProcessingCapture && container.contains(event.target)) {
                            positionHook(event.clientX);
                        }
                    });

                    canvas.addEventListener('click', (event) => {
                        if (!missionStarted || isOralReading || isFlashcards || !container.contains(event.target)) return;
                        if (isHooking || isHatching || isProcessingCapture) return;
                        const fish = event.target.closest('.answer-fish');
                        const fishRect = fish?.getBoundingClientRect();
                        positionHook(event.detail === 0 && fishRect ? fishRect.left + fishRect.width / 2 : event.clientX);
                        playGameMusic();
                        fireHook();
                    });

                    if (!isOralReading && !isFlashcards) {
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
                        word.dataset.mark = String(mark);
                        word.classList.remove('story-word-mark-1', 'story-word-mark-2');
                        if (mark > 0) word.classList.add(`story-word-mark-${mark}`);
                    }

                    function setSentenceMark(sentence, mark) {
                        sentence.dataset.sentenceMark = String(mark);
                        sentence.classList.remove('story-sentence-mark-1', 'story-sentence-mark-2');
                        if (mark > 0) sentence.classList.add(`story-sentence-mark-${mark}`);
                        sentence.querySelectorAll('.story-word').forEach((word) => setWordMark(word, mark));
                    }

                    function setOralMarkMode(mode) {
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
                            const mark = Number(oralMarkMode.replace('sentence-', '')) || 2;
                            const nextMark = Number(sentence.dataset.sentenceMark || '0') === mark ? 0 : mark;
                            setSentenceMark(sentence, nextMark);
                            return;
                        }

                        if (!word) {
                            if (sentence) {
                                event.preventDefault();
                                const nextMark = (Number(sentence.dataset.sentenceMark || '0') + 1) % 3;
                                setSentenceMark(sentence, nextMark);
                            }

                            return;
                        }

                        event.preventDefault();
                        const nextMark = (Number(word.dataset.mark || '0') + 1) % 3;
                        setWordMark(word, nextMark);
                    });
                    function renderFlashcardQuestion() {
                        if (!isFlashcards || isOralReading || !frogQuestionText || !questions[currentQuestionIndex]) return;

                        const question = questions[currentQuestionIndex];
                        frogQuestionText.innerText = question.text;
                        frogLevelText.innerText = `Question ${currentQuestionIndex + 1}/${targetsNeeded}`;
                        frogOutcomeAnimations.splice(0).forEach((animation) => animation.cancel());
                        frogTongue?.classList.remove('is-catching');
                        delete canvas.dataset.frogOutcome;
                        delete canvas.dataset.frogCatchStart;
                        delete canvas.dataset.frogCatchDuration;
                        delete frogQuestionCard.dataset.result;
                        document.getElementById('frog-feedback').textContent = '';
                        frogAnswerButtons.forEach((button) => {
                            const letter = button.dataset.frogAnswer;
                            const option = (question.options || []).find((item) => item.l === letter);
                            const label = button.querySelector('[data-frog-answer-label]');
                            button.disabled = !option;
                            button.hidden = !option;
                            button.classList.remove('is-caught', 'is-attacking');
                            delete button.dataset.result;
                            button.setAttribute('aria-label', `${letter}: ${option?.t || ''}`);
                            if (label) label.innerText = option?.t || '';
                            button.querySelector('.frog-answer-verdict').textContent = '';
                        });
                        canvas.dispatchEvent(new CustomEvent('frog:question'));
                    }

                    function updateFrogProgress(correct) {
                        const progress = targetsNeeded > 0 ? (caughtCount / targetsNeeded) * 100 : 0;
                        if (frogScore) frogScore.innerText = String(totalScore);
                        if (frogProgressBar) frogProgressBar.style.width = `${Math.min(progress, 100)}%`;
                        if (frogQuestionCard) frogQuestionCard.dataset.result = correct ? 'correct' : 'incorrect';
                        document.getElementById('frog-progress-track')?.setAttribute('aria-valuenow', caughtCount);
                        const count = document.getElementById('frog-progress-count');
                        if (count) count.textContent = `${caughtCount} / ${targetsNeeded}`;
                    }

                    function animateFrogCatch(button, correct, duration) {
                        if (!frogTongue || !frogCharacter || !button) return;
                        canvas.dataset.frogOutcome = correct ? 'correct' : 'incorrect';
                        const startedAt = performance.now();
                        canvas.dataset.frogCatchStart = String(startedAt);
                        canvas.dataset.frogCatchDuration = String(duration);
                        canvas.dispatchEvent(new CustomEvent('frog:catch', { detail: { letter: button.dataset.frogAnswer, correct, duration, startedAt } }));
                        button.classList.add(correct ? 'is-caught' : 'is-attacking');
                        if (correct && canvas.dataset.pondState === 'ready') return;

                        const canvasBox = canvas.getBoundingClientRect();
                        const frogBox = frogCharacter.getBoundingClientRect();
                        const targetBox = button.querySelector('.frog-target-visual').getBoundingClientRect();
                        if (!correct) {
                            const graphic = button.querySelector('.frog-target-fallback');
                            const body = frogCharacter.querySelector('.frog-character-body');
                            const gulpMouth = button.querySelector('.frog-target-mouth');
                            const scale = reducedMotion ? 1.25 : 1.7;
                            const attackX = frogBox.left + frogBox.width / 2 - (targetBox.left + targetBox.width / 2);
                            const attackY = frogBox.top + frogBox.height * .24 - (targetBox.top + targetBox.height / 2);
                            const destination = `translate(${attackX}px, ${attackY}px) scale(${scale})`;
                            const bodyBox = body.getBoundingClientRect();
                            const svgMatrix = frogCharacter.querySelector('svg').getScreenCTM();
                            const swallowX = reducedMotion ? 0 : (frogBox.left + frogBox.width / 2 - bodyBox.left - bodyBox.width / 2) / svgMatrix.a;
                            const swallowY = reducedMotion ? 0 : (frogBox.top + frogBox.height * .24 + targetBox.height * .143 * scale - bodyBox.top - bodyBox.height / 2) / svgMatrix.d;
                            const swallowed = `translate(${swallowX}px, ${swallowY}px) scale(.001)`;
                            frogOutcomeAnimations.push(
                                graphic.animate([
                                    { transform: reducedMotion ? destination : 'translate(0, 0) scale(1)', offset: 0 },
                                    { transform: destination, offset: .32 },
                                    { transform: destination, offset: .7 },
                                    { transform: 'translate(0, 0) scale(1)', offset: 1 },
                                ], { duration, easing: reducedMotion ? 'steps(1, end)' : 'ease-in-out', fill: 'forwards' }),
                                body.animate([
                                    { transform: 'translate(0, 0) scale(1)', opacity: 1, offset: 0 },
                                    { transform: 'translate(0, 0) scale(1)', opacity: 1, offset: .32 },
                                    { transform: swallowed, opacity: 0, offset: .58 },
                                    { transform: swallowed, opacity: 0, offset: .84 },
                                    { transform: 'translate(0, 0) scale(1)', opacity: 1, offset: 1 },
                                ], { duration, easing: 'linear', fill: 'forwards' }),
                                gulpMouth.animate([
                                    { opacity: 1, offset: 0 }, { opacity: 1, offset: .58 },
                                    { opacity: 0, offset: .64 }, { opacity: 0, offset: 1 },
                                ], { duration, fill: 'forwards' }),
                            );
                            return;
                        }
                        const startX = frogBox.left + frogBox.width / 2 - canvasBox.left;
                        const startY = frogBox.top + frogBox.height * .48 - canvasBox.top;
                        const targetX = targetBox.left + targetBox.width / 2 - canvasBox.left;
                        const targetY = targetBox.top + targetBox.height / 2 - canvasBox.top;
                        const dx = targetX - startX;
                        const dy = targetY - startY;
                        frogTongue.style.setProperty('--tongue-left', `${startX}px`);
                        frogTongue.style.setProperty('--tongue-top', `${startY}px`);
                        frogTongue.style.setProperty('--tongue-width', `${Math.sqrt(dx * dx + dy * dy)}px`);
                        frogTongue.style.setProperty('--tongue-angle', `${Math.atan2(dy, dx) * 180 / Math.PI}deg`);
                        frogTongue.classList.remove('is-catching');
                        void frogTongue.offsetWidth;
                        frogTongue.classList.add('is-catching');
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
                            ? 'Correct! The frog eats the mosquito.'
                            : 'Not quite! The mosquito eats the frog. The frog will return for the next question.';
                        const catchDuration = correct ? 850 : (reducedMotion ? 650 : 2400);
                        animateFrogCatch(button, correct, catchDuration);
                        updateFrogProgress(correct);

                        setTimeout(() => {
                            if (caughtCount >= targetsNeeded) {
                                victory();
                                return;
                            }

                            currentQuestionIndex++;
                            isProcessingCapture = false;
                            renderFlashcardQuestion();
                        }, catchDuration + 80);
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
                        const duration = reducedMotion ? 700 : 2600;
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
                            resultNeedsImprovement,
                            uniqueMarkedWords('1'),
                            'bg-tertiary-container/70 text-on-tertiary-container',
                            'No yellow words marked.'
                        );
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
                            resultCorrect.innerText = `${result.correct_count}/${result.question_count}`;
                            resultSummary.innerText = `${result.correct_count} of ${result.question_count} correct. You earned ${result.points.toLocaleString()} of ${result.possible_points.toLocaleString()} EXP.`;
                            setResultBadge(accuracy);
                        } catch (error) {
                            resultPoints.innerText = 'Sync failed';
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
                        const colors = [
                            ['#54d8c7', '#169f99', '#b6f6d9', '#176273'],
                            ['#ffa38d', '#ed706e', '#ffdbba', '#9e464a'],
                            ['#f5d05e', '#dea035', '#fff0a3', '#8c681f'],
                            ['#82c5f6', '#4b92d0', '#d5f2ff', '#34668e'],
                        ];
                        fishSchool = questions[currentQuestionIndex].options.map((option, index) => {
                            const element = fishTemplate.content.firstElementChild.cloneNode(true);
                            element.dataset.letter = option.l;
                            element.setAttribute('aria-label', `${option.l}: ${option.t}`);
                            element.querySelector('.fish-letter').textContent = option.l;
                            ['color', 'dark', 'light', 'outline'].forEach((name, colorIndex) => {
                                element.style.setProperty(`--fish-${name}`, colors[index % colors.length][colorIndex]);
                            });
                            container.appendChild(element);
                            return { element, x: Math.max(0, container.clientWidth - element.offsetWidth - 16) * ((index * .29 + .1) % 1) + 8, direction: index % 2 ? -1 : 1, speed: reducedMotion ? 0 : 32 + index * 4 };
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
                            fish.x += fish.direction * fish.speed * elapsed;
                            if (fish.x >= maxX) { fish.x = maxX; fish.direction = -1; }
                            if (fish.x <= 8) { fish.x = 8; fish.direction = 1; }
                            const bob = reducedMotion ? 0 : Math.sin(now / 650 + index * 2) * 3;
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
                        if (missionStarted && (fishFrame || frogGame?.dataset.started === 'true')) return;
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
                    window.addEventListener('pagehide', pauseAndSave);
                    document.addEventListener('visibilitychange', () => {
                        if (document.hidden) pauseAndSave();
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
                    if (missionFinished) victory();
                    else {
                        if (isFlashcards) renderFlashcardQuestion();
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
