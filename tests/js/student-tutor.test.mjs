import test from 'node:test';
import assert from 'node:assert/strict';
import studentTutor from '../../resources/js/student-tutor.js';

function tutor(post) {
    globalThis.window = { axios: { post }, history: { replaceState() {} } };
    return Object.assign(studentTutor({
        available: true, subject: 'literacy', chatId: null, submissionId: null,
        turns: [], chats: [], sendUrl: '/student/tutor/messages',
    }), { $nextTick(callback) { callback(); }, $refs: { question: { focus() {} }, messages: { scrollHeight: 200 } } });
}

function reply(id = 1) {
    return { data: { chat_id: 4, title: 'Reading questions', url: '/student/tutor/4', delete_url: '/delete', help_url: '/help', turn: { id, question: 'Main idea?', answer: 'It is what the story is mostly about.' } } };
}

test('successful send updates history and clears the composer', async () => {
    const state = tutor(async (url, payload) => {
        assert.equal(payload.question, 'Main idea?');
        assert.match(payload.request_id, /^[0-9a-f-]{36}$/);
        return reply();
    });
    state.draft = 'Main idea?';
    await state.send();
    assert.equal(state.chatId, 4);
    assert.equal(state.draft, '');
    assert.equal(state.busy, false);
    assert.equal(state.turns.length, 1);
    assert.equal(state.chats.length, 1);
    assert.equal(state.$refs.messages.scrollTop, 200);
});

test('network failure keeps question and reuses id for a safe retry', async () => {
    const ids = [];
    const state = tutor(async (url, payload) => {
        ids.push(payload.request_id);
        if (ids.length === 1) throw new Error('offline');
        return reply();
    });
    state.draft = 'Main idea?';
    await state.send();
    assert.equal(state.draft, 'Main idea?');
    assert.match(state.error, /connection/);
    assert.equal(state.turns.length, 0);
    await state.send();
    assert.equal(ids[0], ids[1]);
    assert.equal(state.error, '');
});

test('editing a failed question creates a new request id', async () => {
    const ids = [];
    const state = tutor(async (url, payload) => { ids.push(payload.request_id); throw new Error('offline'); });
    state.draft = 'Main idea?';
    await state.send();
    state.draft = 'What is a fraction?';
    await state.send();
    assert.notEqual(ids[0], ids[1]);
});

test('double click while waiting sends once', async () => {
    let resolve;
    let calls = 0;
    const state = tutor(() => { calls++; return new Promise(done => { resolve = done; }); });
    state.draft = 'Main idea?';
    const first = state.send();
    await state.send();
    assert.equal(calls, 1);
    resolve(reply());
    await first;
    assert.equal(state.turns.length, 1);
});

test('changing subjects after a failure creates a new request id', async () => {
    const ids = [];
    const state = tutor(async (url, payload) => { ids.push(payload.request_id); throw new Error('offline'); });
    state.draft = 'Can you give an example?';
    await state.send();
    state.subject = 'numeracy';
    await state.send();
    assert.notEqual(ids[0], ids[1]);
});

test('unconfigured, blank, and oversized requests never send', async () => {
    let calls = 0;
    const state = tutor(async () => { calls++; return reply(); });
    await state.send();
    state.draft = 'a'.repeat(1501);
    await state.send();
    state.draft = 'Main idea?';
    state.available = false;
    await state.send();
    assert.equal(calls, 0);
});

test('expired sessions give a readable recovery message', async () => {
    const state = tutor(async () => { throw { response: { status: 419 } }; });
    state.draft = 'Main idea?';
    await state.send();
    assert.match(state.error, /session has expired/);
    assert.equal(state.draft, 'Main idea?');
    assert.equal(state.busy, false);
});
