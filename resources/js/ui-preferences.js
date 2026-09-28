const key = 'pgaals-comfort';
const systemMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');
const media = new Set();
const readingDefaults = { story: 16, questions: 18, answers: 16 };
const readingLimits = { story: [16, 32], questions: [18, 34], answers: [16, 28] };
const normalizeReadingSizes = value => Object.fromEntries(Object.entries(readingDefaults).map(([name, fallback]) => {
    const size = value?.[name];
    const [min, max] = readingLimits[name];
    return [name, Number.isInteger(size) && size >= min && size <= max && size % 2 === 0 ? size : fallback];
}));
let saved = {};
try { saved = JSON.parse(localStorage.getItem(key)) || {}; } catch { /* Storage may be unavailable. */ }

export const preferences = {
    sound: saved.sound !== false,
    motion: ['system', 'reduce'].includes(saved.motion) ? saved.motion : 'system',
    theme: ['system', 'light', 'dark'].includes(saved.theme) ? saved.theme : 'light',
    readingSizes: normalizeReadingSizes(saved.readingSizes),
    get reducedMotion() { return this.motion === 'reduce' || systemMotion.matches; },
    get darkMode() { return this.theme === 'dark' || (this.theme === 'system' && systemTheme.matches); },
    apply() {
        document.documentElement.dataset.reducedMotion = String(this.reducedMotion);
        document.documentElement.dataset.theme = this.darkMode ? 'dark' : 'light';
        document.documentElement.classList.toggle('dark', this.darkMode);
        document.documentElement.style.colorScheme = this.darkMode ? 'dark' : 'light';
        Object.entries(this.readingSizes).forEach(([name, size]) => {
            document.documentElement.style.setProperty(`--assessment-${name}-size`, `${size / 16}rem`);
        });
        document.querySelectorAll('audio, video').forEach(element => media.add(element));
        media.forEach(element => { element.muted = !this.sound; });
        window.dispatchEvent(new CustomEvent('pgaals:preferences', { detail: { sound: this.sound, reducedMotion: this.reducedMotion, theme: this.theme, darkMode: this.darkMode } }));
    },
    set(name, value) {
        if (name === 'sound') this.sound = Boolean(value);
        if (name === 'motion') this.motion = value === 'reduce' ? 'reduce' : 'system';
        if (name === 'theme') this.theme = ['light', 'dark', 'system'].includes(value) ? value : 'light';
        this.save();
    },
    setReadingSize(name, value) {
        if (!Object.hasOwn(readingDefaults, name)) return;
        this.readingSizes = normalizeReadingSizes({ ...this.readingSizes, [name]: Number(value) });
        this.save();
    },
    resetReadingSizes() { this.readingSizes = { ...readingDefaults }; this.save(); },
    save() {
        try { localStorage.setItem(key, JSON.stringify({ sound: this.sound, motion: this.motion, theme: this.theme, readingSizes: this.readingSizes })); } catch { /* Preferences still work for this page. */ }
        this.apply();
    },
    register(element) { media.add(element); element.muted = !this.sound; },
};

window.PgaalsPreferences = preferences;
preferences.apply();
systemMotion.addEventListener('change', () => preferences.apply());
systemTheme.addEventListener('change', () => preferences.apply());
document.addEventListener('play', event => preferences.register(event.target), true);
window.addEventListener('storage', event => {
    if (event.key !== key) return;
    try { saved = JSON.parse(event.newValue) || {}; } catch { saved = {}; }
    preferences.sound = saved.sound !== false;
    preferences.motion = saved.motion === 'reduce' ? 'reduce' : 'system';
    preferences.theme = ['light', 'dark', 'system'].includes(saved.theme) ? saved.theme : 'light';
    preferences.readingSizes = normalizeReadingSizes(saved.readingSizes);
    preferences.apply();
});

export default () => ({
    open: false,
    sound: preferences.sound,
    motion: preferences.motion,
    theme: preferences.theme,
    reduced: preferences.reducedMotion,
    darkMode: preferences.darkMode,
    systemReduced: systemMotion.matches,
    systemDark: systemTheme.matches,
    readingSizes: { ...preferences.readingSizes },
    refresh() { this.sound = preferences.sound; this.motion = preferences.motion; this.theme = preferences.theme; this.reduced = preferences.reducedMotion; this.darkMode = preferences.darkMode; this.systemReduced = systemMotion.matches; this.systemDark = systemTheme.matches; this.readingSizes = { ...preferences.readingSizes }; },
    set(name, value) { preferences.set(name, value); this.refresh(); },
    setReadingSize(name, value) { preferences.setReadingSize(name, value); this.refresh(); },
    resetReadingSizes() { preferences.resetReadingSizes(); this.refresh(); },
    close() { this.open = false; this.$refs.trigger.focus(); },
});
