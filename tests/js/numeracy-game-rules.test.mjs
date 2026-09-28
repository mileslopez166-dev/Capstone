import test from 'node:test';
import assert from 'node:assert/strict';
import { validDigits, gradeGameAnswer, gameAnswerExists, gameHint } from '../../resources/js/numeracy-game-rules.js';
import { normalizeResponses } from '../../resources/js/worksheet-responses.js';

test('rocket uses divisibility, not rounded quotients, for Yes and No', () => {
    const game = { type: 'rocket', field: 'choice', number: 160, divisor: 2 };
    assert.equal(gradeGameAnswer(game, 'Yes').correct, true);
    assert.equal(gradeGameAnswer(game, 'No').correct, false);
    assert.equal(gradeGameAnswer({ ...game, number: 265, divisor: 9 }, 'No').correct, true);
    assert.match(gradeGameAnswer({ ...game, number: 265, divisor: 9 }, 'Yes').explanation, /remainder of 4/);
    assert.equal(gradeGameAnswer(game, 'Maybe'), null);
    assert.equal(gradeGameAnswer({ ...game, divisor: 0 }, 'Yes'), null);
});

test('rocket keeps the justification required by the worksheet', () => {
    const game = { type: 'rocket', field: 'choice', number: 160, divisor: 2, reason: true };
    assert.equal(gameAnswerExists(game, { choice: 'Yes' }), false);
    assert.equal(gameAnswerExists(game, { choice: 'Yes', reason: '   ' }), false);
    assert.equal(gameAnswerExists(game, { choice: 'Yes', reason: '160 is even.' }), true);
    assert.equal(gameAnswerExists({ ...game, reason: false }, { choice: 'No' }), true);
});

test('puzzle accepts only the smallest digit when the worksheet requires it', () => {
    const game = { type: 'puzzle', field: 'digit', pattern: '72_', divisor: 9, smallest: true };
    assert.deepEqual(validDigits(game), ['0', '9']);
    assert.equal(gradeGameAnswer(game, '0').correct, true);
    assert.equal(gradeGameAnswer(game, '9').correct, false);
    assert.equal(gradeGameAnswer({ ...game, smallest: false }, '9').correct, true);
    assert.equal(gradeGameAnswer(game, ''), null);
    assert.equal(gradeGameAnswer(game, '12'), null);
    assert.equal(gameAnswerExists(game, { digit: '0' }), true);
});

test('puzzle does not introduce leading zeros or accept impossible patterns', () => {
    assert.deepEqual(validDigits({ pattern: '_77', divisor: 9 }), ['4']);
    assert.deepEqual(validDigits({ pattern: '_85', divisor: 3 }), ['2', '5', '8']);
    assert.deepEqual(validDigits({ pattern: '4,6_7', divisor: 9 }), []);
    assert.deepEqual(validDigits({ pattern: '4__7', divisor: 9 }), []);
    assert.deepEqual(validDigits({ pattern: '4_7', divisor: 0 }), []);
});

test('archer requires the exact divisor set, allows multiple targets and is order independent', () => {
    const game = { type: 'archer', field: 'divisors', number: 240, options: ['2', '3', '5', '9', '10'] };
    assert.equal(gradeGameAnswer(game, ['10', '3', '2', '5']).correct, true);
    assert.equal(gradeGameAnswer(game, ['2']).correct, false);
    assert.equal(gradeGameAnswer(game, ['2', '3', '5', '9', '10']).correct, false);
    assert.equal(gradeGameAnswer(game, ['2', '2']), null);
    assert.equal(gradeGameAnswer(game, ['7']), null);
    assert.equal(gradeGameAnswer(game, '2'), null);
});

test('a confirmed empty archer selection differs from an untouched question', () => {
    const game = { type: 'archer', field: 'divisors', number: 7, options: ['2', '3', '5'] };
    assert.equal(gameAnswerExists(game, {}), false);
    assert.equal(gameAnswerExists(game, { divisors: [] }), true);
    assert.equal(gradeGameAnswer(game, []).correct, true);
    assert.equal(gradeGameAnswer({ ...game, number: 10 }, []).correct, false);
});

test('game answers round-trip through the existing worksheet response format', () => {
    const definitions = {
        '0-0': { fields: [{ key: 'choice', type: 'radio', options: ['Yes', 'No'] }, { key: 'reason', type: 'textarea' }] },
        '1-0': { fields: [{ key: 'digit', type: 'digit' }] },
        '2-0': { fields: [{ key: 'divisors', type: 'checkbox', options: ['2', '3', '5'] }] },
    };
    const responses = { '0-0': { choice: 'No', reason: 'There is a remainder.' }, '1-0': { digit: '0' }, '2-0': { divisors: ['3', '5'] } };
    assert.deepEqual(normalizeResponses(responses, definitions), responses);
    assert.match(gameHint({ type: 'puzzle', divisor: 9, smallest: true }), /smallest/);
    assert.match(gameHint({ type: 'archer' }), /More than one/);
});
