#!/usr/bin/env node
// Prints what a new design must respect and must not copy, read live from the project:
//   node .ai/skills/methaq-card-designer/scripts/context.mjs [category] [--sheet <out.jpg>]
// category: verses and phrases suited to it, the library templates of that occasion, the drafts in the workspace.
// --sheet: a labelled contact sheet of every library template preview (needs ImageMagick), to look at before designing.
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { PROJECT, WORKSPACE, projectData, workspaceDesigns } from './lib/project.mjs';

const args = process.argv.slice(2);
const sheetIndex = args.indexOf('--sheet');
const sheet = sheetIndex >= 0 ? path.resolve(args[sheetIndex + 1] ?? 'existing-templates.jpg') : null;
const category = args.find((arg, index) => !arg.startsWith('--') && index !== sheetIndex + 1);
const data = projectData();
const categories = data.categories.map((entry) => entry.value);
if (category && !categories.includes(category)) {
    console.error(`category must be one of: ${categories.join(', ')}`);
    process.exit(1);
}

const lines = [];
const section = (title) => lines.push('', `## ${title}`);
lines.push(`# Methaq card design context${category ? ` — ${category}` : ''}`, `project: ${PROJECT}`, `workspace: ${WORKSPACE}`);

section('Occasions (category → Arabic label)');
for (const entry of data.categories) lines.push(`- ${entry.value} → ${entry.label}`);

section('Library templates (do not repeat their look)');
for (const template of data.templates) {
    if (category && template.category !== category) continue;
    lines.push(`- ${template.slug} «${template.name}» (${template.category}) public/${template.thumbnail}`);
}
if (category) lines.push(`  (${data.templates.filter((template) => template.category !== category).length} more in other occasions; --sheet shows them all)`);

section('Fonts (only these; Latin-only fonts carry Latin text only)');
for (const [slug, font] of Object.entries(data.fonts)) {
    lines.push(`- ${slug}: ${font.family} [${font.languages.join(', ')}]${font.variable ? ' weights 400-700' : ' keep 400/500'}`);
}

section(category ? `Verses for ${category} (kind "verse", id only)` : 'Verses (kind "verse", id only)');
for (const [id, verse] of Object.entries(data.verses)) {
    if (category && !verse.categories.includes(category)) continue;
    const words = verse.text.split(' ');
    lines.push(`- ${id} (${verse.source}; ${words.length} words) ${words.slice(0, 6).join(' ')}${words.length > 6 ? ' …' : ''}`);
}

if (category) {
    section(`Phrases the editor already suggests for ${category} (tone reference; do not repeat sibling templates' lines)`);
    for (const phrase of data.phrases) if (phrase.categories.includes(category)) lines.push(`- ${phrase.text}`);
}

section('Scene and material options');
lines.push(`- envelope.style: ${data.envelopeStyles.join(', ')}`);
lines.push(`- envelope.sealStyle: ${data.sealStyles.join(', ')}`);
lines.push(`- backdrop: ${data.backdrops.join(', ')} (or null)`);
lines.push(`- cover.decoration: ${data.coverDecorations.join(', ')}`);
lines.push(`- audio.track: ${Object.entries(data.templateAudio).map(([slug, track]) => `${slug} (${track.categories.join(', ')})`).join(', ')} or null`);
lines.push(`- canvas.material.preset: ${data.materials.join(', ')}`);

const drafts = workspaceDesigns().filter((design) => !category || design.category === category);
section('Drafts in the workspace');
if (drafts.length === 0) lines.push('- none');
for (const draft of drafts) {
    let status = 'not built';
    try {
        const report = JSON.parse(fs.readFileSync(path.join(draft.dir, 'report.json'), 'utf8'));
        status = report.errors.length === 0 ? `PASS, ${report.warnings.length} warnings` : `FAIL, ${report.errors.length} errors`;
    } catch {
        // no report yet
    }
    const imported = data.templatePresets.includes(draft.slug) ? ', imported' : '';
    lines.push(`- ${draft.slug} (${draft.category}) ${status}${imported}: ${draft.dir}`);
}

if (sheet) {
    const previews = data.templates
        .map((template) => ({ slug: template.slug, file: path.join(PROJECT, 'public', template.thumbnail) }))
        .filter((template) => fs.existsSync(template.file));
    try {
        execFileSync('magick', [
            'montage',
            ...previews.flatMap((template) => ['-label', template.slug, template.file]),
            '-tile', '8x', '-geometry', '180x320+6+6', '-pointsize', '15', '-background', '#e9e6df', sheet,
        ], { stdio: 'pipe' });
        lines.push('', `contact sheet: ${sheet}`);
    } catch (error) {
        lines.push('', `contact sheet failed (${String(error.message).split('\n')[0]}); look at the PNGs listed above instead`);
    }
}

console.log(lines.join('\n'));
