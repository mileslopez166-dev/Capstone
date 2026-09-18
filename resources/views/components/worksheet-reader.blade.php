@props(['worksheet', 'config', 'readonly' => false])
<section class="worksheet-reader" data-worksheet-reader data-readonly="{{ $readonly ? 'true' : 'false' }}" data-step="{{ $readonly ? 'answer' : 'read' }}" aria-label="Worksheet book">
    <script type="application/json" data-worksheet-config>{!! json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <div class="worksheet-reader-heading">
        <div><p class="worksheet-kicker">WORKSHEET {{ $worksheet['number'] }}</p><h2>{{ $readonly ? 'Your submitted work' : 'One part at a time' }}</h2></div>
        @unless ($readonly)
            <div class="worksheet-stages" role="group" aria-label="Worksheet step">
                <button type="button" data-book-step="read" aria-pressed="true"><span class="material-symbols-outlined" aria-hidden="true">auto_stories</span>1. Read</button>
                <button type="button" data-book-step="answer" aria-pressed="false"><span class="material-symbols-outlined" aria-hidden="true">edit_note</span>2. Answer</button>
            </div>
        @endunless
    </div>
    <div class="worksheet-toolbar">
        <div class="worksheet-tools" role="group" aria-label="Page navigation">
            <button type="button" class="worksheet-icon" data-book-prev title="Previous part" aria-label="Previous part"><span class="material-symbols-outlined">chevron_left</span></button>
            <label class="sr-only" for="worksheet-part">Worksheet part</label><select id="worksheet-part" data-book-part>@foreach ($worksheet['pages'] as $i => $page)<option value="{{ $i }}">Part {{ $page['part'] }} of {{ count($worksheet['pages']) }}</option>@endforeach</select>
            <button type="button" class="worksheet-icon" data-book-next title="Next part" aria-label="Next part"><span class="material-symbols-outlined">chevron_right</span></button>
        </div>
        <div class="worksheet-tools" role="group" aria-label="Worksheet text size">
            <button type="button" class="worksheet-icon" data-book-font="-" title="Smaller worksheet text" aria-label="Smaller worksheet text"><span class="material-symbols-outlined" aria-hidden="true">text_decrease</span></button>
            <output data-book-font-label>22px</output>
            <button type="button" class="worksheet-icon" data-book-font="+" title="Larger worksheet text" aria-label="Larger worksheet text"><span class="material-symbols-outlined" aria-hidden="true">text_increase</span></button>
        </div>
        @unless ($readonly)<button type="button" class="worksheet-icon" data-book-table-open title="Multiplication table - teacher approval" aria-label="Multiplication table - teacher approval"><span class="material-symbols-outlined" aria-hidden="true">grid_on</span></button>@endunless
        <details class="worksheet-settings" data-book-settings>
            <summary class="worksheet-icon" title="Reading settings" aria-label="Reading settings"><span class="material-symbols-outlined" aria-hidden="true">tune</span></summary>
            <div class="worksheet-settings-panel">
                <div><strong>Reading settings</strong><button class="worksheet-icon" type="button" data-book-reset-settings title="Reset reading settings" aria-label="Reset reading settings"><span class="material-symbols-outlined" aria-hidden="true">restart_alt</span></button><button class="worksheet-icon" type="button" data-book-close-settings title="Close reading settings" aria-label="Close reading settings"><span class="material-symbols-outlined" aria-hidden="true">close</span></button></div>
                <label for="worksheet-reading-size">Worksheet text <output data-book-setting-reading>22px</output></label><input id="worksheet-reading-size" data-book-reading-size type="range" min="16" max="32" step="2" value="22">
                <label for="worksheet-answer-size">Answer text <output data-book-setting-size>18px</output></label><input id="worksheet-answer-size" data-book-answer-size type="range" min="16" max="28" step="2" value="18">
                <label for="worksheet-spacing">Line spacing <output data-book-setting-spacing>1.7</output></label><input id="worksheet-spacing" data-book-spacing type="range" min="1.4" max="2" step="0.1" value="1.7">
                <label for="worksheet-zoom">Original page zoom <output data-book-setting-zoom>100%</output></label><input id="worksheet-zoom" data-book-zoom-range type="range" min="100" max="300" step="25" value="100">
            </div>
        </details>
    </div>
    <div class="worksheet-reader-progress"><p class="worksheet-save-state" data-book-status role="status" aria-live="polite">{{ $readonly ? 'Submitted work' : 'Ready when you are' }}</p><span data-book-completion></span><progress data-book-progress max="{{ count($worksheet['pages']) }}" value="0" aria-label="Parts answered"></progress></div>
    <div class="worksheet-workspace">
        <div class="worksheet-reference">
            <h3 class="worksheet-area-title"><span class="material-symbols-outlined" aria-hidden="true">menu_book</span>Worksheet <span data-book-page-label>Part 1</span></h3>
            <div class="worksheet-reading" data-book-reading>
                @foreach ($worksheet['pages'] as $pageIndex => $page)<x-worksheet-text :worksheet="$worksheet" :page="$page" :page-index="$pageIndex" :readonly="$readonly" />@endforeach
            </div>
            <details class="worksheet-original" data-book-original>
                <summary><span class="material-symbols-outlined" aria-hidden="true">draw</span>Original page &amp; drawing</summary>
                <div class="worksheet-tools worksheet-original-zoom" role="group" aria-label="Original page zoom">
                    <button type="button" class="worksheet-icon" data-book-zoom="-" title="Zoom out" aria-label="Zoom out"><span class="material-symbols-outlined" aria-hidden="true">zoom_out</span></button>
                    <output data-book-zoom-label>100%</output>
                    <button type="button" class="worksheet-icon" data-book-zoom="+" title="Zoom in" aria-label="Zoom in"><span class="material-symbols-outlined" aria-hidden="true">zoom_in</span></button>
                </div>
            @unless ($readonly)
            <div class="worksheet-tools worksheet-drawing-tools" role="group" aria-label="Drawing tools" data-no-click-sound data-book-answer-only>
                <button type="button" class="worksheet-icon" data-book-tool="read" aria-pressed="true" title="Read and scroll" aria-label="Read and scroll"><span class="material-symbols-outlined">pan_tool</span></button>
                <button type="button" class="worksheet-icon" data-book-tool="draw" aria-pressed="false" title="Draw on worksheet" aria-label="Draw on worksheet"><span class="material-symbols-outlined">edit</span></button>
                <button type="button" class="worksheet-icon" data-book-undo title="Undo last stroke" aria-label="Undo last stroke"><span class="material-symbols-outlined">undo</span></button>
                <button type="button" class="worksheet-icon" data-book-clear title="Clear this page's drawing" aria-label="Clear this page's drawing"><span class="material-symbols-outlined">ink_eraser</span></button>
                @foreach (['#174d97' => 'Blue ink', '#252c35' => 'Black ink', '#d73742' => 'Red ink'] as $color => $label)<button type="button" class="worksheet-swatch" data-book-color="{{ $color }}" style="--ink: {{ $color }}" title="{{ $label }}" aria-label="{{ $label }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"></button>@endforeach
                <label class="worksheet-pen-size">Pen <input data-book-width type="range" min="1" max="12" value="3" aria-label="Pen width"></label>
            </div>
            @endunless
            <div class="worksheet-viewport" data-book-viewport tabindex="0" aria-label="Worksheet page">
                <div class="worksheet-paper" data-book-paper>
                    <img data-book-image src="{{ route('worksheets.image', [$worksheet['number'], 1]) }}" width="{{ $worksheet['pages'][0]['width'] }}" height="{{ $worksheet['pages'][0]['height'] }}" alt="Worksheet {{ $worksheet['number'] }}, Part 1">
                    <canvas data-book-canvas aria-label="Drawing layer" role="img"></canvas>
                </div>
            </div>
            </details>
        </div>
        <section class="worksheet-response" data-book-answer-only aria-label="Answer workspace">
            <details class="worksheet-legacy-answers" data-book-legacy hidden><summary>Earlier saved answers</summary><div data-book-answers class="worksheet-answer-list"></div></details>
            <details class="worksheet-extra-notes" data-book-notes>
                <summary class="worksheet-area-title"><span class="material-symbols-outlined" aria-hidden="true">edit_note</span>{{ $readonly ? 'Saved notes' : 'Additional notes' }} <span data-book-response-part>Part 1</span></summary>
                <label class="worksheet-working-label" for="worksheet-response">{{ $readonly ? 'Working & notes' : 'Your working & notes' }}</label><textarea id="worksheet-response" data-book-text rows="5" maxlength="10000" @readonly($readonly)></textarea>
            </details>
            @unless ($readonly)<div class="worksheet-answer-footer"><button type="button" class="ui-button" data-book-continue><span data-book-continue-label>Next Part</span><span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></button></div>@endunless
        </section>
    </div>
    <template data-book-answer-template>
        <div class="worksheet-answer-row">
            <label class="worksheet-item-label">Item<input data-answer-label type="text" maxlength="40" @readonly($readonly)></label>
            <label class="worksheet-item-answer">Answer<textarea data-answer-value rows="2" maxlength="2000" @readonly($readonly)></textarea></label>
            @unless ($readonly)<button type="button" class="worksheet-icon" data-answer-remove title="Remove answer" aria-label="Remove answer"><span class="material-symbols-outlined" aria-hidden="true">delete</span></button>@endunless
        </div>
    </template>
    <div class="worksheet-actions">
        @unless ($readonly)
            <button class="ui-button" type="button" data-book-start-answer><span class="material-symbols-outlined" aria-hidden="true">edit_note</span>Ready to Answer</button>
            <button class="ui-button ui-button-secondary" type="button" data-book-save><span class="material-symbols-outlined" aria-hidden="true">save</span>Save Progress</button>
            <button class="ui-button" type="button" data-book-submit data-book-answer-only><span class="material-symbols-outlined" aria-hidden="true">task_alt</span>Submit Worksheet</button>
        @endunless
    </div>
    @unless ($readonly)<x-worksheet-table />@endunless
    <noscript><p class="worksheet-error">JavaScript is required to answer and save this worksheet.</p></noscript>
</section>
