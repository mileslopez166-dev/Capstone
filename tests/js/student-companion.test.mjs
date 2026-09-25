import test from 'node:test';
import assert from 'node:assert/strict';
import studentCompanion from '../../resources/js/student-companion.js';

function companion(post, options = {}) {
    globalThis.window = { axios: { post } };
    globalThis.document = { hidden: false };

    return Object.assign(studentCompanion(['Keep going!'], {
        available: options.available ?? true,
        sendUrl: '/student/tutor/messages',
        tutorUrl: '/student/tutor',
        subject: options.subject || 'literacy',
    }), {
        $nextTick(callback) { callback(); },
        $refs: { quickQuestion: { focus() {} }, avatarChatLog: { scrollHeight: 120, scrollTop: 0 } },
    });
}

function reply(answer = 'A main idea tells what a story is mostly about.') {
    return { data: { chat_id: 4, url: '/student/tutor/7', turn: { id: 11, question: 'What is main idea?', answer } } };
}

test('avatar tutor sends a question and keeps the chat inline', async () => {
    const state = companion(async (url, payload) => {
        assert.equal(url, '/student/tutor/messages');
        assert.equal(payload.question, 'What is main idea?');
        assert.equal(payload.subject, 'literacy');
        assert.equal(payload.chat_id, null);
        assert.match(payload.request_id, /^[0-9a-f-]{36}$/);
        return reply();
    });

    state.quickQuestion = 'What is main idea?';
    await state.askTutor();

    assert.equal(state.quickQuestion, '');
    assert.equal(state.tutorTurns.length, 1);
    assert.equal(state.tutorTurns[0].answer, 'A main idea tells what a story is mostly about.');
    assert.equal(state.tutorChatId, 4);
    assert.equal(state.tutorChatUrl, '/student/tutor/7');
    assert.equal(state.tutorBusy, false);
});

test('avatar click opens the floating tutor panel', () => {
    const state = companion(async () => reply());

    state.compact = true;
    state.openTutorPanel();

    assert.equal(state.chatOpen, true);
    assert.equal(state.compact, false);
    assert.equal(state.displayText, 'Keep going!');
});

test('avatar tutor follow ups stay in the same chat', async () => {
    const chatIds = [];
    const state = companion(async (url, payload) => {
        chatIds.push(payload.chat_id);
        return { data: { chat_id: 4, url: '/student/tutor/4', turn: { id: chatIds.length, question: payload.question, answer: 'Answer '+chatIds.length } } };
    });

    state.quickQuestion = 'First question';
    await state.askTutor();
    state.quickQuestion = 'Second question';
    await state.askTutor();

    assert.deepEqual(chatIds, [null, 4]);
    assert.equal(state.tutorTurns.length, 2);
});

test('avatar tutor preserves failed questions and reuses the request id', async () => {
    const ids = [];
    const state = companion(async (url, payload) => {
        ids.push(payload.request_id);
        if (ids.length === 1) throw new Error('offline');
        return reply('Try counting by twos: 2, 4, 6.');
    }, { subject: 'numeracy' });

    state.quickQuestion = 'How do I count by twos?';
    await state.askTutor();
    assert.equal(state.quickQuestion, 'How do I count by twos?');
    assert.match(state.tutorError, /reachable|Try again/);

    await state.askTutor();
    assert.equal(ids[0], ids[1]);
    assert.equal(state.tutorTurns[0].answer, 'Try counting by twos: 2, 4, 6.');
});

test('avatar tutor does not send when unavailable or blank', async () => {
    let calls = 0;
    const state = companion(async () => { calls++; return reply(); }, { available: false });

    state.quickQuestion = 'Can you help?';
    await state.askTutor();
    state.tutor.available = true;
    state.quickQuestion = '';
    await state.askTutor();

    assert.equal(calls, 0);
});
