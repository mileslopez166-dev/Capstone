import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/js/ml-prediction.js', import.meta.url), 'utf8');
function setup() {
    const fields = new Map();
    const panel = { hidden: true, querySelector(selector) {
        if (!fields.has(selector)) fields.set(selector, { hidden: false, textContent: '' });
        return fields.get(selector);
    } };
    let handler;
    vm.runInNewContext(source, { document: {
        addEventListener(name, callback) { assert.equal(name, 'assessment:graded'); handler = callback; },
        querySelectorAll() { return [panel]; },
    } });
    return { panel, send: result => handler({ detail: { ml_prediction: result } }),
        text: name => panel.querySelector(`[data-ml-field="${name}"]`).textContent };
}

test('shows independent confidence and held-out test accuracy', () => {
    const ui = setup();
    ui.send({ prediction: 'Instructional', confidence: 0.89,
        evaluation: { method: 'held_out_test', accuracy: 0.925, test_rows: 80 } });
    assert.equal(ui.panel.hidden, false);
    assert.equal(ui.text('confidence'), '89%');
    assert.equal(ui.text('accuracy'), '92.5%');
    assert.equal(ui.text('evaluation'), 'Held-out test: 80 records.');
});

test('missing and invalid percentages are unavailable, but zero is valid', () => {
    const ui = setup();
    for (const confidence of [null, undefined, '', 89, -1, NaN]) {
        ui.send({ prediction: 'Instructional', confidence });
        assert.equal(ui.text('confidence'), 'Not available');
        assert.equal(ui.text('accuracy'), 'Not available');
    }
    ui.send({ confidence: 0, evaluation: { method: 'held_out_test', accuracy: 0, test_rows: 10 } });
    assert.equal(ui.text('confidence'), '0%');
    assert.equal(ui.text('accuracy'), '0%');
});

test('training-data accuracy is never displayed as held-out accuracy', () => {
    const ui = setup();
    ui.send({ confidence: 0.9, evaluation: { method: 'full_dataset', accuracy: 0.9998, test_rows: 44497 } });
    assert.equal(ui.text('accuracy'), 'Not available');
});

test('a missing prediction clears previous values and shows the unavailable state', () => {
    const ui = setup();
    ui.send({ prediction: '<img src=x onerror=alert(1)>', confidence: 0.5 });
    assert.equal(ui.text('prediction'), '<img src=x onerror=alert(1)>');
    ui.send(null);
    assert.equal(ui.text('prediction'), '');
    assert.equal(ui.panel.querySelector('[data-ml-details]').hidden, true);
    assert.equal(ui.panel.querySelector('[data-ml-unavailable]').hidden, false);
});
