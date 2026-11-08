import { gameNames, gameAnswerExists, gradeGameAnswer, gameHint } from './numeracy-game-rules.js';

export function initNumeracyGames(root, templates, getState, canEdit, write, advancePart = () => false) {
    const panel = root.querySelector('[data-numeracy-game]');
    if (!panel) return { sync() {} };
    const find = name => panel.querySelector(`[data-ng-${name}]`);
    const modeBar = root.querySelector('[data-numeracy-mode]');
    const select = root.querySelector('[data-numeracy-select]');
    const workspace = root.querySelector('.worksheet-workspace');
    const guide = root.querySelector('[data-numeracy-guide]');
    const guidePages = guide ? [...guide.querySelectorAll('[data-numeracy-guide-page]')] : [];
    const startedGuidePages = new Set();
    let page = -1;
    let entries = [];
    let items = [];
    let index = 0;
    let signature = '';
    let checked = false;
    let summary = false;
    const questionTimeLimitSeconds = Math.max(10, Math.min(3600, Number(panel.dataset.questionTimeLimitSeconds) || 120));
    let timerId = null;
    let timerDeadline = null;
    let timerItemKey = null;
    let timeoutPending = false;
    let advanceTimerId = null;
    let showingGuide = false;
    const current = () => items[index];
    const values = key => getState().pages[page]?.responses[key] || {};
    const answer = () => values(current().key)[current().game.field];
    const motion = () => !matchMedia('(prefers-reduced-motion: reduce)').matches && document.documentElement.dataset.reducedMotion !== 'true';
    const formatTime = seconds => `${Math.floor(seconds / 60).toString().padStart(2, '0')}:${Math.floor(seconds % 60).toString().padStart(2, '0')}`;

    function stopTimer(clearDeadline = true) {
        clearInterval(timerId);
        timerId = null;
        if (clearDeadline) {
            timerDeadline = null;
            timerItemKey = null;
            find('timer').hidden = true;
        }
    }

    function cancelAdvance() {
        clearTimeout(advanceTimerId);
        advanceTimerId = null;
    }

    function scheduleAdvance(delay = motion() ? 1800 : 900) {
        cancelAdvance();
        advanceTimerId = setTimeout(() => {
            advanceTimerId = null;
            advance();
        }, delay);
    }

    function updateTimer() {
        if (!current() || timerDeadline === null || panel.hidden || summary) return;
        const remaining = Math.max(0, Math.ceil((timerDeadline - Date.now()) / 1000));
        find('time').textContent = formatTime(remaining);
        find('timer').dataset.urgency = remaining <= 10 ? 'critical' : (remaining <= 30 ? 'warning' : 'normal');
        find('timer').setAttribute('aria-label', `${remaining} seconds remaining for this question`);
        if (remaining === 30 || remaining === 10) {
            find('timer').setAttribute('aria-live', 'polite');
            setTimeout(() => find('timer')?.setAttribute('aria-live', 'off'), 1000);
        }
        if (remaining <= 0) timeoutQuestion();
    }

    function startTimer(reset = true) {
        if (!current() || panel.hidden || summary || checked || !canEdit() || gameAnswerExists(current().game, values(current().key))) {
            find('timer').hidden = true;
            return;
        }
        find('timer').hidden = false;
        clearInterval(timerId);
        const canResume = !reset && timerItemKey === current().key && Number.isFinite(timerDeadline);
        if (!canResume) { timerItemKey = current().key; timerDeadline = Date.now() + questionTimeLimitSeconds * 1000; }
        timeoutPending = false;
        updateTimer();
        if (timerDeadline > Date.now()) timerId = setInterval(updateTimer, 250);
    }

    function advance() {
        if (!canEdit() || !checked) return;
        cancelAdvance();
        stopTimer();
        if (index < items.length - 1) { index++; render(); find('check').focus({ preventScroll: true }); }
        else {
            const remaining = entries.find(item => !gameAnswerExists(item.game, values(item.key)));
            if (remaining) {
                setType(remaining.game.type);
                find('check').focus({ preventScroll: true });
                return;
            }
            if (advancePart()) return;
            summary = true;
            panel.dataset.summary = 'true';
            find('summary').hidden = false;
            stats();
            find('summary').focus({ preventScroll: true });
        }
    }

    function timeoutQuestion() {
        if (timeoutPending || checked || !current() || !canEdit()) return;
        timeoutPending = true;
        stopTimer();
        write(current().key, { __timed_out: true });
        checked = true;
        panel.dataset.result = 'incorrect';
        find('feedback').textContent = "Time's up. This question was skipped.";
        find('next').hidden = false;
        find('check').hidden = true;
        syncDisabled();
        stats();
        scheduleAdvance();
    }

    function stats() {
        const done = items.filter(item => gameAnswerExists(item.game, values(item.key)));
        const correct = done.filter(item => values(item.key).__timed_out !== true && gradeGameAnswer(item.game, values(item.key)[item.game.field])?.correct).length;
        find('score').textContent = `${correct} / ${items.length} correct`;
        find('counter').textContent = `Question ${index + 1} of ${items.length}`;
        find('progress').max = items.length;
        find('progress').value = done.length;
        find('summary-text').textContent = `${correct} of ${items.length} correct. ${done.length} of ${items.length} answered.`;
    }

    function clearFeedback() {
        cancelAdvance();
        checked = false;
        panel.dataset.result = '';
        find('feedback').textContent = '';
        find('next').hidden = true;
        find('check').hidden = false;
        panel.getAnimations({ subtree: true }).forEach(animation => animation.cancel());
        panel.querySelectorAll('.numeracy-arrow, .numeracy-target-fragment').forEach(element => element.remove());
        panel.querySelectorAll('[data-hit]').forEach(target => delete target.dataset.hit);
        panel.querySelectorAll('[data-broken]').forEach(target => delete target.dataset.broken);
    }

    function breakTarget(target) {
        if (target.dataset.broken) return;
        const scene = find('scene');
        const sceneBox = scene.getBoundingClientRect();
        const box = target.getBoundingClientRect();
        const animated = motion();
        target.dataset.broken = 'true';
        for (let piece = 0; piece < 8; piece++) {
            const start = piece * Math.PI / 4;
            const end = start + Math.PI / 4;
            const middle = (start + end) / 2;
            const fragment = document.createElement('span');
            fragment.className = 'numeracy-target-fragment';
            fragment.setAttribute('aria-hidden', 'true');
            Object.assign(fragment.style, {
                left: `${box.left - sceneBox.left}px`, top: `${box.top - sceneBox.top}px`,
                width: `${box.width}px`, height: `${box.height}px`,
                clipPath: `polygon(50% 50%, ${50 + 75 * Math.cos(start)}% ${50 + 75 * Math.sin(start)}%, ${50 + 75 * Math.cos(end)}% ${50 + 75 * Math.sin(end)}%)`,
            });
            scene.append(fragment);
            if (!animated) {
                fragment.style.transform = `translate(${Math.cos(middle) * 7}px, ${Math.sin(middle) * 7}px)`;
                continue;
            }
            const x = Math.cos(middle) * box.width * .7;
            const y = Math.sin(middle) * box.height * .55;
            const rotation = (piece % 2 ? 1 : -1) * (35 + piece * 12);
            fragment.animate([
                { transform: 'translate(0, 0) rotate(0deg)', opacity: 1 },
                { transform: `translate(${x * .6}px, ${y - 20}px) rotate(${rotation * .4}deg)`, opacity: 1, offset: .4 },
                { transform: `translate(${x}px, ${y + 85}px) rotate(${rotation}deg)`, opacity: 0 },
            ], { duration: 650, easing: 'ease-in', fill: 'forwards' }).finished
                .then(() => fragment.remove()).catch(() => fragment.remove());
        }
    }

    function syncAnswer() {
        if (!current()) return;
        const value = answer();
        panel.querySelectorAll('[data-ng-option]').forEach(button => {
            const selected = Array.isArray(value) ? value.includes(button.dataset.ngOption) : value === button.dataset.ngOption;
            button.setAttribute('aria-pressed', String(selected));
        });
        const slot = find('slots').querySelector('[data-ng-blank]');
        if (slot) slot.textContent = value || '?';
        const reason = values(current().key).reason || '';
        if (find('reason').value !== reason) find('reason').value = reason;
        const nextSignature = JSON.stringify(values(current().key));
        if (signature !== nextSignature) clearFeedback();
        signature = nextSignature;
        stats();
    }

    function choose(value, button) {
        if (!canEdit()) return;
        const { game, key } = current();
        if (game.type === 'archer') {
            const selected = Array.isArray(answer()) ? answer() : [];
            value = selected.includes(value) ? selected.filter(item => item !== value) : [...selected, value];
        }
        write(key, { [game.field]: value, __timed_out: false });
        if (motion() && button.animate) button.animate([
            { transform: 'scale(1)' }, { transform: 'scale(.92)' }, { transform: 'scale(1)' },
        ], { duration: 230 });
    }

    function option(value, target = false) {
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.ngOption = value;
        button.className = target ? 'numeracy-target' : 'numeracy-choice';
        button.setAttribute('aria-pressed', 'false');
        button.setAttribute('aria-label', target ? `Divisor ${value}` : value);
        const label = document.createElement('span');
        label.textContent = value;
        button.append(label);
        if (target) {
            const hit = document.createElement('span');
            hit.className = 'numeracy-target-hit';
            hit.setAttribute('aria-hidden', 'true');
            button.append(hit);
        }
        button.addEventListener('click', () => choose(value, button));
        return button;
    }

    function render() {
        if (!current()) return;
        summary = false;
        clearFeedback();
        const { game } = current();
        panel.dataset.game = game.type;
        panel.dataset.summary = 'false';
        find('summary').hidden = true;
        find('title').textContent = gameNames[game.type];
        const description = typeof game.instruction === 'string' ? game.instruction.trim() : '';
        find('description').textContent = description;
        find('description').hidden = description === '';
        find('hint').hidden = true;
        find('hint-button').setAttribute('aria-expanded', 'false');
        find('hint').textContent = gameHint(game);
        find('choices').replaceChildren();
        find('targets').replaceChildren();
        find('slots').replaceChildren();
        find('reason-label').hidden = !game.reason;
        find('prompt').textContent = game.type === 'puzzle' ? (game.smallest ? 'Find the smallest digit' : 'Find the missing digit')
            : game.type === 'archer' ? 'Select every divisor of' : 'Is this number divisible?';
        find('number').textContent = game.type === 'puzzle' ? '' : game.number.toLocaleString();
        find('condition').textContent = game.type === 'archer' ? '' : `Divisible by ${game.divisor}${game.type === 'rocket' ? '?' : ''}`;
        find('check-label').textContent = game.type === 'rocket' ? 'Launch' : game.type === 'archer' ? 'Fire arrows' : 'Check digit';
        find('next-label').textContent = index === items.length - 1 ? 'Finish round' : 'Next question';
        if (game.type === 'puzzle') {
            for (const character of game.pattern) {
                const tile = document.createElement('span');
                tile.textContent = character === '_' ? '?' : character;
                if (character === '_') tile.dataset.ngBlank = '';
                find('slots').append(tile);
            }
            find('slots').setAttribute('aria-label', `Complete ${game.pattern.replace('_', 'blank')}`);
            for (let digit = 0; digit <= 9; digit++) find('choices').append(option(String(digit)));
        } else if (game.type === 'rocket') {
            for (const value of ['Yes', 'No']) find('choices').append(option(value));
        } else {
            for (const value of game.options) find('targets').append(option(value, true));
        }
        signature = '';
        syncAnswer();
        syncDisabled();
        startTimer();
    }

    function setType(type) {
        items = entries.filter(item => item.game.type === type);
        index = Math.max(0, items.findIndex(item => !gameAnswerExists(item.game, values(item.key))));
        select.value = type;
        render();
    }

    function updateVisibility(state = getState()) {
        const available = entries.length > 0 && state.step === 'answer';
        const visible = available && !showingGuide;
        modeBar.hidden = !visible;
        panel.hidden = !visible;
        if (guide) {
            guide.hidden = !available || !showingGuide;
            guidePages.forEach(item => { item.hidden = Number(item.dataset.numeracyGuidePage) !== page; });
        }
        workspace.hidden = guide ? available : visible;
        if (!visible) {
            panel.getAnimations({ subtree: true }).forEach(animation => animation.cancel());
            clearInterval(timerId);
            timerId = null;
            cancelAdvance();
        } else if (items.length && !checked) startTimer(false);
    }

    function syncDisabled() {
        panel.querySelectorAll('button, textarea').forEach(control => control.disabled = !canEdit());
        panel.querySelectorAll('[data-ng-option], [data-ng-reason]').forEach(control => control.disabled = !canEdit() || checked);
        find('prev').disabled = !canEdit() || index === 0;
        select.disabled = !canEdit();
    }

    function sync() {
        const state = getState();
        if (page !== state.page) {
            page = state.page;
            entries = Object.entries(templates[page]?.responses || {}).filter(([, item]) => gameNames[item.game?.type])
                .map(([key, item]) => ({ key, ...item }));
            showingGuide = Boolean(guide && !startedGuidePages.has(page)
                && !entries.some(item => gameAnswerExists(item.game, values(item.key))));
            select.replaceChildren();
            for (const type of [...new Set(entries.map(item => item.game.type))]) {
                const option = document.createElement('option');
                option.value = type;
                option.textContent = gameNames[type];
                select.append(option);
            }
            if (entries.length) {
                const first = entries.find(item => !gameAnswerExists(item.game, values(item.key))) || entries[0];
                setType(first.game.type);
            } else items = [];
        }
        select.hidden = select.options.length < 2;
        updateVisibility(state);
        if (items.length) {
            syncAnswer();
            syncDisabled();
            if (!panel.hidden && !checked) startTimer(false);
        }
    }

    guide?.addEventListener('click', event => {
        const button = event.target.closest('[data-numeracy-guide-proceed]');
        if (!button || !canEdit()) return;
        startedGuidePages.add(page);
        showingGuide = false;
        updateVisibility();
        find('check').focus({ preventScroll: true });
    });
    select.addEventListener('change', () => { if (canEdit()) setType(select.value); });
    find('reason').addEventListener('input', event => { if (canEdit()) write(current().key, { reason: event.target.value, __timed_out: false }); });
    find('hint-button').addEventListener('click', () => {
        find('hint').hidden = !find('hint').hidden;
        find('hint-button').setAttribute('aria-expanded', String(!find('hint').hidden));
    });
    find('check').addEventListener('click', () => {
        if (!canEdit()) return;
        const { game, key } = current();
        if (!gameAnswerExists(game, values(key))) {
            find('feedback').textContent = game.reason && answer() ? 'Add your explanation before launching.' : 'Choose an answer first.';
            if (game.reason && answer()) find('reason').focus();
            return;
        }
        const result = gradeGameAnswer(game, answer());
        stopTimer();
        checked = true;
        panel.dataset.result = result.correct ? 'correct' : 'incorrect';
        find('feedback').textContent = `${result.correct ? 'Correct!' : 'Not quite.'} ${result.explanation}`;
        find('next').hidden = false;
        find('check').hidden = true;
        syncDisabled();
        if (game.type === 'archer') {
            const scene = find('scene').getBoundingClientRect();
            const bow = find('bow');
            const bowBox = bow.getBoundingClientRect();
            const originX = bowBox.left + bowBox.width / 2 - scene.left;
            const originY = bowBox.top + bowBox.height * .28 - scene.top;
            panel.querySelectorAll('[data-ng-option][aria-pressed=true]').forEach((target, shot) => {
                const hit = game.number % Number(target.dataset.ngOption) === 0 ? 'correct' : 'incorrect';
                if (!motion()) {
                    if (hit === 'correct') breakTarget(target);
                    else target.dataset.hit = 'incorrect';
                    return;
                }
                const box = target.getBoundingClientRect();
                const x = box.left + box.width / 2 - scene.left;
                const y = box.top + box.height / 2 - scene.top;
                const angle = Math.atan2(y - originY, x - originX) * 180 / Math.PI;
                const arrow = document.createElement('span');
                arrow.className = 'numeracy-arrow';
                arrow.setAttribute('aria-hidden', 'true');
                find('scene').append(arrow);
                bow.animate([{ transform: 'scaleY(1)' }, { transform: 'scaleY(.85)' }, { transform: 'scaleY(1)' }],
                    { duration: 260, delay: shot * 160 });
                arrow.animate([
                    { transform: `translate(${originX - 44}px, ${originY}px) rotate(${angle}deg)`, opacity: 0 },
                    { transform: `translate(${originX - 44}px, ${originY}px) rotate(${angle}deg)`, opacity: 1, offset: .08 },
                    { transform: `translate(${x - 44}px, ${y}px) rotate(${angle}deg)`, opacity: 1 },
                ], { duration: 480, delay: shot * 160, fill: 'both', easing: 'ease-in' }).finished.then(() => {
                    if (arrow.isConnected && checked && !panel.hidden) {
                        if (hit === 'correct') breakTarget(target);
                        else target.dataset.hit = 'incorrect';
                    }
                    arrow.remove();
                }).catch(() => arrow.remove());
            });
        }
        if (motion()) {
            if (game.type === 'rocket') find('rocket').animate(result.correct ? [
                { transform: 'translateY(0) rotate(0)' }, { transform: 'translateY(12px) rotate(-3deg)', offset: .15 },
                { transform: 'translateY(-300px) rotate(6deg)' },
            ] : [{ transform: 'rotate(0)' }, { transform: 'rotate(-8deg)' }, { transform: 'rotate(8deg)' }, { transform: 'rotate(0)' }],
            { duration: result.correct ? 1600 : 600, easing: 'ease-in', fill: result.correct ? 'forwards' : 'none' });
            else if (game.type === 'puzzle') panel.querySelectorAll('[data-ng-blank]').forEach(element => {
                element.animate([{ transform: 'scale(1)' }, { transform: 'scale(1.12)' }, { transform: 'scale(1)' }], { duration: 450 });
            });
        }
        const shots = panel.querySelectorAll('[data-ng-option][aria-pressed=true]').length;
        scheduleAdvance(game.type === 'archer' && motion() ? Math.max(1800, (shots - 1) * 160 + 480 + 650 + 200) : undefined);
    });
    find('prev').addEventListener('click', () => { if (canEdit() && index > 0) { index--; render(); } });
    find('next').addEventListener('click', advance);
    find('return').addEventListener('click', () => { summary = false; index = 0; render(); sync(); find('check').focus({ preventScroll: true }); });
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) { clearInterval(timerId); timerId = null; cancelAdvance(); }
        else if (!panel.hidden && !summary) {
            if (checked) scheduleAdvance();
            else startTimer(false);
        }
    });
    window.addEventListener('pagehide', () => { clearInterval(timerId); timerId = null; cancelAdvance(); });
    return { sync, focus: () => { if (!panel.hidden && !summary) find('check').focus({ preventScroll: true }); } };
}
