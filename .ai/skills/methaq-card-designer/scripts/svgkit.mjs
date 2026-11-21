// svgkit: small helpers for authoring Methaq library SVGs from a Node generator script.
// Everything returns SVG strings and only produces elements/attributes the library validator accepts:
//   elements: svg g defs title desc path rect circle ellipse line polyline polygon linearGradient radialGradient stop clipPath mask
//   never:    style attributes, <style>, filter, pattern, use, image, symbol, marker, text, external hrefs, event handlers
// Use it like:  import * as K from '../../../tools/svgkit.mjs';   (adjust the relative path)
import fs from 'node:fs';
import path from 'node:path';

/* ------------------------------------------------------------------ numbers and attributes */

export const n = (value) => String(Math.round(value * 100) / 100);
export const rad = (degrees) => (degrees * Math.PI) / 180;
export const lerp = (a, b, t) => a + (b - a) * t;
export const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

const ATTRIBUTE_ALIASES = { strokeWidth: 'stroke-width', strokeLinecap: 'stroke-linecap', strokeLinejoin: 'stroke-linejoin', strokeDasharray: 'stroke-dasharray', strokeOpacity: 'stroke-opacity', fillOpacity: 'fill-opacity', fillRule: 'fill-rule', clipRule: 'clip-rule', stopColor: 'stop-color', stopOpacity: 'stop-opacity', gradientUnits: 'gradientUnits', gradientTransform: 'gradientTransform' };

export function attrs(attributes = {}) {
    let out = '';
    for (const [key, value] of Object.entries(attributes)) {
        if (value === undefined || value === null || value === false) continue;
        if (key === 'style' || key.startsWith('on')) throw new Error(`svgkit: "${key}" is not allowed in library SVGs`);
        out += ` ${ATTRIBUTE_ALIASES[key] ?? key}="${typeof value === 'number' ? n(value) : value}"`;
    }
    return out;
}

const flat = (children) => children.flat(Infinity).filter((child) => child !== undefined && child !== null && child !== false).join('');

export const el = (tag, attributes, ...children) => {
    const body = flat(children);
    return body === '' ? `<${tag}${attrs(attributes)}/>` : `<${tag}${attrs(attributes)}>${body}</${tag}>`;
};
export const g = (attributes, ...children) => el('g', attributes, ...children);
export const path_ = (d, attributes) => el('path', { d, ...attributes });
export { path_ as path };
export const rect = (x, y, width, height, attributes) => el('rect', { x, y, width, height, ...attributes });
export const circle = (cx, cy, r, attributes) => el('circle', { cx, cy, r, ...attributes });
export const ellipse = (cx, cy, rx, ry, attributes) => el('ellipse', { cx, cy, rx, ry, ...attributes });
export const line = (x1, y1, x2, y2, attributes) => el('line', { x1, y1, x2, y2, ...attributes });
export const polygon = (points, attributes) => el('polygon', { points: points.map(([x, y]) => `${n(x)},${n(y)}`).join(' '), ...attributes });
export const polyline = (points, attributes) => el('polyline', { points: points.map(([x, y]) => `${n(x)},${n(y)}`).join(' '), ...attributes });
export const clipPath = (id, ...children) => el('clipPath', { id }, ...children);
export const mask = (id, ...children) => el('mask', { id }, ...children);
export const defs = (...children) => el('defs', {}, ...children);

export const translate = (x, y = 0) => `translate(${n(x)} ${n(y)})`;
export const rotate = (degrees, cx = 0, cy = 0) => `rotate(${n(degrees)} ${n(cx)} ${n(cy)})`;
export const scale = (x, y = x) => `scale(${n(x)} ${n(y)})`;

/** A complete, valid SVG document string. `defs` and `body` can be strings or nested arrays of strings. */
export function svg({ width, height, defs: definitions = [], body = [], title }) {
    const defsBlock = flat([definitions]);
    return `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">${title ? `<title>${title}</title>` : ''}${defsBlock ? `<defs>${defsBlock}</defs>` : ''}${flat([body])}</svg>\n`;
}

export function writeSvg(file, content) {
    fs.mkdirSync(path.dirname(file), { recursive: true });
    fs.writeFileSync(file, content);
    return content.length;
}

/* ------------------------------------------------------------------ gradients */

/** stops: [[offset 0..1, '#hex', opacity?], ...] */
const stopList = (stops) => stops.map(([offset, color, opacity]) => el('stop', { offset: `${n(offset * 100)}%`, stopColor: color, stopOpacity: opacity })).join('');

export function linearGradient(id, stops, { x1 = 0, y1 = 0, x2 = 1, y2 = 0, units = 'objectBoundingBox', transform } = {}) {
    return el('linearGradient', { id, x1, y1, x2, y2, gradientUnits: units, gradientTransform: transform }, stopList(stops));
}

export function radialGradient(id, stops, { cx = 0.5, cy = 0.5, r = 0.5, fx, fy, units = 'objectBoundingBox', transform } = {}) {
    return el('radialGradient', { id, cx, cy, r, fx, fy, gradientUnits: units, gradientTransform: transform }, stopList(stops));
}

export const METALS = {
    gold: [[0, '#8a6a2c'], [0.22, '#e8cf86'], [0.45, '#b88a3a'], [0.68, '#f4e3a8'], [1, '#9a7330']],
    roseGold: [[0, '#a8685f'], [0.25, '#f0c4b4'], [0.5, '#c68478'], [0.75, '#f6d6c8'], [1, '#a8685f']],
    silver: [[0, '#7d8794'], [0.25, '#e4e8ee'], [0.5, '#a0a9b6'], [0.75, '#f2f4f7'], [1, '#808a97']],
    bronze: [[0, '#6e4a22'], [0.3, '#c99a5b'], [0.55, '#8a5f2d'], [0.8, '#d9b073'], [1, '#6e4a22']],
    champagne: [[0, '#b79c6a'], [0.3, '#f1e3bf'], [0.6, '#cdb27a'], [1, '#b79c6a']],
    copper: [[0, '#7d3b1d'], [0.3, '#d9854f'], [0.6, '#a65a2c'], [0.85, '#e8a06b'], [1, '#7d3b1d']],
    brass: [[0, '#7a5a1c'], [0.3, '#d6b25a'], [0.55, '#9c7a2e'], [0.8, '#e4c675'], [1, '#7a5a1c']],
};

/** A metallic linear gradient at `angle` degrees (0 = left to right, 90 = top to bottom). */
export function metal(id, name = 'gold', { angle = 45, stops } = {}) {
    const a = rad(angle);
    const x1 = 0.5 - Math.cos(a) / 2; const y1 = 0.5 - Math.sin(a) / 2;
    const x2 = 0.5 + Math.cos(a) / 2; const y2 = 0.5 + Math.sin(a) / 2;
    return linearGradient(id, stops ?? METALS[name], { x1, y1, x2, y2 });
}

/* ------------------------------------------------------------------ geometry */

/** Point at `degrees` on a circle; 0 = east, 90 = south (SVG y grows downward), -90 = north. */
export const polar = (cx, cy, r, degrees) => [cx + r * Math.cos(rad(degrees)), cy + r * Math.sin(rad(degrees))];

export const pointsToPath = (points, close = true) => `M${points.map(([x, y]) => `${n(x)} ${n(y)}`).join('L')}${close ? 'Z' : ''}`;

export function polygonPoints(cx, cy, r, sides, rotation = -90) {
    return Array.from({ length: sides }, (_, i) => polar(cx, cy, r, rotation + (360 / sides) * i));
}

export function starPoints(cx, cy, outer, inner, points, rotation = -90) {
    return Array.from({ length: points * 2 }, (_, i) => polar(cx, cy, i % 2 === 0 ? outer : inner, rotation + (180 / points) * i));
}

/** Cubic Bézier segments through the points (Catmull-Rom). tension 0.5 is the classic smooth curve. */
export function smoothPath(points, { closed = false, tension = 0.5 } = {}) {
    const count = points.length;
    if (count < 2) return '';
    const k = tension / 3;
    const at = (i) => (closed ? points[(i + count) % count] : points[clamp(i, 0, count - 1)]);
    let d = `M${n(points[0][0])} ${n(points[0][1])}`;
    const last = closed ? count : count - 1;
    for (let i = 0; i < last; i += 1) {
        const p0 = at(i - 1); const p1 = at(i); const p2 = at(i + 1); const p3 = at(i + 2);
        d += `C${n(p1[0] + (p2[0] - p0[0]) * k)} ${n(p1[1] + (p2[1] - p0[1]) * k)} ${n(p2[0] - (p3[0] - p1[0]) * k)} ${n(p2[1] - (p3[1] - p1[1]) * k)} ${n(p2[0])} ${n(p2[1])}`;
    }
    return closed ? `${d}Z` : d;
}

/** Evenly spaced samples along the smooth curve: [{x, y, angle}] (angle of the tangent in degrees). */
export function sampleSmooth(points, samples, { closed = false, tension = 0.5 } = {}) {
    const count = points.length;
    const k = tension / 3;
    const at = (i) => (closed ? points[(i + count) % count] : points[clamp(i, 0, count - 1)]);
    const dense = [];
    const last = closed ? count : count - 1;
    for (let i = 0; i < last; i += 1) {
        const p0 = at(i - 1); const p1 = at(i); const p2 = at(i + 1); const p3 = at(i + 2);
        const c1 = [p1[0] + (p2[0] - p0[0]) * k, p1[1] + (p2[1] - p0[1]) * k];
        const c2 = [p2[0] - (p3[0] - p1[0]) * k, p2[1] - (p3[1] - p1[1]) * k];
        for (let step = 0; step < 40; step += 1) {
            const t = step / 40; const u = 1 - t;
            dense.push([
                u * u * u * p1[0] + 3 * u * u * t * c1[0] + 3 * u * t * t * c2[0] + t * t * t * p2[0],
                u * u * u * p1[1] + 3 * u * u * t * c1[1] + 3 * u * t * t * c2[1] + t * t * t * p2[1],
            ]);
        }
    }
    dense.push(closed ? dense[0] : points[count - 1]);
    const lengths = [0];
    for (let i = 1; i < dense.length; i += 1) lengths.push(lengths[i - 1] + Math.hypot(dense[i][0] - dense[i - 1][0], dense[i][1] - dense[i - 1][1]));
    const total = lengths[lengths.length - 1];
    const out = [];
    for (let s = 0; s < samples; s += 1) {
        const target = samples === 1 ? total / 2 : (total * s) / (samples - 1);
        let i = 1;
        while (i < lengths.length - 1 && lengths[i] < target) i += 1;
        const span = lengths[i] - lengths[i - 1] || 1;
        const t = (target - lengths[i - 1]) / span;
        const [x0, y0] = dense[i - 1]; const [x1, y1] = dense[i];
        out.push({ x: lerp(x0, x1, t), y: lerp(y0, y1, t), angle: (Math.atan2(y1 - y0, x1 - x0) * 180) / Math.PI });
    }
    return out;
}

export const arcPath = (cx, cy, r, startDeg, endDeg) => {
    const [x1, y1] = polar(cx, cy, r, startDeg); const [x2, y2] = polar(cx, cy, r, endDeg);
    const large = Math.abs(endDeg - startDeg) % 360 > 180 ? 1 : 0;
    return `M${n(x1)} ${n(y1)}A${n(r)} ${n(r)} 0 ${large} ${endDeg > startDeg ? 1 : 0} ${n(x2)} ${n(y2)}`;
};

/* ------------------------------------------------------------------ repetition and symmetry */

/** `count` copies of make(i) rotated evenly around (cx, cy). */
export const around = (count, cx, cy, make, offsetDegrees = 0) => Array.from({ length: count }, (_, i) => g({ transform: rotate(offsetDegrees + (360 / count) * i, cx, cy) }, make(i))).join('');
export const mirrorX = (content, axisX) => `${content}${g({ transform: `${translate(2 * axisX, 0)} ${scale(-1, 1)}` }, content)}`;
export const mirrorY = (content, axisY) => `${content}${g({ transform: `${translate(0, 2 * axisY)} ${scale(1, -1)}` }, content)}`;
/** Four-fold mirror about the point (cx, cy): the content plus its reflections. */
export const quad = (content, cx, cy) => mirrorY(mirrorX(content, cx), cy);
export const tile = (content, columns, rows, dx, dy, x0 = 0, y0 = 0) => {
    let out = '';
    for (let r = 0; r < rows; r += 1) for (let c = 0; c < columns; c += 1) out += g({ transform: translate(x0 + c * dx, y0 + r * dy) }, content);
    return out;
};

/* ------------------------------------------------------------------ seeded randomness */

export function rng(seed = 1) {
    let a = seed >>> 0;
    return () => {
        a = (a + 0x6d2b79f5) >>> 0;
        let t = a;
        t = Math.imul(t ^ (t >>> 15), t | 1);
        t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

/** Scatter `count` items inside a box with a minimum spacing; make(x, y, random) returns SVG. */
export function scatter(count, { x, y, width, height, seed = 7, minDistance = 0, avoid = () => false }, make) {
    const random = rng(seed);
    const placed = [];
    let out = '';
    for (let attempts = 0; placed.length < count && attempts < count * 60; attempts += 1) {
        const px = x + random() * width; const py = y + random() * height;
        if (avoid(px, py)) continue;
        if (placed.some(([qx, qy]) => Math.hypot(px - qx, py - qy) < minDistance)) continue;
        placed.push([px, py]);
        out += make(px, py, random);
    }
    return out;
}

/* ------------------------------------------------------------------ shapes */

/** A pointed leaf whose base is at (x, y) and whose tip is `length` away along `angle` (degrees). */
export function leaf(x, y, length, width, angle = 0, attributes = {}) {
    const d = `M0 0C${n(length * 0.25)} ${n(-width / 2)} ${n(length * 0.7)} ${n(-width / 2)} ${n(length)} 0C${n(length * 0.7)} ${n(width / 2)} ${n(length * 0.25)} ${n(width / 2)} 0 0Z`;
    return path_(d, { transform: `${translate(x, y)} ${rotate(angle)}`, ...attributes });
}

/** A rounded petal (teardrop) with its narrow end at (x, y), pointing along `angle`. */
export function petal(x, y, length, width, angle = 0, attributes = {}) {
    const d = `M0 0C${n(length * 0.1)} ${n(-width * 0.6)} ${n(length * 0.75)} ${n(-width * 0.62)} ${n(length)} 0C${n(length * 0.75)} ${n(width * 0.62)} ${n(length * 0.1)} ${n(width * 0.6)} 0 0Z`;
    return path_(d, { transform: `${translate(x, y)} ${rotate(angle)}`, ...attributes });
}

/** Flower seen from above: `petals` petals around (cx, cy) with a centre disc. */
export function flower(cx, cy, radius, { petals = 6, petalFill = '#fff', petalStroke, petalStrokeWidth = 0, centerFill = '#e8cf86', centerRadius = radius * 0.2, rotation = -90, petalWidth = radius * 0.62 } = {}) {
    return g({}, around(petals, cx, cy, () => petal(cx, cy, radius, petalWidth, rotation, { fill: petalFill, stroke: petalStroke, strokeWidth: petalStrokeWidth }), 0), circle(cx, cy, centerRadius, { fill: centerFill }));
}

/** Eight-pointed khatam star (two overlapping squares) as one outlined shape. */
export function khatam(cx, cy, radius, attributes = {}) {
    return path_(pointsToPath(starPoints(cx, cy, radius, radius * 0.7071, 8, -90)), attributes);
}

/** Crescent moon: outer circle minus an offset inner circle, rotated by `rotation` degrees. */
export function crescent(cx, cy, radius, thickness = 0.45, rotation = 0, attributes = {}) {
    const inner = radius * (1 - thickness * 0.2);
    const offset = radius * thickness;
    const d = `M${n(cx)} ${n(cy - radius)}A${n(radius)} ${n(radius)} 0 1 0 ${n(cx)} ${n(cy + radius)}A${n(inner)} ${n(inner)} 0 1 1 ${n(cx)} ${n(cy - radius)}Z`;
    return path_(d, { transform: `${translate(offset * 0.25, 0)} ${rotate(rotation, cx, cy)}`, ...attributes });
}

/** Four-point sparkle star. `squash` (0..0.5) controls how thin the arms are. */
export function sparkle(cx, cy, radius, squash = 0.16, attributes = {}) {
    const s = radius * squash;
    return path_(`M${n(cx)} ${n(cy - radius)}Q${n(cx + s)} ${n(cy - s)} ${n(cx + radius)} ${n(cy)}Q${n(cx + s)} ${n(cy + s)} ${n(cx)} ${n(cy + radius)}Q${n(cx - s)} ${n(cy + s)} ${n(cx - radius)} ${n(cy)}Q${n(cx - s)} ${n(cy - s)} ${n(cx)} ${n(cy - radius)}Z`, attributes);
}

export function heart(cx, cy, size, attributes = {}) {
    const s = size / 2;
    return path_(`M${n(cx)} ${n(cy + s * 0.9)}C${n(cx - s * 1.5)} ${n(cy - s * 0.1)} ${n(cx - s * 0.7)} ${n(cy - s * 1.1)} ${n(cx)} ${n(cy - s * 0.35)}C${n(cx + s * 0.7)} ${n(cy - s * 1.1)} ${n(cx + s * 1.5)} ${n(cy - s * 0.1)} ${n(cx)} ${n(cy + s * 0.9)}Z`, attributes);
}

/** A scalloped line from (x1, y1) to (x2, y2): `count` arcs bulging to the left of travel when depth > 0. */
export function scallopLine(x1, y1, x2, y2, count, depth) {
    const dx = (x2 - x1) / count; const dy = (y2 - y1) / count;
    const chord = Math.hypot(dx, dy);
    const radius = (chord * chord / 4 + depth * depth) / (2 * Math.abs(depth));
    let d = `M${n(x1)} ${n(y1)}`;
    for (let i = 1; i <= count; i += 1) d += `A${n(radius)} ${n(radius)} 0 0 ${depth > 0 ? 0 : 1} ${n(x1 + dx * i)} ${n(y1 + dy * i)}`;
    return d;
}

/**
 * Outline of an arch standing on the baseline y + h, left edge x, right edge x + w.
 * kind: 'round' | 'pointed' | 'ogee' | 'horseshoe' | 'trefoil'. Returns an open or closed path string.
 */
export function archPath(x, y, w, h, kind = 'pointed', { close = true } = {}) {
    const cx = x + w / 2; const bottom = y + h; const right = x + w;
    let top;
    if (kind === 'round') {
        top = `M${n(x)} ${n(bottom)}V${n(y + w / 2)}A${n(w / 2)} ${n(w / 2)} 0 0 1 ${n(right)} ${n(y + w / 2)}V${n(bottom)}`;
    } else if (kind === 'pointed') {
        const spring = y + h * 0.42;
        top = `M${n(x)} ${n(bottom)}V${n(spring)}C${n(x)} ${n(y + h * 0.18)} ${n(cx - w * 0.14)} ${n(y + h * 0.06)} ${n(cx)} ${n(y)}C${n(cx + w * 0.14)} ${n(y + h * 0.06)} ${n(right)} ${n(y + h * 0.18)} ${n(right)} ${n(spring)}V${n(bottom)}`;
    } else if (kind === 'ogee') {
        const spring = y + h * 0.4;
        top = `M${n(x)} ${n(bottom)}V${n(spring)}C${n(x)} ${n(y + h * 0.22)} ${n(cx - w * 0.36)} ${n(y + h * 0.2)} ${n(cx - w * 0.12)} ${n(y + h * 0.1)}C${n(cx - w * 0.05)} ${n(y + h * 0.07)} ${n(cx)} ${n(y + h * 0.03)} ${n(cx)} ${n(y)}C${n(cx)} ${n(y + h * 0.03)} ${n(cx + w * 0.05)} ${n(y + h * 0.07)} ${n(cx + w * 0.12)} ${n(y + h * 0.1)}C${n(cx + w * 0.36)} ${n(y + h * 0.2)} ${n(right)} ${n(y + h * 0.22)} ${n(right)} ${n(spring)}V${n(bottom)}`;
    } else if (kind === 'horseshoe') {
        const r = w * 0.56;
        top = `M${n(x + w * 0.06)} ${n(bottom)}V${n(y + r + h * 0.1)}A${n(r)} ${n(r)} 0 1 1 ${n(right - w * 0.06)} ${n(y + r + h * 0.1)}V${n(bottom)}`;
    } else {
        top = `M${n(x)} ${n(bottom)}V${n(y + h * 0.3)}Q${n(x)} ${n(y + h * 0.1)} ${n(x + w * 0.25)} ${n(y + h * 0.1)}Q${n(x + w * 0.4)} ${n(y + h * 0.1)} ${n(x + w * 0.42)} ${n(y + h * 0.04)}Q${n(cx)} ${n(y - h * 0.04)} ${n(x + w * 0.58)} ${n(y + h * 0.04)}Q${n(x + w * 0.6)} ${n(y + h * 0.1)} ${n(x + w * 0.75)} ${n(y + h * 0.1)}Q${n(right)} ${n(y + h * 0.1)} ${n(right)} ${n(y + h * 0.3)}V${n(bottom)}`;
    }
    return close ? `${top}Z` : top;
}

/** A vine: stem along smooth points with leaves on alternating sides. */
export function vine(points, { stem = '#4a6a4a', stemWidth = 3, leafFill = '#6c8f6c', leafLength = 56, leafWidth = 26, leaves = 10, spread = 48, taper = true, extra } = {}) {
    let out = path_(smoothPath(points), { fill: 'none', stroke: stem, strokeWidth: stemWidth, strokeLinecap: 'round' });
    const samples = sampleSmooth(points, leaves);
    samples.forEach((sample, i) => {
        const side = i % 2 === 0 ? -1 : 1;
        const t = leaves === 1 ? 0 : i / (leaves - 1);
        const size = taper ? lerp(1, 0.62, t) : 1;
        out += leaf(sample.x, sample.y, leafLength * size, leafWidth * size, sample.angle + side * spread, { fill: leafFill });
        if (extra) out += extra(sample, i, side);
    });
    return out;
}

/** Balloon: ellipse body, knot and a curved string. */
export function balloon(cx, cy, rx, ry, fill, { highlight = '#ffffff', stringColor = '#9a8a7a', stringLength = ry * 1.6, stringSway = rx * 0.5 } = {}) {
    const bottom = cy + ry;
    return g({},
        path_(`M${n(cx)} ${n(bottom + ry * 0.12)}C${n(cx - stringSway)} ${n(bottom + stringLength * 0.35)} ${n(cx + stringSway)} ${n(bottom + stringLength * 0.7)} ${n(cx)} ${n(bottom + stringLength)}`, { fill: 'none', stroke: stringColor, strokeWidth: 3, strokeLinecap: 'round' }),
        path_(`M${n(cx)} ${n(cy - ry)}C${n(cx + rx * 1.15)} ${n(cy - ry)} ${n(cx + rx * 1.1)} ${n(cy + ry * 0.55)} ${n(cx)} ${n(bottom)}C${n(cx - rx * 1.1)} ${n(cy + ry * 0.55)} ${n(cx - rx * 1.15)} ${n(cy - ry)} ${n(cx)} ${n(cy - ry)}Z`, { fill }),
        path_(`M${n(cx - rx * 0.1)} ${n(bottom - 2)}L${n(cx - rx * 0.22)} ${n(bottom + ry * 0.16)}L${n(cx + rx * 0.22)} ${n(bottom + ry * 0.16)}L${n(cx + rx * 0.1)} ${n(bottom - 2)}Z`, { fill }),
        ellipse(cx - rx * 0.38, cy - ry * 0.4, rx * 0.16, ry * 0.3, { fill: highlight, fillOpacity: 0.42, transform: rotate(18, cx - rx * 0.38, cy - ry * 0.4) }),
    );
}

/** Soft cloud made from overlapping circles on a flat base. */
export function cloud(cx, cy, width, height, fill, attributes = {}) {
    const r = height / 2;
    const parts = [[-0.32, 0.12, 0.7], [-0.1, -0.12, 1], [0.16, -0.02, 0.85], [0.34, 0.14, 0.6]];
    return g(attributes,
        ...parts.map(([dx, dy, k]) => circle(cx + dx * width, cy + dy * height, r * k, { fill })),
        rect(cx - width * 0.4, cy + height * 0.02, width * 0.8, height * 0.34, { rx: height * 0.17, fill }),
    );
}
