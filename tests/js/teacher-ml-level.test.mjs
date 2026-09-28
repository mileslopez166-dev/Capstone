import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/js/teacher-ml-level.js', import.meta.url), 'utf8');
const flush = () => new Promise(resolve => setImmediate(resolve));
function setup(fetch, count = 1) {
    const panels = Array.from({ length: count }, (_, i) => {
        const fields = new Map();
        return { dataset: { url: `/teacher/students/${i}/ml-level` }, setAttribute() {},
            querySelector(selector) {
                if (!fields.has(selector)) fields.set(selector, { textContent: '', hidden: false,
                    addEventListener(name, handler) { this.click = handler; } });
                return fields.get(selector);
            },
        };
    });
    vm.runInNewContext(source, { fetch, AbortController, setTimeout, clearTimeout, window: {},
        document: { querySelectorAll: () => panels } });
    return panels;
}
const ready = { status: 'ready', assessment_count: 2, overall_percentage: 75,
    prediction: { prediction: 'Instructional', confidence: 0.88 } };
const response = body => ({ ok: true, json: async () => body });

test('renders aggregate score and model confidence as separate values', async () => {
    const [panel] = setup(async () => response(ready));
    await flush();
    assert.equal(panel.querySelector('[data-overall-level]').textContent, 'Instructional');
    assert.equal(panel.querySelector('[data-overall-score]').textContent, 'Overall score: 75%');
    assert.equal(panel.querySelector('[data-overall-confidence]').textContent, 'Model confidence: 88%');
    assert.match(panel.querySelector('[data-overall-basis]').textContent, /2 assessments/);
    assert.equal(panel.querySelector('[data-overall-retry]').hidden, true);
});

test('unavailable estimate can be retried without a fabricated level', async () => {
    let calls = 0;
    const [panel] = setup(async () => ++calls === 1 ? { ok: false } : response(ready));
    await flush();
    assert.equal(panel.querySelector('[data-overall-level]').textContent, 'ML estimate unavailable');
    assert.equal(panel.querySelector('[data-overall-retry]').hidden, false);
    panel.querySelector('[data-overall-retry]').click();
    await flush();
    assert.equal(panel.querySelector('[data-overall-level]').textContent, 'Instructional');
});

test('empty and disabled states do not show confidence or a level', async () => {
    for (const status of ['no_data', 'disabled']) {
        const [panel] = setup(async () => response({ status, assessment_count: 0, overall_percentage: null, prediction: null }));
        await flush();
        assert.equal(panel.querySelector('[data-overall-confidence]').textContent, '');
        assert.equal(panel.querySelector('[data-overall-score]').textContent, '');
        assert.equal(panel.querySelector('[data-overall-retry]').hidden, true);
    }
});

test('roster limits simultaneous model requests to two', async () => {
    const pending = [];
    const panels = setup(() => new Promise(resolve => pending.push(resolve)), 5);
    assert.equal(pending.length, 2);
    pending.shift()(response(ready));
    await flush();
    assert.equal(pending.length, 2);
    while (pending.length) { pending.shift()(response(ready)); await flush(); }
    assert.ok(panels.every(panel => panel.dataset.loading === 'false'));
});
