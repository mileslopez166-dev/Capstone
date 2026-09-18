import test from 'node:test';
import assert from 'node:assert/strict';

let loadNumber = 0;
async function loadPreferences(t, stored = null, blocked = false) {
    const styles = new Map();
    const events = new Map();
    const audio = { muted: false };
    let value = stored;
    const globals = {
        window: {
            matchMedia: () => ({ matches: false, addEventListener() {} }),
            addEventListener: (name, handler) => events.set(name, handler),
            dispatchEvent() {},
        },
        document: {
            documentElement: { dataset: {}, style: { setProperty: (name, size) => styles.set(name, size) } },
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
    return { ...module, styles, events, audio, stored: () => JSON.parse(value) };
}

test('existing preferences keep the larger default reading sizes', async t => {
    const { preferences, styles, audio } = await loadPreferences(t, JSON.stringify({ sound: false, motion: 'reduce' }));
    assert.deepEqual(preferences.readingSizes, { story: 22, questions: 24, answers: 18 });
    assert.equal(styles.get('--assessment-story-size'), '1.375rem');
    assert.equal(styles.get('--assessment-questions-size'), '1.5rem');
    assert.equal(styles.get('--assessment-answers-size'), '1.125rem');
    assert.equal(audio.muted, true);
    assert.equal(preferences.reducedMotion, true);
});

test('sliders save independently without losing sound or motion preferences', async t => {
    const { preferences, styles, stored } = await loadPreferences(t, JSON.stringify({ sound: false, motion: 'reduce' }));
    preferences.setReadingSize('story', '32');
    preferences.setReadingSize('questions', '34');
    preferences.setReadingSize('answers', '28');
    assert.deepEqual(stored(), { sound: false, motion: 'reduce', readingSizes: { story: 32, questions: 34, answers: 28 } });
    assert.equal(styles.get('--assessment-story-size'), '2rem');
    preferences.set('sound', true);
    assert.equal(stored().readingSizes.answers, 28);
    preferences.resetReadingSizes();
    assert.deepEqual(stored(), { sound: true, motion: 'reduce', readingSizes: { story: 22, questions: 24, answers: 18 } });
});

test('saved sizes restore and invalid values cannot create broken CSS', async t => {
    const { preferences, styles } = await loadPreferences(t, JSON.stringify({ readingSizes: { story: 30, questions: 18, answers: 26 } }));
    assert.deepEqual(preferences.readingSizes, { story: 30, questions: 18, answers: 26 });
    preferences.setReadingSize('story', 999);
    preferences.setReadingSize('questions', -2);
    preferences.setReadingSize('answers', 'url(bad)');
    preferences.setReadingSize('__proto__', 20);
    assert.deepEqual(preferences.readingSizes, { story: 22, questions: 24, answers: 18 });
    assert.equal(styles.get('--assessment-story-size'), '1.375rem');
});

test('malformed or partial saved preferences fall back per field', async t => {
    const { preferences, events } = await loadPreferences(t, '{bad json');
    assert.deepEqual(preferences.readingSizes, { story: 22, questions: 24, answers: 18 });
    events.get('storage')({ key: 'pgaals-comfort', newValue: JSON.stringify({ readingSizes: { story: 28, questions: 33, answers: null } }) });
    assert.deepEqual(preferences.readingSizes, { story: 28, questions: 24, answers: 18 });
});

test('other-tab updates and removing preferences update all reading sizes', async t => {
    const { preferences, events, styles, default: controls } = await loadPreferences(t);
    const ui = controls();
    events.get('storage')({ key: 'pgaals-comfort', newValue: JSON.stringify({ sound: false, motion: 'reduce', readingSizes: { story: 16, questions: 18, answers: 16 } }) });
    ui.refresh();
    assert.deepEqual(ui.readingSizes, { story: 16, questions: 18, answers: 16 });
    assert.equal(styles.get('--assessment-answers-size'), '1rem');
    assert.equal(preferences.sound, false);
    events.get('storage')({ key: 'pgaals-comfort', newValue: null });
    assert.deepEqual(preferences.readingSizes, { story: 22, questions: 24, answers: 18 });
});

test('size controls still work when browser storage is blocked', async t => {
    const { preferences, styles } = await loadPreferences(t, null, true);
    assert.doesNotThrow(() => preferences.setReadingSize('answers', 28));
    assert.equal(styles.get('--assessment-answers-size'), '1.75rem');
    assert.doesNotThrow(() => preferences.resetReadingSizes());
    assert.equal(preferences.readingSizes.answers, 18);
});
