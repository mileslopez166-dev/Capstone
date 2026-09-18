@props(['question', 'questionCount'])

<div id="frog-pond-scene" class="frog-pond-scene" aria-hidden="true"></div>
<div class="frog-pond-fallback" aria-hidden="true"></div>

<section class="frog-interface" id="frog-flashcards-game" aria-label="Lily pad finish line game">
    <header class="frog-hud">
        <div class="frog-mission-name"><span class="material-symbols-outlined" aria-hidden="true">sports_score</span>Lily Pad Race</div>
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
                    <svg class="frog-target-fallback" viewBox="0 0 160 144" fill="none">
                        <ellipse cx="80" cy="119" rx="68" ry="25" fill="#226b71" opacity=".18"/>
                        <path d="M80 112 131 95A65 27 0 1 0 141 121Z" transform="translate(0 5)" fill="#2f7945"/>
                        <path d="M80 112 131 95A65 27 0 1 0 141 121Z" fill="#73bd59" stroke="#3a8d48" stroke-width="2"/>
                        <path d="m80 112-55-8m55 8-33-23m33 23 9-25m-9 25-49 13m49-13-8 25m8-25 42 19" stroke="#b3d77b" stroke-width="1.5" stroke-linecap="round"/>
                        <g fill="var(--target-color)" stroke="var(--target-deep)" stroke-width="1">
                            <ellipse cx="44" cy="96" rx="7" ry="5"/>
                            <ellipse cx="36" cy="102" rx="7" ry="5"/>
                            <ellipse cx="52" cy="102" rx="7" ry="5"/>
                            <ellipse cx="40" cy="109" rx="7" ry="5"/>
                            <ellipse cx="48" cy="109" rx="7" ry="5"/>
                        </g>
                        <circle cx="44" cy="103" r="5" fill="#fff3ba"/>
                    </svg>
                    <span class="frog-pad-ripple"></span>
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
        <div class="frog-course-trail"></div>
        <div class="frog-finish-line" id="frog-finish-line">
            <span class="frog-finish-banner">FINISH</span>
            <span class="frog-finish-checks"></span>
            <div class="frog-finish-pad" id="frog-finish-pad">
                <svg class="frog-finish-fallback" viewBox="0 0 160 50" fill="none">
                    <ellipse cx="80" cy="28" rx="76" ry="20" fill="#387e4f"/>
                    <path d="m80 24 65-13C117-4 8 4 4 23c-5 25 130 33 150 9Z" fill="#81bf58" stroke="#508d49" stroke-width="2"/>
                    <path d="m80 24-61-7m61 7-37 13m37-13 5-18m-5 18 55 10" stroke="#d0e493" stroke-width="2"/>
                </svg>
            </div>
        </div>
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

    <div class="frog-course-footer">
        <div class="frog-mission-progress">
            <span>To Finish</span>
            <div id="frog-progress-track" class="frog-progress-track" role="progressbar" aria-label="Correct answers toward the finish line" aria-valuemin="0" aria-valuemax="{{ $questionCount }}" aria-valuenow="0"><div id="frog-progress-bar"></div></div>
            <span id="frog-progress-count">0 / {{ $questionCount }}</span>
        </div>
        <p class="frog-race-status" id="frog-race-status" role="status" aria-live="polite"></p>
    </div>
</section>
<svg id="frog-jump-sprite" class="frog-jump-sprite" viewBox="0 0 260 230" fill="none" aria-hidden="true"></svg>
