import test from 'node:test';
import assert from 'node:assert/strict';
import {
    backgroundMusicPositionKey,
    readBackgroundMusicPosition,
    writeBackgroundMusicPosition,
} from '../../resources/js/background-music-state.js';

function memoryStorage(initial = {}) {
    const values = new Map(Object.entries(initial));
    return {
        getItem: key => values.has(key) ? values.get(key) : null,
        setItem: (key, value) => values.set(key, value),
        value: key => values.get(key),
    };
}

test('music position follows navigation in the same browser tab', () => {
    const session = memoryStorage();
    const local = memoryStorage();

    writeBackgroundMusicPosition([session, local], 84.25);

    assert.equal(readBackgroundMusicPosition([session, local]), 84.25);
    assert.equal(session.value(backgroundMusicPositionKey), '84.25');
    assert.equal(local.value(backgroundMusicPositionKey), '84.25');
});

test('legacy saved positions remain usable and blocked storage is ignored', () => {
    const blocked = {
        getItem() { throw new Error('Storage blocked'); },
        setItem() { throw new Error('Storage blocked'); },
    };
    const local = memoryStorage({ 'pgaals-background-music-position-v1': '31.5' });

    assert.equal(readBackgroundMusicPosition([blocked, local]), 31.5);
    assert.doesNotThrow(() => writeBackgroundMusicPosition([blocked, local], 32));
    assert.equal(readBackgroundMusicPosition([blocked, local]), 32);
});

test('invalid positions cannot replace a valid saved position', () => {
    const storage = memoryStorage({ [backgroundMusicPositionKey]: '12' });

    writeBackgroundMusicPosition([storage], Number.NaN);
    writeBackgroundMusicPosition([storage], -1);

    assert.equal(readBackgroundMusicPosition([storage]), 12);
});
