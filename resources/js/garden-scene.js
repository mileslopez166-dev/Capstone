const butterfly = [
    ' oo       oo ',
    'oaao     oaao',
    'oabaao oaabao',
    'oaabao oabbao',
    ' oaabaoabbao ',
    '  oaaocaoo   ',
    '   oaccao    ',
    '  oaacaaao   ',
    ' oabaocabaao ',
    ' oaaao oaaao ',
    '  ooo   ooo  ',
];

function sprite(ctx, rows, palette, x, y) {
    rows.forEach((row, dy) => {
        [...row].forEach((pixel, dx) => {
            if (!palette[pixel]) return;
            ctx.fillStyle = palette[pixel];
            ctx.fillRect(dx + x, dy + y, 1, 1);
        });
    });
}

export function createGardenScene(canvas, { exclusion = null, campus = false } = {}) {
    const ctx = canvas?.getContext('2d');
    if (!ctx) return null;

    let width = 0;
    let height = 0;
    let compact = false;
    let panel = { left: 0, right: 0, top: 0, bottom: 0 };

    const palettes = [
        { o: '#49282a', a: '#ffab25', b: '#ffe69a', c: '#382c35' },
        { o: '#173254', a: '#28c8f2', b: '#bbf3ff', c: '#183141' },
        { o: '#553240', a: '#fa8db1', b: '#fff3c9', c: '#473338' },
    ];

    function flap(x, y, time, palette, scale = 1) {
        const spread = .55 + .45 * Math.abs(Math.sin(time * 5));
        // Snap each wing pixel to the canvas grid while changing its span.
        butterfly.forEach((row, dy) => {
            [...row].forEach((pixel, dx) => {
                if (!palette[pixel]) return;
                const left = Math.round((dx - 6) * spread * scale);
                const top = Math.round((dy - 5) * scale);
                ctx.fillStyle = palette[pixel];
                ctx.fillRect(x + left, y + top,
                    Math.round((dx - 5) * spread * scale) - left,
                    Math.round((dy - 4) * scale) - top);
            });
        });
        ctx.fillStyle = palette.c;
        ctx.fillRect(Math.round(x), Math.round(y - 3), 1, 7);
        ctx.fillRect(Math.round(x - 2), Math.round(y - 5), 1, 2);
        ctx.fillRect(Math.round(x + 2), Math.round(y - 5), 1, 2);
    }

    function bee(x, y, time) {
        const wing = Math.sin(time * 24) > 0 ? -4 : -2;
        ctx.fillStyle = '#e7faff';
        ctx.fillRect(x - 3, y + wing, 3, 3);
        ctx.fillRect(x + 1, y + wing - 1, 3, 3);
        sprite(ctx, [' ooooo ', 'oyoyoyo', 'oyoyook', ' ooooo ', ' k k   '],
            { o: '#533e25', y: '#ffcc42', k: '#152c38' }, x - 3, y - 1);
    }

    function dragonfly(x, y, time) {
        const wing = Math.sin(time * 18) > 0 ? 1 : -1;
        ctx.fillStyle = '#c9f9f5';
        ctx.fillRect(x - 10, y - 3 - wing, 9, 2);
        ctx.fillRect(x + 2, y - 3 - wing, 9, 2);
        ctx.fillRect(x - 7, y + wing, 6, 2);
        ctx.fillRect(x + 2, y + wing, 6, 2);
        ctx.fillStyle = '#175b72';
        ctx.fillRect(x, y - 5, 2, 13);
        ctx.fillStyle = '#65e3c2';
        ctx.fillRect(x, y - 2, 1, 8);
        ctx.fillStyle = '#112d40';
        ctx.fillRect(x - 1, y - 6, 4, 2);
    }

    function firefly(x, y, time) {
        const glow = .35 + .65 * (Math.sin(time * 1.7) + 1) / 2;
        ctx.fillStyle = `rgba(212, 244, 118, ${glow * .12})`;
        ctx.fillRect(x - 4, y - 4, 9, 9);
        ctx.fillStyle = `rgba(221, 250, 147, ${glow * .28})`;
        ctx.fillRect(x - 2, y - 2, 5, 5);
        ctx.fillStyle = '#8faa95';
        ctx.fillRect(x - 2, y - 2, 2, 1);
        ctx.fillRect(x + 1, y - 2, 2, 1);
        ctx.fillStyle = '#fff6aa';
        ctx.fillRect(x, y - 1, 1, 3);
    }

    function position(index, time, nighttime) {
        const anchors = nighttime
            ? [[.10, .61], [.23, .51], [.32, .78], [.69, .79], [.87, .60], [.94, .75], [.08, .82], [.79, .69], [.20, .39], [.91, .37]]
            : [[.17, .36], [.12, .59], [.90, .69], [.92, .40], [.32, .80], [.83, .81], [.77, .66]];
        const [ax, ay] = anchors[index];
        const phase = time * (.45 + index * .035) + index * 2.4;
        let x = ax * width + Math.sin(phase) * (compact ? 8 : 18);
        let y = ay * height + Math.sin(phase * 1.6) * (compact ? 3 : 8);
        if (campus) {
            // Keep decoration in the exposed edges, behind the page content.
            const inset = compact ? 5 : 8;
            x = (index % 2 === 0 ? inset : width - inset) + Math.sin(phase);
            y = height * (.18 + (index % 5) * .16) + Math.sin(phase * 1.6) * 6;
        } else if (compact) {
            x = width * (.14 + index * .23) + Math.sin(phase) * 6;
            const top = panel.bottom + 12;
            const bottom = Math.max(top, height * .888 - 18);
            y = top + (bottom - top) * (.2 + (index % 3) * .3) + Math.sin(phase * 1.6) * 3;
        }
        const margin = campus ? 4 : 10;
        return [Math.round(Math.max(margin, Math.min(width - margin, x))), Math.round(y)];
    }

    return {
        resize() {
            const bounds = canvas.getBoundingClientRect();
            width = canvas.width = Math.ceil(bounds.width / 2);
            height = canvas.height = Math.ceil(bounds.height / 2);
            compact = window.innerWidth < 768 || window.innerHeight < 600;
            if (exclusion) {
                const form = exclusion.getBoundingClientRect();
                panel = { left: (form.left - bounds.left) / 2, right: (form.right - bounds.left) / 2,
                    top: (form.top - bounds.top) / 2, bottom: (form.bottom - bounds.top) / 2 };
            }
            ctx.imageSmoothingEnabled = false;
        },
        draw(time, nighttime) {
            ctx.clearRect(0, 0, width, height);
            const count = compact ? 4 : nighttime ? 10 : 7;
            for (let index = 0; index < count; index++) {
                const [x, y] = position(index, time, nighttime);
                // Leave a clear margin around the form and its controls.
                if (exclusion && x > panel.left - 12 && x < panel.right + 12 && y > panel.top - 12 && y < panel.bottom + 10) continue;
                const phase = time + index * 1.7;
                if (nighttime) {
                    if (index >= count - 2) flap(x, y, phase * .7, { o: '#4b617a', a: '#c9d5df', b: '#fff5d3', c: '#405267' }, .8);
                    else firefly(x, y, phase);
                } else if (index < (compact ? 3 : 4)) {
                    flap(x, y, phase, palettes[index % palettes.length], campus ? (compact ? .7 : 1) : (compact ? 1 : 1.35));
                } else if (index === 6) dragonfly(x, y, phase);
                else bee(x, y, phase);
            }
        },
    };
}
