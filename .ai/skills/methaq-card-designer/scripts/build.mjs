#!/usr/bin/env node
// Builds one design folder: spec.json -> design.json, SVG -> PNG rasters, preview/thumb/card renders drawn with the
// editor's own Fabric calls, a QA report, then the project's real PHP validators.
//   node .ai/skills/methaq-card-designer/scripts/build.mjs <designDir> [--quiet]
// Exit code 0: no errors (warnings may remain). Exit code 1: errors to fix.
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { compileSpec } from './lib/compile.mjs';
import { acquireChrome, openHarness, startStaticServer } from './lib/chrome.mjs';
import { PATHS, PROJECT, SCRIPTS, WORKSPACE, projectData, workspaceDesigns } from './lib/project.mjs';

const args = process.argv.slice(2);
const quiet = args.includes('--quiet');
const target = args.find((arg) => !arg.startsWith('--'));
if (!target) {
    console.error('usage: node scripts/build.mjs <designDir> [--quiet]');
    process.exit(2);
}
const designDir = path.resolve(target);
const inside = (directory, parent) => directory === parent || directory.startsWith(`${parent}${path.sep}`);
if (inside(designDir, PROJECT) && !inside(designDir, WORKSPACE)) {
    console.error(`FAIL  ${designDir}\n  ERROR   drafts are built outside the project, so renders never land in git: start one with new.mjs (--from copies the example)`);
    process.exit(1);
}
const FONTS = projectData().fonts;

const decode = (dataUrl) => Buffer.from(dataUrl.slice(dataUrl.indexOf(',') + 1), 'base64');
const report = { errors: [], warnings: [], notes: [] };
const finish = () => {
    const lines = [];
    const shown = designDir.startsWith(WORKSPACE) ? path.relative(WORKSPACE, designDir) : designDir;
    lines.push(`${report.errors.length === 0 ? 'PASS' : 'FAIL'}  ${shown}`);
    for (const message of report.errors) lines.push(`  ERROR   ${message}`);
    for (const message of report.warnings) lines.push(`  warn    ${message}`);
    if (!quiet) for (const message of report.notes) lines.push(`  note    ${message}`);
    console.log(lines.join('\n'));
    fs.writeFileSync(path.join(designDir, 'report.json'), JSON.stringify(report, null, 2));
    process.exit(report.errors.length === 0 ? 0 : 1);
};

const specPath = path.join(designDir, 'spec.json');
if (!fs.existsSync(specPath)) {
    report.errors.push('spec.json is missing');
    finish();
}
let spec;
try {
    spec = JSON.parse(fs.readFileSync(specPath, 'utf8'));
} catch (error) {
    report.errors.push(`spec.json is not valid JSON: ${error.message}`);
    finish();
}

const compiled = compileSpec(spec, designDir);
report.warnings.push(...compiled.warnings);
if (!compiled.design) {
    report.errors.push(...compiled.errors);
    finish();
}
const design = compiled.design;

// Workspace-wide uniqueness: no other draft may reuse this slug.
const clash = workspaceDesigns().find((other) => other.slug === design.slug && path.resolve(other.dir) !== designDir);
if (clash) report.errors.push(`slug "${design.slug}" is already used by ${clash.dir}`);

const server = await startStaticServer({
    '/vendor/': PATHS.fabricDist,
    '/harness/': path.join(SCRIPTS, 'harness'),
    '/fonts/': PATHS.fonts,
    '/materials/': PATHS.materials,
    '/design/': designDir,
});
const chrome = await acquireChrome();
try {
    const page = await openHarness(chrome, server.port);

    // 1. Rasterize every SVG to the PNG the editor draws.
    for (const asset of design.assets) {
        const svg = fs.readFileSync(path.join(designDir, asset.svg), 'utf8');
        try {
            const png = await page.evaluate(`window.Methaq.rasterizeSvg(${JSON.stringify({ svg, width: asset.width, height: asset.height })})`);
            fs.writeFileSync(path.join(designDir, asset.preview), decode(png));
        } catch (error) {
            report.errors.push(`asset ${asset.key}: the SVG does not render (${error.message})`);
        }
    }

    // 2. Render the card exactly as the editor does.
    const fonts = {};
    for (const [slug, font] of Object.entries(FONTS)) {
        fonts[font.placeholderId] = { family: font.family, slug, url: `/fonts/${font.file}`, variable: font.variable };
    }
    const assets = {};
    for (const asset of design.assets) assets[asset.placeholderId] = { previewUrl: `/design/${asset.preview}?v=${Date.now()}` };
    const material = design.document.canvas.material;
    const watermarkFont = FONTS['font-reem-kufi'];
    const payload = {
        design: design.document,
        fonts,
        assets,
        materialUrl: material && material.preset !== 'plain' ? `/materials/${material.preset}.svg` : null,
        extraFonts: watermarkFont ? [{ family: watermarkFont.family, url: `/fonts/${watermarkFont.file}`, variable: watermarkFont.variable }] : [],
        outputs: true,
    };
    if (report.errors.length === 0) {
        const result = await page.evaluate(`window.Methaq.renderDocument(${JSON.stringify(payload)})`, 120_000);
        fs.writeFileSync(path.join(designDir, 'preview.png'), decode(result.png));
        fs.writeFileSync(path.join(designDir, 'thumb.png'), decode(result.thumb));
        fs.writeFileSync(path.join(designDir, 'card.jpg'), decode(result.card));
        fs.writeFileSync(path.join(designDir, 'diagnostics.json'), JSON.stringify(result.diagnostics, null, 2));

        const { text, overlaps, failedAssets } = result.diagnostics;
        for (const id of failedAssets) report.errors.push(`layer ${id}: image failed to load`);
        for (const item of text) {
            const tag = `text ${item.id}`;
            if (!item.ink) {
                report.errors.push(`${tag}: nothing is drawn (empty or fully clipped)`);
                continue;
            }
            const right = item.ink.x + item.ink.w;
            const bottom = item.ink.y + item.ink.h;
            if (item.ink.x < 0 || item.ink.y < 0 || right > 1080 || bottom > 1920) report.errors.push(`${tag}: runs outside the card`);
            else if (item.ink.x < 48 || item.ink.y < 48 || right > 1032 || bottom > 1872) report.warnings.push(`${tag}: closer than 48px to the card edge (ink ${item.ink.x},${item.ink.y} ${item.ink.w}x${item.ink.h})`);
            if (item.wordOverflow) report.warnings.push(`${tag}: a word is wider than its box (${item.maxLineWidth}px in ${item.declared.w}px); widen the box or lower the size`);
            if (item.heightExceedsDeclared) report.warnings.push(`${tag}: rendered ${item.box.h}px tall but declared h=${item.declared.h}; the editor will show an overflow warning, raise h`);
            if (item.contrastP10 !== null) {
                const limit = item.fontSize >= 56 ? 2.6 : 3.4;
                if (item.contrastP10 < limit - 1) report.errors.push(`${tag}: unreadable, contrast p10 ${item.contrastP10}:1 (median ${item.contrastMedian}:1)`);
                else if (item.contrastP10 < limit) report.warnings.push(`${tag}: weak contrast p10 ${item.contrastP10}:1 (median ${item.contrastMedian}:1)`);
            }
            const expected = design.expectations?.[item.id];
            if (expected !== undefined && expected !== item.lines) report.errors.push(`${tag}: expected ${expected} line(s) but it renders on ${item.lines}; adjust the box width/size or the expectation`);
            if (item.fontSize < 26) report.warnings.push(`${tag}: ${item.fontSize}px is small for a phone screen (min 26, prefer 30+)`);
        }
        for (const overlap of overlaps) report.errors.push(`text ${overlap.a} overlaps text ${overlap.b} by ${overlap.w}x${overlap.h}px`);
        report.notes.push(`text layers: ${text.map((item) => `${item.id}(${item.lines}l ${item.fontSize}px c${item.contrastMedian})`).join(', ')}`);
    }
} catch (error) {
    report.errors.push(`render failed: ${error.message}`);
} finally {
    await chrome.close();
    await server.close();
}

// 3. design.json + authoritative PHP validation (the project's own hydrator, SVG validator and scripture).
fs.writeFileSync(path.join(designDir, 'design.json'), JSON.stringify(design, null, 2));
try {
    const output = execFileSync('php', [path.join(SCRIPTS, 'validate.php'), designDir, PROJECT], { encoding: 'utf8' });
    const php = JSON.parse(output);
    report.errors.push(...php.errors);
    report.warnings.push(...php.warnings);
    if (php.info?.svgBytes) report.notes.push(`svg bytes: ${JSON.stringify(php.info.svgBytes)}; layers: ${php.info.layers}`);
} catch (error) {
    report.errors.push(`PHP validation could not run: ${String(error.message).split('\n')[0]}`);
}

finish();
