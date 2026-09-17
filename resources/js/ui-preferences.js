const key = 'pgaals-comfort';
const systemMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const media = new Set();
let saved = {};
try { saved = JSON.parse(localStorage.getItem(key)) || {}; } catch { /* Storage may be unavailable. */ }

export const preferences = {
    sound: saved.sound !== false,
    motion: ['system', 'reduce'].includes(saved.motion) ? saved.motion : 'system',
    get reducedMotion() { return this.motion === 'reduce' || systemMotion.matches; },
    apply() {
        document.documentElement.dataset.reducedMotion = String(this.reducedMotion);
        document.querySelectorAll('audio, video').forEach(element => media.add(element));
        media.forEach(element => { element.muted = !this.sound; });
        window.dispatchEvent(new CustomEvent('pgaals:preferences', { detail: { sound: this.sound, reducedMotion: this.reducedMotion } }));
    },
    set(name, value) {
        if (name === 'sound') this.sound = Boolean(value);
        if (name === 'motion') this.motion = value === 'reduce' ? 'reduce' : 'system';
        try { localStorage.setItem(key, JSON.stringify({ sound: this.sound, motion: this.motion })); } catch { /* Preferences still work for this page. */ }
        this.apply();
    },
    register(element) { media.add(element); element.muted = !this.sound; },
};

window.PgaalsPreferences = preferences;
preferences.apply();
systemMotion.addEventListener('change', () => preferences.apply());
document.addEventListener('play', event => preferences.register(event.target), true);
window.addEventListener('storage', event => {
    if (event.key !== key) return;
    try { saved = JSON.parse(event.newValue) || {}; } catch { saved = {}; }
    preferences.sound = saved.sound !== false;
    preferences.motion = saved.motion === 'reduce' ? 'reduce' : 'system';
    preferences.apply();
});

export default () => ({
    open: false,
    sound: preferences.sound,
    motion: preferences.motion,
    reduced: preferences.reducedMotion,
    systemReduced: systemMotion.matches,
    refresh() { this.sound = preferences.sound; this.motion = preferences.motion; this.reduced = preferences.reducedMotion; this.systemReduced = systemMotion.matches; },
    set(name, value) { preferences.set(name, value); this.refresh(); },
    close() { this.open = false; this.$refs.trigger.focus(); },
});
