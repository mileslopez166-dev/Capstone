import { normalizeResponses, hasResponses, initWorksheetResponses } from './worksheet-responses.js';

const clamp = (value, min, max) => Math.max(min, Math.min(max, value));
const viewKey = 'pgaals-worksheet-view';

export function worksheetSettings(value) {
    return {
        zoom: Number.isInteger(value?.zoom) && value.zoom >= 100 && value.zoom <= 300 && value.zoom % 25 === 0 ? value.zoom : 100,
        spacing: Number.isFinite(value?.spacing) && value.spacing >= 1.4 && value.spacing <= 2 ? Math.round(value.spacing * 10) / 10 : 1.7,
    };
}

export function worksheetState(state, count, templates = []) {
    return {
        page: clamp(Number.isInteger(state?.page) ? state.page : 0, 0, count - 1),
        step: state?.step === 'answer' ? 'answer' : 'read',
        pages: Array.from({ length: count }, (_, i) => ({
            text: typeof state?.pages?.[i]?.text === 'string' ? state.pages[i].text.slice(0, 10000) : '',
            responses: normalizeResponses(state?.pages?.[i]?.responses, templates[i]?.responses),
            answers: Array.isArray(state?.pages?.[i]?.answers) ? state.pages[i].answers.slice(0, 100).map((entry, index) => ({
                label: typeof entry?.label === 'string' ? entry.label.slice(0, 40) : String(index + 1),
                answer: typeof entry?.answer === 'string' ? entry.answer.slice(0, 2000) : '',
            })) : [],
            shading: Object.fromEntries(Object.entries(state?.pages?.[i]?.shading && typeof state.pages[i].shading === 'object' ? state.pages[i].shading : {})
                .filter(([key, cells]) => /^\d+-\d+$/.test(key) && Array.isArray(cells)).slice(0, 100)
                .map(([key, cells]) => [key, [...new Set(cells.filter(cell => Number.isInteger(cell) && cell >= 0 && cell < 100))]])),
            strokes: Array.isArray(state?.pages?.[i]?.strokes) ? state.pages[i].strokes.filter(stroke =>
                ['#174d97', '#252c35', '#d73742'].includes(stroke?.color) && Number.isFinite(stroke.width) && stroke.width >= 1 && stroke.width <= 12 &&
                Array.isArray(stroke.points) && stroke.points.length > 0 && stroke.points.length <= 1000 &&
                stroke.points.every(point => Array.isArray(point) && point.length === 2 && point.every(n => Number.isFinite(n) && n >= 0 && n <= 1))
            ).slice(0, 300) : [],
        })),
    };
}

export const answeredParts = (pages, templates = []) => pages.filter((page, index) => !templates[index]?.readingOnly &&
    (hasResponses(page.responses) || page.text.trim() || page.strokes.length || page.answers?.some(entry => entry.answer.trim()) || Object.values(page.shading || {}).some(cells => cells.length))).length;

export function initWorksheetBook(root) {
    const config = JSON.parse(root.querySelector('[data-worksheet-config]').textContent);
    const readonly = root.dataset.readonly === 'true';
    const find = name => root.querySelector(`[data-book-${name}]`);
    const status = find('status');
    const image = find('image');
    const canvas = find('canvas');
    const paper = find('paper');
    const context = canvas.getContext('2d');
    let state = worksheetState(config.state, config.pages.length, config.pages);
    let revision = Number(config.revision || 0);
    let storedRevision = revision;
    let timer;
    let pending = null;
    let submitting = false;
    let submitted = false;
    let conflict = false;
    let settings = worksheetSettings();
    try { settings = worksheetSettings(JSON.parse(localStorage.getItem(viewKey))); } catch {}
    let mode = 'read';
    let color = '#174d97';
    let stroke = null;
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const requiredParts = config.pages.filter(page => !page.readingOnly).length;

    if (!readonly) {
        try {
            const backup = JSON.parse(localStorage.getItem(config.storageKey));
            if (Number.isSafeInteger(backup?.revision) && backup.revision > revision) {
                state = worksheetState(backup, config.pages.length, config.pages);
                revision = backup.revision;
                status.textContent = 'Restored answers from this device';
            }
        } catch {}
    }
    const responses = initWorksheetResponses(root, () => state, () => !readonly && !submitting && !submitted && state.step === 'answer', changed);
    const snapshot = () => ({ ...state, revision, attempt_key: config.attemptKey });
    function backup() {
        try { localStorage.setItem(config.storageKey, JSON.stringify(snapshot())); return true; } catch { return false; }
    }
    function changed() {
        if (readonly || submitted) return;
        revision++;
        status.textContent = backup() ? 'Saved on this device; syncing...' : 'Saving...';
        updateControls();
        clearTimeout(timer);
        timer = setTimeout(save, 650);
    }
    async function request(url, body, leaving = false) {
        const response = await fetch(url, { method: 'POST', credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body, keepalive: leaving && new Blob([body]).size < 60000 });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'Unable to save. Please try again.');
        return result;
    }
    async function save(leaving = false) {
        if (readonly || submitted || conflict) return false;
        clearTimeout(timer);
        if (pending) { await pending; if (storedRevision >= revision) return true; }
        if (storedRevision >= revision) return true;
        const savedRevision = revision;
        status.textContent = 'Saving...';
        const operation = request(config.saveUrl, JSON.stringify(snapshot()), leaving).then(result => {
            if (result.revision > savedRevision) {
                conflict = true;
                throw new Error('Newer work was saved in another tab. Reload before continuing.');
            }
            storedRevision = savedRevision;
            status.textContent = revision === savedRevision ? 'All changes saved' : 'Saving latest changes...';
            return true;
        }).catch(error => {
            status.textContent = navigator.onLine ? error.message : (backup() ? 'Offline - saved on this device' : 'Offline - answers not saved. Keep this page open.');
            return false;
        });
        pending = operation;
        const result = await operation;
        if (pending === operation) pending = null;
        if (result && revision > storedRevision && !leaving) timer = setTimeout(save, 200);
        return result;
    }
    function draw() {
        const width = Math.max(1, Math.min(2400, Math.round(paper.clientWidth * Math.min(devicePixelRatio || 1, 2))));
        const ratio = image.naturalHeight / image.naturalWidth || .7074;
        canvas.width = width;
        canvas.height = Math.round(width * ratio);
        for (const line of state.pages[state.page].strokes) {
            context.strokeStyle = line.color;
            context.fillStyle = line.color;
            context.lineWidth = line.width * width / 1000;
            context.lineCap = 'round';
            context.lineJoin = 'round';
            context.beginPath();
            line.points.forEach(([x, y], i) => i ? context.lineTo(x * canvas.width, y * canvas.height) : context.moveTo(x * canvas.width, y * canvas.height));
            context.stroke();
            if (line.points.length === 1) {
                context.beginPath(); context.arc(line.points[0][0] * canvas.width, line.points[0][1] * canvas.height, context.lineWidth / 2, 0, Math.PI * 2); context.fill();
            }
        }
    }
    function updateControls() {
        const completed = answeredParts(state.pages, config.pages);
        find('prev').disabled = submitting || state.page === 0;
        find('next').disabled = submitting || state.page === config.pages.length - 1;
        find('part').disabled = submitting;
        find('part').value = String(state.page);
        find('response-part').textContent = `Part ${state.page + 1}`;
        find('page-label').textContent = `Part ${state.page + 1}`;
        find('completion').textContent = `${completed} / ${requiredParts} parts with answers`;
        find('progress').max = requiredParts;
        find('progress').value = completed;
        if (!readonly) {
            root.querySelectorAll('[data-book-step], [data-book-start-answer], [data-book-save], [data-answer-remove], [data-book-tool]').forEach(button => button.disabled = submitting);
            root.querySelectorAll('[data-answer-label], [data-answer-value]').forEach(input => input.readOnly = submitting);
            find('undo').disabled = submitting || !state.pages[state.page].strokes.length;
            find('clear').disabled = submitting || !state.pages[state.page].strokes.length;
            find('submit').disabled = submitting || conflict || completed !== requiredParts;
            find('continue').disabled = submitting || (!config.pages[state.page].readingOnly && !answeredParts([state.pages[state.page]]));
            find('continue-label').textContent = state.page < config.pages.length - 1 ? 'Next Part' : completed === requiredParts ? 'Finish Worksheet' : 'Next Unanswered Part';
        }
        syncModels();
        responses.sync();
    }
    function setTool(value) {
        mode = value;
        paper.dataset.mode = mode;
        root.querySelectorAll('[data-book-tool]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.bookTool === mode)));
    }
    function showStep(step, focus = false) {
        responses.finish();
        if (stroke) endStroke();
        state.step = readonly ? 'answer' : step;
        root.dataset.step = state.step;
        root.querySelectorAll('[data-book-step]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.bookStep === state.step)));
        root.querySelectorAll('[data-book-answer-only]').forEach(element => element.hidden = state.step !== 'answer');
        if (!readonly) find('start-answer').hidden = state.step === 'answer';
        setTool('read');
        draw();
        syncModels();
        responses.sync();
        if (focus) {
            const page = root.querySelector(`[data-book-reading-page="${state.page}"]`);
            const target = state.step === 'answer' ? page.querySelector('[data-response-field], [data-model-cell], [data-temperature-input]') || page : page;
            target?.focus({ preventScroll: true });
            target?.scrollIntoView({ block: 'nearest' });
        }
    }
    function syncModels() {
        const page = root.querySelector(`[data-book-reading-page="${state.page}"]`);
        if (!page) return;
        page.querySelectorAll('[data-book-model]').forEach(model => model.querySelectorAll('[data-model-cell]').forEach(button => {
            button.setAttribute('aria-pressed', String(state.pages[state.page].shading[model.dataset.bookModel]?.includes(Number(button.dataset.modelCell)) || false));
            button.disabled = readonly || submitting || state.step !== 'answer';
        }));
        page.querySelectorAll('[data-book-temperature]').forEach(model => {
            const value = state.pages[state.page].responses[model.dataset.temperatureItem]?.temperature ?? state.pages[state.page].answers.find(entry => entry.label === model.dataset.bookTemperature)?.answer ?? '';
            const match = value.match(/^\s*(-?\d+)(?:\s*(?:degrees\s*)?(?:C|Celsius))?\s*$/i);
            const mark = match ? Number(match[1]) : null;
            const valid = mark !== null && mark >= -30 && mark <= 50;
            const height = valid ? (mark + 30) * 3 : 0;
            const fill = model.querySelector('[data-temperature-fill]');
            fill.setAttribute('height', height); fill.setAttribute('y', 260 - height);
            model.querySelector('[data-temperature-output]').textContent = valid ? `${mark} C` : 'Not marked';
            const input = model.querySelector('[data-temperature-input]');
            input.value = valid ? mark : -30;
            input.disabled = readonly || submitting || state.step !== 'answer';
        });
    }
    function renderAnswers() {
        const entries = state.pages[state.page].answers;
        find('legacy').hidden = !entries.some(entry => entry.answer.trim());
        find('answers').replaceChildren();
        entries.forEach((entry, index) => {
            const row = find('answer-template').content.firstElementChild.cloneNode(true);
            const label = row.querySelector('[data-answer-label]');
            const answer = row.querySelector('[data-answer-value]');
            label.value = entry.label;
            answer.value = entry.answer;
            label.setAttribute('aria-label', `Item label for answer ${index + 1}`);
            answer.setAttribute('aria-label', `Answer ${index + 1}`);
            if (!readonly) {
                label.addEventListener('input', () => { entry.label = label.value; changed(); });
                answer.addEventListener('input', () => { entry.answer = answer.value; changed(); });
                const remove = row.querySelector('[data-answer-remove]');
                remove.setAttribute('aria-label', `Remove answer ${index + 1}`);
                remove.addEventListener('click', () => {
                    if (entry.answer.trim() && !confirm('Remove this answer?')) return;
                    entries.splice(index, 1); renderAnswers(); changed();
                    find('text').focus();
                });
            }
            find('answers').append(row);
        });
    }
    function showPage(index) {
        responses.finish();
        if (stroke) endStroke();
        state.page = clamp(index, 0, config.pages.length - 1);
        image.src = config.pages[state.page].url;
        image.alt = config.pages[state.page].alt;
        find('text').value = state.pages[state.page].text;
        find('notes').open = Boolean(state.pages[state.page].text.trim());
        root.querySelectorAll('[data-book-reading-page]').forEach(page => page.hidden = Number(page.dataset.bookReadingPage) !== state.page);
        find('reading').scrollTo(0, 0);
        renderAnswers();
        find('viewport').scrollTo(0, 0);
        updateControls(); draw();
    }
    function navigate(index) { if (submitting) return; showPage(index); showStep('read'); changed(); }
    find('prev').addEventListener('click', () => navigate(state.page - 1));
    find('next').addEventListener('click', () => navigate(state.page + 1));
    find('part').addEventListener('change', event => navigate(Number(event.target.value)));
    function applySettings(persist = false) {
        paper.style.width = `${settings.zoom}%`;
        root.style.setProperty('--worksheet-line-height', settings.spacing);
        find('zoom-label').textContent = `${settings.zoom}%`;
        find('setting-zoom').textContent = `${settings.zoom}%`;
        find('zoom-range').value = settings.zoom;
        find('setting-spacing').textContent = settings.spacing.toFixed(1);
        find('spacing').value = settings.spacing;
        const size = window.PgaalsPreferences?.readingSizes.answers ?? 18;
        find('answer-size').value = size;
        find('setting-size').textContent = `${size}px`;
        const readingSize = window.PgaalsPreferences?.readingSizes.story ?? 22;
        find('reading-size').value = readingSize;
        find('setting-reading').textContent = `${readingSize}px`;
        find('font-label').textContent = `${readingSize}px`;
        root.querySelector('[data-book-font="-"]').disabled = readingSize <= 16;
        root.querySelector('[data-book-font="+"]').disabled = readingSize >= 32;
        root.querySelector('[data-book-zoom="-"]').disabled = settings.zoom === 100;
        root.querySelector('[data-book-zoom="+"]').disabled = settings.zoom === 300;
        if (persist) { try { localStorage.setItem(viewKey, JSON.stringify(settings)); } catch {} }
        draw();
    }
    root.querySelectorAll('[data-book-zoom]').forEach(button => button.addEventListener('click', () => {
        settings.zoom = clamp(settings.zoom + (button.dataset.bookZoom === '+' ? 25 : -25), 100, 300);
        applySettings(true);
    }));
    find('zoom-range').addEventListener('input', event => { settings.zoom = Number(event.target.value); applySettings(true); });
    find('spacing').addEventListener('input', event => { settings.spacing = Number(event.target.value); applySettings(true); });
    find('answer-size').addEventListener('input', event => window.PgaalsPreferences?.setReadingSize('answers', event.target.value));
    find('reading-size').addEventListener('input', event => window.PgaalsPreferences?.setReadingSize('story', event.target.value));
    root.querySelectorAll('[data-book-font]').forEach(button => button.addEventListener('click', () => {
        const size = window.PgaalsPreferences?.readingSizes.story ?? 22;
        window.PgaalsPreferences?.setReadingSize('story', clamp(size + (button.dataset.bookFont === '+' ? 2 : -2), 16, 32));
    }));
    find('reset-settings').addEventListener('click', () => {
        settings = worksheetSettings();
        window.PgaalsPreferences?.setReadingSize('answers', 18);
        window.PgaalsPreferences?.setReadingSize('story', 22);
        applySettings(true);
    });
    const closeSettings = () => { find('settings').open = false; find('settings').querySelector('summary').focus(); };
    find('close-settings').addEventListener('click', closeSettings);
    find('settings').addEventListener('keydown', event => { if (event.key === 'Escape') { event.preventDefault(); closeSettings(); } });
    document.addEventListener('click', event => { if (!find('settings').contains(event.target)) find('settings').open = false; });
    window.addEventListener('pgaals:preferences', () => applySettings());
    window.addEventListener('storage', event => {
        if (event.key !== viewKey) return;
        try { settings = worksheetSettings(JSON.parse(event.newValue)); } catch { settings = worksheetSettings(); }
        applySettings();
    });
    image.addEventListener('load', draw);
    image.addEventListener('error', () => status.textContent = 'This worksheet page could not load. Reconnect and reload to try again.');
    const resize = new ResizeObserver(draw);
    resize.observe(paper);
    find('original').addEventListener('toggle', draw);
    showPage(state.page);
    showStep(state.step);
    root.dataset.initialized = 'true';
    applySettings();
    if (readonly) return;

    root.querySelectorAll('[data-book-model]').forEach(model => model.addEventListener('click', event => {
        const button = event.target.closest('[data-model-cell]');
        if (!button || button.disabled) return;
        const key = model.dataset.bookModel;
        const cells = state.pages[state.page].shading[key] || [];
        const cell = Number(button.dataset.modelCell);
        state.pages[state.page].shading[key] = cells.includes(cell) ? cells.filter(value => value !== cell) : [...cells, cell];
        changed();
    }));
    root.querySelectorAll('[data-book-temperature]').forEach(model => model.querySelector('input').addEventListener('input', event => {
        if (submitting || state.step !== 'answer') return;
        const page = state.pages[state.page];
        page.responses[model.dataset.temperatureItem] ||= {};
        page.responses[model.dataset.temperatureItem].temperature = event.target.value;
        changed();
    }));
    const answerStep = () => { showStep('answer', true); changed(); };
    find('start-answer').addEventListener('click', answerStep);
    root.querySelectorAll('[data-book-step]').forEach(button => button.addEventListener('click', () => { showStep(button.dataset.bookStep); changed(); }));
    find('continue').addEventListener('click', () => {
        if (state.page < config.pages.length - 1) {
            navigate(state.page + 1); root.querySelector(`[data-book-reading-page="${state.page}"]`).focus();
        } else if (answeredParts(state.pages, config.pages) < requiredParts) {
            navigate(state.pages.findIndex((page, index) => !config.pages[index].readingOnly && !answeredParts([page])));
            root.querySelector(`[data-book-reading-page="${state.page}"]`).focus();
        } else find('submit').click();
    });
    find('text').addEventListener('input', event => { state.pages[state.page].text = event.target.value; changed(); });
    find('save').addEventListener('click', () => save());
    root.querySelectorAll('[data-book-tool]').forEach(button => button.addEventListener('click', () => {
        setTool(button.dataset.bookTool);
    }));
    root.querySelectorAll('[data-book-color]').forEach(button => button.addEventListener('click', () => {
        color = button.dataset.bookColor;
        root.querySelectorAll('[data-book-color]').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
    }));
    const point = event => { const box = canvas.getBoundingClientRect(); return [clamp((event.clientX - box.left) / box.width, 0, 1), clamp((event.clientY - box.top) / box.height, 0, 1)]; };
    canvas.addEventListener('pointerdown', event => {
        if (mode !== 'draw' || submitting || event.button !== 0 || !event.isPrimary) return;
        if (state.pages[state.page].strokes.length >= 300) { status.textContent = 'Drawing limit reached. Undo a stroke or use Answers & Working.'; return; }
        event.preventDefault(); canvas.setPointerCapture(event.pointerId);
        stroke = { color, width: Number(find('width').value), points: [point(event)] };
        state.pages[state.page].strokes.push(stroke); draw();
    });
    canvas.addEventListener('pointermove', event => {
        if (!stroke || !event.isPrimary) return;
        if (stroke.points.length < 1000) { stroke.points.push(point(event)); draw(); }
    });
    function endStroke() { if (stroke) { stroke = null; changed(); } }
    canvas.addEventListener('pointerup', endStroke);
    canvas.addEventListener('pointercancel', endStroke);
    canvas.addEventListener('lostpointercapture', endStroke);
    find('undo').addEventListener('click', () => { state.pages[state.page].strokes.pop(); draw(); changed(); });
    find('clear').addEventListener('click', () => {
        if (confirm('Clear the drawing on this part? Typed answers will remain.')) { state.pages[state.page].strokes = []; draw(); changed(); }
    });
    find('submit').addEventListener('click', async () => {
        if (submitting || conflict || !confirm('Submit this worksheet for teacher review? You cannot edit it after submitting.')) return;
        responses.finish(); endStroke(); submitting = true; clearTimeout(timer); updateControls();
        find('text').readOnly = true;
        try {
            if (pending) await pending;
            if (conflict) throw new Error('Reload to resolve newer saved work before submitting.');
            revision++; backup();
            status.textContent = 'Submitting worksheet...';
            const result = await request(config.submitUrl, JSON.stringify(snapshot()));
            submitted = true;
            try { localStorage.removeItem(config.storageKey); } catch {}
            location.assign(result.url);
        } catch (error) { status.textContent = error.message; submitting = false; find('text').readOnly = false; updateControls(); }
    });
    window.addEventListener('online', () => save());
    window.addEventListener('pagehide', () => { endStroke(); if (!submitted) { backup(); save(true); } });
    document.addEventListener('visibilitychange', () => { if (document.hidden && !submitted) { endStroke(); backup(); save(true); } });
    window.addEventListener('beforeunload', event => {
        if (!submitted && (stroke || (revision > storedRevision && !backup()))) { event.preventDefault(); event.returnValue = ''; }
    });
    if (revision > storedRevision) save();
    initMultiplicationTable(root, config);
}

function initMultiplicationTable(root, config) {
    const find = name => root.querySelector(`[data-book-table-${name}]`);
    const dialog = find('dialog');
    let allowed = Boolean(config.tableAllowed);
    let busy = false;
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const clearPassword = () => { find('password').value = ''; };
    find('close').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', clearPassword);
    window.addEventListener('pagehide', clearPassword);
    async function fetchTable(password) {
        if (busy) return;
        busy = true;
        find('unlock').disabled = true;
        find('content').hidden = true;
        find('status').textContent = 'Checking permission...';
        const payload = {attempt_key: config.attemptKey};
        if (password !== undefined) payload.password = password;
        clearPassword();
        try {
            const response = await fetch(config.tableUrl, {method: 'POST', credentials: 'same-origin', cache: 'no-store',
                headers: {Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf}, body: JSON.stringify(payload)});
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'Unable to check permission. Try again.');
            allowed = true;
            find('form').hidden = true;
            find('status').textContent = 'Teacher approved for this attempt';
            const table = document.createElement('table');
            const caption = table.createCaption(); caption.textContent = 'Multiplication facts 1 to 12';
            const heading = table.createTHead().insertRow();
            for (let column = 0; column <= 12; column++) {
                const cell = document.createElement('th'); cell.scope = 'col'; cell.textContent = column || '\u00d7'; heading.append(cell);
            }
            const body = table.createTBody();
            result.table.forEach((values, index) => {
                const row = body.insertRow(); const title = document.createElement('th'); title.scope = 'row'; title.textContent = index + 1; row.append(title);
                values.forEach(value => { row.insertCell().textContent = value; });
            });
            find('content').replaceChildren(table); find('content').hidden = false;
            find('open').title = 'Multiplication table'; find('open').setAttribute('aria-label', 'Multiplication table');
        } catch (error) {
            find('content').replaceChildren();
            find('status').textContent = navigator.onLine ? error.message : 'Connect to the internet to check teacher permission.';
            find('form').hidden = false;
        } finally { delete payload.password; busy = false; find('unlock').disabled = false; }
    }
    find('open').addEventListener('click', () => {
        dialog.showModal(); clearPassword();
        find('content').hidden = true;
        find('form').hidden = allowed;
        find('status').textContent = '';
        if (allowed) fetchTable(); else find('password').focus();
    });
    find('form').addEventListener('submit', event => { event.preventDefault(); fetchTable(find('password').value); });
}

if (typeof document !== 'undefined') document.querySelectorAll('[data-worksheet-reader]').forEach(initWorksheetBook);
