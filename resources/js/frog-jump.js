const clamp = value => Math.max(0, Math.min(1, value));
const smooth = value => { const t = clamp(value); return t * t * (3 - 2 * t); };

// Kept for compatibility with older saved assessment pages.
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

export function frogRiverJumpFrame(progress, correct = true, reduced = false) {
    const p = clamp(progress);
    if (reduced) return { travel: correct ? 1 : 0, lift: 0, squash: 1, sink: 0, splash: 0, phase: correct ? 'safe-log' : 'safe-return' };
    const crouch = clamp(p / .12);
    if (correct) {
        const travel = smooth((p - .1) / .78);
        return {
            travel,
            lift: Math.sin(travel * Math.PI),
            squash: 1 - Math.sin(crouch * Math.PI) * .16,
            sink: 0,
            splash: 0,
            phase: p >= .88 ? 'safe-log' : p < .1 ? 'crouching' : 'jumping',
        };
    }
    const outward = smooth((p - .08) / .34);
    const returning = smooth((p - .62) / .34);
    const sinkIn = smooth((p - .39) / .13);
    const surface = smooth((p - .60) / .18);
    return {
        travel: outward * (1 - returning),
        lift: Math.sin(outward * Math.PI) * (1 - returning) + Math.sin(returning * Math.PI) * .55,
        squash: 1 - Math.sin(crouch * Math.PI) * .16,
        sink: sinkIn * (1 - surface),
        splash: Math.sin(clamp((p - .36) / .34) * Math.PI),
        phase: p >= .96 ? 'safe-return' : p >= .62 ? 'returning' : p >= .39 ? 'splashing' : p < .08 ? 'crouching' : 'jumping',
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
    const stage = root.querySelector('.frog-stage');
    const anchor = root.querySelector('#frog-character');
    const sprite = root.querySelector('#frog-jump-sprite');
    const waterTarget = root.querySelector('#frog-water-target');
    const splash = root.querySelector('#frog-water-splash');
    if (!stage || !anchor || !sprite || !waterTarget || !splash) return;

    const body = anchor.querySelector('.frog-character-body').cloneNode(true);
    body.removeAttribute('class');
    sprite.replaceChildren(body);
    let animation = null;
    let frameId = null;
    let disposed = false;
    const reducedMotion = () => window.PgaalsPreferences?.reducedMotion ?? matchMedia('(prefers-reduced-motion: reduce)').matches;
    const safeIndex = () => Number.isFinite(Number(root.dataset.frogSafeIndex)) ? Number(root.dataset.frogSafeIndex) : -1;
    const logAt = index => root.querySelector(`[data-frog-log-index="${index}"]`);

    function placeAnchor(index = safeIndex()) {
        anchor.style.removeProperty('left');
        anchor.style.removeProperty('bottom');
        if (index < 0) return;
        const stageBox = stage.getBoundingClientRect();
        const logBox = logAt(index)?.getBoundingClientRect();
        if (!logBox) return;
        anchor.style.left = `${logBox.left + logBox.width / 2 - stageBox.left}px`;
        anchor.style.bottom = `${Math.max(8, stageBox.bottom - logBox.top - 3)}px`;
    }

    function positionWaterTarget(fromIndex, toIndex) {
        const stageBox = stage.getBoundingClientRect();
        const fromBox = (fromIndex >= 0 ? logAt(fromIndex) : anchor)?.getBoundingClientRect();
        const nextBox = (logAt(Math.max(0, toIndex)) || root.querySelector('#frog-finish-pad'))?.getBoundingClientRect();
        if (!fromBox || !nextBox) return;
        waterTarget.style.left = `${(fromBox.left + fromBox.width / 2 + nextBox.left + nextBox.width / 2) / 2 - stageBox.left}px`;
        waterTarget.style.top = `${Math.min(stageBox.height - 30, Math.max(65, stageBox.height * .62))}px`;
        splash.style.left = waterTarget.style.left;
        splash.style.top = waterTarget.style.top;
        splash.style.bottom = 'auto';
    }

    function reset(reposition = true) {
        cancelAnimationFrame(frameId);
        frameId = null;
        animation = null;
        root.classList.remove('frog-jumping');
        delete root.dataset.frogPhase;
        sprite.style.removeProperty('opacity');
        sprite.style.removeProperty('transform');
        splash.style.removeProperty('--splash-opacity');
        splash.style.removeProperty('--splash-scale');
        if (reposition) placeAnchor();
    }

    function render(now) {
        frameId = null;
        if (!animation || disposed || document.hidden) return;
        const { target, correct, startedAt, duration, finish, fromSafeIndex, toSafeIndex } = animation;
        const progress = clamp((now - startedAt) / duration);
        const pose = finish ? frogFinishFrame(progress, reducedMotion()) : frogRiverJumpFrame(progress, correct, reducedMotion());
        const box = root.getBoundingClientRect();
        const home = anchor.getBoundingClientRect();
        const destination = target.getBoundingClientRect();
        const startX = home.left + home.width / 2 - box.left;
        const startY = home.top + home.height * .87 - box.top;
        const endX = destination.left + destination.width / 2 - box.left;
        const endY = destination.top - box.top + (finish ? destination.height * .45 : 2);
        const arc = finish ? 32 : Math.min(88, Math.hypot(endX - startX, endY - startY) * .2 + 24);
        const width = home.width;
        const height = home.height;
        sprite.style.width = `${width}px`;
        sprite.style.height = `${height}px`;
        sprite.style.left = `${startX + (endX - startX) * pose.travel - width / 2}px`;
        sprite.style.top = `${startY + (endY - startY) * pose.travel - height * .87 - arc * pose.lift + pose.sink * 28}px`;
        sprite.style.transform = `scale(${1 / pose.squash}, ${pose.squash})`;
        sprite.style.opacity = String(1 - pose.sink * .72);
        splash.style.setProperty('--splash-opacity', String(pose.splash || 0));
        splash.style.setProperty('--splash-scale', String(.4 + (pose.splash || 0) * .9));
        root.dataset.frogPhase = pose.phase;
        root.classList.add('frog-jumping');
        if (finish && pose.phase === 'finish-crossed') {
            root.dataset.frogFinishCrossed = 'true';
            root.querySelector('#frog-race-status').textContent = 'Perfect crossing! The frog reached the far bank.';
        }
        if (progress < 1) return void (frameId = requestAnimationFrame(render));
        if (!finish) {
            placeAnchor(correct ? toSafeIndex : fromSafeIndex);
            root.classList.remove('frog-jumping');
        }
        splash.style.removeProperty('--splash-opacity');
        splash.style.removeProperty('--splash-scale');
    }

    function jump({ detail }) {
        reset(false);
        const fromSafeIndex = Number.isFinite(Number(detail.fromSafeIndex)) ? Number(detail.fromSafeIndex) : safeIndex();
        const toSafeIndex = Number.isFinite(Number(detail.toSafeIndex)) ? Number(detail.toSafeIndex) : fromSafeIndex + 1;
        placeAnchor(fromSafeIndex);
        positionWaterTarget(fromSafeIndex, detail.correct === false ? fromSafeIndex + 1 : toSafeIndex);
        const target = detail.correct === false ? waterTarget : logAt(toSafeIndex);
        if (!target) return;
        animation = { ...detail, fromSafeIndex, toSafeIndex, target };
        render(performance.now());
    }

    function finish({ detail }) {
        if (!isPerfectFrogRun(detail.correct, detail.answered, detail.total)) return;
        reset(false);
        placeAnchor(safeIndex());
        animation = { ...detail, target: root.querySelector('#frog-finish-pad'), finish: true };
        render(performance.now());
    }

    function visibility() {
        cancelAnimationFrame(frameId);
        frameId = null;
        if (!document.hidden && animation && !disposed) render(performance.now());
    }

    function resize() {
        if (animation) {
            placeAnchor(animation.fromSafeIndex ?? safeIndex());
            if (!animation.finish) positionWaterTarget(animation.fromSafeIndex, animation.correct ? animation.toSafeIndex : animation.fromSafeIndex + 1);
            visibility();
        } else {
            placeAnchor();
        }
    }

    root.addEventListener('frog:jump', jump);
    root.addEventListener('frog:finish-line', finish);
    root.addEventListener('frog:question', reset);
    root.addEventListener('frog:finished', reset);
    document.addEventListener('visibilitychange', visibility);
    window.addEventListener('resize', resize);
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
        window.removeEventListener('resize', resize);
        window.removeEventListener('pageshow', visibility);
    });

    placeAnchor();
    if (root.dataset.frogFinishing === 'true' && root.dataset.assessmentFinished !== 'true') {
        finish({ detail: { correct: Number(root.dataset.frogCorrect), answered: Number(root.dataset.frogAnswered), total: Number(root.dataset.frogTotal),
            startedAt: Number(root.dataset.frogFinishStart), duration: Number(root.dataset.frogFinishDuration) } });
    } else if (root.dataset.frogOutcome && root.dataset.assessmentFinished !== 'true') {
        jump({ detail: { correct: root.dataset.frogOutcome === 'correct', fromSafeIndex: Number(root.dataset.frogJumpFrom),
            toSafeIndex: Number(root.dataset.frogJumpTo), startedAt: Number(root.dataset.frogJumpStart), duration: Number(root.dataset.frogJumpDuration) } });
    }
}
