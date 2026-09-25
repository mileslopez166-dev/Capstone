import test from 'node:test';
import assert from 'node:assert/strict';
import teacherPhotoUpload from '../../resources/js/teacher-photo-upload.js';

test('teacher photo preview replaces and releases temporary URLs', (t) => {
    const create = t.mock.method(URL, 'createObjectURL', () => 'blob:preview');
    const revoke = t.mock.method(URL, 'revokeObjectURL', () => {});
    const state = teacherPhotoUpload();
    const choose = (type, size = 100) => state.selectPhoto({ target: { files: [{ type, size }] } });
    for (const type of ['image/jpeg', 'image/png', 'image/webp']) {
        choose(type);
        assert.equal(state.preview, 'blob:preview');
    }
    assert.equal(create.mock.callCount(), 3);
    assert.equal(revoke.mock.callCount(), 2);
    choose('image/svg+xml');
    assert.equal(state.preview, null);
    assert.equal(revoke.mock.callCount(), 3);
    choose('image/png', 2 * 1024 * 1024 + 1);
    assert.equal(state.preview, null);
    state.selectPhoto({ target: { files: [] } });
    assert.equal(state.preview, null);
    choose('image/png');
    state.destroy();
    assert.equal(revoke.mock.callCount(), 4);
});
