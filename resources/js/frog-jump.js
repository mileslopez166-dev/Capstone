const clamp = value => Math.max(0, Math.min(1, value));
const smooth = value => { const t = clamp(value); return t * t * (3 - 2 * t); };

export function frogJumpFrame(progress, correct = true, reduced = false) {
    const p = clamp(progress);
    const outward = clamp((p - .12) / .31);
    const homeward = correct ? clamp((p - .70) / .30) : 0;
    const landing = clamp((p - .43) / .27);
    const atPad = p >= .43 && p < .70;
    return {
        travel: reduced ? (!correct || p < .70 ? 1 : 0) : smooth(outward) * (1 - smooth(homeward)),
        lift: reduced ? 0 : Math.sin(outward * Math.PI) * (1 - homeward) + Math.sin(homeward * Math.PI) * .7,
        squash: reduced ? 1 : 1 - (p < .12 ? Math.sin(p / .12 * Math.PI) * .18 : atPad ? Math.sin(landing * Math.PI) * .12 : 0),
        dip: reduced || !atPad || !correct ? 0 : Math.sin(landing * Math.PI) * 5,
        sink: correct ? 0 : reduced ? (p >= .56 ? 1 : 0) : smooth((p - .50) / .34),
        wobble: reduced || correct || p < .43 || p >= .55 ? 0 : Math.sin(landing * Math.PI * 4) * .08,
        ripple: p >= .43 && p < .85 ? (p - .43) / .42 : -1,
        phase: !correct && p >= .84 ? 'submerged' : !correct && p >= .50 ? 'sinking' : p === 1 ? 'idle' : p < .12 ? 'crouching' : p < .43 ? 'jumping' : p < .70 ? 'landed' : 'returning',
    };
}

export function isPerfectFrogRun(correct, answered, total) {
    return Number.isInteger(total) && total > 0 && correct === total && answered === total;
}

export function frogFinishFrame(progress, reduced = false) {
    const p = clamp(progress);
    const hop = clamp((p - .1) / .65);
    return {
        travel: reduced ? 1 : smooth(hop),
        lift: reduced ? 0 : Math.sin(hop * Math.PI),
        squash: reduced ? 1 : 1 - (p < .1 ? Math.sin(p / .1 * Math.PI) * .15 : 0),
        sink: 0, dip: 0, wobble: 0, ripple: p >= .75 ? (p - .75) / .25 : -1,
        phase: p >= .75 ? 'finish-crossed' : 'finishing',
    };
}

export function initFrogJump(root) {
    const anchor = root.querySelector('#frog-character');
    const sprite = root.querySelector('#frog-jump-sprite');
    if (!anchor || !sprite) return;
    const body = anchor.querySelector('.frog-character-body').cloneNode(true);
    body.removeAttribute('class');
    sprite.replaceChildren(body);
    let animation = null;
    let frameId = null;
    let disposed = false;
    const reducedMotion = () => window.PgaalsPreferences?.reducedMotion ?? matchMedia('(prefers-reduced-motion: reduce)').matches;

    function reset() {
        cancelAnimationFrame(frameId);
        frameId = null;
        animation = null;
        root.classList.remove('frog-jumping');
        delete root.dataset.frogPhase;
        root.querySelectorAll('.frog-target-visual').forEach(target => {
            target.style.removeProperty('--pad-dip');
            target.style.removeProperty('--pad-wobble');
            target.style.removeProperty('--pad-opacity');
            target.style.removeProperty('--pad-clip');
            target.style.removeProperty('--ripple-scale');
            target.style.removeProperty('--ripple-opacity');
        });
        sprite.style.clipPath = '';
    }

    function render(now) {
        frameId = null;
        if (!animation || disposed || document.hidden) return;
        const { target, correct, startedAt, duration, finish } = animation;
        const progress = clamp((now - startedAt) / duration);
        const pose = finish ? frogFinishFrame(progress, reducedMotion()) : frogJumpFrame(progress, correct, reducedMotion());
        // Measure each frame so a resized layout or enlarged answer text keeps the landing aligned.
        const box = root.getBoundingClientRect();
        const home = anchor.getBoundingClientRect();
        const pad = target.getBoundingClientRect();
        const startX = home.left + home.width / 2 - box.left;
        const startY = home.top + home.height * .87 - box.top;
        const endX = pad.left + pad.width / 2 - box.left;
        const endY = pad.top + pad.height * (finish ? .5 : .80) - box.top;
        const arc = finish ? Math.min(28, pad.width * .2) : Math.min(90, Math.hypot(endX - startX, endY - startY) * .18 + 25);
        const scale = 1 + (pad.width * (finish ? 1.08 : .92) / home.width - 1) * pose.travel;
        const width = home.width * scale;
        const height = home.height * scale;
        sprite.style.width = `${width}px`;
        sprite.style.height = `${height}px`;
        sprite.style.left = `${startX + (endX - startX) * pose.travel - width / 2}px`;
        const depth = pose.dip + pose.sink * pad.height * 1.05;
        const top = startY + (endY - startY) * pose.travel - height * .87 - arc * pose.lift + depth;
        sprite.style.top = `${top}px`;
        sprite.style.transform = `rotate(${pose.wobble}rad) scale(${1 / pose.squash}, ${pose.squash})`;
        sprite.style.clipPath = pose.sink > 0 ? `inset(0 0 ${Math.max(0, Math.min(height, height - endY + top))}px 0)` : '';
        target.style.setProperty('--pad-dip', `${depth}px`);
        target.style.setProperty('--pad-opacity', String(1 - pose.sink));
        target.style.setProperty('--pad-clip', `${pose.sink > 0 ? Math.min(pad.height, pad.height * .2 + depth) : 0}px`);
        target.style.setProperty('--pad-wobble', `${pose.wobble}rad`);
        target.style.setProperty('--ripple-scale', String(1 + Math.max(0, pose.ripple) * .7));
        target.style.setProperty('--ripple-opacity', String(pose.ripple < 0 ? 0 : (1 - pose.ripple) * .8));
        root.dataset.frogPhase = pose.phase;
        root.classList.add('frog-jumping');
        if (finish && pose.phase === 'finish-crossed') {
            root.dataset.frogFinishCrossed = 'true';
            root.querySelector('#frog-race-status').textContent = 'Perfect run! Finish line crossed.';
        }
        if (progress < 1) frameId = requestAnimationFrame(render);
    }

    function jump({ detail }) {
        reset();
        const target = [...root.querySelectorAll('[data-frog-answer]')]
            .find(button => button.dataset.frogAnswer === detail.letter)?.querySelector('.frog-target-visual');
        if (!target) return;
        animation = { ...detail, target };
        render(performance.now());
    }
    function finish({ detail }) {
        if (!isPerfectFrogRun(detail.correct, detail.answered, detail.total)) return;
        reset();
        animation = { ...detail, target: root.querySelector('#frog-finish-pad'), finish: true };
        render(performance.now());
    }
    function visibility() {
        cancelAnimationFrame(frameId);
        frameId = null;
        if (!document.hidden && animation && !disposed) render(performance.now());
    }
    root.addEventListener('frog:jump', jump);
    root.addEventListener('frog:finish-line', finish);
    root.addEventListener('frog:question', reset);
    root.addEventListener('frog:finished', reset);
    document.addEventListener('visibilitychange', visibility);
    window.addEventListener('pageshow', visibility);
    window.addEventListener('pagehide', event => {
        cancelAnimationFrame(frameId);
        if (event.persisted) return;
        disposed = true;
        reset();
        root.removeEventListener('frog:jump', jump);
        root.removeEventListener('frog:finish-line', finish);
        root.removeEventListener('frog:question', reset);
        root.removeEventListener('frog:finished', reset);
        document.removeEventListener('visibilitychange', visibility);
        window.removeEventListener('pageshow', visibility);
    });
    const selected = root.querySelector('[data-frog-answer][data-result]');
    if (root.dataset.frogFinishing === 'true' && root.dataset.assessmentFinished !== 'true') {
        finish({ detail: { correct: Number(root.dataset.frogCorrect), answered: Number(root.dataset.frogAnswered), total: Number(root.dataset.frogTotal),
            startedAt: Number(root.dataset.frogFinishStart), duration: Number(root.dataset.frogFinishDuration) } });
    } else if (selected && root.dataset.frogOutcome && root.dataset.assessmentFinished !== 'true') {
        jump({ detail: { letter: selected.dataset.frogAnswer, correct: root.dataset.frogOutcome === 'correct',
            startedAt: Number(root.dataset.frogJumpStart), duration: Number(root.dataset.frogJumpDuration) } });
    }
}
