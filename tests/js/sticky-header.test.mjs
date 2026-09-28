import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import postcss from 'postcss';

test('page roots clip horizontal overflow without trapping sticky headers in a body scroller', () => {
    const css = postcss.parse(readFileSync(new URL('../../resources/css/app.css', import.meta.url), 'utf8'));
    for (const selector of ['html', 'body']) {
        const values = [];
        css.walkRules(selector, rule => rule.walkDecls('overflow-x', declaration => values.push(declaration.value)));
        assert.deepEqual(values, ['clip'], `${selector} must not create a separate scrolling ancestor`);
    }
});
