<div class="numeracy-mode" data-numeracy-mode hidden>
    <div class="numeracy-mode-switch" role="group" aria-label="Answer mode">
        <button type="button" data-numeracy-view="game" aria-pressed="true"><span class="material-symbols-outlined" aria-hidden="true">sports_esports</span>Games</button>
        <button type="button" data-numeracy-view="worksheet" aria-pressed="false"><span class="material-symbols-outlined" aria-hidden="true">menu_book</span>Worksheet</button>
    </div>
    <label class="sr-only" for="numeracy-game-select">Choose game</label>
    <select id="numeracy-game-select" data-numeracy-select aria-label="Choose game"></select>
</div>
<section class="numeracy-game" data-numeracy-game hidden aria-label="Numeracy game">
    <header class="numeracy-game-header">
        <h3 data-ng-title></h3>
        <span class="numeracy-game-score"><span class="material-symbols-outlined" aria-hidden="true">stars</span><strong data-ng-score>0</strong></span>
    </header>
    <div class="numeracy-game-progress"><span data-ng-counter></span><progress data-ng-progress value="0" max="1" aria-label="Game questions answered"></progress></div>
    <div class="numeracy-scene" data-ng-scene>
        <svg class="numeracy-rocket-world" viewBox="0 0 800 240" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
            <path d="M55 25v10m-5-5h10m120 76v12m-6-6h12m517-55v12m-6-6h12m-330-34v9m-4-4h8m210 153v10m-5-5h10" stroke="#f8dfa2" stroke-width="2"/>
            <circle cx="113" cy="62" r="2" fill="#fff"/><circle cx="587" cy="28" r="2" fill="#fff"/><circle cx="458" cy="89" r="2" fill="#fff"/><circle cx="251" cy="166" r="2" fill="#fff"/>
            <ellipse cx="735" cy="193" rx="108" ry="37" fill="none" stroke="#97c8ed" stroke-width="7" transform="rotate(-25 735 193)"/><circle cx="735" cy="193" r="52" fill="#597fb3"/><path d="M695 163q32 29 82 26m-89-4q30 31 87 25" fill="none" stroke="#7096c8" stroke-width="9"/>
            <path d="M0 234q80-30 160 0t160 0 160 0 160 0 160 0v30H0Z" fill="#b9d9f2"/>
        </svg>
        <div class="numeracy-rocket" data-ng-rocket aria-hidden="true">
            <svg viewBox="0 0 110 180"><path class="numeracy-exhaust" d="m39 126 16 48 17-48" fill="#ffb942"/><path d="m46 124 9 32 9-32" fill="#fff2a9"/><path d="m35 88-20 35 3 22 27-24m32-33 19 35-3 22-25-24" fill="#f26673" stroke="#783a65" stroke-width="2"/><path d="M55 6C29 31 27 91 36 130h39C85 88 83 31 55 6Z" fill="#f5fcff" stroke="#9bbcd4" stroke-width="2"/><path d="M55 6C43 17 36 33 34 46h43C73 29 66 17 55 6Z" fill="#ec5d75"/><circle cx="55" cy="68" r="18" fill="#395a7b"/><circle cx="55" cy="68" r="13" fill="#86d9e7"/><path d="m50 57-6 17" stroke="#e8ffff" stroke-width="4"/><path d="M36 119h39v11H36Z" fill="#56778e"/></svg>
        </div>
        <div class="numeracy-mission-number"><span data-ng-prompt></span><strong data-ng-number></strong><span data-ng-condition></span></div>
        <div class="numeracy-puzzle-number" data-ng-slots></div>
        <svg class="numeracy-archer-world" viewBox="0 0 800 240" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><path d="M0 120q120-80 260 0t270 0 270 0v120H0Z" fill="#b1d698"/><path d="M0 168q120-60 280 0t250 0 270 0v72H0Z" fill="#6ba377"/><path d="M0 220q180-40 400 0t400 0v20H0Z" fill="#3f775e"/><path d="M26 124v-76m-16 39 16-31 20 31m-29-16 9-33 18 34m691 57V27m-27 56 27-48 32 49m-43-15 11-47 23 47" fill="#4f9d7c" stroke="#40745d" stroke-width="8" stroke-linejoin="round"/><path d="M80 161h640m-600-14v41m68-41v41m68-41v41m68-41v41m68-41v41m68-41v41m68-41v41m68-41v41m68-41v41" stroke="#a68760" stroke-width="9"/></svg>
        <div class="numeracy-targets" data-ng-targets role="group" aria-label="Divisor targets"></div>
        <svg class="numeracy-bow" viewBox="0 0 120 45" aria-hidden="true"><path d="M10 37q50-66 100 0" fill="none" stroke="#845431" stroke-width="6"/><path d="m10 37 50-8 50 8" fill="none" stroke="#faf4d7" stroke-width="2"/><path d="M60 43V4m-6 9 6-9 6 9" fill="none" stroke="#294951" stroke-width="3"/></svg>
    </div>
    <div class="numeracy-game-body">
        <div class="numeracy-choices" data-ng-choices role="group" aria-label="Answer choices"></div>
        <label class="numeracy-reason" data-ng-reason-label hidden>Explain your answer<textarea data-ng-reason rows="2" maxlength="2000"></textarea></label>
        <p class="numeracy-feedback" data-ng-feedback role="status" aria-live="polite"></p>
        <p class="numeracy-hint" data-ng-hint hidden></p>
        <footer class="numeracy-game-actions">
            <button type="button" class="worksheet-icon" data-ng-prev title="Previous question" aria-label="Previous question"><span class="material-symbols-outlined" aria-hidden="true">arrow_back</span></button>
            <button type="button" class="numeracy-hint-button" data-ng-hint-button aria-expanded="false"><span class="material-symbols-outlined" aria-hidden="true">lightbulb</span>Hint</button>
            <button type="button" class="ui-button" data-ng-check><span class="material-symbols-outlined" aria-hidden="true">check</span><span data-ng-check-label>Check answer</span></button>
            <button type="button" class="ui-button" data-ng-next hidden><span data-ng-next-label>Next question</span><span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></button>
        </footer>
    </div>
    <div class="numeracy-game-summary" data-ng-summary hidden tabindex="-1">
        <span class="material-symbols-outlined" aria-hidden="true">emoji_events</span><h4 data-ng-summary-title>Round complete</h4><p data-ng-summary-text></p>
        <button type="button" class="ui-button" data-ng-return><span class="material-symbols-outlined" aria-hidden="true">menu_book</span>Back to worksheet</button>
    </div>
</section>
