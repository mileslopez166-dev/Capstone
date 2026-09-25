import test from 'node:test';
import assert from 'node:assert/strict';
import assessmentWordHelp from '../../resources/js/assessment-word-help.js';

function helper(post) {
    globalThis.window = { axios: { post } };
    return Object.assign(assessmentWordHelp({
        available: true,
        sendUrl: '/student/assessments/1/tutor',
    }), {
        $nextTick(callback) { callback(); },
        $refs: { question: { focus() {} }, messages: { scrollHeight: 120 } },
    });
}

function reply(id = 1) {
    return { data: { chat_id: 8, turn: { id, question: 'What does harvest mean?', answer: 'It means collecting crops when they are ready.' } } };
}

test('assessment helper sends a question and keeps the chat id', async () => {
    const state = helper(async (url, payload) => {
        assert.equal(url, '/student/assessments/1/tutor');
        assert.equal(payload.question, 'What does harvest mean?');
        assert.match(payload.request_id, /^[0-9a-f-]{36}$/);
        assert.equal(payload.chat_id, null);
        return reply();
    });
    state.draft = 'What does harvest mean?';
    await state.send();
    assert.equal(state.chatId, 8);
    assert.equal(state.draft, '');
    assert.equal(state.turns.length, 1);
    assert.equal(state.$refs.messages.scrollTop, 120);
});

test('assessment helper reuses request id after a failed send', async () => {
    const ids = [];
    const state = helper(async (url, payload) => {
        ids.push(payload.request_id);
        if (ids.length === 1) throw new Error('offline');
        return reply();
    });
    state.draft = 'What is a clue?';
    await state.send();
    assert.equal(state.draft, 'What is a clue?');
    assert.match(state.error, /Unable to reach/);
    await state.send();
    assert.equal(ids[0], ids[1]);
    assert.equal(state.turns.length, 1);
});

test('assessment helper does not send blank, unavailable, or oversized questions', async () => {
    let calls = 0;
    const state = helper(async () => { calls++; return reply(); });
    await state.send();
    state.draft = 'a'.repeat(1501);
    await state.send();
    state.draft = 'What is this word?';
    state.available = false;
    await state.send();
    assert.equal(calls, 0);
});
