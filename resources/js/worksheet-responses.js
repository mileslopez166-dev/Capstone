const validStroke = stroke => ['#174d97', '#252c35', '#d73742'].includes(stroke?.color) &&
    Number.isFinite(stroke.width) && stroke.width >= 1 && stroke.width <= 12 &&
    Array.isArray(stroke.points) && stroke.points.length > 0 && stroke.points.length <= 1000 &&
    stroke.points.every(point => Array.isArray(point) && point.length === 2 && point.every(n => Number.isFinite(n) && n >= 0 && n <= 1));

export function normalizeResponses(value, definitions = {}) {
    const result = {};
    for (const [key, definition] of Object.entries(definitions)) {
        const input = value?.[key];
        if (!input || typeof input !== 'object' || Array.isArray(input)) continue;
        const fields = {};
        for (const field of definition.fields) {
            const answer = input[field.key];
            if (field.type === 'checkbox') {
                if (Array.isArray(answer)) fields[field.key] = [...new Set(answer.filter(option => field.options.includes(option)))];
            } else if (field.type === 'drawing') {
                if (Array.isArray(answer)) fields[field.key] = answer.filter(validStroke).slice(0, 100);
            } else if (typeof answer === 'string') {
                fields[field.key] = field.options && !field.options.includes(answer) ? '' : answer.slice(0, field.type === 'textarea' ? 2000 : 200);
            }
        }
        result[key] = fields;
    }
    return result;
}

export const hasResponses = responses => Object.values(responses || {}).some(fields =>
    Object.values(fields).some(value => Array.isArray(value) ? value.length > 0 : typeof value === 'string' && value.trim() !== ''));

export function initWorksheetResponses(root, getState, canEdit, changed) {
    const items = [...root.querySelectorAll('[data-item-response]')].map(element => {
        const pageIndex = Number(element.closest('[data-book-reading-page]').dataset.bookReadingPage);
        const key = element.dataset.itemResponse;
        const values = () => getState().pages[pageIndex].responses[key] || {};
        const write = (field, value) => {
            const page = getState().pages[pageIndex];
            page.responses[key] ||= {};
            page.responses[key][field] = value;
        };
        const inputs = [...element.querySelectorAll('[data-response-field]')];
        inputs.forEach(input => input.addEventListener(input.matches('select, [type=checkbox], [type=radio]') ? 'change' : 'input', () => {
            if (!canEdit() || pageIndex !== getState().page) return;
            const field = input.dataset.responseField;
            if (input.type === 'checkbox') {
                write(field, inputs.filter(other => other.dataset.responseField === field && other.checked).map(other => other.value));
            } else write(field, input.value);
            changed();
        }));
        const drawings = [...element.querySelectorAll('[data-item-drawing]')].map(board => {
            const canvas = board.querySelector('canvas');
            const context = canvas.getContext('2d');
            const field = board.dataset.itemDrawing;
            const lines = () => values()[field] || [];
            let active = null;
            const paint = () => {
                const width = Math.round(canvas.clientWidth * Math.min(devicePixelRatio || 1, 2));
                if (!width) return;
                canvas.width = width; canvas.height = Math.round(width * .6);
                for (const stroke of lines()) {
                    context.strokeStyle = context.fillStyle = stroke.color;
                    context.lineWidth = stroke.width * width / 600;
                    context.lineCap = context.lineJoin = 'round';
                    context.beginPath();
                    stroke.points.forEach(([x, y], index) => index ? context.lineTo(x * canvas.width, y * canvas.height) : context.moveTo(x * canvas.width, y * canvas.height));
                    context.stroke();
                    if (stroke.points.length === 1) {
                        const [x,y] = stroke.points[0];context.beginPath();context.arc(x*canvas.width,y*canvas.height,context.lineWidth/2,0,Math.PI*2);context.fill();
                    }
                }
                board.querySelectorAll('button').forEach(button => button.disabled = !canEdit() || !lines().length);
            };
            const point = event => {
                const box = canvas.getBoundingClientRect();
                return [Math.max(0, Math.min(1, (event.clientX-box.left)/box.width)), Math.max(0, Math.min(1, (event.clientY-box.top)/box.height))];
            };
            const finish = () => { if (active) { active = null; changed(); } };
            canvas.addEventListener('pointerdown', event => {
                if (!canEdit() || pageIndex !== getState().page || event.button !== 0 || !event.isPrimary || lines().length >= 100) return;
                event.preventDefault(); canvas.setPointerCapture(event.pointerId);
                active = {color:'#174d97',width:3,points:[point(event)]};
                write(field, [...lines(), active]);paint();
            });
            canvas.addEventListener('pointermove', event => {
                if (!active || !event.isPrimary) return;
                if (active.points.length < 1000) { active.points.push(point(event));paint(); }
            });
            for (const event of ['pointerup','pointercancel','lostpointercapture']) canvas.addEventListener(event, finish);
            window.addEventListener('pagehide', finish);
            board.querySelector('[data-item-undo]')?.addEventListener('click', () => { if (canEdit()) { write(field, lines().slice(0,-1));paint();changed(); } });
            board.querySelector('[data-item-clear]')?.addEventListener('click', () => { if (canEdit() && confirm('Clear this drawing?')) { write(field, []);paint();changed(); } });
            new ResizeObserver(paint).observe(canvas);
            return {paint, finish};
        });
        return {element, inputs, values, drawings, pageIndex};
    });
    return {
        finish: () => items.forEach(item => item.drawings.forEach(board => board.finish())),
        sync: () => items.forEach(item => {
            const editable = canEdit() && item.pageIndex === getState().page;
            item.inputs.forEach(input => {
                const value = item.values()[input.dataset.responseField];
                if (input.type === 'checkbox') input.checked = Array.isArray(value) && value.includes(input.value);
                else if (input.type === 'radio') input.checked = value === input.value;
                else if (input.value !== (value || '')) input.value = value || '';
                input.disabled = !editable;
            });
            item.drawings.forEach(board => board.paint());
        }),
    };
}
