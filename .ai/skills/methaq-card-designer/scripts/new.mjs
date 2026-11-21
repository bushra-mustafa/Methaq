#!/usr/bin/env node
// Creates a design folder in the workspace (outside git) with a brief, a spec skeleton and a generator stub.
//   node .ai/skills/methaq-card-designer/scripts/new.mjs <category> <slug> [--from <designDir>]
// --from copies spec.json and source/ from another design (the skill's example/ or an earlier draft) to start a variant.
import fs from 'node:fs';
import path from 'node:path';
import { SCRIPTS, WORKSPACE, projectData, workspaceDesigns } from './lib/project.mjs';

const args = process.argv.slice(2);
const fromIndex = args.indexOf('--from');
const from = fromIndex >= 0 ? path.resolve(args[fromIndex + 1] ?? '') : null;
const [category, slug] = args.filter((arg, index) => !arg.startsWith('--') && (fromIndex < 0 || index !== fromIndex + 1));

const data = projectData();
const categories = data.categories.map((entry) => entry.value);
const fail = (message) => {
    console.error(message);
    process.exit(1);
};
if (!category || !slug) fail('usage: node scripts/new.mjs <category> <slug> [--from <designDir>]');
if (!categories.includes(category)) fail(`category must be one of: ${categories.join(', ')}`);
if (!/^[a-z][a-z0-9-]{2,38}[a-z0-9]$/.test(slug)) fail('slug: kebab-case, 4-40 characters, starts with a letter');
if (data.templatePresets.includes(slug)) fail(`slug "${slug}" is already a library template`);
const taken = workspaceDesigns().find((design) => design.slug === slug);
if (taken) fail(`slug "${slug}" is already used by ${taken.dir}`);

const designDir = path.join(WORKSPACE, category, slug);
if (fs.existsSync(designDir)) fail(`${designDir} already exists`);
fs.mkdirSync(path.join(designDir, 'source'), { recursive: true });
fs.mkdirSync(path.join(designDir, 'assets'), { recursive: true });

const svgkitImport = path.relative(path.join(designDir, 'source'), path.join(SCRIPTS, 'svgkit.mjs')).split(path.sep).join('/');
const label = data.categories.find((entry) => entry.value === category).label;

if (from) {
    for (const required of ['spec.json', 'source/gen.mjs']) {
        if (!fs.existsSync(path.join(from, required))) fail(`--from ${from}: ${required} is missing`);
    }
    const spec = JSON.parse(fs.readFileSync(path.join(from, 'spec.json'), 'utf8'));
    fs.writeFileSync(path.join(designDir, 'spec.json'), `${JSON.stringify({ ...spec, slug, category }, null, 2)}\n`);
    fs.cpSync(path.join(from, 'source'), path.join(designDir, 'source'), { recursive: true });
    const generator = path.join(designDir, 'source', 'gen.mjs');
    const code = fs.readFileSync(generator, 'utf8').replace(/from '[^']*svgkit\.mjs'/, `from '${svgkitImport}'`);
    fs.writeFileSync(generator, code);
} else {
    const verse = Object.entries(data.verses).find(([, entry]) => entry.categories.includes(category))?.[0] ?? 'verse-30-21-p';
    const spec = {
        slug,
        name: 'اسم القالب',
        category,
        concept: 'Replace with 1-3 sentences: the idea, the signature motif and the composition.',
        tags: [],
        palette: { background: '#fbf7ee', surface: '#ffffff', primaryText: '#3a2c1a', secondaryText: '#6b5a40', accent: '#a8812f', effect: '#f1dca0' },
        canvas: { background: 'background' },
        assets: {
            frame: { name: 'إطار', type: 'frame', file: 'assets/frame.svg', width: 1080, height: 1920 },
        },
        layers: [
            { id: 'frame', kind: 'image', asset: 'frame', x: 0, y: 0, w: 1080, h: 1920 },
            { id: 'names', kind: 'text', font: 'font-aref-ruqaa', text: 'يوسف وملاك', x: 140, y: 520, w: 800, h: 230, size: 128, fill: 'primaryText', expectLines: 1 },
            { id: 'verse', kind: 'verse', verse, x: 190, y: 860, w: 700, h: 230, size: 36, fill: 'secondaryText' },
            { id: 'details', kind: 'text', font: 'font-noto-arabic', text: 'الخميس 14/8/2027 · الثامنة مساءً\nقاعة الياسمين · طرابلس', x: 160, y: 1200, w: 760, h: 140, size: 38, weight: 500, fill: 'primaryText' },
        ],
        scene: {
            opening: 'envelope',
            durationMs: 1700,
            envelope: { paper: '#e9d9c0', lining: 'surface', seal: 'accent', monogram: 'ي م', style: 'luxury', sealStyle: 'wax', sealX: 50, sealY: 52, sealSize: 20 },
            effects: { sparkle: { enabled: true, color: 'effect', intensity: 0.5, speed: 0.9 } },
            audio: null,
            backdrop: null,
        },
        notes: '',
    };
    fs.writeFileSync(path.join(designDir, 'spec.json'), `${JSON.stringify(spec, null, 2)}\n`);
    fs.writeFileSync(path.join(designDir, 'source', 'gen.mjs'), `// Generator for ${slug}: writes every SVG declared in spec.json. Re-run it after each change.
//   node ${path.join(designDir, 'source', 'gen.mjs')}
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import * as K from '${svgkitImport}';

const here = path.dirname(fileURLToPath(import.meta.url));
const W = 1080;
const H = 1920;

const defs = [K.metal('gold', 'gold', { angle: 35 })];
const body = [];
// Draw the frame here (see the skill's example/source/gen.mjs and references/svg-craft.md).
body.push(K.rect(48, 48, W - 96, H - 96, { rx: 10, fill: 'none', stroke: 'url(#gold)', strokeWidth: 3 }));

K.writeSvg(path.join(here, '../assets/frame.svg'), K.svg({ width: W, height: H, defs, body }));
console.log('frame.svg written');
`);
}

fs.writeFileSync(path.join(designDir, 'brief.md'), `# ${slug} (${label})

## فكرة المستخدم كما كُتبت

> (انسخ نص الفكرة حرفياً هنا)

## القراءة التصميمية

- المناسبة:
- المزاج والإحساس:
- الفكرة البصرية الأساسية (motif) وكيف تختلف عن القوالب الحالية:
- التكوين (أين الأسماء، الآية، التفاصيل):
- الألوان (6 أدوار):
- الخطوط:
- الآية (معرّف من TemplateScripture):
- الظرف والختم والمؤثرات:

## الافتراضات

- (ما لم يذكره المستخدم واخترته أنا)

## سجل المراجعات

- جولة 1:
`);

console.log(designDir);
console.log(`next: node ${path.join(designDir, 'source', 'gen.mjs')} && node ${path.relative(process.cwd(), path.join(SCRIPTS, 'build.mjs'))} ${designDir}`);
