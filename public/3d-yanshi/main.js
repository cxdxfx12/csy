import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { CSS2DRenderer, CSS2DObject } from 'three/addons/renderers/CSS2DRenderer.js';

// ==================== 全局 ====================
let scene, camera, renderer, labelRenderer, controls, clock;
let fpsFrames = 0, fpsTime = 0;
const ENABLE_SHADOWS = true;
let currentCommunity = 'feicui';

// 动态物体跟踪（切换场景时清理）
let dynamicObjects = [];
let monitorPoints = [];
let cars = [];
let particles = [];
let allBuildings = [];

function track(obj) { dynamicObjects.push(obj); return obj; }
function trackGrp(grp) { grp.children.forEach(c => dynamicObjects.push(c)); dynamicObjects.push(grp); return grp; }

const rand = (min, max) => min + Math.random() * (max - min);

// ==================== 场景配置 ====================
const communities = {
    feicui: {
        name: '翡翠名苑', desc: '14栋高层 · 1680户', tag: '经典高层住宅',
        buildingColor: { body: 0xd4c5b2, band: 0xc7b49e, win: 0xb3d8f0, roof: 0x8d7b6a },
        info: { buildings: 14, households: '1,680', monitors: 18, devices: '320+' },
        legend: [
            { color: '#d4c5b2', label: '高层住宅' },
            { color: '#4a7c59', label: '绿化园林' },
            { color: '#ffb74d', label: '社区配套' },
            { color: '#e57373', label: '智能监控' }
        ],
        ground: { innerW: 100, innerD: 80, innerColor: 0x2a2d35 },
        entrance: { z: -41, name: '翡翠名苑' },
        camera: { pos: [80, 55, 100], target: [0, 0, -5] },
        shortcuts: [
            { key: 'f', cam: [0, 140, -4], tgt: [0, 0, -5] },
            { key: 'g', cam: [0, 8, -20], tgt: [0, -2, -41] },
            { key: '1', cam: [0, 35, 60], tgt: [0, 10, 20] },
            { key: '2', cam: [-20, 20, -45], tgt: [0, 4, -26] }
        ]
    },
    yunqi: {
        name: '云栖别墅', desc: '48栋独栋 · 低密豪宅', tag: '高端花园别墅区',
        buildingColor: { body: 0xf5f0e8, band: 0xe8dcc8, win: 0xd4e8f8, roof: 0xb33a2a },
        info: { buildings: 48, households: '96', monitors: 24, devices: '480+' },
        legend: [
            { color: '#f5f0e8', label: '独栋别墅' },
            { color: '#b33a2a', label: '精装屋顶' },
            { color: '#4a8c5a', label: '私家庭院' },
            { color: '#4fc3f7', label: '社区泳池' }
        ],
        ground: { innerW: 110, innerD: 90, innerColor: 0x2a3028 },
        entrance: { z: -47, name: '云栖别墅' },
        camera: { pos: [70, 50, 90], target: [0, 0, -5] },
        shortcuts: [
            { key: 'f', cam: [0, 120, -4], tgt: [0, 0, -5] },
            { key: 'g', cam: [0, 8, -30], tgt: [0, -2, -47] },
            { key: '1', cam: [20, 30, 60], tgt: [20, 0, -5] },
            { key: '2', cam: [-40, 25, 10], tgt: [0, 4, 0] }
        ]
    },
    zhongliang: {
        name: '中粮壹号', desc: '6栋超高层 · 玻璃幕墙', tag: '城市地标豪宅',
        buildingColor: { body: 0x3a6b8c, band: 0x2d5a78, win: 0x88ccff, roof: 0x334455 },
        info: { buildings: 6, households: '720', monitors: 30, devices: '600+' },
        legend: [
            { color: '#3a6b8c', label: '玻璃幕墙塔楼' },
            { color: '#88ccff', label: '智能幕墙' },
            { color: '#e0e0e0', label: '中央广场' },
            { color: '#ffb74d', label: '商业配套' }
        ],
        ground: { innerW: 90, innerD: 70, innerColor: 0x3a3a40 },
        entrance: { z: -37, name: '中粮壹号' },
        camera: { pos: [60, 70, 80], target: [0, 5, -2] },
        shortcuts: [
            { key: 'f', cam: [0, 160, -2], tgt: [0, 10, -2] },
            { key: 'g', cam: [0, 10, -20], tgt: [0, 0, -37] },
            { key: '1', cam: [-40, 60, 40], tgt: [-20, 15, 0] },
            { key: '2', cam: [0, 25, -20], tgt: [0, 5, -2] }
        ]
    },
    shanshui: {
        name: '山水居', desc: '12栋洋房 · 叠水园林', tag: '新中式园林社区',
        buildingColor: { body: 0xc8b896, band: 0xb8a480, win: 0xc5e3f6, roof: 0x3d5a40 },
        info: { buildings: 12, households: '360', monitors: 16, devices: '240+' },
        legend: [
            { color: '#c8b896', label: '花园洋房' },
            { color: '#3d5a40', label: '中式园林' },
            { color: '#5b8fa8', label: '水景叠瀑' },
            { color: '#a08060', label: '木质连廊' }
        ],
        ground: { innerW: 100, innerD: 80, innerColor: 0x2a3328 },
        entrance: { z: -41, name: '山水居' },
        camera: { pos: [75, 50, 100], target: [0, 2, -8] },
        shortcuts: [
            { key: 'f', cam: [0, 130, -5], tgt: [0, 2, -8] },
            { key: 'g', cam: [0, 8, -25], tgt: [0, -2, -41] },
            { key: '1', cam: [-30, 25, 50], tgt: [-20, 5, 5] },
            { key: '2', cam: [0, 30, -50], tgt: [0, 5, -30] }
        ]
    },
    yifeng: {
        name: '怡丰城', desc: '9栋商业住宅 · 杭州临平', tag: '城市综合体',
        buildingColor: { body: 0xdcdcdc, band: 0xcccccc, win: 0xb8def8, roof: 0x556b7c },
        info: { buildings: 9, households: '800+', monitors: 24, devices: '500+' },
        legend: [
            { color: '#dcdcdc', label: '商业住宅楼' },
            { color: '#4a90c2', label: '中央广场' },
            { color: '#ff9800', label: '商铺配套' },
            { color: '#e57373', label: '智能监控' }
        ],
        ground: { innerW: 110, innerD: 95, innerColor: 0x282a32 },
        entrance: { z: 48, name: '怡丰城', isEast: true, skipDefault: true },
        camera: { pos: [85, 60, 110], target: [0, 5, 0] },
        shortcuts: [
            { key: 'f', cam: [0, 130, 2], tgt: [0, 5, 0] },
            { key: 'g', cam: [0, 10, 52], tgt: [0, 2, 48] },
            { key: '1', cam: [-35, 40, 45], tgt: [-15, 10, 15] },
            { key: '2', cam: [30, 35, -35], tgt: [5, 8, -15] }
        ]
    }
};

// ==================== 初始化 ====================
function initScene() {
    scene = new THREE.Scene();
    scene.background = new THREE.Color(0x0a0e1a);
    scene.fog = new THREE.FogExp2(0x0a0e1a, 0.000022);
}

function initCamera() {
    camera = new THREE.PerspectiveCamera(50, window.innerWidth / window.innerHeight, 2, 500);
    camera.position.set(80, 55, 100);
    camera.lookAt(0, 0, 0);
}

function initRenderer() {
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.shadowMap.enabled = ENABLE_SHADOWS;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.2;
    document.getElementById('canvas-container').appendChild(renderer.domElement);

    labelRenderer = new CSS2DRenderer();
    labelRenderer.setSize(window.innerWidth, window.innerHeight);
    labelRenderer.domElement.style.position = 'absolute';
    labelRenderer.domElement.style.top = '0';
    labelRenderer.domElement.style.pointerEvents = 'none';
    document.getElementById('canvas-container').appendChild(labelRenderer.domElement);
}

function initControls() {
    controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.08;
    controls.minDistance = 15;
    controls.maxDistance = 220;
    controls.maxPolarAngle = Math.PI * 0.47;
    controls.target.set(0, 0, -5);
    controls.update();
}

function initLights() {
    const amb = new THREE.AmbientLight(0x334455, 1.0); scene.add(amb);
    const hemi = new THREE.HemisphereLight(0x8899bb, 0x223340, 0.6); scene.add(hemi);
    const sun = new THREE.DirectionalLight(0xfff5e0, 4.0);
    sun.position.set(80, 100, 40);
    if (ENABLE_SHADOWS) {
        sun.castShadow = true;
        sun.shadow.mapSize.width = 4096;
        sun.shadow.mapSize.height = 4096;
        sun.shadow.camera.near = 1;
        sun.shadow.camera.far = 350;
        sun.shadow.camera.left = -80; sun.shadow.camera.right = 80;
        sun.shadow.camera.top = 80; sun.shadow.camera.bottom = -80;
        sun.shadow.bias = -0.0002;
    }
    scene.add(sun);
    const fill = new THREE.DirectionalLight(0x4477aa, 0.5);
    fill.position.set(-30, 20, -30); scene.add(fill);
}

// ==================== 通用：地面 ====================
function buildGround(cfg) {
    const iw = cfg.innerW, id = cfg.innerD;
    const bgGeo = new THREE.PlaneGeometry(300, 300);
    const bg = new THREE.Mesh(bgGeo, new THREE.MeshStandardMaterial({ color: 0x121722, roughness: 0.95 }));
    bg.rotation.x = -Math.PI / 2; bg.position.y = -0.1; bg.receiveShadow = true;
    scene.add(track(bg));

    const grd = new THREE.Mesh(new THREE.PlaneGeometry(iw, id),
        new THREE.MeshStandardMaterial({ color: cfg.innerColor, roughness: 0.85 }));
    grd.rotation.x = -Math.PI / 2; grd.position.set(0, 0.01, -5); grd.receiveShadow = true;
    scene.add(track(grd));

    // 外围道路
    const roadMat = new THREE.MeshStandardMaterial({ color: 0x1a1a22, roughness: 0.7 });
    const hw = iw / 2 + 7, hd = id / 2 + 7;
    function addRoad(cx, cz, w, d) {
        const m = new THREE.Mesh(new THREE.PlaneGeometry(w, d), roadMat);
        m.rotation.x = -Math.PI / 2; m.position.set(cx, 0.06, cz); m.receiveShadow = true;
        scene.add(track(m));
    }
    addRoad(0, -hd, iw + 14, 7);
    addRoad(0, hd - 14, iw + 14, 7);
    addRoad(-hw, -5, 7, id + 24);
    addRoad(hw, -5, 7, id + 24);
}

// ==================== 通用：围墙 ====================
function buildWall(cfg) {
    const iw = cfg.innerW, id = cfg.innerD;
    const hw = iw / 2, hd = id / 2;
    const wallH = 1.8;
    const wallMat = new THREE.MeshStandardMaterial({ color: 0x8d7b6a, roughness: 0.6, metalness: 0.1 });
    const topMat = new THREE.MeshStandardMaterial({ color: 0x5d4b3a, roughness: 0.4 });

    function wallSeg(cx, cz, len, rotY) {
        const g = new THREE.BoxGeometry(len, wallH, 0.3);
        const m = new THREE.Mesh(g, wallMat);
        const t = new THREE.Mesh(new THREE.BoxGeometry(len, 0.2, 0.5), topMat);
        m.position.set(cx, wallH / 2, cz); m.rotation.y = rotY || 0;
        t.position.set(cx, wallH + 0.1, cz); t.rotation.y = rotY || 0;
        m.castShadow = true; m.receiveShadow = true; t.castShadow = true;
        scene.add(track(m)); scene.add(track(t));
        for (let i = -len / 2; i <= len / 2; i += 5) {
            const p = new THREE.Mesh(new THREE.BoxGeometry(0.4, wallH + 0.6, 0.5), topMat);
            p.position.set(cx + (rotY === 0 ? i : 0), (wallH + 0.6) / 2, cz + (rotY === 0 ? 0 : i));
            p.castShadow = true; scene.add(track(p));
        }
    }
    const topZ = hd, botZ = -(hd + 7);
    wallSeg(0, topZ, iw, 0);
    wallSeg(0, botZ, iw, 0);
    wallSeg(-hw, -5, id + 10, 0);
    wallSeg(hw, -5, id + 10, 0);
}

// ==================== 通用：大门 ====================
function buildEntrance(cfg) {
    const entZ = cfg.entrance.z;
    const name = cfg.entrance.name;
    // 底座
    const base = new THREE.Mesh(new THREE.BoxGeometry(14, 0.4, 2.5),
        new THREE.MeshStandardMaterial({ color: 0x5d4b3a, roughness: 0.3 }));
    base.position.set(0, 0.2, entZ); base.receiveShadow = true;
    scene.add(track(base));

    // 门柱
    for (let s = -1; s <= 1; s += 2) {
        const col = new THREE.Mesh(new THREE.BoxGeometry(1, 6, 1.5),
            new THREE.MeshStandardMaterial({ color: 0x8d7b6a, roughness: 0.2, metalness: 0.3 }));
        col.position.set(s * 5.5, 3.4, entZ); col.castShadow = true;
        scene.add(track(col));
        const light = new THREE.Mesh(new THREE.SphereGeometry(0.5, 8, 8),
            new THREE.MeshStandardMaterial({ color: 0xffd54f, emissive: 0xffa000, emissiveIntensity: 0.8, roughness: 0.1 }));
        light.position.set(s * 5.5, 6.8, entZ); light.castShadow = true;
        scene.add(track(light));
    }
    // 横梁
    const arch = new THREE.Mesh(new THREE.BoxGeometry(12, 0.5, 1.2),
        new THREE.MeshStandardMaterial({ color: 0x5d4b3a, roughness: 0.2, metalness: 0.5 }));
    arch.position.set(0, 6.2, entZ); arch.castShadow = true;
    scene.add(track(arch));

    // 名牌
    const nameDiv = document.createElement('div');
    nameDiv.innerHTML = name;
    nameDiv.style.cssText = 'color:#fff;font-size:18px;font-weight:bold;background:rgba(0,0,0,0.75);padding:6px 24px;border-radius:8px;letter-spacing:4px;white-space:nowrap;border:2px solid #ffd54f;text-shadow:0 0 10px rgba(255,213,79,0.6);';
    const nameLabel = new CSS2DObject(nameDiv);
    nameLabel.position.set(0, 7.5, entZ);
    scene.add(track(nameLabel));

    // 保安亭
    const booth = new THREE.Mesh(new THREE.BoxGeometry(2.5, 2.8, 2.5),
        new THREE.MeshStandardMaterial({ color: 0x4a7c9b, roughness: 0.3, metalness: 0.3 }));
    booth.position.set(-7.5, 1.4, entZ); booth.castShadow = true;
    scene.add(track(booth));
    const win = new THREE.Mesh(new THREE.PlaneGeometry(1.5, 1.2),
        new THREE.MeshStandardMaterial({ color: 0xb3e5fc, emissive: 0x4fc3f7, emissiveIntensity: 0.4 }));
    win.position.set(-7.5, 1.5, entZ + 1.3);
    scene.add(track(win));

    // 道闸
    for (let s = -1; s <= 1; s += 2) {
        const bar = new THREE.Mesh(new THREE.BoxGeometry(3, 0.1, 0.2),
            new THREE.MeshStandardMaterial({ color: 0xff5722, roughness: 0.2 }));
        bar.position.set(s * 3.5, 1.1, entZ + 1.8); bar.rotation.x = -0.2;
        scene.add(track(bar));
        const box = new THREE.Mesh(new THREE.BoxGeometry(0.4, 1, 0.4),
            new THREE.MeshStandardMaterial({ color: 0xcccccc, roughness: 0.2, metalness: 0.5 }));
        box.position.set(s * 3.5 + (s > 0 ? 1.6 : -1.6), 0.5, entZ + 1.8);
        scene.add(track(box));
    }
}

// ==================== 通用：树木 ====================
function createTree(x, z, scale = 1, type = 0) {
    const group = new THREE.Group();
    const trunkH = 1.8 * scale;
    const trunk = new THREE.Mesh(new THREE.CylinderGeometry(0.1 * scale, 0.12 * scale, trunkH, 6),
        new THREE.MeshStandardMaterial({ color: 0x5d4037, roughness: 0.8 }));
    trunk.position.y = trunkH / 2; trunk.castShadow = true;
    group.add(trunk);

    const greens = [0x2e7d32, 0x388e3c, 0x43a047, 0x1b5e20, 0x33691e, 0x4caf50];
    const crownC = greens[Math.floor(Math.random() * greens.length)];
    if (type === 0) {
        for (let i = 0; i < 3; i++) {
            const crown = new THREE.Mesh(new THREE.SphereGeometry((1.2 - i * 0.3) * scale, 8, 6),
                new THREE.MeshStandardMaterial({ color: crownC, roughness: 0.7 }));
            crown.position.y = trunkH + i * 0.7 * scale; crown.castShadow = true; crown.receiveShadow = true;
            group.add(crown);
        }
    } else {
        for (let i = 0; i < 3; i++) {
            const crown = new THREE.Mesh(new THREE.ConeGeometry((1 - i * 0.25) * scale, 1.4 * scale, 8),
                new THREE.MeshStandardMaterial({ color: 0x1b5e20, roughness: 0.6 }));
            crown.position.y = trunkH + i * 0.8 * scale; crown.castShadow = true;
            group.add(crown);
        }
    }
    group.position.set(x, 0, z);
    scene.add(trackGrp(group));
    return group;
}

// ==================== 通用：路灯 ====================
function buildStreetLights(cfg) {
    const iw = cfg.innerW, id = cfg.innerD;
    const hw = iw / 2, hd = id / 2;
    const poleMat = new THREE.MeshStandardMaterial({ color: 0x444444, roughness: 0.2, metalness: 0.7 });
    for (let x = -hw; x <= hw; x += 15) {
        for (let z = -hd - 5; z <= hd - 5; z += 22) {
            if (Math.abs(x) < 4 && z >= cfg.entrance.z - 4 && z <= cfg.entrance.z) continue;
            const pole = new THREE.Mesh(new THREE.CylinderGeometry(0.1, 0.15, 5, 8), poleMat);
            pole.position.set(x, 2.5, z); pole.castShadow = true; scene.add(track(pole));
            const arm = new THREE.Mesh(new THREE.CylinderGeometry(0.05, 0.05, 1.5, 6), poleMat);
            arm.rotation.z = Math.PI / 2; arm.position.set(x + 0.5, 4.8, z); scene.add(track(arm));
            const light = new THREE.Mesh(new THREE.SphereGeometry(0.25, 8, 8),
                new THREE.MeshStandardMaterial({ color: 0xfff9c4, emissive: 0xfff176, emissiveIntensity: 0.6 }));
            light.position.set(x + 1.2, 4.8, z); scene.add(track(light));
        }
    }
}

// ==================== 通用：监控 ====================
function buildMonitors(count, xRange, zRange, minY, maxY) {
    for (let i = 0; i < count; i++) {
        const x = rand(xRange[0], xRange[1]), z = rand(zRange[0], zRange[1]);
        const h = rand(minY || 4, maxY || 6);
        const sphere = new THREE.Mesh(new THREE.SphereGeometry(0.2, 8, 8),
            new THREE.MeshStandardMaterial({ color: 0xff1744, emissive: 0xff1744, emissiveIntensity: 0.8, roughness: 0.1 }));
        sphere.position.set(x, h, z); sphere.userData = { baseY: h, phase: Math.random() * Math.PI * 2, speed: 1.5 + Math.random() * 2 };
        sphere.name = 'monitor'; scene.add(track(sphere)); monitorPoints.push(sphere);
        const bracket = new THREE.Mesh(new THREE.CylinderGeometry(0.05, 0.05, 1, 6),
            new THREE.MeshStandardMaterial({ color: 0x666666, metalness: 0.5 }));
        bracket.position.set(x, h - 0.6, z); scene.add(track(bracket));
    }
}

// ==================== 通用：车辆 ====================
function createCar(x, z, angle = 0) {
    const group = new THREE.Group();
    const carColors = [0xe53935, 0x1e88e5, 0x43a047, 0xffffff, 0x333333, 0xfb8c00, 0x6d4c41, 0x78909c];
    const color = carColors[Math.floor(Math.random() * carColors.length)];
    const body = new THREE.Mesh(new THREE.BoxGeometry(1.8, 0.7, 3.8),
        new THREE.MeshStandardMaterial({ color, roughness: 0.25, metalness: 0.6 }));
    body.position.y = 0.6; body.castShadow = true; group.add(body);
    const cabin = new THREE.Mesh(new THREE.BoxGeometry(1.6, 0.45, 1.8),
        new THREE.MeshStandardMaterial({ color: 0x222233, roughness: 0.15, metalness: 0.3, transparent: true, opacity: 0.65 }));
    cabin.position.set(0, 1.15, -0.1); cabin.castShadow = true; group.add(cabin);
    const wheelGeo = new THREE.CylinderGeometry(0.32, 0.32, 0.25, 10);
    const wheelMat = new THREE.MeshStandardMaterial({ color: 0x111111, roughness: 0.7 });
    for (let s = -1; s <= 1; s += 2) for (let t = -1; t <= 1; t += 2) {
        const wheel = new THREE.Mesh(wheelGeo, wheelMat);
        wheel.rotation.z = Math.PI / 2; wheel.position.set(s * 0.95, 0.3, t * 1.2); wheel.castShadow = true;
        group.add(wheel);
    }
    group.position.set(x, 0.12, z); group.rotation.y = angle;
    scene.add(trackGrp(group));
    cars.push({ mesh: group, speed: 0.015 + Math.random() * 0.04, progress: Math.random(), direction: angle });
}

function buildCars(nRoad, nPark, xRange, zRange) {
    for (let i = 0; i < nRoad; i++) createCar(rand(xRange[0], xRange[1]), rand(zRange[0], zRange[1]), rand(0, Math.PI * 2));
    for (let i = 0; i < nPark; i++) createCar(rand(xRange[0] * 0.7, xRange[1] * 0.7), rand(zRange[0] * 0.7, zRange[1] * 0.7), 0);
}

// ==================== 粒子 ====================
function buildParticles() {
    const count = 200;
    const geo = new THREE.BufferGeometry();
    const posArr = new Float32Array(count * 3), colArr = new Float32Array(count * 3);
    for (let i = 0; i < count; i++) {
        const a = Math.random() * Math.PI * 2, r = 40 + Math.random() * 30;
        posArr[i * 3] = Math.cos(a) * r; posArr[i * 3 + 1] = 1 + Math.random() * 20; posArr[i * 3 + 2] = Math.sin(a) * r;
        const c = new THREE.Color(); c.setHSL(0.55 + Math.random() * 0.15, 0.7, 0.5 + Math.random() * 0.3);
        colArr[i * 3] = c.r; colArr[i * 3 + 1] = c.g; colArr[i * 3 + 2] = c.b;
    }
    geo.setAttribute('position', new THREE.BufferAttribute(posArr, 3));
    geo.setAttribute('color', new THREE.BufferAttribute(colArr, 3));
    const pts = new THREE.Points(geo, new THREE.PointsMaterial({
        size: 0.2, vertexColors: true, transparent: true, opacity: 0.5,
        blending: THREE.AdditiveBlending, depthWrite: false
    }));
    pts.name = 'particles'; scene.add(track(pts)); particles.push(pts);
}

// ==================== 场景1：翡翠名苑（经典高层住宅） ====================
function buildFeicui(cfg) {
    function createTower(x, z, w, d, floors, name) {
        const group = new THREE.Group();
        const totalH = floors * 3.2, floorH = 3.2;
        const body = new THREE.Mesh(new THREE.BoxGeometry(w, totalH, d),
            new THREE.MeshStandardMaterial({ color: cfg.buildingColor.body, roughness: 0.35, metalness: 0.1 }));
        body.position.y = totalH / 2; body.castShadow = true; body.receiveShadow = true; group.add(body);

        const bandMat = new THREE.MeshStandardMaterial({ color: cfg.buildingColor.band, roughness: 0.3, metalness: 0.15 });
        for (let f = 0; f < floors; f++) {
            const band = new THREE.Mesh(new THREE.BoxGeometry(w + 0.2, 0.15, d + 0.2), bandMat);
            band.position.y = (f + 1) * floorH; band.castShadow = true; group.add(band);
        }

        const winMat = new THREE.MeshStandardMaterial({ color: cfg.buildingColor.win, emissive: cfg.buildingColor.win, emissiveIntensity: 0.2, roughness: 0.1, metalness: 0.2 });
        const winW = (w - 1.5) / 4 - 0.3, winH = floorH * 0.6, winOffset = (w - 1.5) / 4;
        for (let f = 0; f < floors; f++) {
            const fy = f * floorH + floorH * 0.5;
            for (let wi = 0; wi < 4; wi++) {
                const wx = -(w / 2) + 1 + wi * winOffset;
                const wf = new THREE.Mesh(new THREE.PlaneGeometry(winW, winH), winMat);
                wf.position.set(wx, fy, d / 2 + 0.03); group.add(wf);
                const wb = new THREE.Mesh(new THREE.PlaneGeometry(winW, winH), winMat);
                wb.position.set(wx, fy, -d / 2 - 0.03); wb.rotation.y = Math.PI; group.add(wb);
            }
            for (let wi = 0; wi < Math.floor(d / 2.5); wi++) {
                const wz = -d / 2 + 1.5 + wi * (d - 3) / Math.max(1, Math.floor(d / 2.5) - 1);
                const swGeo = new THREE.PlaneGeometry(winH * 0.7, winH);
                const wr = new THREE.Mesh(swGeo, winMat); wr.position.set(w / 2 + 0.03, fy, wz); wr.rotation.y = Math.PI / 2; group.add(wr);
                const wl = new THREE.Mesh(swGeo, winMat); wl.position.set(-w / 2 - 0.03, fy, wz); wl.rotation.y = -Math.PI / 2; group.add(wl);
            }
        }
        // 屋顶
        const equip = new THREE.Mesh(new THREE.BoxGeometry(w * 0.5, 2.5, d * 0.5), new THREE.MeshStandardMaterial({ color: 0x555555, roughness: 0.3 }));
        equip.position.y = totalH + 1.25; equip.castShadow = true; group.add(equip);
        const cornice = new THREE.Mesh(new THREE.BoxGeometry(w + 0.6, 0.3, d + 0.6), new THREE.MeshStandardMaterial({ color: cfg.buildingColor.roof, roughness: 0.25, metalness: 0.3 }));
        cornice.position.y = totalH + 0.15; cornice.castShadow = true; group.add(cornice);
        const canopy = new THREE.Mesh(new THREE.BoxGeometry(w * 0.4, 0.2, 3.5), new THREE.MeshStandardMaterial({ color: 0x556677, roughness: 0.2, metalness: 0.6 }));
        canopy.position.set(0, 3, d / 2 + 1.5); canopy.castShadow = true; group.add(canopy);

        group.position.set(x, 0.02, z);
        scene.add(trackGrp(group));
        const lDiv = document.createElement('div');
        lDiv.textContent = name; lDiv.style.cssText = 'color:#fff;font-size:10px;font-weight:600;background:rgba(0,0,0,0.7);padding:3px 10px;border-radius:8px;white-space:nowrap;border:1px solid rgba(255,255,255,0.2);letter-spacing:1px;';
        const label = new CSS2DObject(lDiv); label.position.set(x, totalH + 4, z);
        scene.add(track(label));
        allBuildings.push({ mesh: group, floors, name, type: 'residential' });
    }

    // 14栋楼
    [-32, -16, 0, 16, 32].forEach((x, i) => createTower(x, 22, 9, 8, 28 + (i === 2 ? 4 : i === 1 || i === 3 ? 2 : 0), (i + 1) + '号楼'));
    [-32, -16, 0, 16, 32].forEach((x, i) => createTower(x, 4, 8, 9, 30 + (i === 2 ? 3 : i === 1 || i === 3 ? 2 : 0), (i + 6) + '号楼'));
    [-20, -4, 12, 28].forEach((x, i) => createTower(x, -14, 10, 8, 26 + i, (i + 11) + '号楼'));

    // 社区会所
    const center = new THREE.Mesh(new THREE.BoxGeometry(14, 8, 10), new THREE.MeshStandardMaterial({ color: 0xe8d5c0, roughness: 0.3, metalness: 0.1 }));
    center.position.set(0, 4.01, -26); center.castShadow = true; center.receiveShadow = true; scene.add(track(center));
    const roof = new THREE.Mesh(new THREE.ConeGeometry(9, 3, 4), new THREE.MeshStandardMaterial({ color: 0x5d4037, roughness: 0.4 }));
    roof.position.set(0, 9.5, -26); roof.rotation.y = Math.PI / 4; roof.castShadow = true; scene.add(track(roof));
    for (let i = 0; i < 3; i++) {
        const w = new THREE.Mesh(new THREE.PlaneGeometry(2, 2.5), new THREE.MeshStandardMaterial({ color: 0xffecb3, emissive: 0xffcc80, emissiveIntensity: 0.3 }));
        w.position.set(-3 + i * 3, 3, -20.8); scene.add(track(w));
    }
    const cDiv = document.createElement('div');
    cDiv.textContent = '社区活动中心'; cDiv.style.cssText = 'color:#ffe082;font-size:10px;font-weight:bold;background:rgba(0,0,0,0.7);padding:2px 10px;border-radius:6px;white-space:nowrap;';
    const cLabel = new CSS2DObject(cDiv); cLabel.position.set(0, 13, -26); scene.add(track(cLabel));

    // 中央花园
    const lawn = new THREE.Mesh(new THREE.PlaneGeometry(30, 14), new THREE.MeshStandardMaterial({ color: 0x2e5a2e, roughness: 0.8 }));
    lawn.rotation.x = -Math.PI / 2; lawn.position.set(0, 0.05, -26); lawn.receiveShadow = true; scene.add(track(lawn));
    // 小路
    [[0, -26, 2, 6], [-5, -26, 10, 2], [5, -26, 10, 2]].forEach(([px, pz, pw, pd]) => {
        const p = new THREE.Mesh(new THREE.PlaneGeometry(pw, pd), new THREE.MeshStandardMaterial({ color: 0x8d7b6a, roughness: 0.7 }));
        p.rotation.x = -Math.PI / 2; p.position.set(px, 0.07, pz); p.receiveShadow = true; scene.add(track(p));
    });
    // 池塘
    const pond = new THREE.Mesh(new THREE.CircleGeometry(3.5, 32), new THREE.MeshStandardMaterial({ color: 0x1565c0, roughness: 0.05, metalness: 0.9, transparent: true, opacity: 0.7 }));
    pond.rotation.x = -Math.PI / 2; pond.position.set(7, 0.15, -26); pond.receiveShadow = true; pond.name = 'pond'; scene.add(track(pond));
    for (let i = 0; i < 8; i++) {
        const a = (i / 8) * Math.PI * 2;
        const stone = new THREE.Mesh(new THREE.SphereGeometry(0.4 + Math.random() * 0.5, 5, 4), new THREE.MeshStandardMaterial({ color: 0x888888, roughness: 0.6 }));
        stone.position.set(7 + Math.cos(a) * 3.5, 0.2, -26 + Math.sin(a) * 3.5); stone.castShadow = true; scene.add(track(stone));
    }
    // 凉亭
    buildPavilion(0, -24);

    // 儿童游乐区
    const playArea = new THREE.Mesh(new THREE.PlaneGeometry(10, 8), new THREE.MeshStandardMaterial({ color: 0x4a8c5a, roughness: 0.9, transparent: true, opacity: 0.4 }));
    playArea.rotation.x = -Math.PI / 2; playArea.position.set(-25, 0.06, -26); playArea.receiveShadow = true; scene.add(track(playArea));
    const slide = new THREE.Mesh(new THREE.BoxGeometry(1.5, 3, 0.5), new THREE.MeshStandardMaterial({ color: 0xff5722, roughness: 0.3 }));
    slide.position.set(-23, 1.5, -23); slide.rotation.z = 0.3; slide.castShadow = true; scene.add(track(slide));
    // 秋千
    [1, -1].forEach(s => {
        const leg = new THREE.Mesh(new THREE.CylinderGeometry(0.08, 0.1, 3, 6), new THREE.MeshStandardMaterial({ color: 0x795548, roughness: 0.3 }));
        leg.position.set(-27 + s * 1.3, 1.4, -25); leg.castShadow = true; scene.add(track(leg));
        const rope = new THREE.Mesh(new THREE.CylinderGeometry(0.03, 0.03, 2.2, 4), new THREE.MeshStandardMaterial({ color: 0x444444 }));
        rope.position.set(-27 + s * 0.4, 1.5, -25); rope.castShadow = true; scene.add(track(rope));
    });
    const swTop = new THREE.Mesh(new THREE.BoxGeometry(3, 0.15, 0.15), new THREE.MeshStandardMaterial({ color: 0x795548, roughness: 0.4 }));
    swTop.position.set(-27, 2.8, -25); scene.add(track(swTop));
    const seat = new THREE.Mesh(new THREE.BoxGeometry(1.2, 0.1, 0.3), new THREE.MeshStandardMaterial({ color: 0x8d6e63 }));
    seat.position.set(-27, 0.5, -25); seat.castShadow = true; scene.add(track(seat));

    // 篮球场
    const court = new THREE.Mesh(new THREE.PlaneGeometry(10, 6), new THREE.MeshStandardMaterial({ color: 0x4a6a5a, roughness: 0.7 }));
    court.rotation.x = -Math.PI / 2; court.position.set(30, 0.08, -26); court.receiveShadow = true; scene.add(track(court));
    [1, -1].forEach(s => {
        const pole = new THREE.Mesh(new THREE.CylinderGeometry(0.1, 0.15, 4, 8), new THREE.MeshStandardMaterial({ color: 0xcccccc, metalness: 0.8 }));
        pole.position.set(30, 2, -26 + s * 3.5); pole.castShadow = true; scene.add(track(pole));
        const board = new THREE.Mesh(new THREE.BoxGeometry(1.8, 1.2, 0.1), new THREE.MeshStandardMaterial({ color: 0xffffff }));
        board.position.set(30, 3.5, -26 + s * 3.5); board.castShadow = true; scene.add(track(board));
        const hoop = new THREE.Mesh(new THREE.TorusGeometry(0.45, 0.05, 8, 16), new THREE.MeshStandardMaterial({ color: 0xff5722 }));
        hoop.position.set(30, 2.4, -26 + s * 3.5); hoop.rotation.x = Math.PI / 2; scene.add(track(hoop));
    });

    // 健身区
    const fitArea = new THREE.Mesh(new THREE.PlaneGeometry(8, 4), new THREE.MeshStandardMaterial({ color: 0x5a6a4a, roughness: 0.9, transparent: true, opacity: 0.4 }));
    fitArea.rotation.x = -Math.PI / 2; fitArea.position.set(30, 0.07, -18); fitArea.receiveShadow = true; scene.add(track(fitArea));
    for (let i = 0; i < 4; i++) {
        const eq = new THREE.Mesh(new THREE.BoxGeometry(0.3, 1.2, 1.5), new THREE.MeshStandardMaterial({ color: 0x4fc3f7, roughness: 0.2, metalness: 0.6 }));
        eq.position.set(28 + i * 1.5, 0.6, -18); eq.rotation.set(rand(-0.3, 0.3), rand(-0.3, 0.3), 0); eq.castShadow = true; scene.add(track(eq));
    }

    // 地下车库
    [-20, 0, 20].forEach(px => {
        const ramp = new THREE.Mesh(new THREE.BoxGeometry(4, 0.3, 8), new THREE.MeshStandardMaterial({ color: 0x3a3a3a, roughness: 0.6 }));
        ramp.position.set(px, 0.1, 0); ramp.rotation.x = -0.25; ramp.receiveShadow = true; scene.add(track(ramp));
        const top = new THREE.Mesh(new THREE.BoxGeometry(5, 0.3, 4), new THREE.MeshStandardMaterial({ color: 0x556677, roughness: 0.2, metalness: 0.5 }));
        top.position.set(px, 3, 4); top.castShadow = true; scene.add(track(top));
        [1, -1].forEach(s => {
            const sup = new THREE.Mesh(new THREE.CylinderGeometry(0.15, 0.15, 3, 6), new THREE.MeshStandardMaterial({ color: 0x999999, metalness: 0.6 }));
            sup.position.set(px + s * 2, 1.5, 4); sup.castShadow = true; scene.add(track(sup));
        });
        const pDiv = document.createElement('div');
        pDiv.textContent = 'P'; pDiv.style.cssText = 'color:#4fc3f7;font-size:14px;font-weight:bold;background:rgba(0,0,0,0.7);padding:2px 8px;border-radius:4px;';
        const pLabel = new CSS2DObject(pDiv); pLabel.position.set(px, 3.5, 4); scene.add(track(pLabel));
    });

    // 树木
    for (let x = -48; x <= 48; x += 12) { createTree(x, 36.5, 0.8, 1); createTree(x, -46.5, 0.8, 1); }
    for (let z = -36; z <= 26; z += 12) { createTree(-51.5, z, 0.8, 1); createTree(51.5, z, 0.8, 1); }
    for (let i = 0; i < 20; i++) createTree(rand(-12, 12), rand(-31, -21), rand(0.6, 1.2), Math.random() > 0.5 ? 0 : 1);
    for (let x = -35; x <= 35; x += 8) for (let z = -10; z <= 20; z += 15)
        if (Math.random() > 0.3) createTree(x + rand(-2, 2), z + rand(-2, 2), rand(0.5, 1), Math.random() > 0.5 ? 0 : 1);
    for (let i = 0; i < 10; i++) createTree(rand(-30, -18), rand(-28, -22), rand(0.7, 1.3), Math.random() > 0.5 ? 0 : 1);
}

// ==================== 场景2：云栖别墅 ====================
function buildYunqi(cfg) {
    // 48栋别墅 8行×6列
    const rows = 8, cols = 6, spacingX = 14, spacingZ = 12;
    const startX = -(cols - 1) * spacingX / 2, startZ = -(rows - 1) * spacingZ / 2 + 16;
    let villaNum = 1;

    function createVilla(x, z, num) {
        const g = new THREE.Group();
        const body = new THREE.Mesh(new THREE.BoxGeometry(5, 5, 4.5),
            new THREE.MeshStandardMaterial({ color: cfg.buildingColor.body, roughness: 0.3, metalness: 0.1 }));
        body.position.y = 2.5; body.castShadow = true; body.receiveShadow = true; g.add(body);
        const roofGeo = new THREE.ConeGeometry(3.8, 2.5, 4);
        const roof = new THREE.Mesh(roofGeo, new THREE.MeshStandardMaterial({ color: cfg.buildingColor.roof, roughness: 0.4, metalness: 0.15 }));
        roof.position.y = 6.25; roof.rotation.y = Math.PI / 4; roof.castShadow = true; g.add(roof);
        // 窗户
        const winMat = new THREE.MeshStandardMaterial({ color: cfg.buildingColor.win, emissive: cfg.buildingColor.win, emissiveIntensity: 0.3, roughness: 0.1 });
        [1, 2].forEach(level => {
            [1, -1].forEach(s => {
                const w = new THREE.Mesh(new THREE.PlaneGeometry(1.2, 1.5), winMat);
                w.position.set(s * 1.6, level * 2, 2.3); g.add(w);
            });
        });
        // 门
        const door = new THREE.Mesh(new THREE.PlaneGeometry(1.5, 2.5),
            new THREE.MeshStandardMaterial({ color: 0x5d4037, roughness: 0.5 }));
        door.position.set(0, 1.25, 2.3); g.add(door);
        // 车库
        const garage = new THREE.Mesh(new THREE.BoxGeometry(3, 2, 4),
            new THREE.MeshStandardMaterial({ color: cfg.buildingColor.body, roughness: 0.4 }));
        garage.position.set(3.5, 1, -1); garage.castShadow = true; g.add(garage);
        // 私家花园
        const garden = new THREE.Mesh(new THREE.PlaneGeometry(6, 5),
            new THREE.MeshStandardMaterial({ color: 0x3a6b3a, roughness: 0.9 }));
        garden.rotation.x = -Math.PI / 2; garden.position.set(0, 0.06, 5); garden.receiveShadow = true; g.add(garden);

        g.position.set(x, 0.02, z);
        scene.add(trackGrp(g));
        const lDiv = document.createElement('div');
        lDiv.textContent = num + '号'; lDiv.style.cssText = 'color:#ffe082;font-size:9px;font-weight:600;background:rgba(0,0,0,0.65);padding:2px 8px;border-radius:4px;white-space:nowrap;';
        const label = new CSS2DObject(lDiv); label.position.set(x, 9, z); scene.add(track(label));
        allBuildings.push({ mesh: g, name: num + '号', type: 'villa' });
    }

    for (let r = 0; r < rows; r++)
        for (let c = 0; c < cols; c++)
            createVilla(startX + c * spacingX, startZ + r * spacingZ, villaNum++);

    // 中央会所 + 泳池
    const clubBody = new THREE.Mesh(new THREE.BoxGeometry(16, 5, 8),
        new THREE.MeshStandardMaterial({ color: 0xf5f0e8, roughness: 0.25, metalness: 0.1 }));
    clubBody.position.set(0, 2.5, -6); clubBody.castShadow = true; clubBody.receiveShadow = true; scene.add(track(clubBody));
    const clubRoof = new THREE.Mesh(new THREE.ConeGeometry(10, 2, 4),
        new THREE.MeshStandardMaterial({ color: cfg.buildingColor.roof, roughness: 0.35 }));
    clubRoof.position.set(0, 6, -6); clubRoof.rotation.y = Math.PI / 4; clubRoof.castShadow = true; scene.add(track(clubRoof));

    // 泳池
    const pool = new THREE.Mesh(new THREE.BoxGeometry(8, 0.2, 5),
        new THREE.MeshStandardMaterial({ color: 0x29b6f6, roughness: 0.05, metalness: 0.8, transparent: true, opacity: 0.8 }));
    pool.position.set(0, 0.3, 3); pool.name = 'pool'; scene.add(track(pool));
    const poolEdge = new THREE.Mesh(new THREE.BoxGeometry(8.6, 0.5, 5.6),
        new THREE.MeshStandardMaterial({ color: 0xeeeeee, roughness: 0.3 }));
    poolEdge.position.set(0, 0.4, 3); poolEdge.name = 'poolEdge'; scene.add(track(poolEdge));

    // 喷泉
    const fountainBase = new THREE.Mesh(new THREE.CylinderGeometry(1.5, 2, 0.6, 16),
        new THREE.MeshStandardMaterial({ color: 0xcccccc, roughness: 0.3 }));
    fountainBase.position.set(0, 0.3, -16); scene.add(track(fountainBase));
    const fountainTop = new THREE.Mesh(new THREE.CylinderGeometry(0.8, 1.2, 1.5, 8),
        new THREE.MeshStandardMaterial({ color: 0xdddddd, roughness: 0.2, metalness: 0.7 }));
    fountainTop.position.set(0, 1.2, -16); scene.add(track(fountainTop));

    // 树木
    for (let r = 0; r < rows; r++)
        for (let c = 0; c <= cols; c++)
            createTree(startX - 7 + c * spacingX, startZ + r * spacingZ, rand(0.7, 1.2), 1);
    for (let c = 0; c < cols; c++)
        for (let r = 0; r <= rows; r++)
            createTree(startX + c * spacingX, startZ - 6 + r * spacingZ, rand(0.6, 1), 0);

    buildMonitors(24, [-50, 50], [-45, 35], 3, 5.5);
    buildCars(8, 6, [-50, 50], [-45, 35]);
}

// ==================== 场景3：中粮壹号（现代玻璃塔楼） ====================
function buildZhongliang(cfg) {
    function createGlassTower(x, z, w, d, floors, name) {
        const g = new THREE.Group();
        const totalH = floors * 3.4;
        const body = new THREE.Mesh(new THREE.BoxGeometry(w, totalH, d),
            new THREE.MeshStandardMaterial({ color: cfg.buildingColor.body, roughness: 0.1, metalness: 0.7, transparent: true, opacity: 0.75 }));
        body.position.y = totalH / 2; body.castShadow = true; g.add(body);

        // 玻璃幕墙网格
        const gridMat = new THREE.MeshStandardMaterial({ color: cfg.buildingColor.band, roughness: 0.1, metalness: 0.8 });
        for (let f = 0; f < floors; f++) {
            const band = new THREE.Mesh(new THREE.BoxGeometry(w + 0.1, 0.2, d + 0.1), gridMat);
            band.position.y = (f + 1) * 3.4; g.add(band);
        }
        // 竖框
        for (let i = 0; i < 5; i++) {
            const vert = new THREE.Mesh(new THREE.BoxGeometry(0.15, totalH, d + 0.05), gridMat);
            vert.position.set(-w / 2 + (i + 1) * (w / 5), totalH / 2, 0); g.add(vert);
        }
        // 发光窗户
        const winMat = new THREE.MeshStandardMaterial({ color: cfg.buildingColor.win, emissive: cfg.buildingColor.win, emissiveIntensity: 0.5, roughness: 0.05, metalness: 0.4 });
        for (let f = 0; f < floors; f += 2)
            for (let wi = 0; wi < 3; wi++) {
                const wf = new THREE.Mesh(new THREE.PlaneGeometry(1.8, 2.2), winMat);
                wf.position.set(-w / 2 + 2 + wi * (w / 3), f * 3.4 + 1.5, d / 2 + 0.05); g.add(wf);
                const wb = new THREE.Mesh(new THREE.PlaneGeometry(1.8, 2.2), winMat);
                wb.position.set(-w / 2 + 2 + wi * (w / 3), f * 3.4 + 1.5, -d / 2 - 0.05); wb.rotation.y = Math.PI; g.add(wb);
            }

        // 楼顶天线
        const antenna = new THREE.Mesh(new THREE.CylinderGeometry(0.3, 0.4, 6, 8),
            new THREE.MeshStandardMaterial({ color: 0xcccccc, roughness: 0.1, metalness: 0.9 }));
        antenna.position.y = totalH + 3; g.add(antenna);
        const tip = new THREE.Mesh(new THREE.SphereGeometry(0.4, 8, 8),
            new THREE.MeshStandardMaterial({ color: 0xff1744, emissive: 0xff0000, emissiveIntensity: 1, roughness: 0.1 }));
        tip.position.y = totalH + 6.5; g.add(tip);

        g.position.set(x, 0.02, z);
        scene.add(trackGrp(g));
        const lDiv = document.createElement('div');
        lDiv.textContent = name; lDiv.style.cssText = 'color:#88ccff;font-size:11px;font-weight:bold;background:rgba(0,0,0,0.7);padding:3px 12px;border-radius:8px;white-space:nowrap;border:1px solid rgba(136,204,255,0.3);';
        const label = new CSS2DObject(lDiv); label.position.set(x, totalH + 8, z); scene.add(track(label));
        allBuildings.push({ mesh: g, floors, name, type: 'tower' });
    }

    // 6栋超高层玻璃塔楼
    createGlassTower(-25, 20, 10, 8, 42, 'A座');
    createGlassTower(-10, 20, 10, 8, 48, 'B座');
    createGlassTower(5, 20, 10, 8, 55, 'C座');
    createGlassTower(20, 20, 10, 8, 50, 'D座');
    createGlassTower(-18, -10, 11, 9, 45, 'E座');
    createGlassTower(10, -10, 11, 9, 52, 'F座');

    // 中央智能广场
    const plaza = new THREE.Mesh(new THREE.PlaneGeometry(35, 20),
        new THREE.MeshStandardMaterial({ color: 0x555560, roughness: 0.3, metalness: 0.5 }));
    plaza.rotation.x = -Math.PI / 2; plaza.position.set(0, 0.08, 4); plaza.receiveShadow = true; scene.add(track(plaza));

    // 几何喷泉水池
    const fpBase = new THREE.Mesh(new THREE.CylinderGeometry(5, 5.5, 0.4, 32),
        new THREE.MeshStandardMaterial({ color: 0x888899, roughness: 0.2, metalness: 0.8 }));
    fpBase.position.set(0, 0.3, 4); scene.add(track(fpBase));
    const fpInner = new THREE.Mesh(new THREE.CylinderGeometry(4.5, 4.8, 0.2, 32),
        new THREE.MeshStandardMaterial({ color: 0x4488bb, roughness: 0.05, metalness: 0.9, transparent: true, opacity: 0.7 }));
    fpInner.position.set(0, 0.5, 4); fpInner.name = 'fountain'; scene.add(track(fpInner));
    // 喷泉中心柱
    const fpCol = new THREE.Mesh(new THREE.CylinderGeometry(0.4, 0.6, 4, 8),
        new THREE.MeshStandardMaterial({ color: 0xcccccc, roughness: 0.1, metalness: 0.9 }));
    fpCol.position.set(0, 2.2, 4); fpCol.name = 'fpCol'; scene.add(track(fpCol));

    // 商业裙楼
    for (let s = -1; s <= 1; s += 2) {
        const podium = new THREE.Mesh(new THREE.BoxGeometry(28, 6, 5),
            new THREE.MeshStandardMaterial({ color: 0x5a7a8a, roughness: 0.2, metalness: 0.6 }));
        podium.position.set(s * 22, 3, -20); podium.castShadow = true; podium.receiveShadow = true; scene.add(track(podium));
        const signDiv = document.createElement('div');
        signDiv.textContent = s > 0 ? '商业配套' : '社区服务'; signDiv.style.cssText = 'color:#ffb74d;font-size:10px;font-weight:bold;background:rgba(0,0,0,0.7);padding:2px 10px;border-radius:4px;white-space:nowrap;';
        const sLabel = new CSS2DObject(signDiv); sLabel.position.set(s * 22, 7, -20); scene.add(track(sLabel));
    }

    // 地下车库
    [-15, 0, 15].forEach(px => {
        const ramp = new THREE.Mesh(new THREE.BoxGeometry(5, 0.3, 9),
            new THREE.MeshStandardMaterial({ color: 0x3a3a3a, roughness: 0.5 }));
        ramp.position.set(px, 0.1, -2); ramp.rotation.x = -0.3; ramp.receiveShadow = true; scene.add(track(ramp));
        const roof = new THREE.Mesh(new THREE.BoxGeometry(6, 0.3, 5),
            new THREE.MeshStandardMaterial({ color: 0x667788, roughness: 0.2, metalness: 0.6 }));
        roof.position.set(px, 3.5, 1); roof.castShadow = true; scene.add(track(roof));
    });

    // 树木（简约现代）
    for (let x = -40; x <= 40; x += 10) createTree(x, 30, 0.6, 1);
    for (let x = -40; x <= 40; x += 10) createTree(x, -25, 0.6, 1);
    for (let i = 0; i < 12; i++) createTree(rand(-40, 40), rand(8, 18), rand(0.5, 0.9), 1);

    buildMonitors(30, [-43, 43], [-30, 32], 4, 7);
    buildCars(12, 10, [-43, 43], [-30, 32]);
}

// ==================== 场景4：山水居（新中式园林） ====================
function buildShanshui(cfg) {
    function createGardenHouse(x, z, w, d, floors, name) {
        const g = new THREE.Group();
        const totalH = floors * 3;
        const body = new THREE.Mesh(new THREE.BoxGeometry(w, totalH, d),
            new THREE.MeshStandardMaterial({ color: cfg.buildingColor.body, roughness: 0.4, metalness: 0.05 }));
        body.position.y = totalH / 2; body.castShadow = true; body.receiveShadow = true; g.add(body);

        const bandMat = new THREE.MeshStandardMaterial({ color: cfg.buildingColor.band, roughness: 0.35 });
        for (let f = 0; f < floors; f++) {
            const band = new THREE.Mesh(new THREE.BoxGeometry(w + 0.15, 0.12, d + 0.15), bandMat);
            band.position.y = (f + 1) * 3; band.castShadow = true; g.add(band);
        }
        const winMat = new THREE.MeshStandardMaterial({ color: cfg.buildingColor.win, emissive: cfg.buildingColor.win, emissiveIntensity: 0.2, roughness: 0.1 });
        for (let f = 0; f < floors; f++)
            for (let wi = 0; wi < 3; wi++) {
                const wf = new THREE.Mesh(new THREE.PlaneGeometry(1.4, 1.8), winMat);
                wf.position.set(-w / 2 + 1.5 + wi * (w / 3), f * 3 + 1.5, d / 2 + 0.03); g.add(wf);
                const wb = new THREE.Mesh(new THREE.PlaneGeometry(1.4, 1.8), winMat);
                wb.position.set(-w / 2 + 1.5 + wi * (w / 3), f * 3 + 1.5, -d / 2 - 0.03); wb.rotation.y = Math.PI; g.add(wb);
            }
        // 中式坡屋顶
        const roofGeo = new THREE.ConeGeometry(Math.max(w, d) * 0.8, 2.5, 4);
        const roof = new THREE.Mesh(roofGeo, new THREE.MeshStandardMaterial({ color: cfg.buildingColor.roof, roughness: 0.5 }));
        roof.position.y = totalH + 1.25; roof.rotation.y = Math.PI / 4; roof.castShadow = true; g.add(roof);

        g.position.set(x, 0.02, z);
        scene.add(trackGrp(g));
        const lDiv = document.createElement('div');
        lDiv.textContent = name; lDiv.style.cssText = 'color:#c8e6c9;font-size:10px;font-weight:600;background:rgba(0,0,0,0.65);padding:2px 8px;border-radius:6px;white-space:nowrap;';
        const label = new CSS2DObject(lDiv); label.position.set(x, totalH + 4, z); scene.add(track(label));
        allBuildings.push({ mesh: g, floors, name, type: 'garden' });
    }

    // 12栋花园洋房分3层台地
    const levels = [
        { y: 0, z: 20, builds: [[-25, 8, 8, 6, '海棠苑'], [-8, 8, 8, 6, '牡丹苑'], [8, 8, 8, 6, '月季苑'], [25, 8, 8, 6, '荷花苑']] },
        { y: 3, z: 0, builds: [[-28, 8, 9, 7, '翠竹苑'], [-12, 8, 9, 7, '幽兰苑'], [12, 8, 9, 7, '寒梅苑'], [28, 8, 9, 7, '劲松苑']] },
        { y: 6, z: -20, builds: [[-20, 8, 8, 6, '桃源居'], [0, 8, 8, 6, '云水居'], [20, 8, 8, 6, '映月居']] }
    ];

    levels.forEach(level => {
        level.builds.forEach(([bx, bw, bd, bf, name]) => {
            createGardenHouse(bx, level.z, bw, bd, bf, name);
        });
        // 台地草坪
        const terrace = new THREE.Mesh(new THREE.PlaneGeometry(70, 16),
            new THREE.MeshStandardMaterial({ color: 0x3d5a30, roughness: 0.9 }));
        terrace.rotation.x = -Math.PI / 2; terrace.position.set(0, level.y + 0.04, level.z);
        terrace.receiveShadow = true; scene.add(track(terrace));
        // 台地护坡
        if (level.y > 0) {
            const slope = new THREE.Mesh(new THREE.BoxGeometry(70, level.y + 0.1, 0.3),
                new THREE.MeshStandardMaterial({ color: 0x5d4b3a, roughness: 0.6 }));
            slope.position.set(0, level.y / 2, level.z + 8.15); slope.castShadow = true; scene.add(track(slope));
        }
    });

    // 叠水瀑布系统
    for (let i = 0; i < 3; i++) {
        const wpZ = 2, wpX = -35 + i * 35;
        for (let lvl = 0; lvl <= 2; lvl++) {
            const water = new THREE.Mesh(new THREE.PlaneGeometry(3, 2),
                new THREE.MeshStandardMaterial({ color: 0x42a5f5, roughness: 0.05, metalness: 0.8, transparent: true, opacity: 0.6 }));
            const lz = [20, 0, -20][lvl];
            water.position.set(wpX, [0, 3, 6][lvl] + 0.2, lz + 8); water.name = 'waterfall';
            scene.add(track(water));
        }
    }

    // 中央池塘 + 连廊
    const pondGeo = new THREE.Mesh(new THREE.CircleGeometry(6, 32),
        new THREE.MeshStandardMaterial({ color: 0x1565c0, roughness: 0.05, metalness: 0.8, transparent: true, opacity: 0.65 }));
    pondGeo.rotation.x = -Math.PI / 2; pondGeo.position.set(0, 0.15, -6); pondGeo.name = 'pond'; scene.add(track(pondGeo));
    // 石桥
    const bridge = new THREE.Mesh(new THREE.BoxGeometry(2, 0.2, 14),
        new THREE.MeshStandardMaterial({ color: 0x8d6e63, roughness: 0.4 }));
    bridge.position.set(0, 0.6, -6); bridge.castShadow = true; scene.add(track(bridge));
    const bridgeArch = new THREE.Mesh(new THREE.TorusGeometry(0.6, 0.1, 8, 8, Math.PI),
        new THREE.MeshStandardMaterial({ color: 0x8d6e63, roughness: 0.3 }));
    bridgeArch.position.set(0, 0.3, -6); bridgeArch.rotation.set(0, 0, 0); scene.add(track(bridgeArch));

    // 中式凉亭×2
    buildPavilion(-12, -8);
    buildPavilion(12, -8);

    // 石头园
    for (let i = 0; i < 15; i++) {
        const stone = new THREE.Mesh(new THREE.SphereGeometry(0.3 + Math.random() * 0.6, 6, 4),
            new THREE.MeshStandardMaterial({ color: 0x888888, roughness: 0.5 }));
        stone.position.set(rand(-30, 30), 0.2 + Math.random() * 0.3, rand(-12, -2));
        stone.castShadow = true; scene.add(track(stone));
    }

    // 竹林
    for (let i = 0; i < 30; i++) {
        const bamboo = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.08, rand(3, 6), 6),
            new THREE.MeshStandardMaterial({ color: 0x388e3c, roughness: 0.6 }));
        bamboo.position.set(rand(-38, 38), rand(1.5, 3), rand(-30, -14) + (Math.random() > 0.5 ? -6 : 6));
        bamboo.castShadow = true; scene.add(track(bamboo));
    }

    // 树木
    for (let i = 0; i < 40; i++) {
        createTree(rand(-45, 45), rand(-35, 32), rand(0.5, 1.3), Math.random() > 0.4 ? 0 : 1);
    }

    buildMonitors(16, [-43, 43], [-38, 32], 3, 5);
    buildCars(6, 4, [-43, 43], [-38, 32]);
}

// ==================== 场景5：怡丰城（城市综合体·真实布局） ====================
function buildYifeng(cfg) {
    // 根据真实平面图：9栋楼(3-11号)围绕中央广场，西门/东门双入口
    function createYfTower(x, z, w, d, floors, name) {
        const g = new THREE.Group();
        const totalH = floors * 3.3;
        const body = new THREE.Mesh(new THREE.BoxGeometry(w, totalH, d),
            new THREE.MeshStandardMaterial({ color: cfg.buildingColor.body, roughness: 0.35, metalness: 0.15 }));
        body.position.y = totalH / 2; body.castShadow = true; body.receiveShadow = true; g.add(body);
        // 楼层分隔线
        const bandMat = new THREE.MeshStandardMaterial({ color: cfg.buildingColor.band, roughness: 0.4 });
        for (let f = 0; f < floors; f++) {
            const band = new THREE.Mesh(new THREE.BoxGeometry(w + 0.2, 0.12, d + 0.2), bandMat);
            band.position.y = (f + 1) * 3.3; band.castShadow = true; g.add(band);
        }
        // 窗户
        const winMat = new THREE.MeshStandardMaterial({
            color: cfg.buildingColor.win, emissive: cfg.buildingColor.win,
            emissiveIntensity: 0.25, roughness: 0.08, metalness: 0.2 });
        const winW = (w - 1.8) / 4 - 0.25;
        for (let f = 0; f < floors; f++) {
            const fy = f * 3.3 + 1.6;
            for (let wi = 0; wi < 4; wi++) {
                const wx = -(w / 2) + 1.1 + wi * ((w - 2.2) / 4 + 0.25);
                const wf = new THREE.Mesh(new THREE.PlaneGeometry(winW, 2), winMat);
                wf.position.set(wx, fy, d / 2 + 0.03); g.add(wf);
                const wb = new THREE.Mesh(new THREE.PlaneGeometry(winW, 2), winMat);
                wb.position.set(wx, fy, -d / 2 - 0.03); wb.rotation.y = Math.PI; g.add(wb);
            }
        }
        // 屋顶设备
        const equip = new THREE.Mesh(new THREE.BoxGeometry(w * 0.45, 2, d * 0.45),
            new THREE.MeshStandardMaterial({ color: 0x667788, roughness: 0.25 }));
        equip.position.y = totalH + 1; equip.castShadow = true; g.add(equip);

        g.position.set(x, 0.02, z);
        scene.add(trackGrp(g));
        const lDiv = document.createElement('div');
        lDiv.textContent = name; lDiv.style.cssText =
            'color:#fff;font-size:10px;font-weight:bold;background:rgba(74,144,194,0.85);padding:3px 12px;border-radius:8px;white-space:nowrap;border:1px solid rgba(255,255,255,0.25);letter-spacing:1px;';
        const label = new CSS2DObject(lDiv); label.position.set(x, totalH + 4, z);
        scene.add(track(label));
        allBuildings.push({ mesh: g, floors, name, type: 'yftower' });
    }

    // === 真实布局：9栋楼围绕中央广场 ===
    // 北排（上）
    createYfTower(-32, -22, 14, 10, 9,  '11号楼');
    createYfTower(-5,  -20, 16, 12, 10, '4号楼');
    createYfTower(26,  -18, 13, 11, 11, '3号楼');
    // 中排
    createYfTower(-36,   2, 13, 10, 8,  '10号楼');
    createYfTower(28,    5, 15, 12, 12, '5号楼');
    createYfTower(44,    0, 11, 10, 9,  '6号楼');
    // 南排（下）
    createYfTower(-34,  28, 12, 10, 8,  '8号楼');
    createYfTower(-5,   30, 14, 11, 10, '9号楼');
    createYfTower(28,   32, 13, 10, 9,  '7号楼');

    // 中央广场（怡丰城中心区域）
    const plaza = new THREE.Mesh(new THREE.PlaneGeometry(38, 24),
        new THREE.MeshStandardMaterial({ color: 0x3a4855, roughness: 0.4, metalness: 0.3 }));
    plaza.rotation.x = -Math.PI / 2; plaza.position.set(-5, 0.06, 5); plaza.receiveShadow = true;
    scene.add(track(plaza));
    // 广场地砖装饰
    for (let i = -3; i <= 3; i++)
        for (let j = -2; j <= 2; j++) {
            if ((i + j) % 2 === 0) {
                const tile = new THREE.Mesh(new THREE.PlaneGeometry(4.5, 4),
                    new THREE.MeshStandardMaterial({ color: 0x4a5865, roughness: 0.35 }));
                tile.rotation.x = -Math.PI / 2; tile.position.set(i * 5, 0.07, 5 + j * 4.5);
                tile.receiveShadow = true; scene.add(track(tile));
            }
        }
    // 怡丰城名牌
    const nameDiv = document.createElement('div');
    nameDiv.innerHTML = '<span style="font-size:18px;font-weight:bold;color:#fff;text-shadow:0 0 12px rgba(74,144,194,0.8)">怡丰城</span>';
    nameDiv.style.cssText = 'background:linear-gradient(135deg,rgba(74,144,194,0.92),rgba(40,80,120,0.88));padding:10px 30px;border-radius:12px;border:2px solid rgba(100,180,240,0.4);';
    const nameLabel = new CSS2DObject(nameDiv);
    nameLabel.position.set(-5, 3.5, 5); scene.add(track(nameLabel));

    // 中央景观喷泉
    const ftBase = new THREE.Mesh(new THREE.CylinderGeometry(4, 4.5, 0.5, 24),
        new THREE.MeshStandardMaterial({ color: 0x999aaa, roughness: 0.25, metalness: 0.6 }));
    ftBase.position.set(-5, 0.27, 5); scene.add(track(ftBase));
    const ftWater = new THREE.Mesh(new THREE.CylinderGeometry(3.6, 3.8, 0.3, 24),
        new THREE.MeshStandardMaterial({ color: 0x29b6f6, roughness: 0.03, metalness: 0.9,
            transparent: true, opacity: 0.75, emissive: 0x29b6f6, emissiveIntensity: 0.15 }));
    ftWater.position.set(-5, 0.42, 5); ftWater.name = 'fountain'; scene.add(track(ftWater));
    // 喷泉中心雕塑
    const sculp = new THREE.Mesh(new THREE.CylinderGeometry(0.5, 0.8, 3.5, 8),
        new THREE.MeshStandardMaterial({ color: 0xcccccc, roughness: 0.1, metalness: 0.85 }));
    sculp.position.set(-5, 2.05, 5); scene.add(track(sculp));
    const topSphere = new THREE.Mesh(new THREE.SphereGeometry(0.8, 12, 10),
        new THREE.MeshStandardMaterial({ color: 0xff9800, emissive: 0xff6600, emissiveIntensity: 0.4 }));
    topSphere.position.set(-5, 4, 5); scene.add(track(topSphere));

    // 内部道路网格
    const roadMat = new THREE.MeshStandardMaterial({ color: 0x222230, roughness: 0.65 });
    // 南北主路
    [-20, 10].forEach(rx => {
        const r = new THREE.Mesh(new THREE.PlaneGeometry(6, 75), roadMat);
        r.rotation.x = -Math.PI / 2; r.position.set(rx, 0.06, 5); r.receiveShadow = true; scene.add(track(r));
    });
    // 东西主路
    [ -17, 17 ].forEach(rz => {
        const r = new THREE.Mesh(new THREE.PlaneGeometry(85, 5), roadMat);
        r.rotation.x = -Math.PI / 2; r.position.set(-5, 0.06, rz); r.receiveShadow = true; scene.add(track(r));
    });

    // 西门入口（S101省道侧）
    const westGate = () => {
        const base = new THREE.Mesh(new THREE.BoxGeometry(12, 0.4, 2),
            new THREE.MeshStandardMaterial({ color: 0x556b7c, roughness: 0.25 }));
        base.position.set(-56, 0.2, 5); base.receiveShadow = true; scene.add(track(base));
        for (let s = -1; s <= 1; s += 2) {
            const col = new THREE.Mesh(new THREE.BoxGeometry(1.2, 5.5, 1.2),
                new THREE.MeshStandardMaterial({ color: 0x7799aa, roughness: 0.2, metalness: 0.3 }));
            col.position.set(-56 + s * 4.5, 2.95, 5); col.castShadow = true; scene.add(track(col));
        }
        const arch = new THREE.Mesh(new THREE.BoxGeometry(10, 0.5, 1),
            new THREE.MeshStandardMaterial({ color: 0x556b7c, roughness: 0.2, metalness: 0.5 }));
        arch.position.set(-56, 5.7, 5); arch.castShadow = true; scene.add(track(arch));
        const wName = document.createElement('div');
        wName.textContent = '西门'; wName.style.cssText =
            'color:#fff;font-size:13px;font-weight:bold;background:rgba(74,144,194,0.8);padding:4px 14px;border-radius:6px;';
        const wl = new CSS2DObject(wName); wl.position.set(-56, 7, 5); scene.add(track(wl));
    };
    westGate();

    // 东门入口（香乐路侧）
    const eastGate = () => {
        const base = new THREE.Mesh(new THREE.BoxGeometry(12, 0.4, 2),
            new THREE.MeshStandardMaterial({ color: 0x556b7c, roughness: 0.25 }));
        base.position.set(46, 0.2, 5); base.receiveShadow = true; scene.add(track(base));
        for (let s = -1; s <= 1; s += 2) {
            const col = new THREE.Mesh(new THREE.BoxGeometry(1.2, 5.5, 1.2),
                new THREE.MeshStandardMaterial({ color: 0x7799aa, roughness: 0.2, metalness: 0.3 }));
            col.position.set(46 + s * 4.5, 2.95, 5); col.castShadow = true; scene.add(track(col));
        }
        const arch = new THREE.Mesh(new THREE.BoxGeometry(10, 0.5, 1),
            new THREE.MeshStandardMaterial({ color: 0x556b7c, roughness: 0.2, metalness: 0.5 }));
        arch.position.set(46, 5.7, 5); arch.castShadow = true; scene.add(track(arch));
        const eName = document.createElement('div');
        eName.textContent = '东门'; eName.style.cssText =
            'color:#fff;font-size:13px;font-weight:bold;background:rgba(74,144,194,0.8);padding:4px 14px;border-radius:6px;';
        const el = new CSS2DObject(eName); el.position.set(46, 7, 5); scene.add(track(el));
    };
    eastGate();

    // 周边商铺配套（沿外围道路）
    const shopPositions = [
        [-50, -35], [-50, -18], [-50, 15], [-50, 30],     // 西侧沿街
        [40, -35], [52, -18], [54, 15], [54, 30],         // 东侧沿街
        [-20, -38], [0, -38], [25, -38]                    // 北侧沿街
    ];
    shopPositions.forEach((pos, i) => {
        const shop = new THREE.Mesh(new THREE.BoxGeometry(5, 3.5, 3),
            new THREE.MeshStandardMaterial({ color: 0xff9800, roughness: 0.3, metalness: 0.1 }));
        shop.position.set(pos[0], 1.77, pos[1]); shop.castShadow = true; shop.receiveShadow = true;
        scene.add(track(shop));
        // 招牌
        const sign = new THREE.Mesh(new THREE.PlaneGeometry(3, 0.8),
            new THREE.MeshStandardMaterial({ color: 0xffffff, emissive: 0xffcc00, emissiveIntensity: 0.3 }));
        sign.position.set(pos[0], 3.7, pos[1] + 1.53); scene.add(track(sign));
    });

    // 停车区标识
    const parkSpots = [[-48, -5, 'P'], [48, -8, 'P'], [-5, 42, 'P'], [35, 42, 'P']];
    parkSpots.forEach(([px, pz, txt]) => {
        const pArea = new THREE.Mesh(new THREE.PlaneGeometry(10, 6),
            new THREE.MeshStandardMaterial({ color: 0x333340, roughness: 0.8, transparent: true, opacity: 0.4 }));
        pArea.rotation.x = -Math.PI / 2; pArea.position.set(px, 0.05, pz); pArea.receiveShadow = true;
        scene.add(track(pArea));
        const pDiv = document.createElement('div');
        pDiv.innerHTML = '<span style="font-size:16px;font-weight:bold;color:#4fc3f7">' + txt + '</span>';
        pDiv.style.cssText = 'background:rgba(0,0,0,0.7);padding:3px 10px;border-radius:6px;';
        const pl = new CSS2DObject(pDiv); pl.position.set(px, 1.5, pz); scene.add(track(pl));
    });

    // 绿化树木
    const treePos = [
        // 西侧绿化带
        [-54,-40],[-54,-25],[-54,0],[-54,20],[-54,40],
        // 东侧
        [56,-30],[56,-5],[56,20],[56,40],
        // 北侧
        [-35,-43],[0,-43],[35,-43],
        // 南侧
        [-40,45],[10,45],[40,45],
        // 楼间点缀
        [-22,-8],[-22,18],[12,-10],[12,20],[-18,5],
        // 广场周边
        [-20,5],[10,5],[-5,-8],[-5,18]
    ];
    treePos.forEach(([tx, tz]) => {
        createTree(tx, tz, rand(0.6, 1.1), Math.random() > 0.5 ? 0 : 1);
    });

    // 监控点
    buildMonitors(24, [-55, 57], [-45, 48], 4, 7);
    // 车辆
    buildCars(15, 8, [-50, 50], [-42, 44]);

    // 外围道路标注
    const labels = [
        { t: 'S101省道', x: -62, z: 5 },
        { t: '翁梅街', x: -5, z: -48 },
        { t: '香乐路', x: 62, z: 5 }
    ];
    labels.forEach(({t,x,z}) => {
        const d = document.createElement('div');
        d.textContent = t;
        d.style.cssText = 'color:rgba(255,255,255,0.45);font-size:9px;background:rgba(0,0,0,0.4);padding:2px 8px;border-radius:4px;white-space:nowrap;';
        const l = new CSS2DObject(d); l.position.set(x, 0.5, z); scene.add(track(l));
    });
}

// ==================== 通用：凉亭 ====================
function buildPavilion(x, z) {
    const base = new THREE.Mesh(new THREE.CylinderGeometry(3, 3.3, 0.5, 16),
        new THREE.MeshStandardMaterial({ color: 0x9e9e9e, roughness: 0.4 }));
    base.position.set(x, 0.26, z); base.receiveShadow = true; scene.add(track(base));
    for (let i = 0; i < 6; i++) {
        const a = (i / 6) * Math.PI * 2;
        const col = new THREE.Mesh(new THREE.CylinderGeometry(0.15, 0.2, 4, 8),
            new THREE.MeshStandardMaterial({ color: 0x5d4037, roughness: 0.5 }));
        col.position.set(x + Math.cos(a) * 2.5, 2.5, z + Math.sin(a) * 2.5); col.castShadow = true; scene.add(track(col));
    }
    const roof = new THREE.Mesh(new THREE.ConeGeometry(3.5, 2, 6),
        new THREE.MeshStandardMaterial({ color: 0x4e342e, roughness: 0.5, metalness: 0.1 }));
    roof.position.set(x, 5, z); roof.castShadow = true; scene.add(track(roof));
    const tip = new THREE.Mesh(new THREE.SphereGeometry(0.3, 8, 8),
        new THREE.MeshStandardMaterial({ color: 0xffd54f, emissive: 0xffa000, emissiveIntensity: 0.5 }));
    tip.position.set(x, 6.2, z); scene.add(track(tip));
}

// ==================== 场景切换 ====================
function clearScene() {
    stopIotPolling();
    dynamicObjects.forEach(obj => {
        if (obj && obj.parent) obj.parent.remove(obj);
        if (obj && obj.geometry) obj.geometry.dispose();
        if (obj && obj.material) {
            if (Array.isArray(obj.material)) obj.material.forEach(m => m.dispose());
            else obj.material.dispose();
        }
    });
    dynamicObjects = [];
    monitorPoints = [];
    cars = [];
    particles = [];
    allBuildings = [];
}

// ==================== IoT 设备数据层 ====================
let iotMarkers = [];
let iotPollTimer = null;
const IOT_API = '/api/iot/devices';
const IOT_POLL_INTERVAL = 5000;

const DEVICE_COLORS = {
    normal: 0x00e676, warning: 0xffc107, alarm: 0xff1744, offline: 0x666666
};
const DEVICE_ICONS = {
    smoke: '🔥', access: '🚪', meter_w: '💧', meter_e: '⚡', camera: '📷', env: '🌡️'
};

function buildIotLayer() {
    const apiUrl = IOT_API + '?community=' + currentCommunity;
    fetch(apiUrl).then(r => r.json()).then(d => {
        if (d.code !== 0 || !d.data || !d.data.devices) return;
        clearIotMarkers();
        d.data.devices.forEach(dev => {
            const color = DEVICE_COLORS[dev.status] || 0xffffff;
            const size = dev.type === 'camera' ? 0.3 : 0.2;
            const geo = new THREE.SphereGeometry(size, 8, 8);
            const mat = new THREE.MeshStandardMaterial({
                color, emissive: color, emissiveIntensity: dev.status === 'alarm' ? 1.2 : dev.status === 'warning' ? 0.6 : 0.15,
                roughness: 0.2, metalness: 0.3
            });
            const marker = new THREE.Mesh(geo, mat);
            marker.position.set(dev.x, dev.y, dev.z);
            marker.castShadow = true;
            marker.userData = { dev, baseY: dev.y, pulse: Math.random() * Math.PI * 2 };
            scene.add(marker);
            iotMarkers.push(marker);

            // 设备标签
            const lbl = document.createElement('div');
            lbl.innerHTML = '<span style="font-size:9px;font-weight:bold;color:' +
                (dev.status === 'alarm' ? '#ff4444' : dev.status === 'warning' ? '#ffaa00' : '#88ff88') + '">' +
                (DEVICE_ICONS[dev.type] || '●') + ' ' + dev.typeName + '</span>' +
                '<br><span style="font-size:8px;color:#aaa">' + (dev.value || '') + '</span>';
            lbl.style.cssText = 'background:rgba(0,0,0,0.75);padding:2px 6px;border-radius:6px;white-space:nowrap;cursor:pointer;pointer-events:auto;';
            lbl.title = (dev.alarm || '') + '\n更新: ' + dev.updated;
            if (dev.status === 'alarm') lbl.style.border = '1px solid #ff4444';
            const label = new CSS2DObject(lbl);
            label.position.set(dev.x, dev.y + 0.8 + (dev.type === 'camera' ? 0.5 : 0), dev.z);
            label.userData = { dev };
            scene.add(label);
            iotMarkers.push(label); // store both mesh and label for cleanup
        });

        // 更新设备统计
        if (d.data.online !== undefined) {
            document.getElementById('device-count').textContent = d.data.online + '/' + d.data.total;
            document.getElementById('device-count').className = 'info-value ' + (d.data.alarm > 0 ? 'alarm' : 'online');
        }
    }).catch(() => {});
}

function clearIotMarkers() {
    iotMarkers.forEach(obj => {
        if (obj && obj.parent) obj.parent.remove(obj);
        if (obj && obj.geometry) obj.geometry.dispose();
        if (obj && obj.material) obj.material.dispose();
    });
    iotMarkers = [];
}

function startIotPolling() {
    stopIotPolling();
    buildIotLayer();
    iotPollTimer = setInterval(buildIotLayer, IOT_POLL_INTERVAL);
}

function stopIotPolling() {
    if (iotPollTimer) { clearInterval(iotPollTimer); iotPollTimer = null; }
    clearIotMarkers();
}

function switchCommunity(id) {
    if (id === currentCommunity) return;
    currentCommunity = id;
    const cfg = communities[id];
    clearScene();

    // 重建场景
    buildGround(cfg);
    buildWall(cfg);
    if (!cfg.entrance?.skipDefault) buildEntrance(cfg);
    buildStreetLights(cfg);
    buildParticles();

    switch (id) {
        case 'feicui': buildFeicui(cfg); break;
        case 'yunqi': buildYunqi(cfg); break;
        case 'zhongliang': buildZhongliang(cfg); break;
        case 'shanshui': buildShanshui(cfg); break;
        case 'yifeng': buildYifeng(cfg); break;
    }

    // 重建 IoT 设备层
    buildIotLayer();

    // 更新UI
    updateUI(cfg);
    // 应用默认视角
    applyView(cfg.camera.pos, cfg.camera.target);

    // 更新选择器高亮
    document.querySelectorAll('.community-option').forEach(el => {
        el.classList.toggle('active', el.dataset.community === id);
    });
}

function applyView(pos, tgt) {
    camera.position.set(pos[0], pos[1], pos[2]);
    controls.target.set(tgt[0], tgt[1], tgt[2]);
    controls.update();
}

function updateUI(cfg) {
    document.title = cfg.name + ' · 智慧物业数字孪生 — 大圣智慧物业';
    document.getElementById('building-count').textContent = cfg.info.buildings;
    document.getElementById('household-count').textContent = cfg.info.households;
    document.getElementById('monitor-count').textContent = cfg.info.monitors;
    document.getElementById('device-count').textContent = cfg.info.devices;
    document.getElementById('project-desc').textContent = cfg.name + ' | ' + cfg.desc + ' · 全场景数字孪生';

    // 更新图例
    const legendEl = document.getElementById('legend');
    const legendItems = legendEl.querySelectorAll('.legend-item');
    cfg.legend.forEach((item, i) => {
        if (i < legendItems.length) {
            legendItems[i].querySelector('.dot').style.background = item.color;
            legendItems[i].childNodes[1].textContent = item.label;
        }
    });
}

// ==================== 初始化场景选择器（HTML） ====================
function initCommunitySelector() {
    const selectors = document.querySelectorAll('.community-option');
    selectors.forEach(el => {
        el.addEventListener('click', () => {
            const id = el.dataset.community;
            if (id) switchCommunity(id);
        });
    });
}

// ==================== 动画循环 ====================
function animate() {
    requestAnimationFrame(animate);
    const dt = Math.min(clock.getDelta(), 0.15);
    const elapsed = performance.now() * 0.001;
    controls.update();

    // 监控点浮动
    monitorPoints.forEach(p => {
        p.position.y = p.userData.baseY + Math.sin(elapsed * p.userData.speed + p.userData.phase) * 0.3;
    });

    // 粒子旋转
    if (particles[0]) particles[0].rotation.y += dt * 0.1;

    // 水面动画
    const pond = scene.getObjectByName('pond');
    if (pond) { pond.material.opacity = 0.55 + Math.sin(elapsed * 1.5) * 0.1; }
    const pool = scene.getObjectByName('pool');
    if (pool) { pool.material.opacity = 0.7 + Math.sin(elapsed * 2) * 0.1; }

    // IoT 设备脉冲动画
    iotMarkers.forEach(obj => {
        if (obj.userData && obj.userData.dev) {
            const dev = obj.userData.dev;
            obj.position.y = dev.y + Math.sin(elapsed * 3 + obj.userData.pulse) * (dev.status === 'alarm' ? 0.4 : 0.12);
        }
    });

    // 车辆
    cars.forEach(car => {
        car.mesh.position.x += Math.cos(car.direction) * car.speed;
        car.mesh.position.z += Math.sin(car.direction) * car.speed;
        if (Math.abs(car.mesh.position.x) > 55 || Math.abs(car.mesh.position.z) > 52) {
            car.direction += Math.PI;
            car.mesh.rotation.y = car.direction;
        }
    });

    renderer.render(scene, camera);
    labelRenderer.render(scene, camera);
    updateFPS();
}

function updateFPS() {
    fpsFrames++;
    const now = performance.now();
    if (now - fpsTime >= 1000) {
        document.getElementById('fps-display').textContent = Math.round(fpsFrames / ((now - fpsTime) / 1000));
        fpsFrames = 0; fpsTime = now;
    }
}

// ==================== 事件 ====================
window.addEventListener('resize', () => {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
    labelRenderer.setSize(window.innerWidth, window.innerHeight);
});

window.addEventListener('keydown', (e) => {
    if (e.key.toLowerCase() === 'r') {
        const cfg = communities[currentCommunity];
        applyView(cfg.camera.pos, cfg.camera.target);
        return;
    }
    const cfg = communities[currentCommunity];
    if (cfg.shortcuts) {
        const sc = cfg.shortcuts.find(s => s.key === e.key.toLowerCase());
        if (sc) applyView(sc.cam, sc.tgt);
    }
    // 数字键切换场景
    const sceneKeys = { '3': 'yunqi', '4': 'zhongliang', '5': 'shanshui', '6': 'yifeng' };
    if (sceneKeys[e.key]) switchCommunity(sceneKeys[e.key]);
});

// ==================== 启动 ====================
const loadingEl = document.getElementById('loading');
function hideLoading() {
    loadingEl.classList.add('hidden');
    setTimeout(() => { if (loadingEl.parentNode) loadingEl.style.display = 'none'; }, 600);
}

function init() {
    clock = new THREE.Clock();
    fpsTime = performance.now();

    initScene();
    initCamera();
    initRenderer();
    initControls();
    initLights();
    initCommunitySelector();

    const cfg = communities[currentCommunity];
    buildGround(cfg);
    buildWall(cfg);
    buildEntrance(cfg);
    buildStreetLights(cfg);
    buildParticles();
    buildFeicui(cfg);

    updateUI(cfg);

    // 启动 IoT 设备数据轮询
    startIotPolling();

    setTimeout(() => hideLoading(), 800);
    animate();
}

init();
