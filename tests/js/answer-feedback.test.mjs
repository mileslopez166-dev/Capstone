import { test } from 'node:test';
import assert from 'node:assert/strict';
import answerFeedback from '../../resources/js/answer-feedback.js';

test('does not fetch until opened; hide and reopen reuse the answer', async () => {
    let calls = 0;
    global.window = { axios: { post: async () => { calls++; return { data: { answer: '<b>Plain text only</b>' } }; } } };
    const state = answerFeedback({ url: '/help' });
    assert.equal(calls, 0);
    await state.toggle();
    assert.equal(state.answer, '<b>Plain text only</b>');
    assert.equal(state.open, true);
    await state.toggle();
    assert.equal(state.open, false);
    await state.toggle();
    assert.equal(calls, 1);
});

test('prevents double clicks while request is in flight', async () => {
    let resolve;
    let calls = 0;
    global.window = { axios: { post: () => { calls++; return new Promise(done => { resolve = done; }); } } };
    const state = answerFeedback({ url: '/help' });
    const pending = state.toggle();
    await state.toggle();
    assert.equal(state.busy, true);
    assert.equal(calls, 1);
    resolve({ data: { answer: 'Explanation' } });
    await pending;
    assert.equal(state.busy, false);
});

test('failure preserves review and supports retry; expired session has useful message', async () => {
    global.window = { axios: { post: async () => { throw { response: { status: 419 } }; } } };
    const state = answerFeedback({ url: '/help' });
    await state.toggle();
    assert.match(state.error, /session expired/);
    assert.equal(state.open, true);
    assert.equal(state.busy, false);
    window.axios.post = async () => ({ data: { answer: 'Try rereading.' } });
    await state.toggle();
    assert.equal(state.error, '');
    assert.equal(state.answer, 'Try rereading.');
});

test('empty responses do not become cached feedback', async () => {
    global.window = { axios: { post: async () => ({ data: { answer: '' } }) } };
    const state = answerFeedback({ url: '/help' });
    await state.toggle();
    assert.ok(state.error);
    assert.equal(state.answer, '');
});
