import test from 'node:test';
import assert from 'node:assert/strict';

let loadNumber = 0;
async function loadPreferences(t, stored = null, blocked = false, initialDark = false) {
    const styles = new Map();
    const events = new Map();
    const audio = { muted: false };
    const classes = new Set();
    let value = stored;
    let prefersDark = initialDark;
    const globals = {
        window: {
            matchMedia: query => ({
                get matches() { return query.includes('prefers-color-scheme') ? prefersDark : false; },
                addEventListener(name, handler) { events.set(query, handler); },
            }),
            addEventListener: (name, handler) => events.set(name, handler),
            dispatchEvent() {},
        },
        document: {
            documentElement: {
                dataset: {},
                style: {
                    setProperty: (name, size) => styles.set(name, size),
                    colorScheme: '',
                },
                classList: {
                    toggle(name, enabled) { enabled ? classes.add(name) : classes.delete(name); },
                    contains(name) { return classes.has(name); },
                },
            },
            querySelectorAll: () => [audio], addEventListener() {},
        },
        localStorage: {
            getItem() { if (blocked) throw new Error('Storage unavailable'); return value; },
            setItem(key, next) { if (blocked) throw new Error('Storage unavailable'); value = next; },
        },
        CustomEvent: class { constructor(type, options) { this.type = type; this.detail = options.detail; } },
    };
    for (const [key, replacement] of Object.entries(globals)) {
        const descriptor = Object.getOwnPropertyDescriptor(globalThis, key);
        Object.defineProperty(globalThis, key, { value: replacement, writable: true, configurable: true });
        t.after(() => descriptor ? Object.defineProperty(globalThis, key, descriptor) : delete globalThis[key]);
    }
    const module = await import(new URL(`../../resources/js/ui-preferences.js?test=${++loadNumber}`, import.meta.url));
    return { ...module, styles, events, audio, classes, setSystemDark: next => { prefersDark = next; }, stored: () => JSON.parse(value) };
}

test('preferences without saved sizes use the compact assessment defaults', async t => {
    const { preferences, styles, audio } = await loadPreferences(t, JSON.stringify({ sound: false, motion: 'reduce' }));
    assert.deepEqual(preferences.readingSizes, { story: 16, questions: 18, answers: 16 });
    assert.equal(styles.get('--assessment-story-size'), '1rem');
    assert.equal(styles.get('--assessment-questions-size'), '1.125rem');
    assert.equal(styles.get('--assessment-answers-size'), '1rem');
    assert.equal(audio.muted, true);
    assert.equal(preferences.reducedMotion, true);
});

test('sliders save independently without losing sound or motion preferences', async t => {
    const { preferences, styles, stored } = await loadPreferences(t, JSON.stringify({ sound: false, motion: 'reduce' }));
    preferences.setReadingSize('story', '32');
    preferences.setReadingSize('questions', '34');
    preferences.setReadingSize('answers', '28');
    assert.deepEqual(stored(), { sound: false, motion: 'reduce', theme: 'light', readingSizes: { story: 32, questions: 34, answers: 28 } });
    assert.equal(styles.get('--assessment-story-size'), '2rem');
    preferences.set('sound', true);
    assert.equal(stored().readingSizes.answers, 28);
    preferences.resetReadingSizes();
    assert.deepEqual(stored(), { sound: true, motion: 'reduce', theme: 'light', readingSizes: { story: 16, questions: 18, answers: 16 } });
});

test('dark mode toggles the html class and saves with other preferences', async t => {
    const { preferences, classes, stored, default: controls } = await loadPreferences(t, JSON.stringify({ theme: 'dark', sound: false }));
    const ui = controls();

    assert.equal(preferences.darkMode, true);
    assert.equal(classes.has('dark'), true);
    assert.equal(ui.darkMode, true);

    ui.set('theme', 'light');

    assert.equal(classes.has('dark'), false);
    assert.equal(stored().theme, 'light');
    assert.equal(stored().sound, false);
});

test('first visit stays light even when the device prefers dark', async t => {
    const { preferences, classes, setSystemDark, events } = await loadPreferences(t, null, false, true);
    assert.equal(preferences.theme, 'light');
    assert.equal(preferences.darkMode, false);
    assert.equal(classes.has('dark'), false);
    assert.equal(document.documentElement.style.colorScheme, 'light');
    setSystemDark(false);
    events.get('(prefers-color-scheme: dark)')();
    setSystemDark(true);
    events.get('(prefers-color-scheme: dark)')();
    assert.equal(preferences.darkMode, false);
});

test('saved system preference still follows device changes', async t => {
    const { preferences, classes, setSystemDark, events } = await loadPreferences(t, '{"theme":"system"}', false, true);
    assert.equal(preferences.darkMode, true);
    setSystemDark(false);
    events.get('(prefers-color-scheme: dark)')();
    assert.equal(classes.has('dark'), false);
    assert.equal(preferences.theme, 'system');
});

test('invalid themes and cleared settings fall back to light on a dark device', async t => {
    const { preferences, classes, events } = await loadPreferences(t, '{"theme":"invalid"}', false, true);
    assert.equal(preferences.theme, 'light');
    preferences.set('theme', 'dark');
    assert.equal(classes.has('dark'), true);
    preferences.set('theme', 'invalid');
    assert.equal(preferences.theme, 'light');
    for (const newValue of ['{"theme":"dark"}', null, '{bad json', '{}']) {
        events.get('storage')({ key: 'pgaals-comfort', newValue });
        assert.equal(preferences.darkMode, newValue === '{"theme":"dark"}');
    }
});

test('blocked browser storage still opens in light mode and allows a manual dark toggle', async t => {
    const { preferences, classes } = await loadPreferences(t, null, true, true);
    assert.equal(preferences.theme, 'light');
    assert.equal(classes.has('dark'), false);
    preferences.set('theme', 'dark');
    assert.equal(classes.has('dark'), true);
});

test('saved sizes restore and invalid values cannot create broken CSS', async t => {
    const { preferences, styles } = await loadPreferences(t, JSON.stringify({ readingSizes: { story: 30, questions: 18, answers: 26 } }));
    assert.deepEqual(preferences.readingSizes, { story: 30, questions: 18, answers: 26 });
    preferences.setReadingSize('story', 999);
    preferences.setReadingSize('questions', -2);
    preferences.setReadingSize('answers', 'url(bad)');
    preferences.setReadingSize('__proto__', 20);
    assert.deepEqual(preferences.readingSizes, { story: 16, questions: 18, answers: 16 });
    assert.equal(styles.get('--assessment-story-size'), '1rem');
});

test('malformed or partial saved preferences fall back per field', async t => {
    const { preferences, events } = await loadPreferences(t, '{bad json');
    assert.deepEqual(preferences.readingSizes, { story: 16, questions: 18, answers: 16 });
    events.get('storage')({ key: 'pgaals-comfort', newValue: JSON.stringify({ readingSizes: { story: 28, questions: 33, answers: null } }) });
    assert.deepEqual(preferences.readingSizes, { story: 28, questions: 18, answers: 16 });
});

test('other-tab updates and removing preferences update all reading sizes', async t => {
    const { preferences, events, styles, default: controls } = await loadPreferences(t);
    const ui = controls();
    events.get('storage')({ key: 'pgaals-comfort', newValue: JSON.stringify({ sound: false, motion: 'reduce', theme: 'dark', readingSizes: { story: 16, questions: 18, answers: 16 } }) });
    ui.refresh();
    assert.deepEqual(ui.readingSizes, { story: 16, questions: 18, answers: 16 });
    assert.equal(styles.get('--assessment-answers-size'), '1rem');
    assert.equal(preferences.sound, false);
    assert.equal(preferences.darkMode, true);
    events.get('storage')({ key: 'pgaals-comfort', newValue: null });
    assert.deepEqual(preferences.readingSizes, { story: 16, questions: 18, answers: 16 });
});

test('size controls still work when browser storage is blocked', async t => {
    const { preferences, styles } = await loadPreferences(t, null, true);
    assert.doesNotThrow(() => preferences.setReadingSize('answers', 28));
    assert.equal(styles.get('--assessment-answers-size'), '1.75rem');
    assert.doesNotThrow(() => preferences.resetReadingSizes());
    assert.equal(preferences.readingSizes.answers, 16);
});
