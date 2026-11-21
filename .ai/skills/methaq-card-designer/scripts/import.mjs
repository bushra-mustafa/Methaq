#!/usr/bin/env node
// Imports an approved, freshly built design into the project as a library template.
//   node .ai/skills/methaq-card-designer/scripts/import.mjs <designDir>            dry run: checks + writes import/SNIPPETS.md
//   node .ai/skills/methaq-card-designer/scripts/import.mjs <designDir> --write    also copies the files into the project
// Copies: SVG originals → storage/app/private/editor/assets/v1/<folder>/, PNG rasters → public/editor/previews/assets/v1/<folder>/,
// the watermarked thumb → public/editor/previews/templates/<slug>.png with WebP and AVIF twins.
// The PHP (preset case, factory method, seeder entries) goes into import/SNIPPETS.md for you to insert, then
// verify-import.php proves the factory rebuilds this exact design.
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { generatePhp } from './lib/phpgen.mjs';
import { PATHS, PROJECT, SCRIPTS, projectData } from './lib/project.mjs';

const args = process.argv.slice(2);
const write = args.includes('--write');
const force = args.includes('--force');
const target = args.find((arg) => !arg.startsWith('--'));
if (!target) {
    console.error('usage: node scripts/import.mjs <designDir> [--write] [--force]');
    process.exit(2);
}
const designDir = path.resolve(target);
const errors = [];
const warnings = [];
const stop = () => {
    for (const message of errors) console.log(`  ERROR   ${message}`);
    for (const message of warnings) console.log(`  warn    ${message}`);
    process.exit(errors.length > 0 ? 1 : 0);
};

// 1. Only a passing, current build may be imported.
const read = (file) => JSON.parse(fs.readFileSync(path.join(designDir, file), 'utf8'));
for (const file of ['spec.json', 'design.json', 'report.json', 'thumb.png', 'preview.png']) {
    if (!fs.existsSync(path.join(designDir, file))) errors.push(`${file} is missing: run build.mjs first`);
}
if (errors.length) stop();
const design = read('design.json');
const report = read('report.json');
if (report.errors.length > 0) errors.push(`the last build FAILED (${report.errors.length} errors): fix and rebuild before importing`);
const mtime = (file) => fs.statSync(path.join(designDir, file)).mtimeMs;
const builtAt = mtime('design.json');
const sources = ['spec.json', ...design.assets.map((asset) => asset.svg)];
for (const source of sources) {
    if (!fs.existsSync(path.join(designDir, source))) errors.push(`${source} is missing`);
    else if (mtime(source) > builtAt) errors.push(`${source} changed after the last build: rebuild before importing`);
}
if (JSON.stringify(read('spec.json').slug) !== JSON.stringify(design.slug)) errors.push('spec.json and design.json disagree on the slug: rebuild');

// 2. Names must still be free in the live library.
const data = projectData();
if (data.templatePresets.includes(design.slug)) errors.push(`TemplatePreset already has "${design.slug}": this design is imported already, or pick another slug`);
for (const asset of design.assets) {
    if (data.assetSlugs.includes(asset.slug)) errors.push(`library asset "${asset.slug}" already exists`);
}
const sameName = data.templates.find((template) => template.name === design.name);
if (sameName) warnings.push(`template name «${design.name}» is already used by ${sameName.slug}`);
if (errors.length) stop();

// 3. Where each asset goes. The seeder derives library placement and orientation from these folders.
const categoryFolder = { project_opening: 'opening', marriage_contract: 'marriage-contract' }[design.category] ?? design.category;
const folders = {};
for (const asset of design.assets) {
    const key = asset.key;
    if (asset.type === 'frame') folders[key] = `frames/${categoryFolder}`;
    else if (asset.type === 'background') folders[key] = `backgrounds/${categoryFolder}`;
    else if (asset.type === 'icon') folders[key] = `icons/${design.slug}`;
    else if (/divider/.test(key)) folders[key] = 'ornaments/dividers';
    else if (/corner/.test(key)) folders[key] = 'ornaments/corners';
    else if (/border/.test(key)) folders[key] = asset.height > asset.width ? 'ornaments/borders/vertical' : 'ornaments/borders';
    else if (/medallion|wreath|emblem|seal/.test(key)) folders[key] = 'ornaments/medallions';
    else folders[key] = `ornaments/${design.slug}`;
}
const copies = [];
for (const asset of design.assets) {
    copies.push({ from: path.join(designDir, asset.svg), to: path.join(PATHS.privateAssets, folders[asset.key], `${asset.slug}.svg`) });
    copies.push({ from: path.join(designDir, asset.preview), to: path.join(PATHS.publicAssetPreviews, folders[asset.key], `${asset.slug}.png`) });
}
const thumbnail = path.join(PATHS.templatePreviews, `${design.slug}.png`);
copies.push({ from: path.join(designDir, 'thumb.png'), to: thumbnail });
for (const copy of copies) {
    if (fs.existsSync(copy.to) && !force) errors.push(`${path.relative(PROJECT, copy.to)} already exists (use --force to replace it)`);
}
if (errors.length) stop();

// 4. PHP for the preset, the factory and the seeder.
const php = generatePhp(design, data, folders);
const factoryPath = path.join(PROJECT, 'app/Domains/Editor/Services/TemplateDesignFactory.php');
const factorySource = fs.readFileSync(factoryPath, 'utf8');
const missingUses = php.classesUsed.filter((name) => !factorySource.includes(`use ${name};`));
const snippets = `# Import snippets for ${design.slug} («${design.name}»)

Generated by import.mjs from design.json. Insert each block where it says, run Pint, then
\`php ${path.relative(PROJECT, path.join(SCRIPTS, 'verify-import.php'))} ${designDir} .\`

## 1. app/Domains/Editor/Enums/TemplatePreset.php — last case

\`\`\`php
${php.presetCase}
\`\`\`

## 2. app/Domains/Editor/Services/TemplateDesignFactory.php — last arm of the match in make()

\`\`\`php
${php.matchArm}
\`\`\`

## 3. TemplateDesignFactory.php — new method after the last template method, before document()

\`\`\`php
${php.factoryMethod}
\`\`\`
${missingUses.length ? `
## 3b. TemplateDesignFactory.php — use lines it does not have yet (keep them alphabetical)

\`\`\`php
${missingUses.map((name) => `use ${name};`).join('\n')}
\`\`\`
` : ''}
## 4. database/seeders/TemplateLibrarySeeder.php — end of the list in assetDefinitions(), before the noir-paper-frame entry or the closing bracket

\`\`\`php
${php.seederAssets}
\`\`\`

## 5. TemplateLibrarySeeder.php — last entry of templateDefinitions()

\`\`\`php
${php.seederTemplate}
\`\`\`

## 6. Files ${write ? 'copied' : 'to copy (run import.mjs with --write)'}

${copies.map((copy) => `- ${path.relative(PROJECT, copy.to)}`).join('\n')}
- ${path.relative(PROJECT, thumbnail).replace(/\.png$/, '.webp')} and .avif twins
`;
fs.mkdirSync(path.join(designDir, 'import'), { recursive: true });
fs.writeFileSync(path.join(designDir, 'import', 'SNIPPETS.md'), snippets);

// 5. Copy the files and make the preview twins.
if (write) {
    for (const copy of copies) {
        fs.mkdirSync(path.dirname(copy.to), { recursive: true });
        fs.copyFileSync(copy.from, copy.to);
    }
    const twin = (command, commandArgs, file) => {
        try {
            execFileSync(command, commandArgs, { stdio: 'pipe' });
            if (!fs.existsSync(file)) throw new Error('no output file');
        } catch (error) {
            warnings.push(`${path.basename(file)} not created (${command}: ${String(error.message).split('\n')[0]}); ThumbnailVariants works without it, but add it before release`);
        }
    };
    const webp = thumbnail.replace(/\.png$/, '.webp');
    const avif = thumbnail.replace(/\.png$/, '.avif');
    twin('cwebp', ['-quiet', '-q', '80', thumbnail, '-o', webp], webp);
    twin('avifenc', ['-q', '60', '-s', '6', thumbnail, avif], avif);
}

console.log(`${write ? 'IMPORTED FILES' : 'DRY RUN'}  ${design.slug}`);
console.log(`  snippets  ${path.join(designDir, 'import', 'SNIPPETS.md')}`);
for (const copy of copies) console.log(`  ${write ? 'copied ' : 'will copy'}  ${path.relative(PROJECT, copy.to)}`);
stop();
