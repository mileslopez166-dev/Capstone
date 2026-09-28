const percent = value => typeof value === 'number' && Number.isFinite(value)
    ? `${Number((value * 100).toFixed(2))}%` : 'Not available';

async function loadLevel(container) {
    const label = container.querySelector('[data-overall-level]');
    const retry = container.querySelector('[data-overall-retry]');
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 15000);
    container.setAttribute('aria-busy', 'true');
    label.textContent = 'Loading ML estimate...';
    retry.hidden = true;
    try {
        const response = await fetch(container.dataset.url, {
            headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: controller.signal,
        });
        if (!response.ok) throw new Error('ML estimate unavailable');
        const result = await response.json();
        if (!['ready', 'no_data', 'disabled', 'unavailable'].includes(result.status)) throw new Error('Invalid ML response');
        label.textContent = result.status === 'ready' ? result.prediction.prediction : {
            no_data: 'No scored assessments yet', disabled: 'ML is not enabled', unavailable: 'ML estimate unavailable',
        }[result.status];
        container.querySelector('[data-overall-score]').textContent = result.overall_percentage === null
            ? '' : `Overall score: ${result.overall_percentage}%`;
        container.querySelector('[data-overall-confidence]').textContent = result.status === 'ready'
            ? `Model confidence: ${percent(result.prediction.confidence)}` : '';
        container.querySelector('[data-overall-basis]').textContent = result.assessment_count > 0
            ? `${result.assessment_count} assessment${result.assessment_count === 1 ? '' : 's'} · Latest scored attempt each` : '';
        retry.hidden = result.status !== 'unavailable';
    } catch {
        label.textContent = 'ML estimate unavailable';
        container.querySelector('[data-overall-confidence]').textContent = '';
        retry.hidden = false;
    } finally {
        clearTimeout(timeout);
        container.setAttribute('aria-busy', 'false');
    }
}

// Limit concurrent inference requests so a large roster does not overload the model service.
const queue = [];
let active = 0;
function enqueue(container) {
    if (queue.includes(container) || container.dataset.loading === 'true') return;
    queue.push(container);
    drain();
}
function drain() {
    while (active < 2 && queue.length) {
        const container = queue.shift();
        container.dataset.loading = 'true';
        active++;
        loadLevel(container).finally(() => {
            container.dataset.loading = 'false';
            active--;
            drain();
        });
    }
}
const observer = 'IntersectionObserver' in window ? new IntersectionObserver(entries => {
    entries.filter(entry => entry.isIntersecting).forEach(entry => {
        observer.unobserve(entry.target);
        enqueue(entry.target);
    });
}, { rootMargin: '200px' }) : null;
document.querySelectorAll('[data-teacher-ml-level]').forEach(container => {
    container.querySelector('[data-overall-retry]').addEventListener('click', () => enqueue(container));
    if (observer) observer.observe(container);
    else enqueue(container);
});
