@props(['student', 'questionCount', 'passage' => null, 'storyTitle' => 'Story'])

<div class="reading-rally" data-total="{{ $questionCount }}" data-completed="0" data-correct="0">
    <header class="rally-heading">
        <h2><span class="material-symbols-outlined" aria-hidden="true">sports_motorsports</span>Reading Rally</h2>
        <span class="rally-correct-count"><strong id="rally-correct">0</strong> / {{ $questionCount }} correct</span>
    </header>
    <div class="rally-layout">
        <div class="rally-adventure">
            <div class="rally-stage" role="img" aria-label="Race track with your red car and three rival cars">
                <div class="rally-fallback" aria-hidden="true">
                    <div class="rally-fallback-road">
                        @foreach (['teal', 'gold', 'violet', 'player'] as $car)
                            <img class="rally-fallback-car rally-car-{{ $car }}" src="{{ asset('images/games/rally-car.svg') }}" alt="" draggable="false">
                        @endforeach
                    </div>
                </div>
                <div id="rally-scene" aria-hidden="true"></div>
                <div class="rally-hud" aria-hidden="true">
                    <div><span>POSITION</span><strong data-rally-position>4th</strong><small> / 4</small></div>
                    <div class="rally-driver"><x-student-character :user="$student" /><span>YOU</span></div>
                    <div class="rally-distance"><span>RACE</span><strong data-rally-distance>0%</strong></div>
                </div>
                <div class="rally-banner" data-rally-banner aria-hidden="true"><span class="material-symbols-outlined">sports_motorsports</span><strong>Ready to race</strong></div>
                <div class="rally-finish-flag" aria-hidden="true"></div>
            </div>
            <div class="rally-race-status">
                <p id="rally-mission-status" role="status">At the starting line.</p>
                <span id="rally-progress-text">0 / {{ $questionCount }} questions</span>
                <progress id="rally-progress" value="0" max="{{ max(1, $questionCount) }}" aria-label="Race progress"></progress>
            </div>
        </div>
        <section class="rally-question" aria-labelledby="rally-question-text">
            <p id="rally-question-number">Question 1 / {{ $questionCount }}</p>
            <h3 id="rally-question-text" tabindex="-1"></h3>
            @if (filled($passage))
                <details class="rally-passage">
                    <summary><span class="material-symbols-outlined" aria-hidden="true">menu_book</span>{{ $storyTitle }}</summary>
                    <div>{{ $passage }}</div>
                </details>
            @endif
            <div class="rally-answers">
                @foreach (['A', 'B', 'C', 'D'] as $letter)
                    <button type="button" data-rally-answer="{{ $letter }}" disabled>
                        <span class="rally-answer-letter">{{ $letter }}</span>
                        <span data-rally-answer-label></span>
                        <span class="material-symbols-outlined rally-verdict" aria-hidden="true"></span>
                    </button>
                @endforeach
            </div>
            <div class="rally-round-footer">
                <p id="rally-feedback" role="status" aria-live="polite"></p>
                <button id="rally-next" type="button" hidden><span id="rally-next-label">Next question</span><span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></button>
            </div>
        </section>
    </div>
</div>
