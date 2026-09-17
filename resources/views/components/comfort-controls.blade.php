<div class="comfort-controls" x-data="comfortControls" @pgaals:preferences.window="refresh()" @click.outside="open = false" @keydown.escape.window="if (open) { $event.preventDefault(); close(); }" data-no-click-sound>
    <button type="button" class="ui-icon-button" x-ref="trigger" @click="open = !open" :aria-expanded="open.toString()" aria-label="Sound and motion settings" title="Sound and motion settings" aria-controls="comfort-panel">
        <span class="material-symbols-outlined" aria-hidden="true">tune</span>
    </button>
    <section id="comfort-panel" class="comfort-panel" x-show="open" x-cloak aria-label="Sound and motion settings">
        <div class="comfort-heading"><strong>Sound &amp; motion</strong><button type="button" class="ui-icon-button" @click="close()" aria-label="Close settings" title="Close settings"><span class="material-symbols-outlined" aria-hidden="true">close</span></button></div>
        <label class="comfort-option"><span><span class="material-symbols-outlined" aria-hidden="true">volume_up</span> Sound</span><input type="checkbox" role="switch" :checked="sound" @change="set('sound', $event.target.checked)"></label>
        <label class="comfort-option" :title="systemReduced ? 'Reduced motion is enabled in your device settings' : ''"><span><span class="material-symbols-outlined" aria-hidden="true">motion_photos_off</span> Reduce motion</span><input type="checkbox" role="switch" :checked="reduced" :disabled="systemReduced" @change="set('motion', $event.target.checked ? 'reduce' : 'system')"></label>
    </section>
</div>
