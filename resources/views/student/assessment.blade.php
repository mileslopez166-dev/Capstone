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
        .joyful-bg { background: linear-gradient(180deg, #38bdf8 0%, #7dd3fc 34%, #bae6fd 66%, #f0f9ff 100%); }
        .sky-cloud { position: absolute; z-index: 1; width: 12rem; height: 3.5rem; border-radius: 9999px; background: linear-gradient(180deg, #fff 0%, rgba(255,255,255,.98) 58%, rgba(224,242,254,.94) 100%); box-shadow: 0 10px 18px rgba(14,116,144,.16), inset 0 -6px 0 rgba(186,230,253,.42); pointer-events: none; }
        .sky-cloud::before, .sky-cloud::after { content: ''; position: absolute; bottom: .35rem; border-radius: 9999px; background: linear-gradient(180deg, #fff, #e0f2fe); }
        .sky-cloud::before { left: 1.5rem; width: 5.5rem; height: 5.5rem; }
        .sky-cloud::after { right: 1.5rem; width: 4.5rem; height: 4.5rem; }
        .sky-cloud.small { width: 9rem; height: 2.5rem; transform: scale(.9); }
        .frog-answer { transition: transform .2s ease, filter .2s ease; }
        .frog-answer:hover { transform: translateY(-4px) scale(1.03); }
        .frog-answer.is-caught { animation: mosquitoCaught .72s ease-in forwards; pointer-events: none; }
        .frog-answer-circle { box-shadow: 0 7px 0 rgba(30, 41, 59, .28); }
        .dragonfly-wing { position: absolute; top: 50%; width: 4.1rem; height: 2.15rem; border: 3px solid rgba(255,255,255,.95); background: linear-gradient(135deg, var(--wing-light), var(--wing-mid) 48%, var(--wing-deep)); box-shadow: inset 0 0 0 1px rgba(255,255,255,.7), 0 0 10px var(--wing-glow), 0 3px 12px var(--wing-glow); pointer-events: none; z-index: 3; animation: wingFlutter .7s ease-in-out infinite alternate; }
        .dragonfly-wing.red-wings { --wing-light: rgba(254,202,202,.95); --wing-mid: rgba(251,113,133,.72); --wing-deep: rgba(225,29,72,.5); --wing-glow: rgba(244,63,94,.65); }
        .dragonfly-wing.gold-wings { --wing-light: rgba(254,249,195,.98); --wing-mid: rgba(251,191,36,.76); --wing-deep: rgba(234,138,0,.5); --wing-glow: rgba(250,204,21,.7); }
        .dragonfly-wing.purple-wings { --wing-light: rgba(233,213,255,.98); --wing-mid: rgba(129,140,248,.72); --wing-deep: rgba(126,34,206,.5); --wing-glow: rgba(168,85,247,.7); }
        .dragonfly-wing.green-wings { --wing-light: rgba(220,252,231,.98); --wing-mid: rgba(74,222,128,.72); --wing-deep: rgba(22,163,74,.5); --wing-glow: rgba(34,197,94,.7); }
        .dragonfly-wing.left { right: 50%; border-radius: 100% 20% 20% 100%; transform: translateY(-50%) rotate(-16deg); }
        .dragonfly-wing.right { left: 50%; border-radius: 20% 100% 100% 20%; transform: translateY(-50%) rotate(16deg); }
        @keyframes wingFlutter { from { transform: translateY(-50%) rotate(-12deg) scaleY(.82); } to { transform: translateY(-50%) rotate(12deg) scaleY(1.12); } }
        @keyframes mosquitoCaught { 0% { transform: scale(1) rotate(0); opacity: 1; } 45% { transform: scale(1.35) rotate(12deg); opacity: 1; } 100% { transform: scale(.05) rotate(-25deg); opacity: 0; } }
        .frog-tongue { position: absolute; height: 12px; left: var(--tongue-left); top: var(--tongue-top); width: var(--tongue-width); z-index: 45; pointer-events: none; border-radius: 999px; background: linear-gradient(90deg, #fb7185, #ef4444 70%, #be123c); border: 3px solid #be123c; transform-origin: left center; transform: rotate(var(--tongue-angle)) scaleX(0); opacity: 0; }
        .frog-tongue.is-catching { animation: tongueShoot .72s cubic-bezier(.2,.8,.25,1) forwards; }
        @keyframes tongueShoot { 0% { transform: rotate(var(--tongue-angle)) scaleX(0); opacity: 0; } 18%, 58% { opacity: 1; } 58% { transform: rotate(var(--tongue-angle)) scaleX(1); } 100% { transform: rotate(var(--tongue-angle)) scaleX(0); opacity: 0; } }
    </style>

    <div class="app-safe-screen overflow-hidden bg-surface text-on-surface font-body selection:bg-primary-container selection:text-on-primary-container">
        <nav class="sticky top-0 z-50 mx-auto flex w-full max-w-full items-center justify-between bg-white/80 px-5 py-3 shadow-[0_20px_40px_rgba(0,94,159,0.06)] backdrop-blur-xl">
            <div class="flex items-center gap-4"><span class="font-headline text-xl font-extrabold italic text-blue-600">AI-PGAALS</span></div>
            <div class="hidden items-center gap-8 md:flex">
                <a class="font-medium text-slate-500 transition-colors hover:text-blue-500" href="{{ route('student.dashboard') }}">Home</a>
                <a class="border-b-4 border-blue-500 font-bold text-blue-700 transition-colors hover:text-blue-500" href="{{ route('student.activities') }}">Missions</a>
                <a class="font-medium text-slate-500 transition-colors hover:text-blue-500" href="{{ route('profile.edit') }}">Backpack</a>
            </div>
            <div class="flex items-center gap-4">
                <a class="rounded-full p-2 transition-colors hover:bg-surface-container-low" href="{{ route('student.activities') }}"><span class="material-symbols-outlined text-on-surface-variant">arrow_back</span></a>
                <a class="rounded-full p-2 transition-colors hover:bg-surface-container-low" href="{{ route('profile.edit') }}"><span class="material-symbols-outlined text-on-surface-variant">account_circle</span></a>
            </div>
        </nav>

        @if ($questionCount > 0 || $isOralReading)
            <audio id="assessment-game-music" src="{{ asset('audio/assessment-game-music.mp3') }}" preload="auto" loop></audio>
            <audio id="multiple-choice-hook-sound" src="{{ asset('audio/multiple-choice-hook-reel.mp3') }}" preload="auto"></audio>
            <audio id="frog-wrong-answer-sound" src="{{ asset('audio/frog-wrong-answer.mp3') }}" preload="auto"></audio>
            <audio id="frog-correct-answer-sound" src="{{ asset('audio/frog-correct-answer.mp3') }}" preload="auto"></audio>
            <main class="mission-canvas relative app-game-screen w-full overflow-hidden {{ $isFlashcards ? 'joyful-bg' : 'bg-gradient-to-b from-surface via-surface-container-low to-surface' }} {{ (! $isOralReading && ! $isFlashcards) ? 'hook-game' : '' }}" id="mission-canvas">
                @if ($isFlashcards)
                    <div class="pointer-events-none absolute inset-0 overflow-hidden">
                        <div class="sky-cloud left-[-3rem] top-20"></div>
                        <div class="sky-cloud small right-[-4rem] top-36"></div>
                        <div class="sky-cloud left-[18%] top-72"></div>
                        <div class="sky-cloud small bottom-44 right-[5%]"></div>
                    </div>
                @else
                    <div class="pointer-events-none absolute inset-0 overflow-hidden">
                        <div class="absolute left-[10%] top-20 h-64 w-64 rounded-full bg-primary-container/10 blur-3xl"></div>
                        <div class="absolute bottom-20 right-[15%] h-96 w-96 rounded-full bg-tertiary-container/10 blur-3xl"></div>
                    </div>
                @endif
                <div id="hook-hud" class="pointer-events-none absolute left-3 right-3 top-3 z-30 flex max-w-sm flex-col gap-3 sm:left-5 sm:right-auto sm:top-5 sm:w-72 {{ ($isOralReading || $isFlashcards) ? 'hidden' : '' }}">
                    <div class="flex flex-col gap-4">
                        <div class="glass-hud pointer-events-auto flex items-center gap-4 rounded-lg border border-white/40 p-3 shadow-sm">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-tertiary-container text-tertiary-dim shadow-sm"><span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">stars</span></div>
                            <div><p class="font-label text-[10px] font-bold uppercase tracking-widest text-slate-500">Progress</p><p class="font-headline text-xl font-black leading-none text-on-surface" id="score">0/{{ $questionCount }}</p></div>
                        </div>
                        <div class="glass-hud pointer-events-auto rounded-lg border border-white/40 p-3 shadow-sm">
                            <p class="font-label mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-500">Energy Sync</p>
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
                    <h1 class="font-headline mb-1 text-xl font-black italic leading-none tracking-tighter text-primary-dim">MISSION: {{ $missionTitle }}</h1>
                    <p class="font-body font-medium text-slate-500">{{ $questionCount }} questions</p>
                </div>

                <div class="pointer-events-none absolute left-1/2 top-0 z-40 flex -translate-x-1/2 flex-col items-center {{ ($isOralReading || $isFlashcards) ? 'hidden' : '' }}" id="hook-assembly" aria-hidden="true">
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
                    <div class="absolute inset-x-0 top-0 z-30 px-3 pt-4 sm:px-6 sm:pt-8" id="frog-flashcards-game">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 rounded-full border-2 border-blue-100 bg-white px-4 py-2 shadow-lg shadow-blue-200/50">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-yellow-500 bg-yellow-400 text-white"><span class="material-symbols-outlined text-lg">stars</span></div>
                                <span class="font-headline text-2xl font-black text-blue-900" id="frog-score">0</span>
                            </div>
                            <div class="rounded-full border-2 border-blue-100 bg-white px-4 py-2 text-sm font-black uppercase tracking-wider text-blue-800 shadow-lg shadow-blue-200/50" id="frog-level-text">Question 1/{{ $questionCount }}</div>
                        </div>
                        <div class="mx-auto mt-5 max-w-4xl">
                            <div class="h-4 w-full rounded-full border border-blue-100 bg-white p-1 shadow-inner">
                                <div class="relative h-full w-0 rounded-full bg-gradient-to-r from-green-400 to-lime-500 transition-all duration-500" id="frog-progress-bar"><div class="absolute -right-2 -top-1 flex h-6 w-6 items-center justify-center rounded-full border-2 border-lime-500 bg-white shadow-sm"><span class="material-symbols-outlined text-xs text-lime-600">pest_control</span></div></div>
                            </div>
                        </div>
                    </div>

                    <section class="absolute inset-x-0 top-24 z-30 mx-auto flex w-full max-w-5xl flex-col items-center px-3 sm:top-28 sm:px-6" aria-label="Flashcards frog game">
                        <div class="relative w-full rounded-2xl border-b-4 border-blue-100 bg-white px-4 py-4 sm:rounded-[2rem] sm:border-b-8 sm:px-8 sm:py-6 text-center shadow-xl shadow-blue-200/50 transition-colors duration-300" id="frog-question-card">
                            <div class="absolute -left-4 -top-4 flex h-12 w-12 rotate-[-10deg] items-center justify-center rounded-full border-4 border-white bg-yellow-400 text-2xl font-black text-white shadow-md">?</div>
                            <h2 class="font-headline text-xl font-black leading-tight text-blue-900 sm:text-3xl md:text-4xl" id="frog-question-text">{{ $firstQuestion ? $firstQuestion['text'] : 'Ready?' }}</h2>
                        </div>
                        <div class="relative mt-5 grid min-h-40 w-full grid-cols-2 gap-3 sm:mt-8 sm:min-h-48 sm:gap-5 md:grid-cols-4" id="frog-answer-zone">
                            @foreach (['A', 'B', 'C', 'D'] as $index => $letter)
                                @php $wingClass = ['red-wings', 'gold-wings', 'purple-wings', 'green-wings'][$index]; @endphp
                                <button class="frog-answer relative mx-auto flex h-20 w-28 items-center justify-center sm:h-24 sm:w-32 focus:outline-none focus:ring-4 focus:ring-blue-200" type="button" data-frog-answer="{{ $letter }}" aria-label="Answer {{ $letter }}">
                                    <span class="dragonfly-wing left {{ $wingClass }}"></span>
                                    <span class="dragonfly-wing right {{ $wingClass }}"></span>
                                    <span class="frog-answer-circle relative z-10 flex h-16 w-16 items-center justify-center rounded-full border-4 border-blue-600 bg-blue-500 text-xl font-black text-white" data-frog-answer-circle>{{ $letter }}</span>
                                    <span class="absolute -bottom-4 left-1/2 z-10 w-32 -translate-x-1/2 truncate sm:w-40 rounded-full bg-white/90 px-3 py-1 text-xs font-black text-blue-900 shadow-sm" data-frog-answer-label>Answer {{ $letter }}</span>
                                </button>
                            @endforeach
                        </div>
                    </section>

                    <div class="frog-tongue" id="frog-tongue" aria-hidden="true"></div>
                    <footer class="absolute inset-x-0 bottom-0 z-30 h-56 overflow-hidden">
                        <div class="absolute bottom-0 h-32 w-full rounded-t-[3rem] bg-gradient-to-b from-blue-300 to-blue-500"></div>
                        <div class="absolute bottom-8 left-1/2 h-20 w-64 -translate-x-1/2 rounded-[100%] border-b-8 border-green-700 bg-green-500 shadow-xl"></div>
                        <div class="absolute bottom-4 left-1/2 h-32 w-36 -translate-x-1/2 rounded-t-full rounded-b-[2rem] border-4 border-lime-600 bg-lime-400 shadow-[inset_-10px_-10px_0_0_rgba(0,0,0,0.1)]" id="frog-character">
                            <div class="absolute -top-10 left-2 flex h-16 w-14 items-center justify-center rounded-full border-4 border-lime-600 bg-lime-400"><span class="h-9 w-8 rounded-full bg-white"><span class="ml-2 mt-2 block h-5 w-4 rounded-full bg-slate-900"></span></span></div>
                            <div class="absolute -top-10 right-2 flex h-16 w-14 items-center justify-center rounded-full border-4 border-lime-600 bg-lime-400"><span class="h-9 w-8 rounded-full bg-white"><span class="ml-2 mt-2 block h-5 w-4 rounded-full bg-slate-900"></span></span></div>
                            <div class="absolute left-1/2 top-10 h-8 w-24 -translate-x-1/2 rounded-[50%] border-4 border-lime-700 bg-lime-950"><span class="absolute left-1/2 top-1/2 h-2 w-16 -translate-x-1/2 -translate-y-1/2 rounded-full bg-pink-400"></span></div>
                            <div class="absolute bottom-0 left-1/2 h-20 w-24 -translate-x-1/2 rounded-t-full rounded-b-xl bg-sky-100/80"></div>
                        </div>
                    </footer>
                @endif
                @if (($storyTitle || $storyDescription) && ! $isListeningComprehension)
                    <div class="absolute inset-0 z-[90] flex items-center justify-center bg-surface/80 p-3 sm:p-5 backdrop-blur-xl" id="story-gate">
                        <section class="glass-hud flex max-h-[calc(100dvh-6rem)] w-full {{ $isOralReading ? 'max-w-6xl' : 'max-w-3xl' }} flex-col rounded-2xl border border-white/70 p-4 shadow-2xl sm:p-6 md:p-8">
                            <div class="mb-5 flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-on-primary shadow-lg shadow-primary/20">
                                    <span class="material-symbols-outlined">auto_stories</span>
                                </div>
                                <div>
                                    <p class="font-label text-[10px] font-black uppercase tracking-[0.24em] text-primary-dim">Read First</p>
                                    <h1 class="font-headline text-xl font-black leading-tight text-on-surface sm:text-2xl md:text-3xl">{{ $storyTitle ?: $assessment->title }}</h1>
                                </div>
                            </div>

                            @if ($isOralReading)
                                <div class="grid min-h-0 flex-1 grid-cols-1 gap-3 sm:gap-5 md:grid-cols-2">
                                    <div class="flex min-h-[220px] sm:min-h-[260px] flex-col overflow-hidden rounded-xl border-2 border-primary/15 bg-white/85 shadow-inner">
                                        <div class="border-b border-primary/10 bg-primary/5 px-5 py-3">
                                            <p class="font-label text-[10px] font-black uppercase tracking-[0.22em] text-primary-dim">Reading</p>
                                        </div>
                                        <div class="min-h-0 flex-1 overflow-y-auto p-4 text-sm sm:p-5 sm:text-base leading-relaxed text-on-surface-variant" data-sync-scroll="oral-story">
                                            {!! $storyReadOnlyHtml !!}
                                        </div>
                                    </div>
                                    <div class="flex min-h-[220px] sm:min-h-[260px] flex-col overflow-hidden rounded-xl border-2 border-secondary/20 bg-white/85 shadow-inner">
                                        <div class="border-b border-secondary/10 bg-secondary/5 px-5 py-3">
                                            <p class="font-label text-[10px] font-black uppercase tracking-[0.22em] text-primary-dim">Teacher Check</p>
                                        </div>
                                        <div class="min-h-0 flex-1 overflow-y-auto p-4 text-sm sm:p-5 sm:text-base leading-relaxed text-on-surface-variant" id="story-reader-text" data-sync-scroll="oral-story">
                                            {!! $storyMarkingHtml !!}
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 flex flex-wrap items-center justify-center gap-3 rounded-xl bg-surface-container-low/80 p-3 text-sm font-bold text-on-surface-variant shadow-sm" aria-label="Oral reading marking legend">
                                    <span class="font-label text-[10px] font-black uppercase tracking-[0.22em] text-primary-dim">Legend</span>
                                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-2 shadow-sm"><span class="h-4 w-4 rounded bg-red-400/70 ring-1 ring-red-500/30"></span>Mispronounced</span>
                                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-2 shadow-sm"><span class="h-4 w-4 rounded bg-yellow-300/80 ring-1 ring-yellow-500/30"></span>Getting Closer</span>
                                </div>
                                <div class="mt-3 flex flex-wrap items-center justify-center gap-2 rounded-xl bg-white/80 p-3 text-sm font-bold text-on-surface-variant shadow-sm" aria-label="Oral reading mark mode">
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
                                <div class="min-h-0 flex-1 overflow-y-auto rounded-xl bg-white/70 p-4 text-sm sm:p-5 sm:text-base leading-relaxed text-on-surface-variant shadow-inner">
                                    <div id="story-reader-text">
                                        {!! $storyHtml !!}
                                    </div>
                                </div>
                            @endif

                            @if ($isSilentReading)
                                <div class="mt-5 rounded-xl bg-surface-container-low p-4 shadow-sm">
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

                            <div class="mt-6 flex justify-end">
                                <button class="inline-flex items-center gap-2 rounded-lg bg-primary px-6 py-3 text-sm font-black uppercase tracking-widest text-on-primary shadow-lg shadow-primary/20 transition-colors hover:bg-primary-dim disabled:cursor-not-allowed disabled:opacity-50" id="start-questions-button" type="button" @if ($isSilentReading) disabled @endif>
                                    {{ $isOralReading ? 'Finish Assessment' : 'Start Questions' }}
                                    <span class="material-symbols-outlined text-lg">{{ $isOralReading ? 'check_circle' : 'arrow_forward' }}</span>
                                </button>
                            </div>
                        </section>
                    </div>
                @endif
                <div class="fixed inset-0 z-[100] hidden overflow-y-auto bg-surface opacity-0 transition-opacity duration-300" id="mission-modal">
                    <div class="flex app-safe-screen flex-col md:flex-row">
                        <aside class="hidden w-72 shrink-0 flex-col gap-8 bg-surface-container-low p-6 md:flex">
                            <div class="mt-6 flex flex-col items-center gap-2 text-center">
                                <div class="mb-2 flex h-24 w-24 items-center justify-center rounded-full border-4 border-surface bg-primary-container font-display text-3xl font-black text-on-primary-container shadow-sm">
                                    {{ $studentInitials ?: 'S' }}
                                </div>
                                <h2 class="font-display text-xl font-black text-primary">{{ $studentName }}</h2>
                                <p class="font-body text-on-surface-variant">Assessment Explorer</p>
                            </div>
                            <nav class="mt-4 flex flex-col gap-2">
                                <a class="mx-2 flex items-center gap-4 rounded-xl px-4 py-3 text-on-surface-variant transition-all hover:bg-surface-container-high" href="{{ route('student.dashboard') }}"><span class="material-symbols-outlined">home</span> Home</a>
                                <a class="mx-2 flex items-center gap-4 rounded-xl bg-surface-container-high px-4 py-3 font-bold text-primary" href="{{ route('student.activities') }}"><span class="material-symbols-outlined">auto_stories</span> Missions</a>
                            </nav>
                        </aside>

                        <div class="relative flex app-safe-screen flex-1 flex-col">
                            <header class="hidden items-center justify-between bg-surface px-8 py-4 shadow-sm md:flex">
                                <div class="font-display text-2xl font-bold text-primary">AI-PGAALS</div>
                                <nav class="flex gap-8">
                                    <a class="rounded-lg px-3 py-2 font-headline font-semibold text-on-surface-variant transition-colors hover:bg-surface-container-high hover:text-primary" href="{{ route('student.dashboard') }}">Dashboard</a>
                                    <a class="rounded-lg border-b-4 border-primary px-3 py-2 font-headline font-bold text-primary transition-colors hover:bg-surface-container-high" href="{{ route('student.activities') }}">Activities</a>
                                    <a class="rounded-lg px-3 py-2 font-headline font-semibold text-on-surface-variant transition-colors hover:bg-surface-container-high hover:text-primary" href="{{ route('profile.edit') }}">Profile</a>
                                </nav>
                                <div class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-primary-container bg-surface-container-low font-bold text-primary">{{ $studentInitials ?: 'S' }}</div>
                            </header>

                            <main class="relative flex flex-1 flex-col items-center justify-center overflow-hidden p-6 pb-28 md:p-12">
                                <div class="result-pattern pointer-events-none absolute inset-0 z-0"></div>
                                <div class="relative z-10 flex w-full max-w-4xl flex-col gap-8">
                                    <div class="mb-4 text-center">
                                        <h1 class="font-display text-4xl font-bold tracking-tight text-primary md:text-6xl">Great job!</h1>
                                        <p class="mt-2 font-body text-xl font-medium text-on-surface-variant" id="result-summary">Checking your real score...</p>
                                    </div>

                                    <div class="grid w-full grid-cols-1 gap-6 md:grid-cols-2">
                                        <div class="flex flex-col gap-6">
                                            <div class="relative flex flex-col gap-4 overflow-hidden rounded-[2rem] bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                                                <div class="absolute -right-12 -top-12 h-32 w-32 rounded-full bg-primary-container opacity-20 blur-xl"></div>
                                                <h3 class="font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">Accuracy Score</h3>
                                                <div class="flex items-end gap-2">
                                                    <span class="font-display text-6xl font-bold leading-none text-primary" id="result-accuracy">0</span>
                                                    <span class="pb-1 font-headline text-xl font-bold text-primary" id="result-accuracy-unit">%</span>
                                                </div>
                                                <div class="mt-2 h-4 w-full overflow-hidden rounded-full bg-surface-container-high">
                                                    <div class="h-full rounded-full bg-secondary shadow-[inset_0_2px_4px_rgba(255,255,255,0.3)] transition-all duration-500" id="result-progress" style="width: 0%"></div>
                                                </div>
                                            </div>

                                            <div class="relative flex flex-col gap-4 overflow-hidden rounded-[2rem] bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                                                <div class="absolute -bottom-8 -left-8 h-24 w-24 rounded-full bg-tertiary-container opacity-30 blur-lg"></div>
                                                <h3 class="font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">Total Experience</h3>
                                                <div class="flex items-center gap-4">
                                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-tertiary-container text-tertiary shadow-sm"><span class="material-symbols-outlined text-3xl">stars</span></div>
                                                    <span class="font-display text-5xl font-bold text-on-surface" id="result-points">Saving...</span>
                                                </div>
                                                <p class="font-body text-sm font-medium text-on-surface-variant">Real assessment points saved to your account.</p>
                                            </div>

                                            <div class="relative flex items-center justify-between overflow-hidden rounded-[2rem] bg-gradient-to-br from-primary to-primary-dim p-8 text-on-primary shadow-lg">
                                                <div class="absolute inset-0 opacity-20" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,0.1) 10px, rgba(255,255,255,0.1) 20px);"></div>
                                                <div class="relative z-10 flex max-w-[65%] flex-col gap-2">
                                                    <h3 class="font-display text-2xl font-bold" id="result-badge-title">Badge Unlocked!</h3>
                                                    <p class="font-body text-primary-container" id="result-badge">Calculating your achievement...</p>
                                                </div>
                                                <div class="relative z-10">
                                                    <div class="relative flex h-20 w-20 items-center justify-center rounded-full border-4 border-tertiary-container bg-primary-dim shadow-inner">
                                                        <div class="absolute inset-0 animate-pulse rounded-full bg-tertiary-container opacity-20 blur-md"></div>
                                                        <span class="material-symbols-outlined text-4xl text-tertiary-container" style="font-variation-settings: 'FILL' 1;">workspace_premium</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex flex-col gap-6">
                                            <div class="flex h-full flex-col gap-6 rounded-[2rem] border border-outline-variant/10 bg-surface-container-low p-8 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                                                <div class="mb-2 flex items-center gap-2">
                                                    <span class="material-symbols-outlined text-secondary">bar_chart</span>
                                                    <h3 class="font-headline text-xl font-bold text-on-surface">Performance Breakdown</h3>
                                                </div>
                                                <div class="flex flex-col gap-4">
                                                    <div class="flex items-center justify-between rounded-xl bg-surface-container-lowest p-4 shadow-sm">
                                                        <span class="font-body font-medium text-on-surface-variant">Correct Answers</span>
                                                        <span class="font-display text-lg font-bold text-secondary" id="result-correct">0/{{ $questionCount }}</span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-4 rounded-xl bg-surface-container-lowest p-4 shadow-sm">
                                                        <span class="font-body font-medium text-on-surface-variant">Passage</span>
                                                        <span class="max-w-[55%] truncate text-right font-display text-sm font-bold text-primary">{{ $storyTitle ?: $assessment->title }}</span>
                                                    </div>
                                                    <div class="flex items-center justify-between rounded-xl bg-surface-container-lowest p-4 shadow-sm">
                                                        <span class="font-body font-medium text-on-surface-variant">Assessment Type</span>
                                                        <span class="font-display text-lg font-bold text-tertiary">{{ $assessmentTypeLabel }}</span>
                                                    </div>
                                                    <div class="hidden items-center justify-between rounded-xl bg-surface-container-lowest p-4 shadow-sm" id="result-reading-time-row">
                                                        <span class="font-body font-medium text-on-surface-variant">Reading Time</span>
                                                        <span class="font-display text-lg font-bold text-primary" id="result-reading-time">00:00</span>
                                                    </div>
                                                    <div class="{{ $isOralReading ? '' : 'hidden' }} rounded-xl bg-surface-container-lowest p-4 shadow-sm">
                                                        <div class="mb-3 flex items-center justify-between gap-4">
                                                            <span class="font-body font-medium text-on-surface-variant">Pronunciation</span>
                                                            <span class="font-label text-xs font-bold uppercase tracking-wider text-tertiary">Needs Improvement</span>
                                                        </div>
                                                        <div class="flex flex-wrap gap-2" id="result-needs-improvement">
                                                            <span class="text-sm text-on-surface-variant">No yellow words marked.</span>
                                                        </div>
                                                    </div>
                                                    <div class="{{ $isOralReading ? '' : 'hidden' }} rounded-xl bg-surface-container-lowest p-4 shadow-sm">
                                                        <div class="mb-3 flex items-center justify-between gap-4">
                                                            <span class="font-body font-medium text-on-surface-variant">Pronunciation</span>
                                                            <span class="font-label text-xs font-bold uppercase tracking-wider text-error">Wrong Pronunciation</span>
                                                        </div>
                                                        <div class="flex flex-wrap gap-2" id="result-wrong-pronunciation">
                                                            <span class="text-sm text-on-surface-variant">No red words marked.</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mx-auto mt-4 flex w-full max-w-md flex-col gap-4">
                                        <a class="w-full rounded-[3rem] bg-gradient-to-br from-primary to-primary-container py-4 text-center font-headline text-xl font-bold text-on-primary shadow-md transition-all duration-200 hover:-translate-y-1 hover:from-primary-dim hover:to-primary active:translate-y-0 active:scale-95" href="{{ route('student.activities') }}">Next Level</a>
                                        <button class="w-full rounded-[3rem] bg-surface-container-high py-4 font-headline text-lg font-bold text-on-surface transition-all duration-200 hover:bg-surface-dim active:scale-95" type="button" onclick="window.location.reload()">Try Again</button>
                                    </div>
                                </div>
                            </main>
                        </div>
                    </div>
                </div>
            </main>

            <script>
                (() => {
                    const canvas = document.getElementById('mission-canvas');
                    const hookAssembly = document.getElementById('hook-assembly');
                    const hookCable = document.getElementById('hook-cable');
                    const hookHead = document.getElementById('hook-head');
                    const container = document.getElementById('fish-container');
                    const fishTemplate = document.getElementById('answer-fish-template');
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
                    let hookReelFadeTimer = null;

                    function formatElapsedTime(totalSeconds) {
                        const minutes = Math.floor(totalSeconds / 60).toString().padStart(2, '0');
                        const seconds = Math.floor(totalSeconds % 60).toString().padStart(2, '0');
                        return `${minutes}:${seconds}`;
                    }

                    function playGameMusic() {
                        if (!gameMusic || isOralReading) return;
                        gameMusic.volume = 0.28;
                        gameMusic.play().catch(() => {});
                    }

                    function stopGameMusic() {
                        if (!gameMusic) return;
                        gameMusic.pause();
                        gameMusic.currentTime = 0;
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
                        if (readingTimer) return;
                        readingStartedAt = Date.now();
                        readingElapsedSeconds = 0;
                        updateReadingTimer();
                        readingTimer = setInterval(updateReadingTimer, 1000);
                        startTimerButton.disabled = true;
                        startTimerButton.classList.add('opacity-60');
                        endTimerButton.disabled = false;
                        endTimerButton.classList.remove('opacity-60');
                        readingTimerStatus.innerText = 'Timer is running. Click End Timer when the reader is done.';
                    }

                    function endReadingTimer() {
                        if (!readingTimer) return;
                        updateReadingTimer();
                        clearInterval(readingTimer);
                        readingTimer = null;
                        endTimerButton.disabled = true;
                        endTimerButton.classList.add('opacity-60');
                        startQuestionsButton.disabled = false;
                        readingTimerStatus.innerText = `Reading finished in ${formatElapsedTime(readingElapsedSeconds)}. You can now start the questions.`;
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
                        if (!isFlashcards || !questions[currentQuestionIndex]) return;

                        const question = questions[currentQuestionIndex];
                        frogQuestionText.innerText = question.text;
                        frogLevelText.innerText = `Question ${currentQuestionIndex + 1}/${targetsNeeded}`;
                        frogQuestionCard.classList.remove('bg-green-100', 'bg-red-100');
                        frogAnswerButtons.forEach((button) => {
                            const letter = button.dataset.frogAnswer;
                            const option = (question.options || []).find((item) => item.l === letter);
                            const label = button.querySelector('[data-frog-answer-label]');
                            const circle = button.querySelector('[data-frog-answer-circle]');
                            button.disabled = !option;
                            button.classList.remove('is-caught');
                            button.style.opacity = option ? '1' : '0.35';
                            if (label) label.innerText = option ? option.t : `Answer ${letter}`;
                            if (circle) {
                                circle.innerText = letter;
                                circle.classList.remove('bg-green-500', 'border-green-700', 'bg-red-500', 'border-red-700');
                                circle.classList.add('bg-blue-500', 'border-blue-600');
                            }
                        });
                    }

                    function updateFrogProgress(correct) {
                        const progress = targetsNeeded > 0 ? (caughtCount / targetsNeeded) * 100 : 0;
                        if (frogScore) frogScore.innerText = String(totalScore);
                        if (frogProgressBar) frogProgressBar.style.width = `${Math.min(progress, 100)}%`;
                        if (frogQuestionCard) {
                            frogQuestionCard.classList.toggle('bg-green-100', correct);
                            frogQuestionCard.classList.toggle('bg-red-100', !correct);
                        }
                    }

                    function animateFrogCatch(button) {
                        if (!frogTongue || !frogCharacter || !button) return;
                        const canvasBox = canvas.getBoundingClientRect();
                        const frogBox = frogCharacter.getBoundingClientRect();
                        const targetBox = button.getBoundingClientRect();
                        const startX = frogBox.left + frogBox.width / 2 - canvasBox.left;
                        const startY = frogBox.top + 44 - canvasBox.top;
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
                        button.classList.add('is-caught');
                    }

                    function answerFlashcard(letter, button) {
                        if (!isFlashcards || isProcessingCapture || !questions[currentQuestionIndex]) return;
                        playGameMusic();
                        isProcessingCapture = true;
                        frogAnswerButtons.forEach((item) => item.disabled = true);
                        const question = questions[currentQuestionIndex];
                        const correct = question.correct === letter;
                        capturedAnswers[currentQuestionIndex] = letter;
                        caughtCount++;
                        if (correct) {
                            totalScore++;
                            playFrogCorrectAnswerSound();
                        } else {
                            playFrogWrongAnswerSound();
                        }
                        const circle = button?.querySelector('[data-frog-answer-circle]');
                        if (circle) {
                            circle.classList.remove('bg-blue-500', 'border-blue-600');
                            circle.classList.add(correct ? 'bg-green-500' : 'bg-red-500', correct ? 'border-green-700' : 'border-red-700');
                        }
                        animateFrogCatch(button);
                        updateFrogProgress(correct);

                        setTimeout(() => {
                            if (caughtCount >= targetsNeeded) {
                                victory();
                                return;
                            }

                            currentQuestionIndex++;
                            isProcessingCapture = false;
                            renderFlashcardQuestion();
                        }, 900);
                    }

                    function fireHook() {
                        isHooking = true;
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
                        isProcessingCapture = true;
                        capturedAnswers[currentQuestionIndex] = element.dataset.letter;
                        const previousRect = element.getBoundingClientRect();
                        const visual = element.querySelector('.fish-visual');
                        const swimmingTransform = getComputedStyle(visual).transform;
                        element.dataset.caught = 'true';
                        element.disabled = true;
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
                        visual.animate([{ transform: swimmingTransform }, { transform: 'rotate(-90deg)' }], { duration: 180, easing: 'ease-out' });
                        reelInFish(element);
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
                    }

                    function nextQuestion() {
                        currentQuestionIndex++;
                        const question = questions[currentQuestionIndex];
                        questionNode.style.opacity = '0';
                        setTimeout(() => {
                            questionNode.innerHTML = `<div class="mb-3 flex items-center gap-2"><span class="material-symbols-outlined text-sm text-primary">terminal</span><h3 class="font-label text-[10px] font-black uppercase tracking-[0.2em] text-primary-dim">Question Node ${escapeHtml(question.node)}</h3></div><h2 class="font-headline mb-4 text-lg font-extrabold leading-tight text-on-surface">${escapeHtml(question.text)}</h2><div class="hook-options space-y-2">${question.options.map((option) => `<div class="group flex cursor-default items-center gap-3 rounded-xl border border-white bg-white/50 p-2.5 transition-colors hover:bg-white"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-container/20 font-bold text-primary">${escapeHtml(option.l)}</span><span class="font-medium text-on-surface-variant">${escapeHtml(option.t)}</span></div>`).join('')}</div>`;
                            questionNode.style.opacity = '1';
                            isProcessingCapture = false;
                            spawnFishSchool();
                        }, 300);
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
                                body: JSON.stringify({ answers: capturedAnswers }),
                            });

                            if (!response.ok) {
                                throw new Error('Score sync failed');
                            }

                            const result = await response.json();
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
                        }
                    }

                    function victory() {
                        stopGameMusic();
                        fadeHookReelSound();
                        updatePronunciationResults();
                        updateReadingTimeResult();
                        modal.classList.remove('hidden');
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
                            return { element, x: (container.clientWidth - 148) * ((index * .29 + .1) % 1) + 8, direction: index % 2 ? -1 : 1, speed: reducedMotion ? 18 : 32 + index * 4 };
                        });
                        positionFishSchool(0, performance.now());
                    }

                    function positionFishSchool(elapsed, now) {
                        const maxX = Math.max(8, container.clientWidth - 140);
                        const laneHeight = Math.max(0, container.clientHeight - 104) / Math.max(1, fishSchool.length - 1);
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
                        if (isOralReading) return;
                        if (missionStarted && (fishFrame || isFlashcards)) return;
                        missionStarted = true;
                        playGameMusic();
                        storyGate?.classList.add('opacity-0', 'pointer-events-none');
                        setTimeout(() => storyGate?.classList.add('hidden'), 300);
                        if (isFlashcards) {
                            renderFlashcardQuestion();
                            return;
                        }
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
                    if (isFlashcards) renderFlashcardQuestion();
                    if (missionStarted && !isOralReading) startMission();
                })();
            </script>
        @elseif ($assetPath)
            <main class="relative flex app-game-screen w-full items-center justify-center overflow-hidden bg-gradient-to-b from-surface via-surface-container-low to-surface p-4 sm:p-8"><div class="glass-hud w-full max-w-xl rounded-2xl border border-white/60 p-6 text-center shadow-xl sm:p-10"><div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-primary text-on-primary"><span class="material-symbols-outlined text-6xl">file_open</span></div><h1 class="font-headline text-3xl font-black text-on-surface sm:text-4xl">Uploaded Mission</h1><p class="mt-3 font-medium text-slate-500">{{ $assessment->instructions ?: 'Open the uploaded assessment material from your teacher.' }}</p><a class="mt-8 inline-flex rounded-lg bg-primary px-8 py-3 text-base font-bold text-white shadow-lg transition-colors hover:bg-primary-dim" href="{{ $assetUrl }}" target="_blank" rel="noopener">Open File</a></div></main>
        @else
            <main class="relative flex app-game-screen w-full items-center justify-center overflow-hidden bg-gradient-to-b from-surface via-surface-container-low to-surface p-4 sm:p-8"><div class="glass-hud w-full max-w-xl rounded-2xl border border-white/60 p-6 text-center shadow-xl sm:p-10"><div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-surface-container text-primary"><span class="material-symbols-outlined text-6xl">pending_actions</span></div><h1 class="font-headline text-3xl font-black text-on-surface sm:text-4xl">No Mission Data</h1><p class="mt-3 font-medium text-slate-500">Your teacher has published this assessment, but no manual questions or uploaded file were attached.</p></div></main>
        @endif
    </div>
</x-app-layout>
