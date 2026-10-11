export const RALLY_RIVALS = [1, 4, 8];

// Race position is derived from saved answers, never time spent reading.
export function rallyStanding(completed, correct, total) {
    total = Math.max(1, Number(total) || 1);
    completed = Math.max(0, Math.min(total, Number(completed) || 0));
    correct = Math.max(0, Math.min(completed, Number(correct) || 0));
    const margin = 12 * (correct - .5 * (completed - correct)) / total;
    const position = 1 + RALLY_RIVALS.filter(rival => rival >= margin).length;
    return { margin, position, label: ['1st', '2nd', '3rd', '4th'][position - 1], progress: completed / total };
}

export function initReadingRally(root) {
    if (!root || root.dataset.initialized) return;
    root.dataset.initialized = 'true';
    const banner = root.querySelector('[data-rally-banner]');
    const status = root.querySelector('#rally-mission-status');
    let stopped = false;
    let cleanupScene;
    const standing = () => rallyStanding(root.dataset.completed, root.dataset.correct, root.dataset.total);

    function announce(icon, text) {
        banner.querySelector('.material-symbols-outlined').textContent = icon;
        banner.querySelector('strong').textContent = text;
    }
    function update() {
        const race = standing();
        root.dataset.position = String(race.position);
        root.dataset.margin = String(race.margin);
        root.querySelector('[data-rally-position]').textContent = race.label;
        root.querySelector('[data-rally-distance]').textContent = `${Math.round(race.progress * 100)}%`;
        RALLY_RIVALS.forEach((offset, i) => root.style.setProperty(`--rival-${i}`, `${Math.max(10, Math.min(80, 52 + (race.margin - offset) * 3))}%`));
        status.textContent = race.progress === 0 ? 'At the starting line.' : `You are in ${race.label} place.`;
        if (root.dataset.finished === 'true') announce('flag', `${race.label} place - race complete!`);
    }
    root.addEventListener('rally:progress', update);
    root.addEventListener('rally:question', () => {
        root.dataset.outcome = '';
        announce('sports_motorsports', Number(root.dataset.completed) ? 'Back on track' : 'Ready to race');
    });
    root.addEventListener('rally:answer', event => {
        root.dataset.outcome = event.detail.correct ? 'correct' : 'incorrect';
        announce(event.detail.correct ? 'bolt' : 'tire_repair', event.detail.correct ? 'BOOST!' : event.detail.timeout ? 'Time up - recovering' : 'Skid! Keep going');
        update();
    });
    root.addEventListener('rally:finish', () => {
        root.dataset.finished = 'true';
        update();
    });
    function stop() {
        stopped = true;
        cleanupScene?.();
    }
    root.addEventListener('rally:finished', stop, { once: true });
    window.addEventListener('pagehide', event => { if (!event.persisted) stop(); });
    update();
    import('./reading-rally-scene').then(({ initRallyScene }) => {
        if (!stopped) cleanupScene = initRallyScene(root);
    }).catch(() => { root.dataset.renderer = 'fallback'; });
}
