// Compiles a designer-authored spec.json into design.json: a Methaq design document (the exact shape
// DesignDocumentHydrator accepts) plus metadata for the import. Asset ids are placeholders that verify-import.php
// and the seeder map to real library assets. All lists (fonts, verses, styles, slugs) come live from the project.
import fs from 'node:fs';
import path from 'node:path';
import { projectData } from './project.mjs';

const DATA = projectData();
const FONTS = DATA.fonts;
const VERSES = DATA.verses;
const CATEGORIES = DATA.categories.map((category) => category.value);

export const ROLES = ['background', 'surface', 'primaryText', 'secondaryText', 'accent', 'effect'];
const HEX = /^#[0-9a-fA-F]{6}$/;
const ASSET_TYPES = ['frame', 'background', 'decoration', 'icon'];

export function compileSpec(spec, designDir) {
    const errors = [];
    const warnings = [];
    const fail = (message) => errors.push(message);

    const color = (value, field) => {
        if (typeof value === 'string' && ROLES.includes(value)) return { source: 'palette', role: value };
        if (typeof value === 'string' && HEX.test(value)) return { source: 'literal', value: value.toLowerCase() };
        fail(`${field}: "${value}" is neither a palette role (${ROLES.join(', ')}) nor #RRGGBB`);
        return { source: 'literal', value: '#000000' };
    };
    const number = (value, field, fallback, min = -Infinity, max = Infinity) => {
        const resolved = value === undefined ? fallback : value;
        if (typeof resolved !== 'number' || !Number.isFinite(resolved) || resolved < min || resolved > max) {
            fail(`${field}: expected a number between ${min} and ${max}, got ${JSON.stringify(value)}`);
            return fallback ?? 0;
        }
        return resolved;
    };

    if (typeof spec.slug !== 'string' || !/^[a-z][a-z0-9-]{2,38}[a-z0-9]$/.test(spec.slug)) fail('slug: kebab-case, 4-40 characters, starts with a letter');
    else if (DATA.templatePresets.includes(spec.slug)) fail(`slug: "${spec.slug}" already exists in the Methaq library (TemplatePreset)`);
    if (typeof spec.name !== 'string' || spec.name.trim() === '' || spec.name.length > 40) fail('name: Arabic display name, 1-40 characters');
    else if (!/\p{Script=Arabic}/u.test(spec.name)) fail('name: must be Arabic');
    if (!CATEGORIES.includes(spec.category)) fail(`category: must be one of ${CATEGORIES.join(', ')}`);
    if (typeof spec.concept !== 'string' || spec.concept.trim().length < 20) fail('concept: write 1-3 sentences describing the idea and signature motif');

    const paletteValues = {};
    for (const role of ROLES) {
        const value = spec.palette?.[role];
        if (typeof value !== 'string' || !HEX.test(value)) fail(`palette.${role}: must be #RRGGBB`);
        paletteValues[role] = typeof value === 'string' ? value.toLowerCase() : '#000000';
    }

    // Assets: slug is prefixed with the design slug so library slugs stay unique.
    const assetEntries = Object.entries(spec.assets ?? {});
    const assetByKey = new Map();
    assetEntries.forEach(([key, asset], index) => {
        if (!/^[a-z][a-z0-9-]{0,24}$/.test(key)) fail(`assets.${key}: key must be kebab-case, up to 25 characters`);
        if (!ASSET_TYPES.includes(asset?.type)) fail(`assets.${key}.type: one of ${ASSET_TYPES.join(', ')}`);
        if (typeof asset?.name !== 'string' || asset.name.trim() === '') fail(`assets.${key}.name: Arabic name required`);
        if (typeof asset?.file !== 'string' || !asset.file.endsWith('.svg')) fail(`assets.${key}.file: path to an .svg inside the design folder`);
        else if (!fs.existsSync(path.join(designDir, asset.file))) fail(`assets.${key}.file: ${asset.file} does not exist`);
        const width = number(asset?.width, `assets.${key}.width`, undefined, 16, 4096);
        const height = number(asset?.height, `assets.${key}.height`, undefined, 16, 4096);
        if ((asset?.type === 'frame' || asset?.type === 'background') && asset.type === 'frame' && (width !== 1080 || height !== 1920)) {
            warnings.push(`assets.${key}: frames are 1080x1920 in the library (got ${width}x${height})`);
        }
        if (DATA.assetSlugs.includes(`${spec.slug}-${key}`)) fail(`assets.${key}: library asset slug "${spec.slug}-${key}" already exists`);
        const png = asset?.file?.replace(/\.svg$/, '.png');
        assetByKey.set(key, {
            key,
            slug: `${spec.slug}-${key}`,
            name: asset?.name,
            type: asset?.type,
            svg: asset?.file,
            preview: png,
            width,
            height,
            placeholderId: String(9_000_000 + index + 1),
        });
    });

    const fontsUsed = new Set();
    const expectations = {};
    const layers = [];
    const seenIds = new Set();
    let verseId = null;
    for (const [index, layer] of (spec.layers ?? []).entries()) {
        const at = `layers[${index}]${layer?.id ? ` (${layer.id})` : ''}`;
        if (typeof layer?.id !== 'string' || !/^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/.test(layer.id)) {
            fail(`${at}.id: letters, digits, _ or -`);
            continue;
        }
        if (seenIds.has(layer.id)) fail(`${at}: duplicate layer id`);
        seenIds.add(layer.id);
        const frame = {
            x: number(layer.x, `${at}.x`, undefined, -4096, 4096),
            y: number(layer.y, `${at}.y`, undefined, -4096, 4096),
            width: number(layer.w, `${at}.w`, undefined, 1, 4096),
            height: number(layer.h, `${at}.h`, undefined, 1, 4096),
            scaleX: number(layer.scaleX, `${at}.scaleX`, 1, 0.05, 20),
            scaleY: number(layer.scaleY, `${at}.scaleY`, 1, 0.05, 20),
            rotation: number(layer.rotation, `${at}.rotation`, 0, -360, 360),
            opacity: number(layer.opacity, `${at}.opacity`, 1, 0, 1),
        };
        const visible = layer.visible !== false;

        if (layer.kind === 'image') {
            const asset = assetByKey.get(layer.asset);
            if (!asset) {
                fail(`${at}.asset: "${layer.asset}" is not declared in assets`);
                continue;
            }
            layers.push({
                id: layer.id,
                type: 'image',
                frame,
                locked: layer.locked ?? (asset.type === 'frame' || asset.type === 'background'),
                visible,
                asset: { assetId: asset.placeholderId, version: 1 },
                fit: layer.fit === 'cover' ? 'cover' : 'contain',
            });
        } else if (layer.kind === 'text' || layer.kind === 'verse') {
            const fontKey = layer.font ?? (layer.kind === 'verse' ? 'font-amiri-quran' : undefined);
            const font = FONTS[fontKey];
            if (!font) {
                fail(`${at}.font: unknown library font "${fontKey}" (${Object.keys(FONTS).join(', ')})`);
                continue;
            }
            fontsUsed.add(fontKey);
            if (layer.expectLines !== undefined) expectations[layer.id] = number(layer.expectLines, `${at}.expectLines`, 1, 1, 40);
            let content = layer.text;
            if (layer.kind === 'verse') {
                const verse = VERSES[layer.verse];
                if (!verse) {
                    fail(`${at}.verse: unknown verse id "${layer.verse}" (${Object.keys(VERSES).join(', ')})`);
                    continue;
                }
                content = verse.content;
                verseId = layer.verse;
                if (verse.categories.length > 0 && !verse.categories.includes(spec.category)) {
                    warnings.push(`${at}.verse: ${layer.verse} is suggested for ${verse.categories.join(', ')}, not ${spec.category}`);
                }
            }
            if (typeof content !== 'string' || content === '' || content.length > 2000) fail(`${at}.text: 1-2000 characters`);
            else if (/<\s*\/?\s*[a-z][^>]*>|javascript\s*:/i.test(content)) fail(`${at}.text: markup is not allowed`);
            if (layer.kind === 'text' && typeof content === 'string' && content.includes('﴿')) {
                fail(`${at}: Quran text must use kind "verse" with a TemplateScripture id, never typed text`);
            }
            const language = layer.kind === 'verse' ? 'ar' : (layer.lang ?? (font.languages.includes('ar') ? 'ar' : 'en'));
            if (!['ar', 'en', 'mixed'].includes(language)) fail(`${at}.lang: ar, en or mixed`);
            if (language !== 'en' && !font.languages.includes('ar') && /\p{Script=Arabic}/u.test(String(content))) {
                fail(`${at}: font ${fontKey} has no Arabic glyphs`);
            }
            const direction = layer.dir ?? (language === 'en' ? 'ltr' : 'rtl');
            if (!['rtl', 'ltr', 'auto'].includes(direction)) fail(`${at}.dir: rtl, ltr or auto`);
            const alignment = layer.align ?? 'center';
            if (!['left', 'center', 'right'].includes(alignment)) fail(`${at}.align: left, center or right`);
            const weight = number(layer.weight, `${at}.weight`, 400, 100, 900);
            if (weight % 100 !== 0) fail(`${at}.weight: steps of 100`);
            layers.push({
                id: layer.id,
                type: 'text',
                frame,
                locked: layer.locked ?? false,
                visible,
                content: content ?? '',
                language,
                direction,
                alignment,
                font: { assetId: font.placeholderId, version: 1 },
                fontSize: number(layer.size, `${at}.size`, undefined, 8, 512),
                fontWeight: weight,
                fill: color(layer.fill ?? 'primaryText', `${at}.fill`),
            });
        } else if (layer.kind === 'shape') {
            if (!['rectangle', 'ellipse', 'line'].includes(layer.shape)) fail(`${at}.shape: rectangle, ellipse or line`);
            layers.push({
                id: layer.id,
                type: 'shape',
                frame,
                locked: layer.locked ?? true,
                visible,
                shapeKind: layer.shape,
                fill: layer.fill === undefined || layer.fill === null ? null : color(layer.fill, `${at}.fill`),
                stroke: layer.stroke === undefined || layer.stroke === null ? null : color(layer.stroke, `${at}.stroke`),
                strokeWidth: number(layer.strokeWidth, `${at}.strokeWidth`, 0, 0, 64),
            });
        } else {
            fail(`${at}.kind: image, text, verse or shape`);
        }
    }
    if (layers.length === 0) fail('layers: at least one layer is required');
    if (layers.length > 200) fail('layers: at most 200');
    if (!layers.some((layer) => layer.type === 'text')) fail('layers: a card needs text layers');
    if (verseId === null) warnings.push('no verse layer: every library template carries a Tanzil verse (use kind "verse")');

    // Scene
    const scene = spec.scene ?? {};
    const opening = scene.opening ?? 'envelope';
    let openingJson;
    if (opening === 'envelope') {
        const envelope = scene.envelope ?? {};
        const monogram = String(envelope.monogram ?? '');
        if (monogram.length > 12) fail('scene.envelope.monogram: at most 12 characters');
        const appearanceKeys = ['style', 'sealStyle', 'sealX', 'sealY', 'sealSize'];
        const wantsAppearance = appearanceKeys.some((key) => envelope[key] !== undefined);
        if (wantsAppearance) {
            if (!DATA.envelopeStyles.includes(envelope.style ?? 'classic')) fail(`scene.envelope.style: ${DATA.envelopeStyles.join(', ')}`);
            if (!DATA.sealStyles.includes(envelope.sealStyle ?? 'wax')) fail(`scene.envelope.sealStyle: ${DATA.sealStyles.join(', ')}`);
        }
        openingJson = {
            type: 'envelope',
            durationMs: Math.round(number(scene.durationMs, 'scene.durationMs', 1700, 100, 5000)),
            envelope: {
                presetId: 'classic-fold',
                presetVersion: 1,
                paperColor: color(envelope.paper ?? 'accent', 'scene.envelope.paper'),
                liningColor: color(envelope.lining ?? 'surface', 'scene.envelope.lining'),
                sealColor: color(envelope.seal ?? 'accent', 'scene.envelope.seal'),
                monogram,
                ...(wantsAppearance ? {
                    appearance: {
                        style: envelope.style ?? 'classic',
                        sealStyle: envelope.sealStyle ?? 'wax',
                        sealX: Math.round(number(envelope.sealX, 'scene.envelope.sealX', 50, 20, 80)),
                        sealY: Math.round(number(envelope.sealY, 'scene.envelope.sealY', 52, 25, 75)),
                        sealSize: Math.round(number(envelope.sealSize, 'scene.envelope.sealSize', 20, 14, 26)),
                    },
                } : {}),
            },
        };
    } else if (opening === 'fade') {
        openingJson = { type: 'fade', durationMs: Math.round(number(scene.durationMs, 'scene.durationMs', 900, 100, 5000)) };
    } else if (opening === 'direct') {
        openingJson = { type: 'direct', durationMs: 0 };
    } else {
        fail('scene.opening: envelope, fade or direct');
        openingJson = { type: 'direct', durationMs: 0 };
    }

    const effectConfig = scene.effects ?? {};
    const effect = (id, defaults, limits) => {
        const config = effectConfig[id] ?? {};
        return {
            effectId: id,
            effectVersion: 1,
            enabled: config.enabled ?? defaults.enabled,
            color: color(config.color ?? 'effect', `scene.effects.${id}.color`),
            intensity: number(config.intensity, `scene.effects.${id}.intensity`, defaults.intensity, limits.intensity[0], limits.intensity[1]),
            speed: number(config.speed, `scene.effects.${id}.speed`, defaults.speed, limits.speed[0], limits.speed[1]),
        };
    };
    let audio = { enabled: false, asset: null, volume: 0.6 };
    if (scene.audio) {
        const track = DATA.templateAudio[scene.audio.track];
        if (!track) fail(`scene.audio.track: one of ${Object.keys(DATA.templateAudio).join(', ')} (the only tracks the template factory can reference), or null`);
        else if (!track.categories.includes(spec.category)) warnings.push(`scene.audio.track: ${scene.audio.track} is meant for ${track.categories.join(', ')}`);
        else audio = { enabled: true, asset: { assetId: track.placeholderId, version: 1 }, volume: number(scene.audio.volume, 'scene.audio.volume', 0.6, 0, 1) };
    }
    const sceneJson = {
        ...(scene.cover ? {
            cover: {
                heading: String(scene.cover.heading ?? ''),
                names: String(scene.cover.names ?? ''),
                dateLabel: String(scene.cover.dateLabel ?? ''),
                message: String(scene.cover.message ?? ''),
                language: scene.cover.language ?? 'ar',
                decoration: scene.cover.decoration ?? 'none',
                animateText: Boolean(scene.cover.animateText),
            },
        } : {}),
        ...(scene.backdrop ? { backdrop: { preset: scene.backdrop } } : {}),
        sceneSchemaVersion: 1,
        opening: openingJson,
        effects: [
            effect('sparkle', { enabled: true, intensity: 0.52, speed: 0.9 }, { intensity: [0, 1], speed: [0.25, 2] }),
            effect('smoke', { enabled: false, intensity: 0.28, speed: 0.65 }, { intensity: [0, 0.8], speed: [0.25, 1.5] }),
        ],
        audio,
        motionPolicy: 'system',
    };
    if (scene.backdrop && !DATA.backdrops.includes(scene.backdrop)) fail(`scene.backdrop: ${DATA.backdrops.join(', ')}`);
    if (scene.cover && !DATA.coverDecorations.includes(scene.cover.decoration ?? 'none')) fail(`scene.cover.decoration: ${DATA.coverDecorations.join(', ')}`);

    const canvasSpec = spec.canvas ?? {};
    let material;
    if (canvasSpec.material) {
        if (!DATA.materials.includes(canvasSpec.material.preset)) fail(`canvas.material.preset: ${DATA.materials.join(', ')}`);
        material = { preset: canvasSpec.material.preset, version: 1, intensity: number(canvasSpec.material.intensity, 'canvas.material.intensity', 0.65, 0, 1) };
    }

    const document = {
        schemaVersion: 1,
        canvas: {
            schemaVersion: 1,
            width: 1080,
            height: 1920,
            background: color(canvasSpec.background ?? 'background', 'canvas.background'),
            layers,
            ...(material ? { material } : {}),
        },
        palette: { schemaVersion: 1, values: paletteValues },
        scene: sceneJson,
    };

    const design = {
        format: 'methaq-template-pack/1',
        slug: spec.slug,
        name: spec.name,
        category: spec.category,
        concept: spec.concept,
        tags: Array.isArray(spec.tags) ? spec.tags : [],
        verse: verseId,
        fonts: [...fontsUsed].sort(),
        assets: [...assetByKey.values()],
        library: {
            fonts: Object.fromEntries([...fontsUsed].map((key) => [key, FONTS[key].placeholderId])),
            audio: scene.audio ? { [scene.audio.track]: DATA.templateAudio[scene.audio.track]?.placeholderId } : {},
        },
        files: { preview: 'preview.png', thumb: 'thumb.png', card: 'card.jpg' },
        expectations,
        notes: typeof spec.notes === 'string' ? spec.notes : '',
        document,
    };

    return { design: errors.length === 0 ? design : null, errors, warnings, fonts: FONTS };
}
