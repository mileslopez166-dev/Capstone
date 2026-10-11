const canvas = document.querySelector('.login-playground-canvas');

if (canvas) {
    const ctx = canvas.getContext('2d');
    if (ctx) initPlayground(canvas, ctx);
}

function initPlayground(canvas, ctx) {
    const page = canvas.closest('.login-page');
    const button = page.querySelector('.login-motion-toggle');
    const marker = page.querySelector('.login-campus-marker');
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let paused = motion.matches;
    let dark = document.documentElement.classList.contains('dark');
    let frame = 0;
    let previous = 0;
    let elapsed = 0;
    let width = 0;
    let height = 90;
    let playingWidth = 0;
    let playOffset = 0;

    const rect = (x, y, w, h, color) => {
        ctx.fillStyle = color;
        ctx.fillRect(Math.round(x), Math.round(y), w, h);
    };
    const ease = value => value * value * (3 - 2 * value);
    const clamp = value => Math.max(0, Math.min(1, value));

    function child(x, floor, direction, shirt, hair, ponytail, run, kick, jump, time) {
        // Feet stay on the shared ground line; only the jump arc lifts the sprite.
        rect(x - 7 + jump / 5, floor + 1, Math.max(7, 16 - jump / 2), 2, '#244b4866');
        ctx.save();
        ctx.translate(Math.round(x), Math.round(floor - jump));
        ctx.scale(direction, 1);
        const step = run ? [0, 3, 1, -3][Math.floor(time * 10) % 4] : 0;
        const bob = run && Math.abs(step) === 3 ? -1 : 0;
        // Back leg and arm, then the body and foreground limbs.
        rect(-3 - step, -8, 3, 7, '#243852');
        rect(-4 - step, -2, 5, 2, '#e6eff4');
        rect(-6, -18 + bob, 3, 7, '#1c4560');
        rect(-6 - step / 2, -12 + bob, 3, 3, '#c97d53');
        rect(-4, -20 + bob, 9, 12, '#203344');
        rect(-3, -19 + bob, 7, 9, shirt);
        rect(-2, -17 + bob, 2, 6, '#d9f0f0');
        rect(-4, -31 + bob, 10, 12, hair);
        rect(-3, -27 + bob, 9, 7, '#efac78');
        rect(-4, -30 + bob, 10, 5, hair);
        rect(-4, -25 + bob, 3, 4, hair);
        rect(3, -25 + bob, 2, 2, '#192936');
        rect(5, -23 + bob, 2, 2, '#efac78');
        rect(3, -21 + bob, 3, 1, '#9a4f44');
        if (ponytail) {
            rect(-8, -29 + bob, 5, 4, hair);
            rect(-9, -26 + bob + (step > 0 ? 1 : 0), 4, 8, hair);
            rect(-6, -28 + bob, 2, 3, '#ef7894');
        }
        const arm = jump > 1 ? -5 : step / 2;
        rect(2, -19 + bob, 3, 5, shirt);
        rect(3 + step / 2, -14 + arm + bob, 3, 5, '#efac78');
        if (kick) {
            rect(0, -8, 5, 3, '#315676');
            rect(3, -6, 7, 3, '#315676');
            rect(8, -6, 5, 3, '#f4f7e9');
        } else {
            rect(1 + step, -8, 3, jump > 1 ? 5 : 7, '#315676');
            rect(1 + step, jump > 1 ? -4 : -2, 5, 2, '#f4f7e9');
        }
        ctx.restore();
    }

    function draw() {
        if (!width) return;
        ctx.clearRect(0, 0, width, height);
        const floor = height - 16;
        rect(0, floor, width, 16, dark ? '#4c454b' : '#846447');
        for (let x = 0; x < width; x += 12) {
            rect(x, floor + 5, 11, 10, dark ? '#60515b' : '#a07c52');
            rect(x + 2, floor + 7, 3, 2, '#c59d6955');
            rect(x + 7, floor + 12, 3, 2, '#312d4355');
            rect(x, floor, 12, 3, dark ? '#3b9363' : '#56ad46');
            rect(x + 1, floor, 8, 1, dark ? '#73bc82' : '#a3de66');
            rect(x + 8, floor + 3, 3, 2, '#366d42');
        }
        for (let x = 7; x < width; x += 29) {
            rect(x, floor - 3, 1, 4, '#3b864a');
            rect(x - 2, floor - 2, 2, 1, '#65b354');
            if (x % 3 === 0) rect(x - 1, floor - 5, 3, 2, '#f9d96d');
        }

        const left = playOffset + Math.max(16, playingWidth * .15);
        const right = playOffset + Math.max(playingWidth * .15 + 40, playingWidth * .83);
        const phase = (elapsed % 8) / 4;
        const returning = phase >= 1;
        const p = phase % 1;
        const approach = p < .2 ? ease(p / .2) : 1 - ease(clamp((p - .42) / .4));
        const leftX = left + (returning ? 0 : approach * 6);
        const rightX = right - (returning ? approach * 6 : 0);
        const flight = clamp((p - .2) / .58);
        const jump = p > .5 && p < .74 ? 11 * Math.sin((p - .5) / .24 * Math.PI) : 0;
        const running = p < .18 || (p > .44 && p < .8);
        const kicking = p >= .18 && p < .29;
        child(leftX, floor, 1, '#418bea', '#3f2934', false,
            !returning && running, !returning && kicking, returning ? jump : 0, elapsed);
        child(rightX, floor, -1, '#e76c86', '#513345', true,
            returning && running, returning && kicking, returning ? 0 : jump, elapsed);

        const start = returning ? right - 16 : left + 16;
        const end = returning ? left + 10 : right - 10;
        const ballX = start + (end - start) * flight;
        // Two diminishing bounce arcs end at the receiving child's feet.
        const bounce = Math.abs(Math.sin(flight * Math.PI * 2)) * (1 - flight * .65) * 15;
        rect(ballX - 3, floor + 1, 7, 1, '#244b4866');
        const ballY = floor - 7 - bounce;
        rect(ballX - 3, ballY + 1, 7, 5, '#faf3d4');
        rect(ballX - 2, ballY, 5, 7, '#faf3d4');
        const spin = Math.floor(elapsed * 9) % 3;
        rect(ballX - 2 + spin, ballY + 2, 2, 2, '#325077');
        rect(ballX - 2, ballY + 5 - spin, 2, 1, '#325077');
        if (running) {
            const runner = returning ? rightX : leftX;
            const sign = returning ? 1 : -1;
            for (let i = 0; i < 3; i++) {
                const age = (elapsed * 3 + i / 3) % 1;
                rect(runner + sign * (7 + age * 12), floor - 2 - age * 4, 2, 2, dark ? '#ad9b76' : '#dec187');
            }
        }
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
        page.classList.toggle('is-motion-paused', paused || document.hidden);
        button.setAttribute('aria-pressed', String(paused));
        button.title = paused ? 'Play background animation' : 'Pause background animation';
        button.setAttribute('aria-label', button.title);
        button.querySelector('span').textContent = paused ? 'play_arrow' : 'pause';
        draw();
        if (!paused && !document.hidden) frame = requestAnimationFrame(tick);
    }

    const resize = new ResizeObserver(() => {
        const box = canvas.getBoundingClientRect();
        width = canvas.width = Math.ceil(box.width / 2);
        height = canvas.height = Math.ceil(box.height / 2);
        const markerBox = marker.getBoundingClientRect();
        const markerLeft = (markerBox.left - box.left) / 2;
        const markerRight = (markerBox.right - box.left) / 2;
        if (markerLeft < width / 2) {
            playOffset = markerRight + 8;
            playingWidth = width - playOffset - 8;
        } else {
            playOffset = 0;
            playingWidth = markerLeft - 8;
        }
        ctx.imageSmoothingEnabled = false;
        draw();
    });
    resize.observe(canvas);
    button.addEventListener('click', () => { paused = !paused; sync(); });
    motion.addEventListener('change', () => { paused = motion.matches; sync(); });
    document.addEventListener('visibilitychange', sync);
    new MutationObserver(() => {
        dark = document.documentElement.classList.contains('dark');
        draw();
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    window.addEventListener('pagehide', () => cancelAnimationFrame(frame));
    window.addEventListener('pageshow', sync);
    sync();
}
