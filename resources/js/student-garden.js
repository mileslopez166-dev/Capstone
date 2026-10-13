import { createGardenScene } from './garden-scene';
import { preferences } from './ui-preferences';

const canvas = document.querySelector('.student-garden-canvas');
const garden = createGardenScene(canvas, { campus: true });

if (garden) {
    let frame = 0;
    let previous = 0;
    let elapsed = 0;
    let suspended = false;

    function draw() {
        const root = document.documentElement;
        garden.draw(elapsed, root.classList.contains('dark') || root.classList.contains('student-night'));
    }

    function tick(now) {
        if (previous) elapsed += Math.min((now - previous) / 1000, .05);
        previous = now;
        draw();
        frame = requestAnimationFrame(tick);
    }

    function sync() {
        cancelAnimationFrame(frame);
        previous = 0;
        draw();
        if (!preferences.reducedMotion && !document.hidden && !suspended) {
            frame = requestAnimationFrame(tick);
        }
    }

    const resize = new ResizeObserver(() => {
        garden.resize();
        draw();
    });
    resize.observe(canvas);
    new MutationObserver(draw).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    window.addEventListener('pgaals:preferences', sync);
    document.addEventListener('visibilitychange', sync);
    window.addEventListener('pagehide', () => { suspended = true; sync(); });
    window.addEventListener('pageshow', () => { suspended = false; sync(); });
    garden.resize();
    sync();
}
