import * as THREE from 'three';
import { frogRiverJumpFrame, frogFinishFrame, isPerfectFrogRun } from './frog-jump';

export function initFrogPond(host) {
    const root = host.closest('.frog-pond-game');
    const stage = root?.querySelector('.frog-stage');
    if (!stage || host.dataset.initialized) return;
    host.dataset.initialized = 'true';
    root.dataset.pondState = 'fallback';
    let renderer;
    try {
        renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false, powerPreference: 'low-power' });
    } catch {
        return;
    }
    // The camera owns its viewport; question and answer height never clips the river.
    stage.prepend(host);
    renderer.setPixelRatio(Math.min(devicePixelRatio, 1.5));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    host.appendChild(renderer.domElement);
    const scene = new THREE.Scene();
    scene.background = new THREE.Color('#8dd6df');
    scene.fog = new THREE.Fog('#8dd6df', 30, 65);
    const camera = new THREE.OrthographicCamera(-9, 9, 7, -7, .1, 100);
    scene.add(new THREE.HemisphereLight('#e8fcff', '#557766', 2.4));
    const sun = new THREE.DirectionalLight('#fff5d6', 3);
    sun.position.set(-8, 18, 8);
    sun.castShadow = true;
    sun.shadow.mapSize.set(1024, 1024);
    Object.assign(sun.shadow.camera, { left: -14, right: 14, top: 14, bottom: -14 });
    sun.shadow.bias = -.001;
    scene.add(sun, sun.target);

    const cube = new THREE.BoxGeometry(1, 1, 1);
    const ring = new THREE.RingGeometry(.75, 1, 32);
    const materials = new Map();
    function material(color) {
        if (!materials.has(color)) materials.set(color, new THREE.MeshStandardMaterial({ color, roughness: .88 }));
        return materials.get(color);
    }
    function block(parent, color, x, y, z, sx, sy, sz) {
        const mesh = new THREE.Mesh(cube, material(color));
        mesh.position.set(x, y, z);
        mesh.scale.set(sx, sy, sz);
        mesh.castShadow = mesh.receiveShadow = true;
        parent.add(mesh);
        return mesh;
    }
    const total = Math.max(1, root.querySelectorAll('.frog-safe-log').length);
    const spacing = 2.65;
    const endZ = -(total + 1) * spacing;
    const length = (total + 5) * spacing;
    block(scene, '#2c9bbb', 0, -.48, endZ / 2, 80, .65, length + 60);
    block(scene, '#278bab', 0, -.14, endZ / 2, 12, .12, length);
    for (const side of [-1, 1]) {
        block(scene, '#647855', side * 8, -.48, endZ / 2, 4.2, .9, length);
        block(scene, '#8fc766', side * 8, .06, endZ / 2, 4.2, .22, length);
    }
    for (const z of [1.5, endZ - 1.5]) {
        block(scene, '#71864e', 0, -.18, z, 12, .65, 3);
        block(scene, '#abd776', 0, .2, z, 12, .18, 3);
        for (let i = 0; i < 12; i++) {
            block(scene, i % 2 ? '#c0e590' : '#91c45f', -5.5 + i, .3, z + (i % 3 - 1) * .6, .7, .03, .45);
        }
    }
    const foam = [];
    for (let row = 0; row < total + 3; row++) {
        const z = -row * spacing;
        block(scene, row % 2 ? '#359fbd' : '#3aa9c4', 0, -.06, z, 11.8, .035, spacing - .12).castShadow = false;
        for (let i = 0; i < 5; i++) {
            const piece = block(scene, '#91dce3', -5 + i * 2.3, -.015, z + (i % 3 - 1) * .55, .3 + (i % 2) * .5, .025, .07);
            piece.castShadow = false;
            foam.push({ piece, row, x: piece.position.x });
        }
        for (const side of [-1, 1]) {
            const x = side * (6.7 + (row % 2) * .8);
            block(scene, '#876349', x, .7, z, .28, 1.3, .3);
            block(scene, row % 2 ? '#3c8b58' : '#579b56', x, 1.65, z, 1.4, 1.4, 1.25);
            block(scene, '#71b563', x - .15, 2.3, z, .95, .65, .85);
            block(scene, '#edf0d8', side * 5.9, .22, z + .8, .45, .38, .55);
        }
    }
    const logs = [];
    for (let index = 0; index < total; index++) {
        const group = new THREE.Group();
        scene.add(group);
        block(group, '#805337', 0, 0, 0, 3.1, .48, .95);
        block(group, '#b18550', 0, .25, 0, 3, .08, .82);
        for (const x of [-1.57, 1.57]) {
            block(group, '#d4ab71', x, 0, 0, .09, .46, .91);
            block(group, '#956239', x * 1.035, 0, 0, .015, .2, .43);
        }
        for (const x of [-.85, .15, .8]) block(group, '#725137', x, .3, .02, .045, .035, .7);
        block(group, '#96b660', -.7, .31, -.15, .6, .07, .24);
        logs.push(group);
    }
    const finishArch = new THREE.Group();
    scene.add(finishArch);
    finishArch.position.z = endZ;
    for (const x of [-1.8, 1.8]) block(finishArch, '#fff3d5', x, 1.3, 0, .18, 2.3, .18);
    for (let i = 0; i < 10; i++) for (let j = 0; j < 2; j++) {
        block(finishArch, (i + j) % 2 ? '#334e52' : '#fff9dd', -1.62 + i * .36, 2.2 + j * .26, 0, .36, .26, .13);
    }

    const frog = new THREE.Group();
    frog.rotation.y = Math.PI;
    const body = new THREE.Group();
    frog.add(body);
    scene.add(frog);
    block(body, '#6cc44e', 0, .42, 0, .86, .62, .8);
    block(body, '#9bdf68', 0, .75, .16, 1, .42, .7);
    block(body, '#daf2a0', 0, .38, .42, .57, .38, .045);
    const eyes = [];
    for (const side of [-1, 1]) {
        block(body, '#88d655', side * .32, 1.04, .2, .3, .32, .3);
        const eye = block(body, '#fffdf2', side * .32, 1.08, .36, .22, .22, .065);
        block(eye, '#203e36', .1 * side, 0, .7, .45, .65, .1);
        eyes.push(eye);
        block(body, '#488f42', side * .47, .19, -.15, .4, .25, .55);
        block(body, '#9ad863', side * .39, .08, .3, .34, .14, .48);
        block(body, '#efa187', side * .4, .66, .52, .14, .075, .025);
    }
    block(body, '#34573a', 0, .65, .525, .29, .04, .025);

    const targetMaterial = new THREE.MeshBasicMaterial({ color: '#ffed91', side: THREE.DoubleSide, transparent: true, opacity: .9, depthWrite: false });
    const target = new THREE.Mesh(ring, targetMaterial);
    target.rotation.x = -Math.PI / 2;
    target.scale.setScalar(.85);
    scene.add(target);
    const splashMaterial = new THREE.MeshBasicMaterial({ color: '#d8fcff', transparent: true, side: THREE.DoubleSide, depthWrite: false });
    const splash = new THREE.Mesh(ring, splashMaterial);
    splash.rotation.x = -Math.PI / 2;
    scene.add(splash);
    const drops = Array.from({ length: 16 }, () => block(scene, '#d9fbff', 0, 0, 0, .12, .2, .12));
    const confetti = Array.from({ length: 20 }, (_, i) => block(scene, ['#ffe483', '#e8919c', '#f8faf0', '#65c596'][i % 4], 0, 0, 0, .12, .09, .09));
    const raycaster = new THREE.Raycaster();
    const pointer = new THREE.Vector2();
    let hover = false;
    let petAt = -Infinity;
    let animation = null;
    let safeIndex = Number(root.dataset.frogSafeIndex ?? -1);
    let disposed = false;
    let lost = false;
    let finished = root.dataset.assessmentFinished === 'true';
    let lastTime = 0;
    let aspect = 1;
    const focus = new THREE.Vector3();
    const reducedMotion = () => window.PgaalsPreferences?.reducedMotion ?? matchMedia('(prefers-reduced-motion: reduce)').matches;
    function animateLogs(time, reduced) {
        logs.forEach((log, index) => {
            log.position.set(Math.sin(index * 1.7) * 1.1 + (reduced ? 0 : Math.sin(time * .65 + index) * .35), .3 + (reduced ? 0 : Math.sin(time * 1.5 + index) * .045), -(index + 1) * spacing);
            log.rotation.z = reduced ? 0 : Math.sin(time + index) * .018;
        });
    }
    function checkpoint(index) {
        if (index >= total) return new THREE.Vector3(0, .31, endZ - .35);
        if (index < 0) return new THREE.Vector3(0, .31, .8);
        return logs[index].position.clone().add(new THREE.Vector3(0, .3, 0));
    }
    function draw(now) {
        if (disposed || lost) return;
        const reduced = reducedMotion();
        const time = now / 1000;
        const delta = Math.min(.05, Math.max(0, (now - lastTime) / 1000));
        lastTime = now;
        animateLogs(time, reduced);
        const resting = checkpoint(safeIndex);
        frog.position.copy(resting);
        body.scale.set(1, reduced ? 1 : 1 + Math.sin(time * 3) * .025, 1);
        body.rotation.set(0, 0, 0);
        frog.visible = true;
        splash.visible = false;
        drops.forEach(drop => { drop.visible = false; });
        confetti.forEach(piece => { piece.visible = false; });
        const blink = !reduced && time % 4.3 > 4.15 ? .15 : 1;
        eyes.forEach(eye => { eye.scale.y = .22 * blink; });
        if (!animation && !reduced) {
            body.rotation.y = hover ? -.16 : Math.sin(time * .8) * .035;
            frog.position.y += Math.max(0, Math.sin(Math.min(1, (now - petAt) / 500) * Math.PI)) * .35;
        }
        if (animation) {
            const p = THREE.MathUtils.clamp((now - animation.start) / animation.duration, 0, 1);
            const pose = animation.finish ? frogFinishFrame(p, reduced) : frogRiverJumpFrame(p, animation.correct, reduced);
            const from = checkpoint(animation.from);
            const destination = animation.correct || animation.finish ? checkpoint(animation.to) : checkpoint(animation.from + 1).lerp(from, .48).setY(.08);
            frog.position.copy(from).lerp(destination, pose.travel);
            frog.position.y += pose.lift * 1.65 - pose.sink * 1.2;
            body.scale.set(1 / pose.squash, pose.squash + pose.lift * .1, 1 / pose.squash);
            body.rotation.x = reduced ? 0 : -pose.lift * .18;
            frog.visible = pose.sink < .88;
            if (pose.splash > .01) {
                splash.visible = true;
                splash.position.set(destination.x, .04, destination.z);
                splash.scale.setScalar(.6 + pose.splash * 1.2);
                splashMaterial.opacity = pose.splash * .8;
                drops.forEach((drop, i) => {
                    drop.visible = true;
                    const angle = i / drops.length * Math.PI * 2;
                    drop.position.set(destination.x + Math.cos(angle) * pose.splash * 1.1, .1 + Math.sin(pose.splash * Math.PI / 2) * (.4 + i % 3 * .15), destination.z + Math.sin(angle) * pose.splash);
                    drop.rotation.z = angle;
                });
            }
            if (animation.correct && p > .78 && !reduced) {
                const burst = (p - .78) / .22;
                confetti.forEach((piece, i) => {
                    piece.visible = true;
                    const angle = i * 2.4;
                    piece.position.set(destination.x + Math.cos(angle) * burst * 1.5, destination.y + .5 + Math.sin(burst * Math.PI) * (1 + i % 3 * .2), destination.z + Math.sin(angle) * burst);
                    piece.rotation.set(burst * 4, angle, burst * 3);
                    piece.scale.setScalar(.09 * (1 - burst));
                });
            }
            if (p >= 1) {
                safeIndex = animation.correct || animation.finish ? animation.to : animation.from;
                if (animation.finish) root.dataset.frogFinishCrossed = 'true';
                animation = null;
                frog.visible = true;
                frog.position.copy(checkpoint(safeIndex));
            }
        }
        target.visible = safeIndex < total && !animation;
        target.position.copy(checkpoint(Math.min(total, safeIndex + 1)));
        target.position.y += .025;
        targetMaterial.opacity = reduced ? .8 : .65 + Math.sin(time * 3) * .2;
        foam.forEach(({ piece, row, x }) => {
            piece.position.x = reduced ? x : ((x + 6 + time * (row % 2 ? .45 : -.45)) % 12 + 12) % 12 - 6;
        });
        const desired = new THREE.Vector3(frog.position.x * .25, 0, frog.position.z - 1.5);
        focus.lerp(desired, reduced ? 1 : 1 - Math.exp(-delta * 5));
        camera.position.copy(focus).add(new THREE.Vector3(8, 13, 14));
        camera.lookAt(focus);
        sun.position.set(focus.x - 8, 18, focus.z + 8);
        sun.target.position.copy(focus);
        renderer.render(scene, camera);
    }
    function layout() {
        if (disposed || lost) return;
        const rect = stage.getBoundingClientRect();
        if (rect.width < 1 || rect.height < 1) return;
        aspect = rect.width / rect.height;
        const halfHeight = Math.max(5.8, 6.8 / aspect);
        camera.left = -halfHeight * aspect;
        camera.right = halfHeight * aspect;
        camera.top = halfHeight;
        camera.bottom = -halfHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(rect.width, rect.height, false);
        draw(performance.now());
        root.classList.add('pond-3d-ready');
        root.dataset.pondState = 'ready';
    }
    function onJump({ detail }) {
        const from = Number(detail.fromSafeIndex ?? safeIndex);
        const correct = detail.correct !== false;
        const to = Number(detail.toSafeIndex ?? from + (correct ? 1 : 0));
        animation = { from, to, correct, start: detail.startedAt ?? performance.now(), duration: detail.duration || 1400 };
        safeIndex = from;
        draw(performance.now());
    }
    function onQuestion() {
        animation = null;
        safeIndex = Number(root.dataset.frogSafeIndex ?? -1);
        draw(performance.now());
    }
    function onFinish({ detail }) {
        if (!isPerfectFrogRun(detail.correct, detail.answered, detail.total)) return;
        animation = { from: total - 1, to: total, correct: true, finish: true, start: detail.startedAt ?? performance.now(), duration: detail.duration || 1800 };
    }
    function visibility() {
        renderer.setAnimationLoop(!disposed && !lost && !finished && !document.hidden ? draw : null);
    }
    function onFinished() { finished = true; visibility(); }
    function pointerMove(event) {
        const rect = host.getBoundingClientRect();
        pointer.set((event.clientX - rect.left) / rect.width * 2 - 1, 1 - (event.clientY - rect.top) / rect.height * 2);
        raycaster.setFromCamera(pointer, camera);
        hover = raycaster.intersectObject(frog, true).length > 0;
        host.style.cursor = hover ? 'pointer' : '';
    }
    function pointerLeave() { hover = false; }
    function pointerDown(event) { pointerMove(event); if (hover && !animation) petAt = performance.now(); }
    function contextLost(event) {
        event.preventDefault();
        lost = true;
        root.classList.remove('pond-3d-ready');
        root.dataset.pondState = 'fallback';
        visibility();
    }
    function contextRestored() { lost = false; layout(); visibility(); }
    const observer = new ResizeObserver(layout);
    observer.observe(stage);
    const listeners = [
        [root, 'frog:jump', onJump], [root, 'frog:question', onQuestion],
        [root, 'frog:finish-line', onFinish], [root, 'frog:finished', onFinished],
        [host, 'pointermove', pointerMove], [host, 'pointerleave', pointerLeave], [host, 'pointerdown', pointerDown],
        [document, 'visibilitychange', visibility], [window, 'pageshow', visibility],
        [renderer.domElement, 'webglcontextlost', contextLost], [renderer.domElement, 'webglcontextrestored', contextRestored],
    ];
    listeners.forEach(([element, name, handler]) => element.addEventListener(name, handler));
    function pagehide(event) {
        renderer.setAnimationLoop(null);
        if (event.persisted) return;
        disposed = true;
        observer.disconnect();
        listeners.forEach(([element, name, handler]) => element.removeEventListener(name, handler));
        window.removeEventListener('pagehide', pagehide);
        cube.dispose();
        ring.dispose();
        materials.forEach(item => item.dispose());
        targetMaterial.dispose();
        splashMaterial.dispose();
        renderer.dispose();
    }
    window.addEventListener('pagehide', pagehide);
    animateLogs(performance.now() / 1000, reducedMotion());
    focus.copy(checkpoint(safeIndex)).setY(0);
    layout();
    // The lazy renderer may arrive after a student has already chosen an answer.
    if (root.dataset.frogFinishing === 'true' && !finished) {
        onFinish({ detail: { correct: Number(root.dataset.frogCorrect), answered: Number(root.dataset.frogAnswered), total,
            startedAt: Number(root.dataset.frogFinishStart), duration: Number(root.dataset.frogFinishDuration) } });
    } else if (root.dataset.frogOutcome && !finished) {
        onJump({ detail: { correct: root.dataset.frogOutcome === 'correct', fromSafeIndex: Number(root.dataset.frogJumpFrom),
            toSafeIndex: Number(root.dataset.frogJumpTo), startedAt: Number(root.dataset.frogJumpStart), duration: Number(root.dataset.frogJumpDuration) } });
    }
    visibility();
}
