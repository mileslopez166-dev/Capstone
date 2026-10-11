import * as THREE from 'three';
import { RALLY_RIVALS, rallyStanding } from './reading-rally';

export function initRallyScene(root) {
    const host = root.querySelector('#rally-scene');
    const stage = root.querySelector('.rally-stage');
    let renderer;
    try {
        renderer = new THREE.WebGLRenderer({ antialias: true, powerPreference: 'low-power' });
    } catch {
        root.dataset.renderer = 'fallback';
        return () => {};
    }
    renderer.setPixelRatio(Math.min(devicePixelRatio, 1.5));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    host.appendChild(renderer.domElement);
    const scene = new THREE.Scene();
    scene.background = new THREE.Color('#a5dfe4');
    scene.fog = new THREE.Fog('#a5dfe4', 48, 85);
    const camera = new THREE.OrthographicCamera(-11, 11, 11, -11, .1, 130);
    camera.position.set(0, 24, 30);
    camera.lookAt(0, 0, 0);
    scene.add(new THREE.HemisphereLight('#fffaf1', '#507e68', 2.6));
    const sun = new THREE.DirectionalLight('#fff3da', 2.4);
    sun.position.set(-10, 20, 10);
    scene.add(sun);
    const cube = new THREE.BoxGeometry(1, 1, 1);
    const tireShape = new THREE.CylinderGeometry(.38, .38, .28, 12);
    tireShape.rotateZ(Math.PI / 2);
    const materials = new Map();
    const material = color => {
        if (!materials.has(color)) materials.set(color, new THREE.MeshStandardMaterial({ color, roughness: .75 }));
        return materials.get(color);
    };
    function block(parent, color, x, y, z, w, h, d) {
        const mesh = new THREE.Mesh(cube, material(color));
        mesh.position.set(x, y, z);
        mesh.scale.set(w, h, d);
        parent.add(mesh);
        return mesh;
    }
    block(scene, '#91bf78', 0, -.35, -8, 90, .5, 110);
    block(scene, '#485d65', 0, -.04, -10, 14, .18, 90);
    for (const side of [-1, 1]) block(scene, '#eee9d4', side * 7.4, .05, -10, .7, .2, 90);
    const road = new THREE.Group();
    scene.add(road);
    for (let z = -48; z < 36; z += 4) {
        for (const x of [-3.5, 0, 3.5]) block(road, '#e3ece7', x, .07, z, .14, .025, 1.9);
        for (const side of [-1, 1]) block(road, '#e76d65', side * 7.4, .17, z, .72, .06, 1.9);
    }
    const scenery = [];
    for (let i = 0; i < 18; i++) {
        const item = new THREE.Group();
        const side = i % 2 ? -1 : 1;
        item.position.set(side * (9 + (i % 3) * 2.2), 0, -42 + Math.floor(i / 2) * 7);
        block(item, '#826448', 0, .8, 0, .32, 1.6, .34);
        block(item, i % 3 ? '#418d61' : '#60a767', 0, 2, 0, 2, 1.9, 1.9);
        block(item, '#86c879', -.25, 3, 0, 1.3, .8, 1.3);
        block(item, '#deedbd', 1.1, .2, .7, .65, .4, .6);
        scene.add(item);
        scenery.push(item);
    }
    for (const side of [-1, 1]) {
        block(scene, '#3f8463', side * 19, 1.1, -30, 12, 3, 12);
        block(scene, '#65a87d', side * 16, 3, -36, 11, 3, 10);
        // Small grandstands give the finish stretch a recognizable race setting.
        for (let step = 0; step < 3; step++) {
            block(scene, '#eee8db', side * (10 + step), .35 + step * .45, 7, 1, .6, 7);
            for (let seat = 0; seat < 5; seat++) block(scene, seat % 2 ? '#dc817c' : '#379798', side * (10 + step), .85 + step * .45, 4.4 + seat * 1.25, .55, .5, .55);
        }
    }
    function car(color, x, z) {
        const group = new THREE.Group();
        group.position.set(x, .15, z);
        const wheels = [];
        block(group, '#30444d', 0, .05, 0, 2.05, .04, 3.5);
        block(group, color, 0, .52, 0, 1.75, .55, 3.1);
        block(group, color, 0, .92, .25, 1.42, .45, 1.5);
        block(group, '#91d7e1', 0, 1.14, -.35, 1.23, .16, .48);
        block(group, '#345c6c', 0, 1.15, .78, 1.2, .12, .35);
        block(group, '#fff9e4', 0, .81, -1.05, .22, .035, 1.02);
        block(group, '#fff9e4', 0, 1.17, .22, .22, .035, .7);
        block(group, color, 0, .94, 1.4, 2.05, .13, .26);
        for (const side of [-1, 1]) {
            block(group, '#fff1b0', side * .59, .6, -1.57, .42, .22, .07);
            block(group, '#b42e3c', side * .58, .56, 1.56, .4, .18, .07);
            for (const wz of [-.95, 1.02]) {
                const wheel = new THREE.Mesh(tireShape, material('#1f3038'));
                wheel.position.set(side * .94, .35, wz);
                group.add(wheel);
                wheels.push(wheel);
            }
        }
        scene.add(group);
        return { group, wheels, x };
    }
    const player = car('#ed525a', -1.75, 4);
    const rivals = [car('#279e9e', -5.2, 3), car('#f3bf43', 1.75, 0), car('#8c68bd', 5.2, -4)];
    const flames = [-.48, .48].map(x => block(player.group, '#ffca4e', x, .45, 2.1, .28, .26, 1.2));
    const smoke = Array.from({ length: 6 }, (_, i) => block(scene, '#c9d6d5', -1.75, .35, 5.7 + i * .24, .3, .3, .3));
    const finish = new THREE.Group();
    scene.add(finish);
    for (const side of [-1, 1]) block(finish, '#eef4e9', side * 7.3, 2.3, 0, .35, 4.6, .35);
    for (let x = 0; x < 20; x++) for (let y = 0; y < 2; y++) block(finish, (x + y) % 2 ? '#29464d' : '#fffef2', -7.15 + x * .75, 4.2 + y * .4, 0, .75, .4, .12);
    finish.position.z = -22;
    finish.visible = false;
    const motion = () => !window.PgaalsPreferences?.reducedMotion && document.documentElement.dataset.reducedMotion !== 'true' && !matchMedia('(prefers-reduced-motion: reduce)').matches;
    let outcome = '', outcomeAt = 0, finishAt = null, frame, last = performance.now(), disposed = false;
    let inView = true, margin = rallyStanding(root.dataset.completed, root.dataset.correct, root.dataset.total).margin;
    const onAnswer = event => { outcome = event.detail.correct ? 'boost' : 'skid'; outcomeAt = performance.now(); };
    const onFinish = () => { finishAt = performance.now(); };
    root.addEventListener('rally:answer', onAnswer);
    root.addEventListener('rally:finish', onFinish);
    function resize() {
        const { width, height } = stage.getBoundingClientRect();
        if (!width || !height) return;
        const aspect = width / height;
        const halfWidth = Math.max(10, 10 * aspect);
        camera.left = -halfWidth;
        camera.right = halfWidth;
        camera.top = halfWidth / aspect;
        camera.bottom = -halfWidth / aspect;
        camera.updateProjectionMatrix();
        renderer.setSize(width, height, false);
    }
    const sizeObserver = new ResizeObserver(resize);
    sizeObserver.observe(stage);
    const visibilityObserver = new IntersectionObserver(entries => { inView = entries[0].isIntersecting; });
    visibilityObserver.observe(stage);
    function tick(now) {
        if (disposed) return;
        frame = requestAnimationFrame(tick);
        const dt = Math.min(.05, (now - last) / 1000);
        last = now;
        if (document.hidden || !inView || !stage.getClientRects().length) return;
        const animate = motion();
        const running = root.dataset.started === 'true';
        const elapsed = (now - outcomeAt) / 1000;
        const reaction = animate && elapsed < 1.6 ? Math.sin(Math.PI * elapsed / 1.6) : 0;
        const boosting = outcome === 'boost' ? reaction : 0;
        const skidding = outcome === 'skid' ? reaction : 0;
        const desired = rallyStanding(root.dataset.completed, root.dataset.correct, root.dataset.total).margin;
        margin = animate ? THREE.MathUtils.damp(margin, desired, 3, dt) : desired;
        const speed = animate && running ? 5 + boosting * 17 - skidding * 4 : 0;
        road.position.z = (road.position.z + dt * speed) % 4;
        scenery.forEach(item => { item.position.z += dt * speed; if (item.position.z > 19) item.position.z -= 63; });
        player.group.position.x = player.x + skidding * Math.sin(elapsed * 14) * .65;
        player.group.rotation.y = skidding * Math.sin(elapsed * 12) * .38;
        player.group.position.z = 4 - boosting * 1.8 + skidding;
        rivals.forEach((rival, i) => {
            const lead = margin - RALLY_RIVALS[i];
            rival.group.position.z = 4 + (lead > 0 ? Math.min(3.5, lead * .4) : lead * .75);
        });
        [...rivals, player].forEach(item => item.wheels.forEach(wheel => { wheel.rotation.x -= dt * speed * 2; }));
        flames.forEach(flame => { flame.visible = boosting > .05; flame.scale.z = 1.2 + boosting * 1.6; });
        smoke.forEach((piece, i) => {
            piece.visible = skidding > .1;
            piece.position.set(player.group.position.x + Math.sin(i * 3) * .8, .4 + i * .12, player.group.position.z + 1.8 + i * .33);
            piece.scale.setScalar(.25 + skidding * .45 + i * .045);
        });
        if (finishAt !== null || root.dataset.finished === 'true') {
            finishAt ??= now;
            const progress = animate ? Math.min(1, (now - finishAt) / 1600) : 1;
            finish.visible = true;
            finish.position.z = -12 + progress * 15;
            player.group.position.z = 4 - progress * 8;
            rivals.forEach(rival => { rival.group.position.z -= progress * 8; });
        }
        renderer.render(scene, camera);
        root.dataset.renderer = '3d';
    }
    function contextLost(event) { event.preventDefault(); root.dataset.renderer = 'fallback'; dispose(); }
    function dispose() {
        if (disposed) return;
        disposed = true;
        cancelAnimationFrame(frame);
        sizeObserver.disconnect();
        visibilityObserver.disconnect();
        root.removeEventListener('rally:answer', onAnswer);
        root.removeEventListener('rally:finish', onFinish);
        renderer.domElement.removeEventListener('webglcontextlost', contextLost);
        cube.dispose();
        tireShape.dispose();
        materials.forEach(value => value.dispose());
        renderer.dispose();
        renderer.domElement.remove();
    }
    renderer.domElement.addEventListener('webglcontextlost', contextLost);
    resize();
    frame = requestAnimationFrame(tick);
    return dispose;
}
