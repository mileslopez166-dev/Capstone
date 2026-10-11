import test from 'node:test';
import assert from 'node:assert/strict';
import { rallyStanding } from '../../resources/js/reading-rally.js';

test('correct answers overtake rivals, wrong answers lose ground, and the next boost recovers', () => {
    const start = rallyStanding(0, 0, 8);
    const boost = rallyStanding(1, 1, 8);
    const skid = rallyStanding(2, 1, 8);
    const recover = rallyStanding(3, 2, 8);
    assert.equal(start.position, 4);
    assert(boost.position < start.position);
    assert(skid.position > boost.position);
    assert(recover.margin > skid.margin);
});

test('saved counts restore exactly the same standing, and all correct wins for every bank size', () => {
    for (const total of [1, 2, 8, 24, 32]) {
        assert.equal(rallyStanding(total, total, total).position, 1);
        assert.equal(rallyStanding(total, 0, total).position, 4);
        assert.equal(rallyStanding(total, total, total).progress, 1);
    }
    assert.deepEqual(rallyStanding(3, 2, 8), rallyStanding('3', '2', '8'));
});

test('race progress counts every question including mistakes and timeout', () => {
    assert.equal(rallyStanding(4, 0, 8).progress, .5);
    assert.equal(rallyStanding(8, 0, 8).progress, 1);
    assert.equal(rallyStanding(8, 4, 8).label, '3rd');
});
