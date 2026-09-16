@props(['question', 'questionCount'])

<div id="frog-pond-scene" class="frog-pond-scene" aria-hidden="true"></div>
<div class="frog-pond-fallback" aria-hidden="true"></div>

<section class="frog-interface" id="frog-flashcards-game" aria-label="Flashcards frog game">
    <header class="frog-hud">
        <div class="frog-mission-name"><span class="material-symbols-outlined" aria-hidden="true">spa</span>Frog Pond</div>
        <div class="frog-hud-stats">
            <span id="frog-level-text">Question 1/{{ $questionCount }}</span>
            <span class="frog-score-chip"><span class="material-symbols-outlined" aria-hidden="true">stars</span><strong id="frog-score">0</strong><span>correct</span></span>
        </div>
    </header>

    <div id="frog-question-card" class="frog-question-card">
        <h1 id="frog-question-text">{{ $question ? $question['text'] : 'Ready?' }}</h1>
        <span id="frog-feedback" class="sr-only" role="status" aria-live="polite"></span>
    </div>

    <div id="frog-answer-zone" class="frog-answer-zone">
        @foreach (['A', 'B', 'C', 'D'] as $index => $letter)
            <button type="button" class="frog-answer" data-frog-answer="{{ $letter }}" aria-label="Answer {{ $letter }}">
                <span class="frog-target-visual" aria-hidden="true">
                    <svg class="frog-target-fallback" viewBox="0 0 160 112" fill="none">
                        <g stroke="#48586c" stroke-width="3" stroke-linecap="round">
                            <path d="m69 57-24 2-10 17m34-10-20 10-3 16m43-35 24 2 10 17m-34-10 20 10 3 16"/>
                            <path d="m70 38-7-17m26 17 8-17"/>
                        </g>
                        <g fill="#e9fbff" stroke="#9acbd8" stroke-width="2" class="frog-fallback-wings">
                            <ellipse cx="46" cy="42" rx="32" ry="13" transform="rotate(24 46 42)"/>
                            <ellipse cx="112" cy="42" rx="32" ry="13" transform="rotate(-24 112 42)"/>
                        </g>
                        <ellipse cx="79" cy="72" rx="18" ry="25" fill="var(--target-color)" stroke="var(--target-deep)" stroke-width="3"/>
                        <path d="M63 70h32M64 81h29" stroke="var(--target-deep)" stroke-width="4"/>
                        <ellipse cx="79" cy="44" rx="23" ry="21" fill="var(--target-color)" stroke="var(--target-deep)" stroke-width="3"/>
                        <ellipse cx="70" cy="42" rx="8" ry="10" fill="white"/><ellipse cx="89" cy="42" rx="8" ry="10" fill="white"/>
                        <circle cx="72" cy="44" r="4" fill="#26394a"/><circle cx="87" cy="44" r="4" fill="#26394a"/>
                        <path class="frog-target-proboscis" d="m79 53 3 16" stroke="#48586c" stroke-width="3" stroke-linecap="round"/>
                        <g class="frog-target-mouth">
                            <ellipse cx="79" cy="72" rx="22" ry="25" fill="var(--target-color)"/>
                            <ellipse cx="79" cy="72" rx="18" ry="21" fill="#442d43"/>
                            <ellipse cx="79" cy="84" rx="10" ry="4" fill="#e991a5"/>
                        </g>
                    </svg>
                </span>
                <span class="frog-answer-copy">
                    <span class="frog-answer-letter" data-frog-answer-circle>{{ $letter }}</span>
                    <span data-frog-answer-label>Answer {{ $letter }}</span>
                    <span class="frog-answer-verdict material-symbols-outlined" aria-hidden="true"></span>
                </span>
            </button>
        @endforeach
    </div>

    <div class="frog-stage" aria-hidden="true">
        <div id="frog-character" class="frog-character-anchor">
            <svg class="frog-character-fallback" viewBox="0 0 260 230" fill="none">
                <ellipse cx="130" cy="206" rx="114" ry="19" fill="#23886a" opacity=".25"/>
                <path d="M21 191C19 153 234 156 239 190c1 39-172 43-218 1Z" fill="#459d57" stroke="#237950" stroke-width="4"/>
                <g class="frog-character-body">
                <ellipse cx="72" cy="167" rx="35" ry="29" fill="#55af49"/><ellipse cx="188" cy="167" rx="35" ry="29" fill="#55af49"/>
                <ellipse cx="130" cy="143" rx="66" ry="60" fill="#81ce58" stroke="#469743" stroke-width="4"/>
                <ellipse cx="130" cy="158" rx="42" ry="37" fill="#e7f3a7"/>
                <ellipse cx="130" cy="105" rx="76" ry="47" fill="#81ce58" stroke="#469743" stroke-width="4"/>
                <circle cx="90" cy="64" r="30" fill="#81ce58" stroke="#469743" stroke-width="4"/><circle cx="169" cy="64" r="30" fill="#81ce58" stroke="#469743" stroke-width="4"/>
                <ellipse cx="93" cy="66" rx="20" ry="23" fill="#fffef3"/><ellipse cx="166" cy="66" rx="20" ry="23" fill="#fffef3"/>
                <ellipse cx="98" cy="69" rx="9" ry="13" fill="#233b30"/><ellipse cx="161" cy="69" rx="9" ry="13" fill="#233b30"/>
                <circle cx="101" cy="64" r="3" fill="white"/><circle cx="164" cy="64" r="3" fill="white"/>
                <path d="M99 111q31 27 62 0" stroke="#367541" stroke-width="5" stroke-linecap="round"/>
                <ellipse cx="81" cy="106" rx="12" ry="7" fill="#edbc7b"/><ellipse cx="178" cy="106" rx="12" ry="7" fill="#edbc7b"/>
                <path d="m86 161-8 29m95-29 8 29" stroke="#60b44c" stroke-width="17" stroke-linecap="round"/>
                <path d="m70 193 9-4 10 5m82 0 10-5 10 4" stroke="#81ce58" stroke-width="9" stroke-linecap="round"/>
                </g>
            </svg>
        </div>
    </div>

    <div class="frog-mission-progress">
        <span>Progress</span>
        <div id="frog-progress-track" class="frog-progress-track" role="progressbar" aria-label="Assessment progress" aria-valuemin="0" aria-valuemax="{{ $questionCount }}" aria-valuenow="0"><div id="frog-progress-bar"></div></div>
        <span id="frog-progress-count">0 / {{ $questionCount }}</span>
    </div>
</section>
<div class="frog-tongue" id="frog-tongue" aria-hidden="true"></div>
