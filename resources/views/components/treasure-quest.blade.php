@props(['student', 'questionCount'])

<div class="treasure-quest" data-scene-state="loading" data-answered="0" data-total="{{ $questionCount }}">
    <header class="treasure-heading">
        <h2><span class="material-symbols-outlined" aria-hidden="true">explore</span> Treasure Quest</h2>
        <span class="treasure-gems"><span class="material-symbols-outlined" aria-hidden="true">diamond</span> <strong id="treasure-gems">0</strong> gems</span>
    </header>
    <div class="treasure-map" aria-label="Island trail">
        <div id="treasure-scene" aria-hidden="true"></div>
        <div class="treasure-fallback" aria-hidden="true">
            <svg viewBox="0 0 900 320" preserveAspectRatio="xMidYMid slice"><path fill="#66cbc6" d="M0 0h900v320H0z"/><path fill="#e9cf8c" d="M80 205C-30 110 320 10 690 70s230 221-140 217S160 275 80 205Z"/><path fill="#8abe6c" d="M95 176C40 109 360 45 680 88s167 145-130 147S160 246 95 176Z"/><path fill="none" stroke="#f6e4ad" stroke-width="19" stroke-linecap="round" d="M240 200Q220 116 423 163T640 111"/></svg>
        </div>
        <div id="treasure-explorer" aria-hidden="true"><x-student-character :user="$student" /></div>
        @foreach (['A', 'B', 'C', 'D'] as $letter)
            <span class="treasure-map-label" data-chest-label="{{ $letter }}" aria-hidden="true"><span class="material-symbols-outlined treasure-fallback-chest">inventory_2</span>{{ $letter }}</span>
        @endforeach
        <span class="treasure-vault-label" aria-hidden="true">Treasure vault</span>
    </div>
    <div class="treasure-trail">
        <span id="treasure-progress-text">0 / {{ $questionCount }} explored</span>
        <progress id="treasure-progress" value="0" max="{{ max(1, $questionCount) }}" aria-label="Questions completed"></progress>
    </div>
    <section class="treasure-question" aria-labelledby="treasure-question-text">
        <p id="treasure-question-number">Question 1 / {{ $questionCount }}</p>
        <h3 id="treasure-question-text" tabindex="-1"></h3>
        <div class="treasure-answers">
            @foreach (['A', 'B', 'C', 'D'] as $letter)
                <button type="button" data-treasure-answer="{{ $letter }}" disabled>
                    <span class="treasure-answer-letter">{{ $letter }}</span>
                    <span data-treasure-answer-label></span>
                    <span class="material-symbols-outlined treasure-verdict" aria-hidden="true"></span>
                </button>
            @endforeach
        </div>
        <div class="treasure-round-footer">
            <p id="treasure-feedback" role="status" aria-live="polite"></p>
            <button id="treasure-next" type="button" hidden><span id="treasure-next-label">Continue</span><span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></button>
        </div>
    </section>
</div>
