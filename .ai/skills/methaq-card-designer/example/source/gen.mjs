// Example generator. Start a draft from it with: node scripts/new.mjs <category> <slug> --from example
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import * as K from '../../scripts/svgkit.mjs';

const here = path.dirname(fileURLToPath(import.meta.url));
const W = 1080;
const H = 1920;

// Gradients live in <defs>; shapes reference them as fill="url(#id)".
const defs = [
    K.metal('gold', 'gold', { angle: 35 }),
    K.radialGradient('halo', [[0, '#fff3d0', 0.85], [1, '#fff3d0', 0]]),
];

const body = [];
// A soft glow behind the arch (radial gradient with fading opacity stands in for a blur filter).
body.push(K.ellipse(540, 620, 420, 520, { fill: 'url(#halo)' }));
// Double metallic border.
body.push(K.rect(40, 40, W - 80, H - 80, { rx: 12, fill: 'none', stroke: 'url(#gold)', strokeWidth: 4 }));
body.push(K.rect(62, 62, W - 124, H - 124, { rx: 8, fill: 'none', stroke: 'url(#gold)', strokeWidth: 1.5 }));
// Pointed arch, outer + inner line.
body.push(K.path(K.archPath(200, 150, 680, 900, 'pointed'), { fill: 'none', stroke: 'url(#gold)', strokeWidth: 3 }));
body.push(K.path(K.archPath(228, 184, 624, 866, 'pointed'), { fill: 'none', stroke: 'url(#gold)', strokeWidth: 1.5 }));
// A vine on the left, mirrored to the right about x = 540.
const vine = K.vine([[90, 1740], [140, 1540], [118, 1330], [188, 1130], [150, 960]], { stem: '#7a613b', leafFill: '#9bb59b', leaves: 11 });
body.push(K.mirrorX(vine, W / 2));
// Ornaments: crescent, sparkles, an eight-point star ringed by petals.
body.push(K.crescent(540, 262, 64, 0.5, -20, { fill: 'url(#gold)' }));
body.push(K.sparkle(420, 250, 24, 0.16, { fill: 'url(#gold)' }), K.sparkle(670, 320, 16, 0.16, { fill: 'url(#gold)' }));
body.push(K.khatam(540, 1560, 96, { fill: 'url(#gold)' }));
body.push(K.around(12, 540, 1560, () => K.petal(540, 1560 - 118, 66, 26, -90, { fill: '#e8cf86', fillOpacity: 0.75 })));

K.writeSvg(path.join(here, '../assets/frame.svg'), K.svg({ width: W, height: H, defs, body }));
console.log('frame.svg written');
