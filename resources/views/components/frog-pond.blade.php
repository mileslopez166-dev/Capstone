@props(['question', 'questionCount'])

@php
    $courseCount = max(1, (int) $questionCount);
    $logWidth = max(42, min(76, (int) floor(560 / $courseCount)));
@endphp

<div id="frog-pond-scene" class="frog-pond-scene" aria-hidden="true"></div>
<div class="frog-pond-fallback" aria-hidden="true"></div>

<section class="frog-interface" id="frog-flashcards-game" aria-label="Frog Adventure river crossing game">
    <header class="frog-hud">
        <div class="frog-mission-name"><span class="material-symbols-outlined" aria-hidden="true">waves</span>Frog Adventure</div>
        <div class="frog-hud-stats">
            <span id="frog-level-text">Question 1/{{ $questionCount }}</span>
            <span class="frog-score-chip"><span class="material-symbols-outlined" aria-hidden="true">forest</span><strong id="frog-score">0</strong><span>logs crossed</span></span>
        </div>
    </header>

    <div id="frog-question-card" class="frog-question-card">
        <h1 id="frog-question-text">{{ $question ? $question['text'] : 'Ready?' }}</h1>
        <span id="frog-feedback" class="sr-only" role="status" aria-live="polite"></span>
    </div>

    <div id="frog-answer-zone" class="frog-answer-zone">
        @foreach (['A', 'B', 'C', 'D'] as $letter)
            <button type="button" class="frog-answer" data-frog-answer="{{ $letter }}" aria-label="Answer {{ $letter }}">
                <span class="frog-answer-copy">
                    <span class="frog-answer-letter" data-frog-answer-circle>{{ $letter }}</span>
                    <span data-frog-answer-label>Answer {{ $letter }}</span>
                    <span class="frog-answer-verdict material-symbols-outlined" aria-hidden="true"></span>
                </span>
            </button>
        @endforeach
    </div>

    <div class="frog-stage" aria-hidden="true">
        <div class="frog-river-current"></div>
        <div class="frog-start-bank">
            <span>START</span>
            <i class="frog-start-log"></i>
        </div>
        <div class="frog-course-logs">
            @for ($index = 0; $index < $courseCount; $index++)
                @php
                    $x = 15 + (($index + 1) / ($courseCount + 1)) * 69;
                    $y = 14 + (($index % 3) * 20);
                @endphp
                <span
                    class="frog-safe-log"
                    data-frog-log-index="{{ $index }}"
                    style="--log-x: {{ number_format($x, 2, '.', '') }}%; --log-y: {{ $y }}%; --log-width: {{ $logWidth }}px;"
                ><i></i></span>
            @endfor
        </div>
        <span class="frog-water-target" id="frog-water-target"></span>
        <span class="frog-water-splash" id="frog-water-splash"></span>

        <div class="frog-finish-line" id="frog-finish-line">
            <span class="frog-finish-banner">FINISH</span>
            <span class="frog-finish-checks"></span>
            <div class="frog-finish-pad" id="frog-finish-pad"><i></i></div>
        </div>

        <div id="frog-character" class="frog-character-anchor">
            <svg class="frog-character-fallback" viewBox="0 0 260 230" fill="none">
                <ellipse cx="130" cy="206" rx="114" ry="19" fill="#174f55" opacity=".24"/>
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
            <span>River progress</span>
            <div id="frog-progress-track" class="frog-progress-track" role="progressbar" aria-label="Safe logs reached" aria-valuemin="0" aria-valuemax="{{ $questionCount }}" aria-valuenow="0"><div id="frog-progress-bar"></div></div>
            <span id="frog-progress-count">0 / {{ $questionCount }}</span>
        </div>
        <p class="frog-race-status" id="frog-race-status" role="status" aria-live="polite">Answer correctly to reach the next safe log.</p>
    </div>
</section>
<svg id="frog-jump-sprite" class="frog-jump-sprite" viewBox="0 0 260 230" fill="none" aria-hidden="true"></svg>
