import test from 'node:test';
import assert from 'node:assert/strict';
import { frogJumpFrame, frogRiverJumpFrame, frogFinishFrame, isPerfectFrogRun } from '../../resources/js/frog-jump.js';

test('frog crouches, jumps, lands on the selected pad, and returns home', () => {
    assert.equal(frogJumpFrame(0).travel, 0);
    assert(frogJumpFrame(.06).squash < 1);
    assert(frogJumpFrame(.27).lift > .9);
    assert.equal(frogJumpFrame(.5).travel, 1);
    assert.equal(frogJumpFrame(.5).phase, 'landed');
    assert(frogJumpFrame(.85).lift > 0);
    assert.equal(frogJumpFrame(1).travel, 0);
    assert.equal(frogJumpFrame(1).phase, 'idle');
});

test('wrong answers sink the frog together with its pad and stay submerged until the next question', () => {
    assert.equal(frogJumpFrame(.45, false).sink, 0);
    assert(frogJumpFrame(.65, false).sink > .3);
    assert(frogJumpFrame(.8, false).sink > .9);
    assert.equal(frogJumpFrame(1, false).sink, 1);
    assert.equal(frogJumpFrame(1, false).travel, 1);
    assert.equal(frogJumpFrame(1, false).phase, 'submerged');
    assert.equal(frogJumpFrame(.8, true).sink, 0);
});

test('reduced motion shows the selected pad without a moving arc', () => {
    for (const correct of [true, false]) {
        for (const progress of [.1, .3, .5, .8, 1]) {
            const frame = frogJumpFrame(progress, correct, true);
            assert.equal(frame.lift, 0);
            assert.equal(frame.wobble, 0);
            assert.equal(frame.dip, 0);
            assert.equal(frame.squash, 1);
            assert.equal(frame.travel, !correct || progress < .7 ? 1 : 0);
        }
    }
});

test('only a non-empty perfect completed run can cross the finish line', () => {
    assert.equal(isPerfectFrogRun(8, 8, 8), true);
    assert.equal(isPerfectFrogRun(1, 1, 1), true);
    for (const counts of [[7, 8, 8], [0, 8, 8], [7, 7, 8], [0, 0, 0], [8, 7, 8], [9, 8, 8]]) {
        assert.equal(isPerfectFrogRun(...counts), false);
    }
});

test('a correct river answer ends on the next safe log', () => {
    assert.equal(frogRiverJumpFrame(0, true).travel, 0);
    assert(frogRiverJumpFrame(.45, true).lift > .8);
    assert.equal(frogRiverJumpFrame(1, true).travel, 1);
    assert.equal(frogRiverJumpFrame(1, true).sink, 0);
    assert.equal(frogRiverJumpFrame(1, true).phase, 'safe-log');
});

test('a wrong river answer splashes and returns to the previous safe log', () => {
    assert.equal(frogRiverJumpFrame(0, false).travel, 0);
    assert(frogRiverJumpFrame(.45, false).travel > .8);
    assert(frogRiverJumpFrame(.52, false).sink > .8);
    assert(frogRiverJumpFrame(.52, false).splash > 0);
    assert.equal(frogRiverJumpFrame(1, false).travel, 0);
    assert.equal(frogRiverJumpFrame(1, false).sink, 0);
    assert.equal(frogRiverJumpFrame(1, false).phase, 'safe-return');
});

test('reduced motion keeps river outcomes at their final safe positions', () => {
    assert.deepEqual(frogRiverJumpFrame(.2, true, true), {
        travel: 1, lift: 0, squash: 1, sink: 0, splash: 0, phase: 'safe-log',
    });
    assert.deepEqual(frogRiverJumpFrame(.8, false, true), {
        travel: 0, lift: 0, squash: 1, sink: 0, splash: 0, phase: 'safe-return',
    });
});

test('the finish hop crosses the line and stays on the finish pad', () => {
    assert.equal(frogFinishFrame(0).travel, 0);
    assert(frogFinishFrame(.4).lift > .9);
    assert.equal(frogFinishFrame(.8).travel, 1);
    assert.equal(frogFinishFrame(.8).phase, 'finish-crossed');
    assert.equal(frogFinishFrame(1).travel, 1);
    assert.equal(frogFinishFrame(.4, true).lift, 0);
    assert.equal(frogFinishFrame(.4, true).travel, 1);
});

test('jump poses are finite and bounded for both outcomes', () => {
    for (const correct of [true, false]) {
        for (let i = -10; i <= 110; i++) {
            const frame = frogJumpFrame(i / 100, correct);
            for (const [key, value] of Object.entries(frame)) if (key !== 'phase') assert(Number.isFinite(value));
            assert(frame.travel >= 0 && frame.travel <= 1);
            assert(frame.squash > 0);
        }
    }
});
