@props(['panelId' => 'comfort-panel'])

<div class="comfort-controls" x-data="comfortControls" @pgaals:preferences.window="refresh()" @pgaals:teacher-ui.window="refresh()" @click.outside="open = false" @keydown.escape.window="if (open) { $event.preventDefault(); close(); }" data-no-click-sound>
    <button type="button" class="ui-icon-button" x-ref="trigger" @click="open = !open" :aria-expanded="open.toString()" aria-label="Sound and motion settings" title="Sound and motion settings" aria-controls="{{ $panelId }}">
        <span class="material-symbols-outlined" aria-hidden="true">tune</span>
    </button>
    <section id="{{ $panelId }}" class="comfort-panel" x-show="open" x-cloak aria-label="Sound and motion settings">
        <div class="comfort-heading"><strong>Display &amp; sound</strong><button type="button" class="ui-icon-button" @click="close()" aria-label="Close settings" title="Close settings"><span class="material-symbols-outlined" aria-hidden="true">close</span></button></div>
        <label class="comfort-option"><span><span class="material-symbols-outlined" aria-hidden="true" x-text="darkMode ? 'dark_mode' : 'light_mode'">dark_mode</span> Dark mode</span><input type="checkbox" role="switch" :checked="darkMode" @change="set('theme', $event.target.checked ? 'dark' : 'light')"></label>
        <label class="comfort-option"><span><span class="material-symbols-outlined" aria-hidden="true">volume_up</span> Sound</span><input type="checkbox" role="switch" :checked="sound" @change="set('sound', $event.target.checked)"></label>
        <label class="comfort-option" :title="systemReduced ? 'Reduced motion is enabled in your device settings' : ''"><span><span class="material-symbols-outlined" aria-hidden="true">motion_photos_off</span> Reduce motion</span><input type="checkbox" role="switch" :checked="reduced" :disabled="systemReduced" @change="set('motion', $event.target.checked ? 'reduce' : 'system')"></label>
        @if (auth()->user()?->isTeacher())
            <div class="comfort-teacher-ui">
                <label for="{{ $panelId }}-teacher-ui-choice">Teacher interface</label>
                <select id="{{ $panelId }}-teacher-ui-choice" :value="teacherUi" @change="setTeacherUi($event.target.value)">
                    <option value="current">Current bento layout</option>
                    <option value="classic">Previous layout</option>
                </select>
                <small>Choose how teacher pages are arranged.</small>
            </div>
        @endif
    </section>
</div>
