import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

for (const file of ['layouts/app.blade.php', 'layouts/guest.blade.php', 'components/admin-head.blade.php', 'errors/layout.blade.php']) {
    test(`${file} sets light on first paint and respects saved themes`, () => {
        const source = readFileSync(new URL(`../../resources/views/${file}`, import.meta.url), 'utf8');
        const script = source.match(/<script>([\s\S]*?)<\/script>/)?.[1];
        assert.ok(script);
        for (const deviceDark of [false, true]) {
            for (const saved of [null, '{}', '{bad json', '{"theme":"invalid"}', '{"theme":"light"}', '{"theme":"dark"}', '{"theme":"system"}', 'blocked']) {
                const classes = new Set();
                const root = { dataset: {}, style: {}, classList: { toggle: (name, on) => on ? classes.add(name) : classes.delete(name) } };
                runInNewContext(script, {
                    localStorage: { getItem() { if (saved === 'blocked') throw new Error('Blocked'); return saved; } },
                    matchMedia: query => ({ matches: query === '(prefers-color-scheme: dark)' && deviceDark }),
                    document: { documentElement: root },
                });
                const expectedDark = saved === '{"theme":"dark"}' || saved === '{"theme":"system"}' && deviceDark;
                assert.equal(classes.has('dark'), expectedDark, `saved=${saved}, deviceDark=${deviceDark}`);
                assert.equal(root.dataset.theme, expectedDark ? 'dark' : 'light');
                assert.equal(root.style.colorScheme, expectedDark ? 'dark' : 'light');
            }
        }
    });
}
