// Where things are: the Methaq project (found by walking up from this skill), the design workspace outside git,
// and the live lists a design must respect, read from the project's own PHP classes by project-data.php.
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export const SCRIPTS = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
export const SKILL = path.resolve(SCRIPTS, '..');

function findProjectRoot() {
    if (process.env.METHAQ_PROJECT) return path.resolve(process.env.METHAQ_PROJECT);
    for (let directory = SCRIPTS; ; directory = path.dirname(directory)) {
        if (fs.existsSync(path.join(directory, 'artisan')) && fs.existsSync(path.join(directory, 'composer.json'))) return directory;
        if (path.dirname(directory) === directory) throw new Error('No Methaq project above the skill folder; set METHAQ_PROJECT');
    }
}

export const PROJECT = findProjectRoot();

/** Drafts live outside git, next to the project, until the user approves an import. */
export const WORKSPACE = path.resolve(process.env.METHAQ_DESIGN_WORKSPACE ?? path.join(PROJECT, '..', 'Methaq-Template-Designs', 'designs'));

export const PATHS = {
    fabricDist: path.join(PROJECT, 'node_modules', 'fabric', 'dist'),
    fonts: path.join(PROJECT, 'storage', 'app', 'private', 'editor', 'assets', 'v1', 'fonts'),
    materials: path.join(PROJECT, 'public', 'editor', 'materials', 'v1'),
    templatePreviews: path.join(PROJECT, 'public', 'editor', 'previews', 'templates'),
    privateAssets: path.join(PROJECT, 'storage', 'app', 'private', 'editor', 'assets', 'v1'),
    publicAssetPreviews: path.join(PROJECT, 'public', 'editor', 'previews', 'assets', 'v1'),
};

let cached;

/** fonts, templateAudio, verses, phrases, categories, templates, templatePresets, assetSlugs, envelope/seal styles, backdrops, materials. */
export function projectData() {
    if (!cached) {
        const output = execFileSync('php', [path.join(SCRIPTS, 'project-data.php'), PROJECT], { encoding: 'utf8' });
        cached = JSON.parse(output);
    }
    return cached;
}

/** Every design folder in the workspace: { dir, category, slug }. */
export function workspaceDesigns() {
    const designs = [];
    if (!fs.existsSync(WORKSPACE)) return designs;
    for (const category of fs.readdirSync(WORKSPACE)) {
        const categoryDir = path.join(WORKSPACE, category);
        if (!fs.statSync(categoryDir).isDirectory()) continue;
        for (const entry of fs.readdirSync(categoryDir)) {
            const specPath = path.join(categoryDir, entry, 'spec.json');
            if (!fs.existsSync(specPath)) continue;
            try {
                designs.push({ dir: path.join(categoryDir, entry), category, slug: JSON.parse(fs.readFileSync(specPath, 'utf8')).slug });
            } catch {
                // an unreadable spec belongs to a design that is being written
            }
        }
    }
    return designs;
}
