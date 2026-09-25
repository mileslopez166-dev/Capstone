import test from 'node:test';
import assert from 'node:assert/strict';
import teacherAiAssistant from '../../resources/js/teacher-ai-assistant.js';

function assistant(post) {
    globalThis.window = { axios: { post } };
    return Object.assign(teacherAiAssistant({
        available: true,
        sendUrl: '/teacher/ai-assistant/messages',
        turns: [],
        prompts: ['Draft a reading passage.'],
    }), {
        $nextTick(callback) { callback(); },
        $refs: { question: { focus() {} }, messages: { scrollHeight: 240 } },
    });
}

function reply(id = 'request-id') {
    return { data: { turn: { id, question: 'Plan a lesson.', answer: 'Start with a short objective and one guided activity.' } } };
}

test('teacher assistant sends and clears the composer', async () => {
    const state = assistant(async (url, payload) => {
        assert.equal(url, '/teacher/ai-assistant/messages');
        assert.equal(payload.question, 'Plan a lesson.');
        assert.match(payload.request_id, /^[0-9a-f-]{36}$/);
        return reply(payload.request_id);
    });
    state.draft = 'Plan a lesson.';
    await state.send();
    assert.equal(state.draft, '');
    assert.equal(state.turns.length, 1);
    assert.equal(state.$refs.messages.scrollTop, 240);
});

test('teacher assistant prompt button fills the draft', () => {
    const state = assistant(async () => reply());
    state.choosePrompt('Draft a reading passage.');
    assert.equal(state.draft, 'Draft a reading passage.');
});

test('teacher assistant preserves failed drafts and request ids', async () => {
    const ids = [];
    const state = assistant(async (url, payload) => {
        ids.push(payload.request_id);
        if (ids.length === 1) throw new Error('offline');
        return reply(payload.request_id);
    });
    state.draft = 'Suggest an intervention.';
    await state.send();
    assert.equal(state.draft, 'Suggest an intervention.');
    assert.match(state.error, /Unable to reach/);
    await state.send();
    assert.equal(ids[0], ids[1]);
    assert.equal(state.turns.length, 1);
});

test('teacher assistant does not send when disabled or invalid', async () => {
    let calls = 0;
    const state = assistant(async () => { calls++; return reply(); });
    await state.send();
    state.draft = 'a'.repeat(2001);
    await state.send();
    state.draft = 'Can you help?';
    state.available = false;
    await state.send();
    assert.equal(calls, 0);
});
