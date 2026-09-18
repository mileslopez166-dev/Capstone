import test from 'node:test';
import assert from 'node:assert/strict';
import { worksheetState, worksheetSettings, answeredParts } from '../../resources/js/worksheet-book.js';

test('each worksheet part gets independent answer and drawing storage', () => {
    const state = worksheetState({}, 3);
    assert.equal(state.pages.length, 3);
    state.pages[0].strokes.push({});
    assert.equal(state.pages[1].strokes.length, 0);
    assert.equal(state.page, 0);
});
test('saved text, part position and valid ink survive restoring', () => {
    const ink = {color: '#174d97', width: 3, points: [[.2, .3], [1, 1]]};
    const state = worksheetState({ page: 1, pages: [{text: '1. 3/4', strokes: []}, {text: '', strokes: [ink]}] }, 2);
    assert.equal(state.page, 1);
    assert.deepEqual(state.pages[1].strokes, [ink]);
    assert.equal(answeredParts(state.pages), 2);
});
test('malformed local backups cannot break the page or inject drawing values', () => {
    const state = worksheetState({page: 99, pages: [{text: {}, strokes: [{color: 'url(evil)', width: 3, points: [[0, 0]]}, {color: '#174d97', width: 3, points: [[NaN, 0]]}]}]}, 2);
    assert.equal(state.page, 1);
    assert.equal(state.pages[0].text, '');
    assert.deepEqual(state.pages[0].strokes, []);
    assert.equal(answeredParts(state.pages), 0);
});
test('whitespace alone does not count as an answered part', () => {
    assert.equal(answeredParts([{text: '   ', strokes: []}]), 0);
});

test('numbered answers and the answer step resume independently for each part', () => {
    const state = worksheetState({page: 1, step: 'answer', pages: [
        {answers: [{label: 'A.1', answer: '3/4'}]}, {answers: [{label: '2', answer: '   '}]},
    ]}, 2);
    assert.equal(state.step, 'answer');
    assert.equal(state.pages[0].answers[0].answer, '3/4');
    assert.equal(answeredParts(state.pages), 1);
    assert.equal(worksheetState({step: '<script>'}, 1).step, 'read');
});

test('answer rows have bounded safe strings and labels alone are not completed work', () => {
    const state = worksheetState({pages: [{answers: [null, {label: 'x'.repeat(50), answer: 'a'.repeat(3000)}]}]}, 1);
    assert.deepEqual(state.pages[0].answers[0], {label: '1', answer: ''});
    assert.equal(state.pages[0].answers[1].label.length, 40);
    assert.equal(state.pages[0].answers[1].answer.length, 2000);
    assert.equal(worksheetState({pages: [{answers: Array(101).fill({})}]}, 1).pages[0].answers.length, 100);
    assert.equal(answeredParts(worksheetState({pages: [{answers: [{label: '1', answer: ''}]}]}, 1).pages), 0);
});

test('worksheet view preferences normalize invalid saved values', () => {
    assert.deepEqual(worksheetSettings(), {zoom: 100, spacing: 1.7});
    assert.deepEqual(worksheetSettings({zoom: 200, spacing: 1.8}), {zoom: 200, spacing: 1.8});
    assert.deepEqual(worksheetSettings({zoom: 999, spacing: NaN}), {zoom: 100, spacing: 1.7});
    assert.deepEqual(worksheetSettings({zoom: '125', spacing: 9}), {zoom: 100, spacing: 1.7});
});

test('fraction shading is bounded and counts only actual selected cells', () => {
    const state = worksheetState({pages: [{shading: {'0-0': [0, 2, 2, -1, 100, '3'], bad: [1]}}]}, 2);
    assert.deepEqual(state.pages[0].shading, {'0-0': [0, 2]});
    assert.equal(answeredParts(state.pages), 1);
    state.pages[0].shading['0-0'] = [];
    assert.equal(answeredParts(state.pages), 0);
});

test('reading-only parts are not counted as required answer parts', () => {
    const state = worksheetState({pages: [{text: 'Notes'}, {answers: [{label: '1', answer: 'Yes'}]}]}, 2);
    assert.equal(answeredParts(state.pages, [{readingOnly: true}, {}]), 1);
});
