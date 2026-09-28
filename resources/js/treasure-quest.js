import * as THREE from 'three';

export function initTreasureQuest(host) {
    const root = host.closest('.treasure-quest');
    const mission = root.closest('#mission-canvas');
    const reduced = () => window.PgaalsPreferences?.reducedMotion ?? matchMedia('(prefers-reduced-motion: reduce)').matches;
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
    renderer.setPixelRatio(Math.min(devicePixelRatio, 1.5));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    host.append(renderer.domElement);
    const scene = new THREE.Scene();
    scene.background = new THREE.Color('#66cbc6');
    const camera = new THREE.OrthographicCamera(-9, 9, 5, -5, .1, 100);
    camera.position.set(0, 12, 17);
    camera.lookAt(0, 0, 0);
    scene.add(new THREE.HemisphereLight('#fff9e8', '#3a847c', 2.4));
    const sun = new THREE.DirectionalLight('#fff3d5', 3);
    sun.position.set(-6, 12, 8);
    sun.castShadow = true;
    sun.shadow.mapSize.set(1024, 1024);
    Object.assign(sun.shadow.camera, { left: -12, right: 12, top: 10, bottom: -10, far: 40 });
    sun.shadow.bias = -.001;
    sun.shadow.normalBias = .04;
    scene.add(sun);
    const materials = new Map();
    function material(color) {
        if (!materials.has(color)) materials.set(color, new THREE.MeshStandardMaterial({ color, roughness: .78 }));
        return materials.get(color);
    }
    function mesh(parent, geometry, color, position, scale = [1, 1, 1]) {
        const object = new THREE.Mesh(geometry, material(color));
        object.position.set(...position);
        object.scale.set(...scale);
        object.castShadow = true;
        object.receiveShadow = true;
        parent.add(object);
        return object;
    }
    const box = new THREE.BoxGeometry(1, 1, 1);
    const sphere = new THREE.SphereGeometry(1, 40, 20);
    const cylinder = new THREE.CylinderGeometry(1, 1, 1, 16);
    mesh(scene, new THREE.PlaneGeometry(120, 120), '#66cbc6', [0, -.72, 0]).rotation.x = -Math.PI / 2;
    mesh(scene, sphere, '#91ddd1', [0, -.62, 0], [8.3, .14, 4.2]);
    mesh(scene, sphere, '#e8ce8e', [0, -.3, 0], [7.8, .8, 3.65]);
    mesh(scene, sphere, '#88b963', [0, .03, -.15], [7.2, .55, 3.05]);

    // Fixed world positions keep the island and trail stable as the viewport changes.
    const path = new THREE.CatmullRomCurve3([
        new THREE.Vector3(-4.9, .6, .05), new THREE.Vector3(-3.1, .6, -1.05),
        new THREE.Vector3(-.6, .6, -.6), new THREE.Vector3(1.6, .6, -.8), new THREE.Vector3(4.5, .6, -1.55),
    ]);
    const markers = [];
    const count = Math.max(1, Number(root.dataset.total));
    for (let i = 0; i <= count; i++) {
        const point = path.getPointAt(i / count);
        const stone = mesh(scene, cylinder, '#f3e4b1', point.toArray(), [.22, .055, .22]);
        markers.push(stone);
    }

    function palm(x, z, size = 1) {
        const group = new THREE.Group();
        group.position.set(x, .4, z);
        group.scale.setScalar(size);
        scene.add(group);
        const trunk = mesh(group, new THREE.CylinderGeometry(.11, .19, 2.1, 9), '#95724e', [0, .95, 0]);
        trunk.rotation.z = -.13;
        for (let j = 0; j < 6; j++) {
            const angle = j * Math.PI / 3;
            const leaf = mesh(group, sphere, j % 2 ? '#328a54' : '#57a858', [Math.cos(angle) * .52, 2.03, Math.sin(angle) * .52], [.86, .10, .28]);
            leaf.rotation.y = -angle;
            leaf.rotation.z = Math.cos(angle) * -.2;
        }
        mesh(group, sphere, '#795535', [.18, 1.86, .12], [.15, .17, .15]);
    }
    palm(-6.1, -1.1, 1.15);
    palm(6.1, -.85, 1.05);
    palm(-5.7, 1.1, .7);
    for (const [x, z] of [[-6.5, .4], [6.4, .7], [-2, -2.5], [2, -2.6]]) {
        mesh(scene, new THREE.DodecahedronGeometry(.35), '#d8e4ce', [x, .45, z], [1.2, .8, 1]);
    }

    function chest(x, z, size = 1, vault = false) {
        const group = new THREE.Group();
        group.position.set(x, .53, z);
        group.scale.setScalar(size);
        scene.add(group);
        mesh(group, box, '#765037', [0, .3, 0], [1.25, .6, .85]);
        mesh(group, box, '#392f26', [0, .606, 0], [1.08, .03, .69]);
        for (const side of [-.46, .46]) mesh(group, box, '#e9bf58', [side, .31, .435], [.10, .58, .04]);
        for (const height of [.08, .28, .48]) mesh(group, box, '#ad7850', [0, height, .445], [.8, .022, .02]);
        const lid = new THREE.Group();
        lid.position.set(0, .6, -.425);
        group.add(lid);
        mesh(lid, box, '#a96c3e', [0, .14, .425], [1.3, .28, .9]);
        mesh(lid, new THREE.CylinderGeometry(.45, .45, 1.3, 24, 1, false, 0, Math.PI), '#b9854e', [0, .17, .425]).rotation.z = Math.PI / 2;
        for (const side of [-.46, .46]) mesh(lid, box, '#f4cf69', [side, .2, .425], [.10, .42, .94]);
        mesh(group, box, '#ffe5a0', [0, .53, .47], [.22, .25, .075]);
        mesh(group, box, '#6b5325', [0, .54, .514], [.055, .11, .01]);
        const gem = mesh(group, new THREE.OctahedronGeometry(.28), vault ? '#f2c64b' : '#27d6ba', [0, 1.02, 0], [1, 1.3, 1]);
        gem.material = new THREE.MeshStandardMaterial({ color: vault ? '#f2c64b' : '#27d6ba', metalness: .25, roughness: .2 });
        const sand = mesh(group, sphere, '#ecd899', [0, .63, 0], [.48, .17, .28]);
        gem.visible = false;
        sand.visible = false;
        return { group, lid, gem, sand, open: 0 };
    }
    const chests = ['A', 'B', 'C', 'D'].map((letter, i) => ({
        ...chest(-4.5 + i * 3, 1.65), letter,
        label: root.querySelector(`[data-chest-label="${letter}"]`),
    }));
    const vault = chest(4.5, -1.65, 1.35, true);
    const flag = mesh(scene, box, '#ec9a78', [4.7, 2.65, -2.45], [.9, .5, .04]);
    mesh(scene, cylinder, '#fff3cf', [4.2, 1.6, -2.45], [.035, 2.4, .035]);
    const waves = [];
    for (let i = 0; i < 12; i++) {
        const wave = mesh(scene, box, '#b5ede3', [-10 + (i % 6) * 4, -.68, i < 6 ? 4 : -4.5], [1.2, .02, .045]);
        wave.castShadow = false;
        waves.push(wave);
    }
    const explorer = root.querySelector('#treasure-explorer');
    let width = 0, height = 0, frame = null, ended = false, disposed = false;
    let trailPosition = Number(root.dataset.answered) / count;
    let lastTime = 0;
    const visible = () => !document.hidden && !mission.hidden && !mission.classList.contains('assessment-reading') && width > 0 && !ended && !disposed;
    function project(element, point) {
        const position = point.clone().project(camera);
        element.style.left = `${(position.x + 1) * width / 2}px`;
        element.style.top = `${(1 - position.y) * height / 2}px`;
    }
    function draw(now = 0) {
        const elapsed = Math.min(.06, (now - lastTime) / 1000 || .016);
        lastTime = now;
        const target = Math.min(1, Number(root.dataset.answered) / count);
        const motion = reduced();
        trailPosition = motion ? target : THREE.MathUtils.damp(trailPosition, target, 3.5, elapsed);
        project(explorer, path.getPointAt(Math.max(0, Math.min(1, trailPosition))));
        markers.forEach((marker, index) => marker.material = material(index <= Number(root.dataset.answered) ? '#3d8863' : '#f3e4b1'));
        chests.forEach(item => {
            const selected = root.dataset.selected === item.letter;
            const correct = root.dataset.outcome === 'correct';
            item.open = motion ? Number(selected) : THREE.MathUtils.damp(item.open, Number(selected), 6, elapsed);
            item.lid.rotation.x = -item.open * 1.9;
            item.gem.visible = selected && correct;
            item.sand.visible = selected && !correct;
            item.gem.position.y = .7 + item.open * .6 + (motion ? 0 : Math.sin(now / 250) * .06);
            item.gem.rotation.y = motion ? .3 : now / 650;
            project(item.label, new THREE.Vector3(item.group.position.x, .5, 2.48));
        });
        const complete = root.dataset.finished === 'true';
        vault.open = motion ? Number(complete) : THREE.MathUtils.damp(vault.open, Number(complete), 5, elapsed);
        vault.lid.rotation.x = -vault.open * 1.9;
        vault.gem.visible = complete;
        vault.gem.position.y = 1.1 + vault.open * .25;
        vault.gem.rotation.y = motion ? 0 : now / 800;
        project(root.querySelector('.treasure-vault-label'), new THREE.Vector3(4.5, 2.15, -1.65));
        waves.forEach((wave, i) => wave.position.x = -10 + (i % 6) * 4 + (motion ? 0 : Math.sin(now / 1800 + i) * .4));
        flag.rotation.y = motion ? 0 : Math.sin(now / 700) * .1;
        renderer.render(scene, camera);
    }
    function tick(now) {
        frame = null;
        if (!visible()) return;
        draw(now);
        if (!reduced()) frame = requestAnimationFrame(tick);
    }
    function refresh() {
        if (disposed) return;
        if (frame !== null) cancelAnimationFrame(frame);
        frame = null;
        if (visible()) { draw(performance.now()); if (!reduced()) frame = requestAnimationFrame(tick); }
    }
    function resize() {
        width = host.clientWidth;
        height = host.clientHeight;
        if (!width || !height) return;
        const aspect = width / height;
        const viewHeight = Math.max(7.5, 17.5 / aspect);
        camera.left = -viewHeight * aspect / 2;
        camera.right = viewHeight * aspect / 2;
        camera.top = viewHeight / 2;
        camera.bottom = -viewHeight / 2;
        camera.updateProjectionMatrix();
        renderer.setSize(width, height, false);
        refresh();
    }
    const observer = new ResizeObserver(resize);
    observer.observe(host);
    const stateObserver = new MutationObserver(refresh);
    stateObserver.observe(root, { attributes: true, attributeFilter: ['data-selected', 'data-outcome', 'data-answered', 'data-finished'] });
    stateObserver.observe(mission, { attributes: true, attributeFilter: ['class', 'hidden'] });
    document.addEventListener('visibilitychange', refresh);
    const motionQuery = matchMedia('(prefers-reduced-motion: reduce)');
    motionQuery.addEventListener('change', refresh);
    const preferenceObserver = new MutationObserver(refresh);
    preferenceObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-reduced-motion'] });
    function dispose() {
        if (disposed) return;
        disposed = true;
        cancelAnimationFrame(frame);
        observer.disconnect();
        stateObserver.disconnect();
        preferenceObserver.disconnect();
        document.removeEventListener('visibilitychange', refresh);
        motionQuery.removeEventListener('change', refresh);
        const geometries = new Set(), usedMaterials = new Set();
        scene.traverse(object => { if (object.geometry) geometries.add(object.geometry); if (object.material) usedMaterials.add(object.material); });
        geometries.forEach(geometry => geometry.dispose());
        usedMaterials.forEach(value => value.dispose());
        materials.forEach(value => value.dispose());
        renderer.dispose();
    }
    renderer.domElement.addEventListener('webglcontextlost', event => {
        event.preventDefault();
        dispose();
        root.dataset.sceneState = 'fallback';
        host.replaceChildren();
    });
    root.addEventListener('treasure:finished', () => { ended = true; dispose(); }, { once: true });
    window.addEventListener('pagehide', event => { if (!event.persisted) dispose(); else refresh(); });
    window.addEventListener('pageshow', refresh);
    root.dataset.sceneState = 'ready';
    resize();
}
