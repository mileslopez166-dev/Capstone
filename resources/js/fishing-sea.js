import * as THREE from 'three';

export function initFishingSea(host) {
    const root = host.closest('.hook-game');
    if (!root || host.dataset.initialized) return;
    host.dataset.initialized = 'true';
    root.dataset.seaState = 'fallback';

    let renderer;
    try {
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('webgl2', { antialias: true, powerPreference: 'low-power' });
        if (!context) return;
        renderer = new THREE.WebGLRenderer({ canvas, context, antialias: true });
    } catch {
        return;
    }
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.15;
    host.appendChild(renderer.domElement);

    const arena = root.querySelector('#fish-container');
    const hookHead = root.querySelector('#hook-head');
    const hookCable = root.querySelector('#hook-cable');
    const hookAssembly = root.querySelector('#hook-assembly');
    const scene = new THREE.Scene();
    const camera = new THREE.OrthographicCamera(-1, 1, 1, -1, .1, 2500);
    camera.position.z = 1200;
    scene.add(new THREE.HemisphereLight('#e3fcff', '#226e78', 2.4));
    const sunlight = new THREE.DirectionalLight('#fff3cf', 3.2);
    sunlight.position.set(-300, 650, 550);
    scene.add(sunlight);
    const rimLight = new THREE.DirectionalLight('#9cf8ff', 1.8);
    rimLight.position.set(500, 100, -100);
    scene.add(rimLight);

    const geometries = new Set();
    const materials = new Set();
    const textures = new Set();
    const keepGeometry = (value) => { geometries.add(value); return value; };
    const keepMaterial = (value) => { materials.add(value); return value; };
    const sphere = keepGeometry(new THREE.SphereGeometry(1, 24, 16));
    const cylinder = keepGeometry(new THREE.CylinderGeometry(1, 1, 1, 8));
    const up = new THREE.Vector3(0, 1, 0);
    const standard = (color, options = {}) => keepMaterial(new THREE.MeshStandardMaterial({ color, roughness: .42, ...options }));
    const mesh = (parent, geometry, material, position = [0, 0, 0], scale = [1, 1, 1]) => {
        const item = new THREE.Mesh(geometry, material);
        item.position.set(...position);
        item.scale.set(...scale);
        parent.add(item);
        return item;
    };
    const tube = (parent, points, radius, material) => mesh(parent, keepGeometry(new THREE.TubeGeometry(
        new THREE.CatmullRomCurve3(points.map((point) => new THREE.Vector3(...point))), 32, radius, 7, false,
    )), material);

    const uniforms = {
        uTime: { value: 0 },
        uSize: { value: new THREE.Vector2(1, 1) },
        uSurface: { value: 160 },
    };
    // Screen-space water keeps the surface and seabed aligned with the accessible game targets.
    const oceanMaterial = keepMaterial(new THREE.ShaderMaterial({
        uniforms,
        depthWrite: false,
        vertexShader: `varying vec2 vUv;
            void main() { vUv = uv; gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0); }`,
        fragmentShader: `
            varying vec2 vUv;
            uniform vec2 uSize;
            uniform float uTime;
            uniform float uSurface;
            float hash(vec2 p) { return fract(sin(dot(p, vec2(127.1,311.7))) * 43758.5453); }
            float noise(vec2 p) {
                vec2 i = floor(p), f = fract(p); f = f*f*(3.0-2.0*f);
                return mix(mix(hash(i),hash(i+vec2(1,0)),f.x), mix(hash(i+vec2(0,1)),hash(i+vec2(1,1)),f.x), f.y);
            }
            float caustic(vec2 p) {
                p += vec2(sin(p.y*1.6+uTime*.4),cos(p.x*1.5-uTime*.3))*.42;
                float a = sin(p.x*2.1+p.y*1.6+uTime*.3);
                float b = cos(p.y*2.7-p.x*.9-uTime*.35);
                return pow(max(0.0,1.0-abs(a+b)*.55),18.0);
            }
            void main() {
                vec2 p = vec2(vUv.x*uSize.x,(1.0-vUv.y)*uSize.y);
                float wave = sin(p.x*.016+uTime*.8)*2.3 + sin(p.x*.036-uTime*.6)*1.2;
                float depth = p.y-uSurface-wave;
                vec3 color;
                if (depth < 0.0) {
                    float sky = clamp(p.y/max(uSurface,1.0),0.0,1.0);
                    color = mix(vec3(.40,.73,.87),vec3(.86,.94,.92),sky);
                    float clouds = smoothstep(.56,.79,noise(vec2(p.x*.003-uTime*.003,p.y*.012)));
                    color = mix(color,vec3(.97,.98,.94),clouds*.42);
                    float horizon = uSurface-28.0;
                    if(p.y>horizon) {
                        float distance = (p.y-horizon)/28.0;
                        color = mix(vec3(.22,.63,.73),vec3(.24,.79,.79),distance);
                        color += pow(max(0.0,sin(p.y*1.5+sin(p.x*.014)+uTime*.6)),16.0)*.13;
                    }
                } else {
                    float d = clamp(depth/max(uSize.y-uSurface,1.0),0.0,1.0);
                    color = mix(vec3(.15,.69,.72),vec3(.025,.25,.34),pow(d,.7));
                    float beam = p.x + depth*.34;
                    float rays = pow(max(0.0,sin(beam*.016+sin(beam*.005+uTime*.12))),10.0);
                    rays += pow(max(0.0,sin(beam*.039-uTime*.1)),22.0)*.35;
                    color += vec3(.26,.39,.32)*rays*exp(-d*2.4)*.48;
                    color += caustic(p*.021)*vec3(.09,.16,.13)*(1.0-d)*.22;
                    float sandLine = uSize.y-35.0 + sin(p.x*.008)*11.0 + sin(p.x*.024)*3.0;
                    float sand = smoothstep(sandLine-14.0,sandLine+4.0,p.y);
                    vec3 sandColor = vec3(.30,.51,.48) + noise(p*.16)*.045;
                    sandColor += caustic(p*.037)*vec3(.13,.21,.16)*.5;
                    color = mix(color,sandColor,sand);
                    float foam = exp(-depth*.32)*(.55+.45*sin(p.x*.044+uTime));
                    color += vec3(.56,.81,.73)*foam*.62;
                }
                gl_FragColor=vec4(color,1.0);
                #include <colorspace_fragment>
            }`,
    }));
    const backdrop = mesh(scene, keepGeometry(new THREE.PlaneGeometry(1, 1)), oceanMaterial, [0, 0, -450]);

    const surfaceGeometry = keepGeometry(new THREE.PlaneGeometry(1, 1, 100, 8));
    const surface = mesh(scene, surfaceGeometry, standard('#83e7d9', { transparent: true, opacity: .3, metalness: .45, roughness: .2, side: THREE.DoubleSide, depthWrite: false }), [0, 0, -80]);
    const surfacePositions = surfaceGeometry.attributes.position;
    const surfaceBase = surfacePositions.array.slice();

    function scaleTexture(color) {
        const canvas = document.createElement('canvas');
        canvas.width = 512;
        canvas.height = 256;
        const context = canvas.getContext('2d');
        const gradient = context.createLinearGradient(0, 0, 0, 256);
        gradient.addColorStop(0, '#244b56');
        gradient.addColorStop(.2, '#4c7e86');
        gradient.addColorStop(.5, color);
        gradient.addColorStop(.78, '#dce8db');
        gradient.addColorStop(1, '#f1efce');
        context.fillStyle = gradient;
        context.fillRect(0, 0, 512, 256);
        for (let row = 0; row < 19; row++) {
            for (let col = 0; col < 39; col++) {
                const x = col * 14 + (row % 2) * 7;
                const y = row * 14;
                context.strokeStyle = 'rgba(237,255,246,.29)';
                context.lineWidth = .9;
                context.beginPath();
                context.arc(x, y, 8, -.5, 2.3);
                context.stroke();
                context.strokeStyle = 'rgba(8,52,65,.20)';
                context.beginPath();
                context.arc(x - 1, y + 1, 8, -.4, 2.1);
                context.stroke();
            }
        }
        const texture = new THREE.CanvasTexture(canvas);
        texture.colorSpace = THREE.SRGBColorSpace;
        texture.anisotropy = Math.min(4, renderer.capabilities.getMaxAnisotropy());
        textures.add(texture);
        return texture;
    }

    // A tapered body with real fins and scale texture; the tail bends independently of the body.
    const bodyPoints = [
        new THREE.Vector2(0, -1.02), new THREE.Vector2(.075, -.95),
        new THREE.Vector2(.18, -.65), new THREE.Vector2(.31, -.3),
        new THREE.Vector2(.34, .08), new THREE.Vector2(.27, .43),
        new THREE.Vector2(.16, .7), new THREE.Vector2(.055, .87), new THREE.Vector2(0, .9),
    ];
    const bodyCurve = new THREE.SplineCurve(bodyPoints);
    const bodyGeometry = keepGeometry(new THREE.LatheGeometry(bodyCurve.getPoints(48), 36));
    bodyGeometry.rotateZ(-Math.PI / 2);
    bodyGeometry.scale(1, 1.1, .58);
    const bodyPosition = bodyGeometry.attributes.position;
    const bodyUv = bodyGeometry.attributes.uv;
    for (let index = 0; index < bodyPosition.count; index++) {
        bodyUv.setXY(index, (bodyPosition.getX(index) + 1.02) / 1.92, (bodyPosition.getY(index) + .38) / .76);
    }

    const eyeWhite = standard('#d9d7a5', { metalness: .28, roughness: .22 });
    const pupilMaterial = standard('#071b21', { roughness: .12 });
    const glintMaterial = keepMaterial(new THREE.MeshBasicMaterial({ color: '#ffffff' }));
    const gillMaterial = standard('#245260', { transparent: true, opacity: .65 });
    const palettes = ['#71bfa8', '#df987c', '#e0c065', '#75b4d6'];
    const fishMaterials = palettes.map((color) => ({
        body: standard('#ffffff', { map: scaleTexture(color), metalness: .36, roughness: .36 }),
        fin: standard(color, { side: THREE.DoubleSide, transparent: true, opacity: .82, roughness: .44 }),
        ray: standard(color, { roughness: .45 }),
    }));
    const finGeometry = (points) => {
        const shape = new THREE.Shape();
        points.forEach(([x, y], index) => index ? shape.lineTo(x, y) : shape.moveTo(x, y));
        shape.closePath();
        return keepGeometry(new THREE.ShapeGeometry(shape));
    };
    const tailGeometry = finGeometry([[0, 0], [-.45, .39], [-.39, .16], [-.28, 0], [-.39, -.16], [-.45, -.39]]);
    const dorsalGeometry = finGeometry([[-.64, .18], [-.47, .43], [-.05, .66], [.07, .34], [.37, .27]]);
    const lowerGeometry = finGeometry([[-.6, -.17], [-.48, -.43], [-.12, -.32], [.06, -.28]]);
    const sideGeometry = finGeometry([[.18, .04], [-.21, -.26], [-.39, -.2], [-.25, -.04]]);

    function createFish(index) {
        const object = new THREE.Group();
        const body = new THREE.Group();
        object.add(body);
        scene.add(object);
        const material = fishMaterials[index % 4];
        mesh(body, bodyGeometry, material.body);
        mesh(body, dorsalGeometry, material.fin);
        mesh(body, lowerGeometry, material.fin);
        const tail = new THREE.Group();
        tail.position.x = -.95;
        body.add(tail);
        mesh(tail, tailGeometry, material.fin);
        for (let ray = -3; ray <= 3; ray++) {
            tube(tail, [[0, 0, .006], [-.2, ray * .06, .015], [-.39, ray * .113, .006]], .005, material.ray);
        }
        const fins = [];
        for (const side of [-1, 1]) {
            const fin = mesh(body, sideGeometry, material.fin, [.05, -.045, side * .185]);
            fin.rotation.y = side * .36;
            fins.push(fin);
            mesh(body, sphere, eyeWhite, [.64, .112, side * .12], [.08, .081, .039]);
            mesh(body, sphere, pupilMaterial, [.656, .117, side * .151], [.046, .052, .018]);
            mesh(body, sphere, glintMaterial, [.667, .14, side * .166], [.014, .016, .009]);
            tube(body, [[.42, .22, side * .14], [.35, .12, side * .174], [.36, -.1, side * .178], [.47, -.21, side * .12]], .009, gillMaterial);
        }
        tube(body, [[.84, -.036, -.05], [.89, -.047, 0], [.84, -.036, .05]], .009, gillMaterial);
        return { object, body, tail, fins, direction: 1, index, button: null, caught: false, splash: false };
    }
    const targets = Array.from({ length: 4 }, (_, index) => createFish(index));

    const silver = standard('#cadcdf', { metalness: .85, roughness: .22 });
    const lineMaterial = keepMaterial(new THREE.MeshBasicMaterial({ color: '#e4f9ee', transparent: true, opacity: .8 }));
    const hook = new THREE.Group();
    scene.add(hook);
    tube(hook, [[0, 0, 0], [0, -26, 0], [2, -36, 0], [10, -41, 0], [18, -34, 0], [18, -20, 0]], 1.9, silver);
    tube(hook, [[18, -20, 0], [12, -25, 0]], 1.2, silver);
    mesh(hook, keepGeometry(new THREE.TorusGeometry(3.1, 1, 6, 12)), silver, [0, 2, 0]);
    const line = mesh(scene, cylinder, lineMaterial);
    const castGeometry = keepGeometry(new THREE.BufferGeometry());
    const castPositions = new Float32Array(17 * 3);
    castGeometry.setAttribute('position', new THREE.BufferAttribute(castPositions, 3));
    const castLine = new THREE.Line(castGeometry, keepMaterial(new THREE.LineBasicMaterial({ color: '#f2fff4', transparent: true, opacity: .8 })));
    castLine.frustumCulled = false;
    scene.add(castLine);
    const float = new THREE.Group();
    scene.add(float);
    const floatRed = standard('#e98356', { roughness: .32 });
    const floatCream = standard('#fff0d2', { roughness: .28 });
    mesh(float, sphere, floatCream, [0, 0, 0], [6, 11, 6]);
    mesh(float, sphere, floatRed, [0, 4, .5], [6.2, 6, 6.2]);
    mesh(float, cylinder, silver, [0, 13, 0], [1, 12, 1]);

    const boat = new THREE.Group();
    scene.add(boat);
    const hullShape = new THREE.Shape();
    hullShape.moveTo(-52, 9);
    hullShape.lineTo(59, 9);
    hullShape.quadraticCurveTo(45, -15, 27, -17);
    hullShape.lineTo(-31, -17);
    hullShape.quadraticCurveTo(-45, -14, -52, 9);
    const hullGeometry = keepGeometry(new THREE.ExtrudeGeometry(hullShape, { depth: 24, bevelEnabled: true, bevelSize: 2, bevelThickness: 2, bevelSegments: 2, steps: 1 }));
    mesh(boat, hullGeometry, standard('#f3ead9'), [0, 0, -12]);
    tube(boat, [[-50, 9, 14], [0, 9, 14], [58, 9, 14]], 2, standard('#316c79'));
    mesh(boat, keepGeometry(new THREE.BoxGeometry(27, 25, 25)), standard('#fff9e5'), [-9, 22, 0]);
    mesh(boat, keepGeometry(new THREE.BoxGeometry(17, 11, 1)), standard('#397186', { roughness: .18 }), [-9, 24, 13]);
    mesh(boat, keepGeometry(new THREE.BoxGeometry(37, 3, 33)), standard('#ea9e64'), [-9, 36, 0]);
    tube(boat, [[26, 9, 4], [34, 38, 4], [48, 54, 4], [65, 47, 4]], 1.3, standard('#274750'));
    const boatColors = new Map();
    boat.traverse((part) => {
        if (!part.isMesh) return;
        part.material.transparent = true;
        boatColors.set(part.material, part.material.color.clone());
    });
    const submergedColor = new THREE.Color('#287f98');

    const seabed = new THREE.Group();
    scene.add(seabed);
    const rockMaterial = standard('#50817c', { roughness: .98 });
    const rockLight = standard('#91b4a1', { roughness: .94 });
    const rockGeometry = keepGeometry(new THREE.IcosahedronGeometry(1, 1));
    const rocks = Array.from({ length: 11 }, (_, index) => mesh(seabed, rockGeometry, index % 3 ? rockMaterial : rockLight));
    const plants = [];
    const kelpMaterials = ['#34796b', '#479a7e', '#6bac80'].map((color) => standard(color, { side: THREE.DoubleSide, roughness: .8 }));
    for (let index = 0; index < 17; index++) {
        const plant = new THREE.Group();
        seabed.add(plant);
        const height = 50 + (index * 37 % 75);
        const stem = tube(plant, [[0, 0, 0], [4, height * .4, 0], [-3, height * .8, 0], [2, height, 0]], 1.1, kelpMaterials[index % 3]);
        for (let leaf = 0; leaf < 5; leaf++) {
            const side = leaf % 2 ? 1 : -1;
            const leafMesh = mesh(plant, sphere, kelpMaterials[(index + leaf) % 3], [side * 8, 13 + leaf * height * .15, 0], [5, height * .15, 1.6]);
            leafMesh.rotation.z = -side * .6;
        }
        plants.push({ object: plant, stem, index });
    }
    const coralMaterial = standard('#c99a79', { roughness: .9 });
    const coral = new THREE.Group();
    seabed.add(coral);
    for (let branch = 0; branch < 7; branch++) {
        const x = (branch - 3) * 9;
        tube(coral, [[0, 0, 0], [x * .45, 15, 0], [x, 26 + branch % 3 * 8, 0]], 2.5, coralMaterial);
        tube(coral, [[x * .7, 22, 0], [x + 7, 27, 0], [x + 8, 39, 0]], 1.8, coralMaterial);
    }

    const distantGeometry = keepGeometry(bodyGeometry.clone());
    const distantMaterial = standard('#28768a', { transparent: true, opacity: .32, roughness: .8 });
    const distantFish = Array.from({ length: 9 }, (_, index) => {
        const object = new THREE.Group();
        scene.add(object);
        mesh(object, distantGeometry, distantMaterial);
        mesh(object, tailGeometry, distantMaterial, [-.95, 0, 0]);
        object.scale.setScalar(10 + index % 3 * 3);
        return object;
    });
    const bubbleMaterial = keepMaterial(new THREE.MeshBasicMaterial({ color: '#baf4ef', transparent: true, opacity: .22, depthWrite: false }));
    const bubbleGeometry = keepGeometry(new THREE.TorusGeometry(1, .13, 5, 12));
    const bubbles = Array.from({ length: 22 }, () => mesh(scene, bubbleGeometry, bubbleMaterial));
    const sinkingBubbleMaterial = keepMaterial(new THREE.MeshBasicMaterial({ color: '#dbfff8', transparent: true, opacity: .65, depthWrite: false }));
    const sinkingBubbles = Array.from({ length: 12 }, () => {
        const bubble = mesh(scene, bubbleGeometry, sinkingBubbleMaterial);
        bubble.visible = false;
        return bubble;
    });
    const splashMaterial = standard('#d5fff6', { transparent: true, opacity: .8, roughness: .18 });
    const droplets = Array.from({ length: 14 }, () => { const item = mesh(scene, sphere, splashMaterial); item.visible = false; return item; });

    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let width = 1;
    let height = 1;
    let waterY = 0;
    let surfaceTop = 160;
    let lastFrame = 0;
    let clockTime = 0;
    let disposed = false;
    let contextLost = false;
    let finished = root.dataset.assessmentFinished === 'true';
    let splashAt = -100;
    let splashX = 0;
    let wasSinking = false;
    let rootBox;

    function syncTargets() {
        const buttons = [...root.querySelectorAll('.answer-fish')];
        targets.forEach((target) => {
            const button = buttons.find((item) => item.dataset.letter === 'ABCD'[target.index]);
            if (target.button !== button) { target.caught = false; target.splash = false; }
            target.button = button;
            target.object.visible = Boolean(button);
        });
    }

    function layout() {
        if (disposed || contextLost) return;
        rootBox = root.getBoundingClientRect();
        width = root.clientWidth;
        height = root.clientHeight;
        if (!width || !height) return;
        camera.left = -width / 2;
        camera.right = width / 2;
        camera.top = height / 2;
        camera.bottom = -height / 2;
        camera.updateProjectionMatrix();
        renderer.setSize(width, height, false);
        surfaceTop = arena.getBoundingClientRect().top - rootBox.top - 14;
        waterY = height / 2 - surfaceTop;
        uniforms.uSize.value.set(width, height);
        uniforms.uSurface.value = surfaceTop;
        backdrop.scale.set(width, height, 1);
        surface.position.set(0, waterY + 5, -80);
        surface.scale.set(width, 16, 1);
        boat.scale.setScalar(width < 761 ? .58 : .82);
        boat.position.set(width * .35, waterY + 15, -70);
        rocks.forEach((rock, index) => {
            const size = 12 + index % 4 * 9;
            rock.position.set(-width / 2 + (index / 10) * width, -height / 2 + 22 + index % 3 * 5, -70 - index % 3 * 30);
            rock.scale.set(size * 1.5, size * .65, size);
            rock.rotation.set(index * .8, index, index * .4);
        });
        plants.forEach(({ object, index }) => {
            const side = index < 9 ? -1 : 1;
            const offset = index < 9 ? index : index - 9;
            object.position.set(side * (width / 2 - 9 - offset * 16), -height / 2 + 22, index % 3 ? -90 : 15);
            object.scale.setScalar(width < 761 ? .65 : 1);
        });
        coral.position.set(width * .28, -height / 2 + 24, -50);
        syncTargets();
        render(performance.now(), true);
    }

    function render(now, force = false) {
        if (disposed || contextLost || (!force && now - lastFrame < 1000 / 40)) return;
        const elapsed = lastFrame ? Math.min((now - lastFrame) / 1000, .05) : 0;
        lastFrame = now;
        if (!motion.matches) clockTime += elapsed;
        const time = clockTime;
        uniforms.uTime.value = time;
        rootBox = root.getBoundingClientRect();
        const pull = Number(root.style.getPropertyValue('--boat-pull')) || 0;
        const pullDirection = Number(root.style.getPropertyValue('--boat-pull-direction')) || 1;
        const sinking = root.dataset.catchResult === 'incorrect' && pull > 0;

        for (let index = 0; index < surfacePositions.count; index++) {
            const x = surfaceBase[index * 3] * width;
            surfacePositions.setZ(index, Math.sin(x * .018 + time * .8) * 3);
            surfacePositions.setY(index, surfaceBase[index * 3 + 1] + Math.sin(x * .016 + time * .8) * .13);
        }
        surfacePositions.needsUpdate = true;
        const sinkDistance = motion.matches ? 12 : Math.min(150, (height - surfaceTop) * .48);
        boat.rotation.z = Math.sin(time * .8) * .026 - pullDirection * pull * (motion.matches ? .08 : .65)
            + (motion.matches ? 0 : Math.sin(time * 22) * .07 * Math.sin(pull * Math.PI));
        boat.position.y = waterY + 15 + Math.sin(time * 1.2) * 1.7 - sinkDistance * pull;
        boatColors.forEach((color, material) => {
            material.color.copy(color).lerp(submergedColor, pull * .8);
            material.opacity = 1 - pull * .75;
        });

        targets.forEach((target) => {
            const { button, object, body, tail, fins, index } = target;
            if (!button?.isConnected) { object.visible = false; return; }
            object.visible = true;
            const box = button.getBoundingClientRect();
            const caught = button.dataset.caught === 'true';
            const desiredDirection = Number(button.style.getPropertyValue('--fish-direction')) || 1;
            target.direction = THREE.MathUtils.damp(target.direction, desiredDirection, 7, elapsed);
            object.scale.setScalar(box.width / 2.7);
            if (caught) {
                const hookBox = hookHead.getBoundingClientRect();
                const diving = button.dataset.result === 'incorrect';
                object.rotation.z = (diving ? -1 : 1) * Math.PI / 2 + (motion.matches ? 0 : Math.sin(time * 24) * .07);
                object.position.set(
                    hookBox.left + 38 - rootBox.left - width / 2 - Math.cos(object.rotation.z) * object.scale.x * .87,
                    height / 2 - (hookBox.top + 21 - rootBox.top) - Math.sin(object.rotation.z) * object.scale.x * .87, 85,
                );
                body.rotation.y = .18;
                if (!diving && !target.splash && object.position.y > waterY - 50) {
                    target.splash = true;
                    splashAt = time;
                    splashX = object.position.x;
                }
            } else {
                object.position.set(box.left + box.width * .54 - rootBox.left - width / 2, height / 2 - (box.top + box.height * .45 - rootBox.top), 45);
                object.rotation.z = motion.matches ? 0 : Math.sin(time * 2.2 + index) * .025;
                body.rotation.y = (1 - target.direction) * Math.PI / 2 + .12;
            }
            tail.rotation.y = Math.sin(time * (caught ? 26 : 10) + index) * .45;
            fins.forEach((fin, side) => { fin.rotation.y = (side ? 1 : -1) * (.42 + Math.sin(time * 7 + index) * .23); });
            body.scale.y = 1 + (motion.matches ? 0 : Math.sin(time * 3 + index) * .015);
            target.caught = caught;
        });

        const hookBox = hookHead.getBoundingClientRect();
        const cableBox = hookCable.getBoundingClientRect();
        const hookX = hookBox.left + 20 - rootBox.left - width / 2;
        const hookY = height / 2 - (hookBox.top - rootBox.top);
        hook.visible = line.visible = castLine.visible = hookAssembly.style.opacity !== '0' && !finished;
        if (sinking) line.visible = false;
        hook.position.set(hookX, hookY, 95);
        line.position.set(hookX, hookY + cableBox.height / 2, 90);
        line.scale.set(.55, Math.max(.01, cableBox.height), .55);
        boat.position.x = THREE.MathUtils.clamp(hookX - 65 * boat.scale.x + pullDirection * pull * (motion.matches ? 0 : 28), -width / 2 + 55, width / 2 - 70);
        if (sinking && !wasSinking) {
            splashAt = time;
            splashX = boat.position.x;
        }
        wasSinking = sinking;
        boat.updateWorldMatrix(true, false);
        const rodTip = boat.localToWorld(new THREE.Vector3(65, 47, 4));
        for (let index = 0; index <= 16; index++) {
            const progress = index / 16;
            castPositions[index * 3] = THREE.MathUtils.lerp(rodTip.x, hookX, progress);
            castPositions[index * 3 + 1] = THREE.MathUtils.lerp(rodTip.y, hookY + (sinking ? 0 : cableBox.height), progress) - Math.sin(progress * Math.PI) * 4 * (1 - pull);
            castPositions[index * 3 + 2] = THREE.MathUtils.lerp(rodTip.z, 90, progress);
        }
        castGeometry.attributes.position.needsUpdate = true;
        float.position.set(hookX, waterY + Math.sin(time * 2) * 1.8 - pull * sinkDistance * .6, 100);
        float.rotation.z = Math.sin(time * 2.3) * .12;
        float.visible = hook.visible;

        plants.forEach(({ object, index }) => { object.rotation.z = Math.sin(time * 1.1 + index * .7) * .055; });
        distantFish.forEach((object, index) => {
            object.position.set(((time * (10 + index % 3 * 3) + index * 173) % (width + 100)) - width / 2 - 50,
                waterY - 58 - (index % 3) * 42 + Math.sin(time * .6 + index) * 6, -230);
            object.rotation.y = .25;
        });
        bubbles.forEach((bubble, index) => {
            const travel = (time * (13 + index % 5 * 2) + index * 29) % Math.max(80, height - surfaceTop - 35);
            bubble.position.set(-width / 2 + (index * 157 % Math.max(width, 1)) + Math.sin(time + index) * 6, -height / 2 + 30 + travel, -120);
            bubble.scale.setScalar(1.2 + index % 3 * .8);
        });
        sinkingBubbles.forEach((bubble, index) => {
            bubble.visible = sinking && !motion.matches && pull > .12;
            if (!bubble.visible) return;
            const rise = (time * 65 + index * 13) % Math.max(24, sinkDistance * pull);
            bubble.position.set(boat.position.x + Math.sin(index * 2.3 + time * 3) * (14 + index % 4 * 7),
                Math.min(waterY - 3, boat.position.y + rise), 110);
            bubble.scale.setScalar(2 + index % 4);
        });
        const splashAge = time - splashAt;
        droplets.forEach((drop, index) => {
            drop.visible = !motion.matches && splashAge >= 0 && splashAge < .85;
            if (!drop.visible) return;
            const angle = index / droplets.length * Math.PI;
            drop.position.set(splashX + Math.cos(angle) * splashAge * 85,
                waterY + Math.sin(angle) * splashAge * 170 - splashAge * splashAge * 210, 115);
            drop.scale.set(1.3, 2.6 * (1 - splashAge), 1.3);
        });
        renderer.render(scene, camera);
    }

    function updateVisibility() {
        renderer.setAnimationLoop(!disposed && !contextLost && !document.hidden && !finished ? render : null);
    }
    function onFinished() {
        finished = true;
        render(performance.now(), true);
        updateVisibility();
    }
    const resizeObserver = new ResizeObserver(layout);
    resizeObserver.observe(root);
    resizeObserver.observe(arena);
    const targetObserver = new MutationObserver(syncTargets);
    targetObserver.observe(arena, { childList: true });
    root.addEventListener('fishing:finished', onFinished);
    document.addEventListener('visibilitychange', updateVisibility);
    renderer.domElement.addEventListener('webglcontextlost', (event) => {
        event.preventDefault();
        contextLost = true;
        root.classList.remove('sea-3d-ready');
        root.dataset.seaState = 'fallback';
        updateVisibility();
    });
    renderer.domElement.addEventListener('webglcontextrestored', () => {
        contextLost = false;
        layout();
        root.classList.add('sea-3d-ready');
        root.dataset.seaState = 'ready';
        updateVisibility();
    });
    window.addEventListener('pagehide', (event) => {
        renderer.setAnimationLoop(null);
        if (event.persisted) return;
        disposed = true;
        resizeObserver.disconnect();
        targetObserver.disconnect();
        root.removeEventListener('fishing:finished', onFinished);
        document.removeEventListener('visibilitychange', updateVisibility);
        geometries.forEach((item) => item.dispose());
        materials.forEach((item) => item.dispose());
        textures.forEach((item) => item.dispose());
        renderer.dispose();
    });
    window.addEventListener('pageshow', (event) => { if (event.persisted) updateVisibility(); });
    layout();
    root.classList.add('sea-3d-ready');
    root.dataset.seaState = 'ready';
    updateVisibility();
}
