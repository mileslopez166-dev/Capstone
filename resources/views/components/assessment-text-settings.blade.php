<div class="assessment-text-settings" x-data="comfortControls" @pgaals:preferences.window="refresh()" @click.outside="open = false" @keydown.escape.window="if (open) { $event.preventDefault(); close(); }" data-no-click-sound>
    <button type="button" class="ui-icon-button" x-ref="trigger" @click="open = !open" aria-expanded="false" :aria-expanded="open.toString()" aria-label="Adjust assessment text size" title="Adjust assessment text size" aria-controls="assessment-text-size-panel">
        <span class="material-symbols-outlined" aria-hidden="true">format_size</span>
    </button>
    <section id="assessment-text-size-panel" class="assessment-text-panel" x-show="open" x-cloak aria-label="Assessment text size">
        <div class="assessment-text-panel-heading">
            <strong>Text size</strong>
            <button type="button" class="ui-icon-button" @click="resetReadingSizes()" aria-label="Reset text sizes" title="Reset text sizes"><span class="material-symbols-outlined" aria-hidden="true">restart_alt</span></button>
            <button type="button" class="ui-icon-button" @click="close()" aria-label="Close text settings" title="Close text settings"><span class="material-symbols-outlined" aria-hidden="true">close</span></button>
        </div>
        <label class="assessment-size-option" for="assessment-story-size">
            <span>Story</span><output for="assessment-story-size" x-text="readingSizes.story + 'px'">22px</output>
            <input id="assessment-story-size" type="range" min="16" max="32" step="2" value="22" :value="readingSizes.story" :aria-valuetext="readingSizes.story + ' pixels'" @input="setReadingSize('story', $event.target.value)">
        </label>
        <label class="assessment-size-option" for="assessment-questions-size">
            <span>Questions</span><output for="assessment-questions-size" x-text="readingSizes.questions + 'px'">24px</output>
            <input id="assessment-questions-size" type="range" min="18" max="34" step="2" value="24" :value="readingSizes.questions" :aria-valuetext="readingSizes.questions + ' pixels'" @input="setReadingSize('questions', $event.target.value)">
        </label>
        <label class="assessment-size-option" for="assessment-answers-size">
            <span>Answers</span><output for="assessment-answers-size" x-text="readingSizes.answers + 'px'">18px</output>
            <input id="assessment-answers-size" type="range" min="16" max="28" step="2" value="18" :value="readingSizes.answers" :aria-valuetext="readingSizes.answers + ' pixels'" @input="setReadingSize('answers', $event.target.value)">
        </label>
    </section>
</div>
