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
        $storyDescription = $assessment->story_description;
        $storyText = $storyDescription ?: $assessment->instructions;
        $storyHtml = collect(preg_split('/\R{2,}/u', trim($storyText ?? '')))
            ->filter(fn (string $paragraph): bool => trim($paragraph) !== '')
            ->map(function (string $paragraph) use ($isOralReading): string {
                $words = collect(preg_split('/(\s+)/u', $paragraph, -1, PREG_SPLIT_DELIM_CAPTURE))
                    ->map(fn (string $part): string => trim($part) === ''
                        ? e($part)
                        : ($isOralReading ? '<button class="story-word" type="button" data-mark="0">'.e($part).'</button>' : e($part)))
                    ->implode('');

                return '<p class="story-paragraph">'.$words.'</p>';
            })
            ->implode('');
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
                ];
            })
            ->filter(fn (array $question): bool => filled($question['text']) && count($question['options']) > 0)
            ->values();
        $questionCount = $gameQuestions->count();
        $firstQuestion = $gameQuestions->first();
        $missionTitle = str($assessment->title)->upper()->limit(28, '');
    @endphp

    <style>
        .bubble-word { transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; will-change: transform, bottom, left; }
        .particle { position: absolute; pointer-events: none; z-index: 100; }
        .glass-hud { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); }
        .liquid-track { box-shadow: inset 0 2px 4px rgba(0,0,0,0.1); }
        .liquid-fill { box-shadow: 0 0 15px rgba(145, 247, 142, 0.6), inset 0 2px 4px rgba(255,255,255,0.4); transition: width 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .hook-cable { width: 2px; background: linear-gradient(to bottom, #74777a, #005e9f); height: 0; transition: height 0.3s cubic-bezier(0.45, 0.05, 0.55, 0.95); }
        .pulse-bag { animation: bag-pulse 2s infinite ease-in-out; transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), background-color 0.4s ease; }
        @keyframes bag-pulse { 0%, 100% { transform: scale(var(--base-scale, 1)) translateY(0); } 50% { transform: scale(calc(var(--base-scale, 1) * 1.05)) translateY(-5px); } }
        .mission-canvas { cursor: crosshair; }
        .caught-word { position: absolute; z-index: 45; transition: all 0.5s cubic-bezier(0.6, -0.28, 0.735, 0.045); }
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
        .story-word { appearance: none; display: inline; cursor: pointer; border: 0; border-radius: 0.25rem; background: transparent; margin: 0; padding: 0.03rem 0.1rem; color: inherit; font: inherit; line-height: inherit; text-align: inherit; vertical-align: baseline; transition: background-color 0.15s ease, color 0.15s ease; user-select: none; -webkit-user-select: none; touch-action: manipulation; }
        .story-word:hover { background: rgba(0, 94, 159, 0.08); }
        .story-word-mark-1 { background: rgba(250, 204, 21, 0.32); color: #854d0e; }
        .story-word-mark-2 { background: rgba(248, 113, 113, 0.28); color: #991b1b; }
        .result-pattern { background-image: radial-gradient(circle at 10px 10px, rgba(68, 165, 255, 0.45) 1px, transparent 1px), radial-gradient(circle at 30px 30px, rgba(145, 247, 142, 0.45) 1px, transparent 1px); background-size: 40px 40px; background-position: 0 0, 20px 20px; opacity: 0.2; }
    </style>

    <div class="h-screen overflow-hidden bg-surface text-on-surface font-body selection:bg-primary-container selection:text-on-primary-container">
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
            <main class="mission-canvas relative h-[calc(100vh-64px)] w-full overflow-hidden bg-gradient-to-b from-surface via-surface-container-low to-surface" id="mission-canvas">
                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    <div class="absolute left-[10%] top-20 h-64 w-64 rounded-full bg-primary-container/10 blur-3xl"></div>
                    <div class="absolute bottom-20 right-[15%] h-96 w-96 rounded-full bg-tertiary-container/10 blur-3xl"></div>
                </div>

                <div class="pointer-events-none absolute left-5 top-5 z-30 flex w-72 flex-col gap-3 {{ $isOralReading ? 'hidden' : '' }}">
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
                            <div class="space-y-2">
                                @foreach ($firstQuestion['options'] as $option)
                                    <div class="group flex cursor-default items-center gap-3 rounded-xl border border-white bg-white/50 p-2.5 transition-colors hover:bg-white">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-container/20 font-bold text-primary">{{ $option['l'] }}</span>
                                        <span class="font-medium text-on-surface-variant">{{ $option['t'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <p class="mt-4 text-[10px] font-medium leading-relaxed text-slate-400 italic">* Capture a data packet (A, B, C, or D) to stabilize the core.</p>
                        @endif
                    </div>
                </div>

                <div class="pointer-events-none absolute right-5 top-5 z-30 text-right {{ $isOralReading ? 'hidden' : '' }}" id="mission-info">
                    <h1 class="font-headline mb-1 text-xl font-black italic leading-none tracking-tighter text-primary-dim">MISSION: {{ $missionTitle }}</h1>
                    <p class="font-body font-medium text-slate-500">Capture <span class="font-bold text-primary">{{ $questionCount }} Nodes</span> to power the drive.</p>
                </div>

                <div class="pointer-events-none absolute left-1/2 top-0 z-40 flex -translate-x-1/2 flex-col items-center transition-all duration-75 {{ $isOralReading ? 'hidden' : '' }}" id="hook-assembly"><div class="hook-cable" id="hook-cable"></div><div class="relative -mt-1"><div class="flex h-10 w-10 rotate-45 transform items-center justify-center rounded-xl border-2 border-white bg-primary shadow-lg" id="hook-head"><span class="material-symbols-outlined -rotate-45 text-white" style="font-variation-settings: 'FILL' 1;">anchor</span></div></div></div>

                <div class="absolute inset-0 z-20 {{ $isOralReading ? 'hidden' : '' }}" id="bubbles-container"></div>

                <div class="absolute bottom-3 left-1/2 z-40 -translate-x-1/2 {{ $isOralReading ? 'hidden' : '' }}" id="egg-wrapper">
                    <div class="egg-glow"></div><div class="hatch-light" id="hatch-flash"></div>
                    <div class="data-egg-container pulse-bag" id="power-core" style="--base-scale: 1;">
                        <div class="data-egg" id="shell-main">
                            <div class="crack left-1/4 top-1/3 rotate-[15deg]" id="crack-1"></div><div class="crack right-1/4 top-1/4 -rotate-[25deg]" id="crack-2"></div><div class="crack left-1/2 bottom-1/4 rotate-[180deg]" id="crack-3"></div><div class="crack left-[20%] bottom-[40%] rotate-[45deg]" id="crack-4"></div><div class="crack right-[15%] bottom-[30%] rotate-[-60deg]" id="crack-5"></div>
                            <div class="relative z-10 flex flex-col items-center pt-14 text-primary"><span class="material-symbols-outlined mb-1 text-4xl opacity-80" id="bag-icon" style="font-variation-settings: 'FILL' 1;">dataset</span><span class="font-label text-[9px] font-bold uppercase tracking-widest">Data Egg</span></div>
                        </div>
                        <div class="hatch-core" id="inside-core"><div class="flex flex-col items-center"><div class="flex h-20 w-20 items-center justify-center rounded-full border-4 border-primary bg-white shadow-[0_0_50px_rgba(68,165,255,0.4)]"><span class="material-symbols-outlined text-5xl text-primary" style="font-variation-settings: 'FILL' 1;">stars</span></div><div class="mt-4 rounded-full bg-primary px-4 py-2 text-xs font-black uppercase tracking-widest text-white shadow-lg">Unlocked</div></div></div>
                    </div>
                </div>

                                @if (($storyTitle || $storyDescription) && ! $isListeningComprehension)
                    <div class="absolute inset-0 z-[90] flex items-center justify-center bg-surface/80 p-5 backdrop-blur-xl" id="story-gate">
                        <section class="glass-hud flex max-h-[82vh] w-full max-w-3xl flex-col rounded-2xl border border-white/70 p-6 shadow-2xl sm:p-8">
                            <div class="mb-5 flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-on-primary shadow-lg shadow-primary/20">
                                    <span class="material-symbols-outlined">auto_stories</span>
                                </div>
                                <div>
                                    <p class="font-label text-[10px] font-black uppercase tracking-[0.24em] text-primary-dim">Read First</p>
                                    <h1 class="font-headline text-2xl font-black leading-tight text-on-surface sm:text-3xl">{{ $storyTitle ?: $assessment->title }}</h1>
                                </div>
                            </div>
                            <div class="min-h-0 flex-1 overflow-y-auto rounded-xl bg-white/70 p-5 text-base leading-relaxed text-on-surface-variant shadow-inner">
                                                                <div id="story-reader-text">
                                    {!! $storyHtml !!}
                                </div>
                            </div>
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
                    <div class="flex min-h-screen flex-col md:flex-row">
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

                        <div class="relative flex min-h-screen flex-1 flex-col">
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
                                        <h1 class="font-display text-5xl font-bold tracking-tight text-primary md:text-6xl">Great job!</h1>
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
                    const container = document.getElementById('bubbles-container');
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
                    const startQuestionsButton = document.getElementById('start-questions-button');
                    const startTimerButton = document.getElementById('start-timer-button');
                    const endTimerButton = document.getElementById('end-timer-button');
                    const readingTimerDisplay = document.getElementById('reading-timer-display');
                    const readingTimerStatus = document.getElementById('reading-timer-status');
                    const resultReadingTimeRow = document.getElementById('result-reading-time-row');
                    const resultReadingTime = document.getElementById('result-reading-time');
                    const submitUrl = @json(route('student.assessments.submit', $assessment));
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    const assessmentType = @json($assessmentType);
                    const isOralReading = assessmentType === 'oral_reading';
                    const isSilentReading = assessmentType === 'silent_reading';
                    const canTrackPronunciation = isOralReading;
                    const letters = ['A', 'B', 'C', 'D'];
                    const questions = @json($gameQuestions->values());
                    const targetsNeeded = questions.length;
                    const capturedAnswers = {};
                    let caughtCount = 0;
                    let totalScore = 0;
                    let isHooking = false;
                    let isHatching = false;
                    let isProcessingCapture = false;
                    let currentQuestionIndex = 0;
                    let currentHookX = window.innerWidth / 2;
                    let missionStarted = !storyGate;
                    let spawnTimer = null;
                    let readingTimer = null;
                    let readingStartedAt = null;
                    let readingElapsedSeconds = 0;

                    function formatElapsedTime(totalSeconds) {
                        const minutes = Math.floor(totalSeconds / 60).toString().padStart(2, '0');
                        const seconds = Math.floor(totalSeconds % 60).toString().padStart(2, '0');
                        return `${minutes}:${seconds}`;
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

                    document.addEventListener('mousemove', (event) => {
                        if (missionStarted && !isHooking && !isHatching) {
                            currentHookX = event.clientX;
                            hookAssembly.style.left = `${currentHookX}px`;
                        }
                    });

                    canvas.addEventListener('click', (event) => {
                        if (!missionStarted) return;
                        if (event.target.closest('.glass-hud')) return;
                        if (isHooking || isHatching || isProcessingCapture) return;
                        fireHook();
                    });

                    function escapeHtml(value) {
                        return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                    }


                    storyReaderText?.addEventListener('pointerdown', (event) => {
                        if (!canTrackPronunciation) return;
                        if (event.target.closest('.story-word')) event.preventDefault();
                    });

                    storyReaderText?.addEventListener('click', (event) => {
                        if (!canTrackPronunciation) return;
                        const word = event.target.closest('.story-word');
                        if (!word) return;
                        event.preventDefault();
                        const nextMark = (Number(word.dataset.mark || '0') + 1) % 3;
                        word.dataset.mark = String(nextMark);
                        word.classList.remove('story-word-mark-1', 'story-word-mark-2');
                        if (nextMark > 0) word.classList.add(`story-word-mark-${nextMark}`);
                    });

                    function fireHook() {
                        isHooking = true;
                        hookCable.style.height = `${Math.max(180, canvas.offsetHeight - 220)}px`;
                        let hasCaughtOne = false;
                        const collisionCheck = setInterval(() => {
                            if (hasCaughtOne) return;
                            const hookRect = hookHead.getBoundingClientRect();
                            document.querySelectorAll('.bubble-word').forEach((node) => {
                                if (hasCaughtOne || node.dataset.caught) return;
                                const nodeRect = node.getBoundingClientRect();
                                const collision = !(hookRect.right < nodeRect.left || hookRect.left > nodeRect.right || hookRect.bottom < nodeRect.top || hookRect.top > nodeRect.bottom);
                                if (collision) {
                                    hasCaughtOne = true;
                                    catchNode(node, node.dataset.letter);
                                }
                            });
                        }, 10);
                        setTimeout(() => {
                            clearInterval(collisionCheck);
                            hookCable.style.height = '0px';
                            setTimeout(() => { isHooking = false; }, 300);
                        }, 300);
                    }

                    function catchNode(element, letter) {
                        isProcessingCapture = true;
                        capturedAnswers[currentQuestionIndex] = letter;
                        element.dataset.caught = 'true';
                        element.style.animation = 'none';
                        element.classList.remove('bubble-word');
                        element.classList.add('caught-word');
                        const hookRect = hookHead.getBoundingClientRect();
                        element.style.left = `${hookRect.left}px`;
                        element.style.top = `${hookRect.top}px`;
                        element.style.zIndex = '41';
                        setTimeout(() => {
                            const missionInfoRect = missionInfo.getBoundingClientRect();
                            element.style.left = `${missionInfoRect.left + missionInfoRect.width / 2 - element.offsetWidth / 2}px`;
                            element.style.top = `${missionInfoRect.top + missionInfoRect.height / 2 - element.offsetHeight / 2}px`;
                            element.style.transform = 'scale(0.1) rotate(180deg)';
                            element.style.opacity = '0';
                            setTimeout(() => {
                                element.remove();
                                updateProgress();
                                if (caughtCount < targetsNeeded) nextQuestion();
                            }, 500);
                        }, 100);
                    }

                    function nextQuestion() {
                        currentQuestionIndex++;
                        const question = questions[currentQuestionIndex];
                        questionNode.style.opacity = '0';
                        setTimeout(() => {
                            questionNode.innerHTML = `<div class="mb-3 flex items-center gap-2"><span class="material-symbols-outlined text-sm text-primary">terminal</span><h3 class="font-label text-[10px] font-black uppercase tracking-[0.2em] text-primary-dim">Question Node ${escapeHtml(question.node)}</h3></div><h2 class="font-headline mb-4 text-lg font-extrabold leading-tight text-on-surface">${escapeHtml(question.text)}</h2><div class="space-y-2">${question.options.map((option) => `<div class="group flex cursor-default items-center gap-3 rounded-xl border border-white bg-white/50 p-2.5 transition-colors hover:bg-white"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-container/20 font-bold text-primary">${escapeHtml(option.l)}</span><span class="font-medium text-on-surface-variant">${escapeHtml(option.t)}</span></div>`).join('')}</div><p class="mt-4 text-[10px] font-medium leading-relaxed text-slate-400 italic">* Capture a data packet (A, B, C, or D) to stabilize the core.</p>`;
                            questionNode.style.opacity = '1';
                            container.innerHTML = '';
                            isProcessingCapture = false;
                            for (let index = 0; index < 3; index++) setTimeout(spawnBubble, index * 800);
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
                        updatePronunciationResults();
                        updateReadingTimeResult();
                        modal.classList.remove('hidden');
                        setTimeout(() => modal.classList.add('opacity-100'), 50);
                        submitAttempt();
                    }

                    function spawnBubble() {
                        if (!missionStarted || caughtCount >= targetsNeeded || isHatching || isProcessingCapture) return;
                        const letter = letters[Math.floor(Math.random() * letters.length)];
                        const bubble = document.createElement('div');
                        bubble.dataset.letter = letter;
                        bubble.className = 'bubble-word absolute z-20 flex min-w-[78px] flex-col items-center justify-center rounded-2xl border-2 border-primary/40 bg-white/90 p-5 shadow-primary/10';
                        const playableLeft = Math.min(340, window.innerWidth * 0.32);
                        const playableWidth = Math.max(220, window.innerWidth - playableLeft - 160);
                        bubble.style.left = `${playableLeft + Math.random() * playableWidth}px`;
                        bubble.style.bottom = '-150px';
                        bubble.innerHTML = `<span class="font-headline text-4xl font-black text-on-surface">${escapeHtml(letter)}</span><span class="mt-2 text-[10px] font-black uppercase tracking-widest text-slate-400">Node</span>`;
                        container.appendChild(bubble);
                        const duration = 8000 + Math.random() * 5000;
                        const startTime = Date.now();
                        const drift = (Math.random() - 0.5) * 200;
                        function animate() {
                            if (bubble.dataset.caught || isHatching) { if (isHatching) bubble.style.opacity = '0'; return; }
                            const progress = (Date.now() - startTime) / duration;
                            if (progress < 1) { bubble.style.bottom = `${progress * 130}%`; bubble.style.transform = `translateX(${Math.sin(progress * 6) * 40 + (progress * drift)}px)`; requestAnimationFrame(animate); } else { bubble.remove(); }
                        }
                        requestAnimationFrame(animate);
                    }

                    function startMission() {
                        if (isOralReading) return;
                        if (missionStarted && spawnTimer) return;
                        missionStarted = true;
                        storyGate?.classList.add('opacity-0', 'pointer-events-none');
                        setTimeout(() => storyGate?.classList.add('hidden'), 300);
                        spawnTimer = setInterval(spawnBubble, 2000);
                        for (let index = 0; index < 3; index++) setTimeout(spawnBubble, index * 800);
                    }

                    startTimerButton?.addEventListener('click', startReadingTimer);
                    endTimerButton?.addEventListener('click', endReadingTimer);
                    startQuestionsButton?.addEventListener('click', () => {
                        if (isOralReading) {
                            victory();
                            return;
                        }

                        startMission();
                    });
                    if (missionStarted && !isOralReading) startMission();
                })();
            </script>
        @elseif ($assetPath)
            <main class="relative flex h-[calc(100vh-64px)] w-full items-center justify-center overflow-hidden bg-gradient-to-b from-surface via-surface-container-low to-surface p-8"><div class="glass-hud max-w-xl rounded-2xl border border-white/60 p-10 text-center shadow-xl"><div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-primary text-on-primary"><span class="material-symbols-outlined text-6xl">file_open</span></div><h1 class="font-headline text-4xl font-black text-on-surface">Uploaded Mission</h1><p class="mt-3 font-medium text-slate-500">{{ $assessment->instructions ?: 'Open the uploaded assessment material from your teacher.' }}</p><a class="mt-8 inline-flex rounded-lg bg-primary px-8 py-3 text-base font-bold text-white shadow-lg transition-colors hover:bg-primary-dim" href="{{ $assetUrl }}" target="_blank" rel="noopener">Open File</a></div></main>
        @else
            <main class="relative flex h-[calc(100vh-64px)] w-full items-center justify-center overflow-hidden bg-gradient-to-b from-surface via-surface-container-low to-surface p-8"><div class="glass-hud max-w-xl rounded-2xl border border-white/60 p-10 text-center shadow-xl"><div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-surface-container text-primary"><span class="material-symbols-outlined text-6xl">pending_actions</span></div><h1 class="font-headline text-4xl font-black text-on-surface">No Mission Data</h1><p class="mt-3 font-medium text-slate-500">Your teacher has published this assessment, but no manual questions or uploaded file were attached.</p></div></main>
        @endif
    </div>
</x-app-layout>
