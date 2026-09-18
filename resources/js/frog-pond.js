import * as THREE from 'three';
import { frogJumpFrame, frogFinishFrame, isPerfectFrogRun } from './frog-jump';

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
    const sinkMask = new THREE.Mesh(plane, water.material);
    sinkMask.visible = false;
    scene.add(sinkMask);
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

    const targets = [...root.querySelectorAll('[data-frog-answer]')].map((button, index) => {
        const object = new THREE.Group();
        scene.add(object);
        const leaf = mesh(object, leafGeometry, ['#63b950', '#82b752', '#49aa77', '#69ae56'][index], [0, 0, 0], [1, 1, 1]);
        leaf.rotation.z = -.36;
        for (let vein = 0; vein < 8; vein++) {
            const angle = .5 + vein * .7;
            segment(object, [0, 0, .09], [Math.cos(angle) * .88, Math.sin(angle) * .88, .09], .009, '#b1d778');
        }
        const color = ['#ed8b7d', '#efc056', '#78b6e5', '#b7a0df'][index];
        for (let petal = 0; petal < 5; petal++) {
            const angle = petal * Math.PI * 2 / 5;
            ellipsoid(object, color, [-.48 + Math.cos(angle) * .13, .23 + Math.sin(angle) * .16, .18], [.16, .16, .08]);
        }
        ellipsoid(object, '#fff1a1', [-.48, .23, .28], [.1, .12, .09]);
        const shadow = mesh(scene, circle, '#286e6c', [0, 0, 0], [1, 1, 1], { transparent: true, opacity: .18, depthWrite: false });
        const ripple = mesh(scene, ring, '#effff7', [0, 0, 0], [1, 1, 1], { transparent: true, opacity: .6, depthWrite: false });
        ripple.visible = false;
        const target = { button, object, shadow, ripple, base: new THREE.Vector3(), width: 1, height: 1, landingScale: 1, hover: false, index };
        button.addEventListener('pointerenter', () => { target.hover = true; });
        button.addEventListener('pointerleave', () => { target.hover = false; });
        button.addEventListener('focus', () => { target.hover = true; });
        button.addEventListener('blur', () => { target.hover = false; });
        return target;
    });
    const splash = new THREE.Group();
    scene.add(splash);
    const drops = Array.from({ length: 8 }, () => ellipsoid(splash, '#c2f5f4', [0, 0, 0], [3, 6, 3], { roughness: .15 }));
    splash.visible = false;
    const finishGate = new THREE.Group();
    scene.add(finishGate);
    const boxGeometry = geometry(new THREE.BoxGeometry(1, 1, 1));
    const finishPosts = [-1, 1].map(side => ({ side, object: mesh(finishGate, boxGeometry, '#fff4d7', [0, 0, 0], [1, 1, 1]) }));
    const finishChecks = Array.from({ length: 16 }, (_, index) => mesh(finishGate, plane, (index + Math.floor(index / 8)) % 2 ? '#fffbe9' : '#254d49', [0, 0, 0], [1, 1, 1]));
    const finishPad = new THREE.Group();
    scene.add(finishPad);
    mesh(finishPad, leafGeometry, '#81b959', [0, 0, 0], [1, 1, 1]).rotation.z = -.36;
    for (let vein = 0; vein < 8; vein++) {
        const angle = .5 + vein * .7;
        segment(finishPad, [0, 0, .09], [Math.cos(angle) * .88, Math.sin(angle) * .88, .09], .009, '#d0e493');
    }
    const motionQuery = { get matches() { return window.PgaalsPreferences?.reducedMotion ?? window.matchMedia('(prefers-reduced-motion: reduce)').matches; } };
    const gaze = new THREE.Vector2();
    const homePosition = new THREE.Vector3();
    const landingPosition = new THREE.Vector3();
    const finishPosition = new THREE.Vector3();
    let dimensions = { width: 0, height: 0 };
    let frogScale = 1;
    let jumpAnimation = null;
    let finishAnimation = null;
    let finishScale = 1;
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
        homePosition.set(anchor.left + anchor.width / 2 - box.left - width / 2, height / 2 - (anchor.top + anchor.height * .46 - box.top), 45);
        frog.position.copy(homePosition);
        frog.scale.setScalar(frogScale);
        lily.position.set(frog.position.x, frog.position.y - frogScale * 1.16, 15);
        lily.scale.set(frogScale * 1.9, frogScale * .48, frogScale * .5);
        frogShadow.position.set(lily.position.x + 7, lily.position.y - 8, -5);
        frogShadow.scale.set(frogScale * 1.92, frogScale * .52, 1);

        const gateBox = root.querySelector('#frog-finish-line').getBoundingClientRect();
        finishGate.position.set(gateBox.left + gateBox.width / 2 - box.left - width / 2, height / 2 - (gateBox.bottom - box.top), 30);
        finishPosts.forEach(({ side, object }) => {
            object.position.set(side * (gateBox.width / 2 - 2), (gateBox.height - 16) / 2, 0);
            object.scale.set(4, gateBox.height - 16, 8);
        });
        finishChecks.forEach((object, index) => {
            object.position.set(-gateBox.width / 2 + (index % 8 + .5) * gateBox.width / 8, gateBox.height - 30 - Math.floor(index / 8) * 10, 8);
            object.scale.set(gateBox.width / 8, 10, 1);
        });
        const finishBox = root.querySelector('#frog-finish-pad').getBoundingClientRect();
        finishPad.position.set(finishBox.left + finishBox.width / 2 - box.left - width / 2, height / 2 - (finishBox.top + finishBox.height / 2 - box.top), 25);
        finishPad.scale.set(finishBox.width * .49, finishBox.height * .48, 15);
        finishScale = Math.min(frogScale, finishBox.width / 3.6);
        finishPosition.copy(finishPad.position).add(new THREE.Vector3(0, finishScale * 1.16, 95));

        const answerZone = root.querySelector('.frog-answer-zone').getBoundingClientRect();
        const waterTop = answerZone.top - box.top - 14;
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
            target.base.set(rect.left + rect.width / 2 - box.left - width / 2, height / 2 - (rect.top + rect.height * .80 - box.top), 25);
            target.width = rect.width * .46;
            target.height = rect.height * .17;
            target.landingScale = Math.min(frogScale, rect.width / 4, rect.height / 3.6);
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
        frog.position.copy(homePosition);
        frog.scale.setScalar(frogScale);
        character.position.set(0, 0, 0);
        character.rotation.z = 0;
        mouth.visible = false;
        smile.visible = true;
        splash.visible = false;
        sinkMask.visible = false;
        character.visible = true;
        frogShadow.position.set(lily.position.x + 7, lily.position.y - 8, -5);
        frogShadow.scale.set(frogScale * 1.92, frogScale * .52, 1);
        targets.forEach((target) => {
            const bob = reduced ? 0 : Math.sin(time * 1.8 + target.index * 1.6) * 1.5;
            const hover = target.hover && !target.button.disabled ? 1.035 : 1;
            target.object.position.copy(target.base);
            target.object.position.y += bob;
            target.object.rotation.z = reduced ? 0 : Math.sin(time * .9 + target.index) * .018;
            target.object.scale.set(target.width * hover, target.height * hover, target.width * .3);
            target.object.visible = target.shadow.visible = !target.button.hidden;
            target.shadow.position.copy(target.base).add(new THREE.Vector3(4, -5, -12));
            target.shadow.scale.set(target.width * 1.04, target.height * 1.1, 1);
            target.ripple.visible = false;
        });
        ripples.forEach((ripple, index) => {
            const wave = reduced ? .5 : (time * .14 + index * .23) % 1;
            const radius = frogScale * (1.95 + index * .28 + wave * .3);
            ripple.scale.set(radius, radius * .22, 1);
            ripple.position.set(lily.position.x, lily.position.y - 6 - index * 4, -65);
        });

        if (jumpAnimation) {
            const { target, duration, start, correct } = jumpAnimation;
            const progress = Math.max(0, Math.min((now - start) / duration, 1));
            const pose = frogJumpFrame(progress, correct, reduced);
            landingPosition.copy(target.base).add(new THREE.Vector3(0, target.landingScale * 1.16, 95));
            const arc = Math.min(90, homePosition.distanceTo(landingPosition) * .18 + 25);
            frog.position.copy(homePosition).lerp(landingPosition, pose.travel);
            const depth = pose.dip + pose.sink * target.height * 6.2;
            frog.position.y += pose.lift * arc - depth;
            frog.position.z = 120;
            const size = THREE.MathUtils.lerp(frogScale, target.landingScale, pose.travel);
            frog.scale.setScalar(size);
            character.scale.set(1 / pose.squash, pose.squash + pose.lift * .08, 1);
            character.rotation.z = pose.wobble;
            target.object.position.copy(target.base);
            target.object.position.y -= depth;
            target.object.rotation.z = pose.wobble;
            target.object.scale.set(target.width, target.height, target.width * .3);
            if (pose.sink > 0) {
                sinkMask.visible = !reduced;
                sinkMask.position.set(target.base.x, target.base.y - target.height * 3.1, 240);
                sinkMask.scale.set(target.width * 2.3, target.height * 6.2, 1);
                if (reduced || pose.sink >= .999) character.visible = target.object.visible = false;
                target.shadow.visible = false;
            }
            frogShadow.position.set(frog.position.x + 4, THREE.MathUtils.lerp(lily.position.y, target.base.y, pose.travel) - 6, 10);
            frogShadow.scale.set(size * 1.65, size * .35, 1);
            if (pose.ripple >= 0) {
                target.ripple.visible = true;
                target.ripple.position.copy(target.base).add(new THREE.Vector3(0, -3, -8));
                target.ripple.scale.set(target.width * (1.1 + pose.ripple * .7), target.height * (1.2 + pose.ripple * .7), 1);
                target.ripple.material.opacity = (1 - pose.ripple) * .65;
                if (!correct && !reduced) {
                    splash.visible = true;
                    splash.position.copy(target.base).add(new THREE.Vector3(0, 0, 100));
                    drops.forEach((drop, index) => {
                        const angle = index * Math.PI * 2 / drops.length;
                        const spread = .4 + pose.ripple;
                        drop.position.set(Math.cos(angle) * target.width * spread, Math.sin(angle) * target.height + Math.sin(pose.ripple * Math.PI) * 28, 0);
                        const size = Math.max(.05, 1 - pose.ripple);
                        drop.scale.set(3 * size, 6 * size, 3 * size);
                    });
                }
            }
            mouth.visible = !correct && pose.phase === 'landed';
            smile.visible = !mouth.visible;
        }
        if (finishAnimation) {
            const pose = frogFinishFrame((now - finishAnimation.start) / finishAnimation.duration, reduced);
            frog.position.copy(homePosition).lerp(finishPosition, pose.travel);
            frog.position.y += pose.lift * 28;
            frog.position.z = 120;
            const size = THREE.MathUtils.lerp(frogScale, finishScale, pose.travel);
            frog.scale.setScalar(size);
            character.scale.set(1 / pose.squash, pose.squash, 1);
            frogShadow.position.set(frog.position.x, THREE.MathUtils.lerp(lily.position.y, finishPad.position.y, pose.travel) - 5, 10);
            frogShadow.scale.set(size * 1.65, size * .35, 1);
        }
        renderer.render(scene, camera);
    }

    function onJump(event) {
        finishAnimation = null;
        const target = targets.find((item) => item.button.dataset.frogAnswer === event.detail.letter);
        if (!target) return;
        jumpAnimation = {
            target, start: event.detail.startedAt ?? performance.now(),
            correct: event.detail.correct !== false,
            duration: event.detail.duration || 1500,
        };
        render(performance.now(), true);
    }
    function onQuestion() {
        jumpAnimation = null;
        finishAnimation = null;
        character.position.set(0, 0, 0);
        character.rotation.set(0, 0, 0);
        character.scale.setScalar(1);
        character.visible = true;
        mouth.visible = false;
        smile.visible = true;
        layout();
    }
    function onFinishLine({ detail }) {
        if (!isPerfectFrogRun(detail.correct, detail.answered, detail.total)) return;
        jumpAnimation = null;
        finishAnimation = { start: detail.startedAt, duration: detail.duration };
        render(performance.now(), true);
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
    root.addEventListener('frog:jump', onJump);
    root.addEventListener('frog:finish-line', onFinishLine);
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
        root.removeEventListener('frog:jump', onJump);
        root.removeEventListener('frog:finish-line', onFinishLine);
        root.removeEventListener('frog:question', onQuestion);
        root.removeEventListener('pointermove', onPointer);
        geometries.forEach((item) => item.dispose());
        materials.forEach((item) => item.dispose());
        renderer.dispose();
    });
    window.addEventListener('pageshow', (event) => { if (event.persisted) updateVisibility(); });

    layout();
    const pendingJump = root.querySelector('[data-frog-answer][data-result]');
    if (!finished && root.dataset.frogFinishing === 'true') {
        onFinishLine({ detail: { correct: Number(root.dataset.frogCorrect), answered: Number(root.dataset.frogAnswered), total: Number(root.dataset.frogTotal),
            startedAt: Number(root.dataset.frogFinishStart), duration: Number(root.dataset.frogFinishDuration) } });
    } else if (!finished && pendingJump && root.dataset.frogOutcome) {
        onJump({ detail: {
            letter: pendingJump.dataset.frogAnswer,
            correct: root.dataset.frogOutcome === 'correct',
            duration: Number(root.dataset.frogJumpDuration),
            startedAt: Number(root.dataset.frogJumpStart),
        } });
    }
    root.classList.add('pond-3d-ready');
    root.dataset.pondState = 'ready';
    updateVisibility();
}
