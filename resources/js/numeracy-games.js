import { gameNames, gameAnswerExists, gradeGameAnswer, gameHint } from './numeracy-game-rules.js';

export function initNumeracyGames(root, templates, getState, canEdit, write) {
    const panel = root.querySelector('[data-numeracy-game]');
    if (!panel) return { sync() {} };
    const find = name => panel.querySelector(`[data-ng-${name}]`);
    const modeBar = root.querySelector('[data-numeracy-mode]');
    const select = root.querySelector('[data-numeracy-select]');
    const workspace = root.querySelector('.worksheet-workspace');
    const views = [...root.querySelectorAll('[data-numeracy-view]')];
    let page = -1;
    let entries = [];
    let items = [];
    let index = 0;
    let mode = 'game';
    let signature = '';
    let checked = false;
    let summary = false;
    const current = () => items[index];
    const values = key => getState().pages[page]?.responses[key] || {};
    const answer = () => values(current().key)[current().game.field];
    const motion = () => !matchMedia('(prefers-reduced-motion: reduce)').matches && document.documentElement.dataset.reducedMotion !== 'true';

    function stats() {
        const done = items.filter(item => gameAnswerExists(item.game, values(item.key)));
        const correct = done.filter(item => gradeGameAnswer(item.game, values(item.key)[item.game.field]).correct).length;
        find('score').textContent = `${correct} / ${items.length} correct`;
        find('counter').textContent = `Question ${index + 1} of ${items.length}`;
        find('progress').max = items.length;
        find('progress').value = done.length;
        find('summary-text').textContent = `${correct} of ${items.length} correct. ${done.length} of ${items.length} answered.`;
    }

    function clearFeedback() {
        checked = false;
        panel.dataset.result = '';
        find('feedback').textContent = '';
        find('next').hidden = true;
        find('check').hidden = false;
        panel.getAnimations({ subtree: true }).forEach(animation => animation.cancel());
        panel.querySelectorAll('.numeracy-arrow').forEach(arrow => arrow.remove());
        panel.querySelectorAll('[data-hit]').forEach(target => delete target.dataset.hit);
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
        write(key, { [game.field]: value });
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
    }

    function setType(type) {
        items = entries.filter(item => item.game.type === type);
        index = Math.max(0, items.findIndex(item => !gameAnswerExists(item.game, values(item.key))));
        select.value = type;
        render();
    }

    function syncDisabled() {
        panel.querySelectorAll('button, textarea').forEach(control => control.disabled = !canEdit());
        find('prev').disabled = !canEdit() || index === 0;
        select.disabled = !canEdit();
    }

    function sync() {
        const state = getState();
        if (page !== state.page) {
            page = state.page;
            entries = Object.entries(templates[page]?.responses || {}).filter(([, item]) => gameNames[item.game?.type])
                .map(([key, item]) => ({ key, ...item }));
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
        const available = entries.length > 0 && state.step === 'answer';
        const visible = available && mode === 'game';
        modeBar.hidden = !available;
        select.hidden = select.options.length < 2 || mode !== 'game';
        panel.hidden = !visible;
        workspace.hidden = visible;
        views.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.numeracyView === mode)));
        if (!visible) panel.getAnimations({ subtree: true }).forEach(animation => animation.cancel());
        if (items.length) { syncAnswer(); syncDisabled(); }
    }

    views.forEach(button => button.addEventListener('click', () => { mode = button.dataset.numeracyView; sync(); }));
    select.addEventListener('change', () => { if (canEdit()) setType(select.value); });
    find('reason').addEventListener('input', event => { if (canEdit()) write(current().key, { reason: event.target.value }); });
    find('hint-button').addEventListener('click', () => {
        find('hint').hidden = !find('hint').hidden;
        find('hint-button').setAttribute('aria-expanded', String(!find('hint').hidden));
    });
    find('check').addEventListener('click', () => {
        if (!canEdit()) return;
        const { game, key } = current();
        // An explicitly confirmed empty selection is different from an untouched item.
        if (game.type === 'archer' && !Array.isArray(answer())) write(key, { [game.field]: [] });
        if (!gameAnswerExists(game, values(key))) {
            find('feedback').textContent = game.reason && answer() ? 'Add your explanation before launching.' : 'Choose an answer first.';
            if (game.reason && answer()) find('reason').focus();
            return;
        }
        const result = gradeGameAnswer(game, answer());
        checked = true;
        panel.dataset.result = result.correct ? 'correct' : 'incorrect';
        find('feedback').textContent = `${result.correct ? 'Correct!' : 'Not quite.'} ${result.explanation}`;
        find('next').hidden = false;
        find('check').hidden = true;
        if (game.type === 'archer') {
            const scene = find('scene').getBoundingClientRect();
            panel.querySelectorAll('[data-ng-option][aria-pressed=true]').forEach((target, shot) => {
                target.dataset.hit = game.number % Number(target.dataset.ngOption) === 0 ? 'correct' : 'incorrect';
                if (!motion()) return;
                const box = target.getBoundingClientRect();
                const x = box.left + box.width / 2 - scene.left;
                const y = box.top + box.height / 2 - scene.top;
                const angle = Math.atan2(y - (scene.height - 28), x - scene.width / 2) * 180 / Math.PI;
                const arrow = document.createElement('span');
                arrow.className = 'numeracy-arrow';
                arrow.setAttribute('aria-hidden', 'true');
                find('scene').append(arrow);
                arrow.animate([
                    { transform: `translate(${scene.width / 2}px, ${scene.height - 28}px) rotate(${angle}deg)` },
                    { transform: `translate(${x}px, ${y}px) rotate(${angle}deg)` },
                ], { duration: 420, delay: shot * 110, fill: 'both', easing: 'ease-in' }).finished.then(() => arrow.remove()).catch(() => arrow.remove());
            });
        }
        if (motion()) {
            if (game.type === 'rocket') find('rocket').animate(result.correct ? [
                { transform: 'translateY(0) rotate(0)' }, { transform: 'translateY(12px) rotate(-3deg)', offset: .15 },
                { transform: 'translateY(-300px) rotate(6deg)' },
            ] : [{ transform: 'rotate(0)' }, { transform: 'rotate(-8deg)' }, { transform: 'rotate(8deg)' }, { transform: 'rotate(0)' }],
            { duration: result.correct ? 1600 : 600, easing: 'ease-in', fill: result.correct ? 'forwards' : 'none' });
            else panel.querySelectorAll(game.type === 'puzzle' ? '[data-ng-blank]' : '[data-ng-option][aria-pressed=true]').forEach(element => {
                element.animate([{ transform: 'scale(1)' }, { transform: 'scale(1.12)' }, { transform: 'scale(1)' }], { duration: 450 });
            });
        }
    });
    find('prev').addEventListener('click', () => { if (canEdit() && index > 0) { index--; render(); } });
    find('next').addEventListener('click', () => {
        if (!canEdit() || !checked) return;
        if (index < items.length - 1) { index++; render(); find('check').focus({ preventScroll: true }); }
        else {
            summary = true;
            panel.dataset.summary = 'true';
            find('summary').hidden = false;
            stats();
            find('summary').focus({ preventScroll: true });
        }
    });
    find('return').addEventListener('click', () => { mode = 'worksheet'; summary = false; render(); sync(); views[1].focus(); });
    return { sync, focus: () => { if (!panel.hidden && !summary) find('check').focus({ preventScroll: true }); } };
}
