import * as THREE from 'three';

export function initFrogPond(host) {
    const root = host.closest('.frog-pond-game');
    if (!root || host.dataset.initialized) return;
    host.dataset.initialized = 'true';
    root.dataset.pondState = 'fallback';

    let renderer;
    try {
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('webgl2', { antialias: true, alpha: false, powerPreference: 'low-power' });
        if (!context) return;
        renderer = new THREE.WebGLRenderer({ canvas, context, antialias: true });
    } catch {
        return;
    }

    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.1;
    host.appendChild(renderer.domElement);

    const scene = new THREE.Scene();
    scene.background = new THREE.Color('#d9eff5');
    const camera = new THREE.OrthographicCamera(-1, 1, 1, -1, .1, 3000);
    camera.position.z = 1000;
    scene.add(new THREE.HemisphereLight('#fff9e7', '#67aeba', 2));
    const sunlight = new THREE.DirectionalLight('#fff3dc', 3);
    sunlight.position.set(-350, 550, 800);
    scene.add(sunlight);
    const fill = new THREE.DirectionalLight('#c1edff', 1.1);
    fill.position.set(500, 80, 400);
    scene.add(fill);

    const geometries = new Set();
    const materials = new Map();
    const geometry = (value) => { geometries.add(value); return value; };
    const sphere = geometry(new THREE.SphereGeometry(1, 24, 16));
    const cylinder = geometry(new THREE.CylinderGeometry(1, 1, 1, 10));
    const plane = geometry(new THREE.PlaneGeometry(1, 1));
    const circle = geometry(new THREE.CircleGeometry(1, 48));
    const ring = geometry(new THREE.TorusGeometry(1, .012, 6, 64));
    const up = new THREE.Vector3(0, 1, 0);
    const material = (color, options = {}) => {
        const key = color + JSON.stringify(options);
        if (!materials.has(key)) materials.set(key, new THREE.MeshStandardMaterial({ color, roughness: .4, ...options }));
        return materials.get(key);
    };
    const mesh = (parent, shape, color, position, scale, options = {}) => {
        const object = new THREE.Mesh(shape, material(color, options));
        object.position.set(...position);
        object.scale.set(...scale);
        parent.add(object);
        return object;
    };
    const ellipsoid = (parent, color, position, scale, options) => mesh(parent, sphere, color, position, scale, options);
    const segment = (parent, from, to, radius, color) => {
        const a = new THREE.Vector3(...from);
        const b = new THREE.Vector3(...to);
        const object = mesh(parent, cylinder, color, a.clone().add(b).multiplyScalar(.5).toArray(), [radius, a.distanceTo(b), radius]);
        object.quaternion.setFromUnitVectors(up, b.sub(a).normalize());
        return object;
    };
    const curve = (parent, points, radius, color) => {
        const path = new THREE.CatmullRomCurve3(points.map((point) => new THREE.Vector3(...point)));
        const object = new THREE.Mesh(geometry(new THREE.TubeGeometry(path, 24, radius, 6, false)), material(color));
        parent.add(object);
        return object;
    };

    const frog = new THREE.Group();
    const character = new THREE.Group();
    frog.add(character);
    scene.add(frog);
    ellipsoid(character, '#69b940', [0, -.25, 0], [1.04, .92, .72]);
    ellipsoid(character, '#e4ed9a', [0, -.3, .64], [.65, .61, .14], { roughness: .6 });
    const head = ellipsoid(character, '#83cc50', [0, .52, .15], [1.16, .72, .75]);
    const eyes = [];
    const pupils = [];
    for (const side of [-1, 1]) {
        ellipsoid(character, '#5ba73e', [side * .99, -.63, -.05], [.51, .42, .55]);
        ellipsoid(character, '#78c343', [side * .63, -.66, .64], [.19, .48, .22]);
        ellipsoid(character, '#8fd151', [side * .67, -1.03, .75], [.3, .13, .28]);
        for (let toe = 0; toe < 3; toe++) {
            ellipsoid(character, '#8fd151', [side * .67 + (toe - 1) * .17, -1.08 - Math.abs(toe - 1) * .03, .95], [.09, .08, .18]);
        }
        ellipsoid(character, '#83cc50', [side * .65, 1.08, .25], [.46, .49, .43]);
        const eye = new THREE.Group();
        eye.position.set(side * .65, 1.1, .55);
        character.add(eye);
        ellipsoid(eye, '#fffdeb', [0, 0, .12], [.32, .36, .18], { roughness: .15 });
        const pupil = ellipsoid(eye, '#192f2c', [-side * .035, -.015, .283], [.14, .205, .08], { roughness: .09 });
        ellipsoid(pupil, '#ffffff', [.32, .38, .75], [.25, .2, .2]);
        eyes.push(eye);
        pupils.push(pupil);
        ellipsoid(character, '#edb578', [side * .8, .39, .71], [.18, .09, .06]);
        ellipsoid(character, '#528c40', [side * .18, .54, .885], [.035, .025, .018]);
    }
    const smile = curve(character, [[-.51, .24, .806], [-.25, .13, .88], [0, .1, .91], [.25, .13, .88], [.51, .24, .806]], .027, '#376c35');
    const mouth = ellipsoid(character, '#472c40', [0, .22, .91], [.31, .18, .035]);
    mouth.visible = false;
    const mouthPoint = new THREE.Vector3(0, .22, .96);

    const lily = new THREE.Group();
    scene.add(lily);
    const leafShape = new THREE.Shape();
    leafShape.moveTo(0, 0);
    for (let step = 0; step <= 64; step++) {
        const angle = .28 + step / 64 * (Math.PI * 2 - .56);
        leafShape.lineTo(Math.cos(angle), Math.sin(angle));
    }
    leafShape.closePath();
    const leafGeometry = geometry(new THREE.ExtrudeGeometry(leafShape, { depth: .04, bevelEnabled: true, bevelThickness: .035, bevelSize: .025, bevelSegments: 2, steps: 1 }));
    const lilyLeaf = mesh(lily, leafGeometry, '#54a854', [0, 0, 0], [1, 1, 1]);
    lilyLeaf.rotation.z = -.36;
    for (let vein = 0; vein < 8; vein++) {
        const angle = .5 + vein * .7;
        segment(lily, [0, 0, .09], [Math.cos(angle) * .88, Math.sin(angle) * .88, .09], .008, '#85bf64');
    }
    const frogShadow = mesh(scene, circle, '#1e776f', [0, 0, -10], [1, 1, 1], { transparent: true, opacity: .18, depthWrite: false });

    const water = mesh(scene, plane, '#63c1ce', [0, 0, -150], [1, 1, 1], { roughness: .25, metalness: .12 });
    const farBank = ellipsoid(scene, '#a6d3a0', [0, 0, -170], [1, 1, 1]);
    const ripples = Array.from({ length: 5 }, (_, index) => mesh(scene, ring, '#ddfff2', [0, 0, -80], [1, 1, 1], { transparent: true, opacity: .26, depthWrite: false }));
    const plants = [];
    for (const side of [-1, 1]) {
        const bank = new THREE.Group();
        scene.add(bank);
        ellipsoid(bank, '#3e985c', [0, -.2, 0], [1.2, .52, .5]);
        ellipsoid(bank, '#74b765', [side * .45, .08, -.1], [.8, .45, .4]);
        for (let index = 0; index < 5; index++) {
            const x = (index - 2) * .3;
            const height = 1.1 + (index % 3) * .28;
            const stem = segment(bank, [x, -.08, .1], [x + side * .12, height, .03], .028, '#4e8750');
            const leaf = ellipsoid(bank, index % 2 ? '#86bd5e' : '#4d9f59', [x + .17 * side, height * .43, .16], [.11, .65, .08]);
            leaf.rotation.z = side * -.4;
            if (index % 2 === 0) ellipsoid(bank, '#866346', [x + side * .12, height, .03], [.07, .22, .08]);
            stem.rotation.z = side * .04;
        }
        ellipsoid(bank, '#b9c6b4', [-side * .7, -.1, .55], [.3, .18, .25], { roughness: 1 });
        plants.push({ object: bank, side });
    }
    const flowers = [];
    for (let index = 0; index < 3; index++) {
        const flower = new THREE.Group();
        scene.add(flower);
        ellipsoid(flower, '#52a96b', [0, -.15, -.1], [1.1, .22, .1]);
        for (let petal = 0; petal < 7; petal++) {
            const angle = petal / 7 * Math.PI * 2;
            const object = ellipsoid(flower, index === 1 ? '#fff4d8' : '#efa6bc', [Math.cos(angle) * .35, Math.sin(angle) * .18, .12], [.38, .17, .12]);
            object.rotation.z = angle;
        }
        ellipsoid(flower, '#f4ca65', [0, 0, .25], [.22, .12, .1]);
        flowers.push(flower);
    }

    const wingMaterial = material('#edfaff', { transparent: true, opacity: .8, roughness: .18, metalness: .08, depthWrite: false });
    const targets = [...root.querySelectorAll('[data-frog-answer]')].map((button, index) => {
        const object = new THREE.Group();
        scene.add(object);
        const color = ['#ed8b7d', '#efc056', '#78b6e5', '#b7a0df'][index];
        const abdomen = ellipsoid(object, color, [0, -.2, 0], [.25, .39, .2]);
        for (let stripe = 0; stripe < 3; stripe++) ellipsoid(object, '#50606a', [0, -.16 - stripe * .14, .15], [.235 - stripe * .025, .022, .065]);
        ellipsoid(object, color, [0, .23, .07], [.29, .27, .24]);
        const wings = [];
        for (const side of [-1, 1]) {
            const pivot = new THREE.Group();
            pivot.position.set(side * .14, .12, -.06);
            object.add(pivot);
            const wing = new THREE.Mesh(sphere, wingMaterial);
            wing.position.set(side * .39, .12, 0);
            wing.scale.set(.54, .19, .045);
            wing.rotation.z = side * .42;
            pivot.add(wing);
            const vein = segment(pivot, [0, 0, .04], [side * .8, .3, .04], .008, '#b1d8df');
            vein.material = wingMaterial;
            wings.push({ pivot, side });
            ellipsoid(object, '#fffbed', [side * .12, .25, .26], [.105, .13, .06]);
            ellipsoid(object, '#2c3748', [side * .1, .24, .315], [.047, .065, .025], { roughness: .12 });
            curve(object, [[side * .12, .43, .06], [side * .22, .66, .05], [side * .28, .65, .05]], .015, '#576174');
            for (let leg = 0; leg < 3; leg++) {
                const y = -.03 - leg * .16;
                curve(object, [[side * .18, y, 0], [side * (.45 + leg * .03), y - .07, .03], [side * (.58 + leg * .03), y - .25, .1]], .012, '#4b586a');
            }
        }
        const proboscis = segment(object, [0, .12, .3], [.02, -.11, .45], .022, '#5c6070');
        const gulpMouth = new THREE.Group();
        gulpMouth.position.set(0, -.055, .38);
        object.add(gulpMouth);
        ellipsoid(gulpMouth, color, [0, 0, 0], [.3, .31, .075]);
        ellipsoid(gulpMouth, '#442d43', [0, 0, .055], [.25, .26, .06]);
        ellipsoid(gulpMouth, '#e991a5', [0, -.16, .11], [.135, .055, .025]);
        gulpMouth.visible = false;
        const target = { button, object, wings, abdomen, proboscis, gulpMouth, base: new THREE.Vector3(), scale: 1, hover: false, index };
        button.addEventListener('pointerenter', () => { target.hover = true; });
        button.addEventListener('pointerleave', () => { target.hover = false; });
        button.addEventListener('focus', () => { target.hover = true; });
        button.addEventListener('blur', () => { target.hover = false; });
        return target;
    });

    const tongue = mesh(scene, cylinder, '#ed7293', [0, 0, 0], [1, 1, 1], { roughness: .3 });
    const tongueTip = ellipsoid(scene, '#f398ac', [0, 0, 0], [1, 1, 1]);
    tongue.visible = tongueTip.visible = false;
    const motionQuery = { get matches() { return window.PgaalsPreferences?.reducedMotion ?? window.matchMedia('(prefers-reduced-motion: reduce)').matches; } };
    const gaze = new THREE.Vector2();
    const mouthWorld = new THREE.Vector3();
    const tip = new THREE.Vector3();
    const direction = new THREE.Vector3();
    const attackPosition = new THREE.Vector3();
    const swallowPosition = new THREE.Vector3();
    let dimensions = { width: 0, height: 0 };
    let frogScale = 1;
    let catchAnimation = null;
    let finished = root.dataset.assessmentFinished === 'true';
    let disposed = false;
    let contextLost = false;
    let previousFrame = 0;

    function layout() {
        if (disposed) return;
        const box = root.getBoundingClientRect();
        const width = root.clientWidth;
        const height = root.clientHeight;
        if (!width || !height) return;
        dimensions = { width, height };
        camera.left = -width / 2;
        camera.right = width / 2;
        camera.top = height / 2;
        camera.bottom = -height / 2;
        camera.updateProjectionMatrix();
        renderer.setSize(width, height, false);

        const anchor = root.querySelector('#frog-character').getBoundingClientRect();
        frogScale = Math.min(anchor.width / 3.6, anchor.height / 3.45);
        frog.position.set(anchor.left + anchor.width / 2 - box.left - width / 2, height / 2 - (anchor.top + anchor.height * .46 - box.top), 45);
        frog.scale.setScalar(frogScale);
        lily.position.set(frog.position.x, frog.position.y - frogScale * 1.16, 15);
        lily.scale.set(frogScale * 1.9, frogScale * .48, frogScale * .5);
        frogShadow.position.set(lily.position.x + 7, lily.position.y - 8, -5);
        frogShadow.scale.set(frogScale * 1.92, frogScale * .52, 1);

        const stage = root.querySelector('.frog-stage').getBoundingClientRect();
        const waterTop = stage.top - box.top + 28;
        const waterHeight = height - waterTop;
        water.position.y = -waterTop / 2;
        water.scale.set(width, waterHeight, 1);
        farBank.position.set(0, height / 2 - waterTop - 8, -170);
        farBank.scale.set(width * .75, 65, 20);
        plants.forEach(({ object, side }) => {
            const size = width > 700 ? 95 : 56;
            object.scale.setScalar(size);
            object.position.set(side * (width / 2 - size * .2), -height / 2 + size * .28, -20);
        });
        flowers.forEach((flower, index) => {
            const size = width > 700 ? 24 : 14;
            flower.scale.setScalar(size);
            flower.position.set((index === 0 ? -.32 : index === 1 ? .34 : .23) * width, -height / 2 + (index === 2 ? 105 : 50), -15);
        });
        targets.forEach((target) => {
            const rect = target.button.querySelector('.frog-target-visual').getBoundingClientRect();
            target.base.set(rect.left + rect.width / 2 - box.left - width / 2, height / 2 - (rect.top + rect.height / 2 - box.top), 60);
            target.scale = Math.min(rect.width / 2, rect.height / 1.7);
        });
        render(performance.now(), true);
    }

    function render(now, force = false) {
        if (disposed || contextLost) return;
        if (!force && now - previousFrame < 1000 / 30) return;
        previousFrame = now;
        const time = now / 1000;
        const reduced = motionQuery.matches;
        const breathing = reduced ? 0 : Math.sin(time * 2) * .018;
        character.scale.set(1, 1 + breathing, 1);
        head.rotation.z = reduced ? 0 : Math.sin(time * .9) * .012;
        const blinkPhase = time % 5.2;
        const blink = !reduced && blinkPhase > 4.95 ? Math.max(.08, Math.abs(blinkPhase - 5.075) / .125) : 1;
        eyes.forEach((eye) => { eye.scale.y = blink; });
        pupils.forEach((pupil, index) => {
            pupil.position.x = (index === 0 ? .035 : -.035) + gaze.x * .028;
            pupil.position.y = -.015 + gaze.y * .025;
        });
        targets.forEach((target) => {
            if (catchAnimation?.target === target) return;
            target.gulpMouth.visible = false;
            target.proboscis.visible = true;
            target.abdomen.scale.set(.25, .39, .2);
            const bob = reduced ? 0 : Math.sin(time * 2.5 + target.index * 1.6) * 4;
            target.object.position.copy(target.base).add(new THREE.Vector3(0, bob, 0));
            target.object.rotation.z = reduced ? 0 : Math.sin(time * 1.6 + target.index) * .055;
            target.object.scale.setScalar(target.scale * (target.hover && !target.button.disabled ? 1.08 : 1));
            target.object.visible = !target.button.hidden;
            target.wings.forEach(({ pivot, side }) => { pivot.rotation.y = reduced ? 0 : side * Math.sin(time * 48) * .52; });
        });
        ripples.forEach((ripple, index) => {
            const wave = reduced ? .5 : (time * .14 + index * .23) % 1;
            const radius = frogScale * (1.95 + index * .28 + wave * .3);
            ripple.scale.set(radius, radius * .22, 1);
            ripple.position.set(lily.position.x, lily.position.y - 6 - index * 4, -65);
        });

        if (catchAnimation && !catchAnimation.correct) {
            const { target, origin, duration, start } = catchAnimation;
            const progress = Math.min((now - start) / duration, 1);
            const smooth = (value) => { const t = THREE.MathUtils.clamp(value, 0, 1); return t * t * (3 - 2 * t); };
            const approach = reduced ? 1 : smooth(progress / .32);
            const swallow = smooth((progress - .32) / .26);
            const retreat = reduced ? (progress >= .84 ? 1 : 0) : smooth((progress - .7) / .3);
            const reappear = smooth((progress - .84) / .16);
            attackPosition.copy(frog.position).add(new THREE.Vector3(0, frogScale * .9, 115));
            target.object.position.copy(origin).lerp(attackPosition, approach).lerp(target.base, retreat);
            if (!reduced) target.object.position.y += Math.sin(approach * Math.PI) * 45 * (1 - retreat);
            const enlarged = 1 + (reduced ? .25 : .7) * approach * (1 - retreat);
            target.object.scale.setScalar(target.scale * enlarged);
            target.object.rotation.z = reduced ? 0 : Math.sin(progress * Math.PI * 5) * .1 * (1 - retreat);
            target.object.visible = !target.button.hidden;
            target.wings.forEach(({ pivot, side }) => { pivot.rotation.y = reduced ? 0 : side * Math.sin(time * 65) * .7; });
            target.gulpMouth.visible = progress < .64;
            target.proboscis.visible = !target.gulpMouth.visible;
            const belly = Math.sin(swallow * Math.PI / 2) * (1 - retreat);
            target.abdomen.scale.set(.25 * (1 + belly * .45), .39 * (1 + belly * .2), .2);

            // Pull the shrinking character into the mouth, keeping the lily pad in place.
            target.gulpMouth.getWorldPosition(swallowPosition);
            frog.worldToLocal(swallowPosition);
            character.position.copy(swallowPosition).multiplyScalar(reduced ? 0 : swallow * (1 - reappear));
            character.scale.setScalar(progress < .84 ? Math.max(.001, 1 - swallow) : reappear);
            character.rotation.z = reduced ? 0 : Math.sin(swallow * Math.PI) * .15;
            character.visible = progress < .58 || progress > .84;
            if (progress > .84) character.position.set(0, 0, 0);
            eyes.forEach((eye) => { eye.scale.y = 1; });
            tongue.visible = tongueTip.visible = false;
            mouth.visible = progress < .58;
            smile.visible = !mouth.visible;
        } else if (catchAnimation) {
            const progress = Math.min((now - catchAnimation.start) / catchAnimation.duration, 1);
            const { target, origin } = catchAnimation;
            character.updateWorldMatrix(true, true);
            mouthWorld.copy(mouthPoint);
            character.localToWorld(mouthWorld);
            const reach = progress < .32 ? Math.sin(progress / .32 * Math.PI / 2) : progress < .42 ? 1 : Math.max(0, 1 - (progress - .42) / .43);
            tip.copy(mouthWorld).lerp(origin, reach);
            direction.copy(tip).sub(mouthWorld);
            tongue.position.copy(mouthWorld).add(tip).multiplyScalar(.5);
            tongue.scale.set(frogScale * .045, Math.max(.01, direction.length()), frogScale * .045);
            tongue.quaternion.setFromUnitVectors(up, direction.normalize());
            tongueTip.position.copy(tip);
            tongueTip.scale.setScalar(frogScale * .066);
            tongue.visible = tongueTip.visible = progress < .86;
            target.object.position.copy(progress > .42 ? tip : origin);
            target.object.scale.setScalar(target.scale * (progress > .42 ? Math.max(.02, reach) : 1));
            target.object.rotation.z = Math.sin(progress * Math.PI * 4) * .12;
            target.object.visible = progress < .86;
            target.wings.forEach(({ pivot, side }) => { pivot.rotation.y = side * Math.sin(time * 65) * .65; });
            mouth.visible = progress < .9;
            smile.visible = !mouth.visible;
            character.scale.y += Math.sin(progress * Math.PI) * .06;
            if (progress === 1) {
                tongue.visible = tongueTip.visible = mouth.visible = false;
                smile.visible = true;
            }
        }
        renderer.render(scene, camera);
    }

    function onCatch(event) {
        const target = targets.find((item) => item.button.dataset.frogAnswer === event.detail.letter);
        if (!target) return;
        catchAnimation = {
            target, origin: target.object.position.clone(), start: event.detail.startedAt ?? performance.now(),
            correct: event.detail.correct !== false,
            duration: event.detail.duration || 850,
        };
        render(performance.now(), true);
    }
    function onQuestion() {
        catchAnimation = null;
        character.position.set(0, 0, 0);
        character.rotation.set(0, 0, 0);
        character.scale.setScalar(1);
        character.visible = true;
        tongue.visible = tongueTip.visible = mouth.visible = false;
        smile.visible = true;
        layout();
    }
    function onPointer(event) {
        const rect = root.getBoundingClientRect();
        gaze.set(THREE.MathUtils.clamp((event.clientX - rect.left) / dimensions.width * 2 - 1, -1, 1), THREE.MathUtils.clamp(1 - (event.clientY - rect.top) / dimensions.height * 2, -1, 1));
    }
    function updateVisibility() {
        renderer.setAnimationLoop(!document.hidden && !finished && !contextLost && !disposed ? render : null);
    }
    const observer = new ResizeObserver(layout);
    observer.observe(root);
    observer.observe(root.querySelector('.frog-interface'));
    root.addEventListener('frog:catch', onCatch);
    root.addEventListener('frog:question', onQuestion);
    root.addEventListener('pointermove', onPointer);
    root.addEventListener('frog:finished', () => { finished = true; updateVisibility(); });
    document.addEventListener('visibilitychange', updateVisibility);
    renderer.domElement.addEventListener('webglcontextlost', (event) => {
        event.preventDefault();
        contextLost = true;
        root.classList.remove('pond-3d-ready');
        root.dataset.pondState = 'fallback';
        updateVisibility();
    });
    renderer.domElement.addEventListener('webglcontextrestored', () => {
        contextLost = false;
        layout();
        root.classList.add('pond-3d-ready');
        root.dataset.pondState = 'ready';
        updateVisibility();
    });
    window.addEventListener('pagehide', (event) => {
        renderer.setAnimationLoop(null);
        if (event.persisted) return;
        disposed = true;
        observer.disconnect();
        document.removeEventListener('visibilitychange', updateVisibility);
        root.removeEventListener('frog:catch', onCatch);
        root.removeEventListener('frog:question', onQuestion);
        root.removeEventListener('pointermove', onPointer);
        geometries.forEach((item) => item.dispose());
        materials.forEach((item) => item.dispose());
        renderer.dispose();
    });
    window.addEventListener('pageshow', (event) => { if (event.persisted) updateVisibility(); });

    layout();
    const pendingCatch = root.querySelector('[data-frog-answer][data-result]');
    if (!finished && pendingCatch && root.dataset.frogOutcome) {
        onCatch({ detail: {
            letter: pendingCatch.dataset.frogAnswer,
            correct: root.dataset.frogOutcome === 'correct',
            duration: Number(root.dataset.frogCatchDuration),
            startedAt: Number(root.dataset.frogCatchStart),
        } });
    }
    root.classList.add('pond-3d-ready');
    root.dataset.pondState = 'ready';
    updateVisibility();
}
