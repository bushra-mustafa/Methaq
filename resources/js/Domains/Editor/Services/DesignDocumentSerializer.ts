import type { CanvasData } from '../../../Types/CanvasData';
import type { DesignDocument, DesignSnapshot, SaveDesignPayload } from '../../../Types/DesignDocument';
import type {
    AssetReference,
    ImageFit,
    Language,
    Layer,
    LayerFrame,
    ShapeKind,
    TextAlignment,
    TextDirection,
} from '../../../Types/Layer';
import { PALETTE_ROLES, type ColorValue, type HexColor, type Palette, type PaletteRole } from '../../../Types/Palette.ts';
import type {
    AudioConfig,
    EffectConfig,
    EffectId,
    EnvelopeConfig,
    EnvelopeAppearance,
    EnvelopePresetId,
    MotionPolicy,
    OpeningConfig,
    SceneConfig,
} from '../../../Types/SceneConfig';
import { DESIGN_CONTRACT, EFFECT_REGISTRY, ENVELOPE_REGISTRY } from './designContract.ts';

const IDENTIFIER_PATTERN = /^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/;
const ASSET_ID_PATTERN = /^[1-9][0-9]{0,19}$/;
const HEX_COLOR_PATTERN = /^#[0-9a-f]{6}$/i;

export class DesignContractError extends Error {
    public readonly path: string;

    constructor(path: string, message: string) {
        super(`${path}: ${message}`);
        this.path = path;
        this.name = 'DesignContractError';
    }
}

function record(value: unknown, path: string): Record<string, unknown> {
    if (typeof value !== 'object' || value === null || Array.isArray(value)) {
        throw new DesignContractError(path, 'يجب أن تكون القيمة كائناً.');
    }

    return value as Record<string, unknown>;
}

function stringValue(value: unknown, path: string, maximumLength: number): string {
    if (typeof value !== 'string' || Array.from(value).length > maximumLength) {
        throw new DesignContractError(path, `يجب أن يكون نصاً لا يتجاوز ${maximumLength} حرفاً.`);
    }

    return value;
}

function booleanValue(value: unknown, path: string): boolean {
    if (typeof value !== 'boolean') throw new DesignContractError(path, 'يجب أن تكون القيمة منطقية.');

    return value;
}

function numberValue(value: unknown, path: string, minimum: number, maximum: number): number {
    if (typeof value !== 'number' || !Number.isFinite(value) || value < minimum || value > maximum) {
        throw new DesignContractError(path, `يجب أن يكون رقماً بين ${minimum} و${maximum}.`);
    }

    return value;
}

function integerValue(value: unknown, path: string, minimum: number, maximum: number): number {
    const parsed = numberValue(value, path, minimum, maximum);
    if (!Number.isInteger(parsed)) throw new DesignContractError(path, 'يجب أن يكون عدداً صحيحاً.');

    return parsed;
}

function literal<T extends string | number>(value: unknown, allowed: readonly T[], path: string): T {
    if ((typeof value !== 'string' && typeof value !== 'number') || !allowed.includes(value as T)) {
        throw new DesignContractError(path, `القيمة غير مدعومة: ${String(value)}.`);
    }

    return value as T;
}

function parseHexColor(value: unknown, path: string): HexColor {
    if (typeof value !== 'string' || !HEX_COLOR_PATTERN.test(value)) {
        throw new DesignContractError(path, 'يجب أن يكون اللون بصيغة #RRGGBB.');
    }

    return value.toLowerCase() as HexColor;
}

function parseColorValue(value: unknown, path: string): ColorValue {
    const color = record(value, path);
    const source = literal(color.source, ['palette', 'literal'] as const, `${path}.source`);

    if (source === 'palette') {
        return { source, role: literal(color.role, PALETTE_ROLES, `${path}.role`) };
    }

    return { source, value: parseHexColor(color.value, `${path}.value`) };
}

function parseAssetReference(value: unknown, path: string): AssetReference {
    const asset = record(value, path);
    const assetId = stringValue(asset.assetId, `${path}.assetId`, 20);
    if (!ASSET_ID_PATTERN.test(assetId)) {
        throw new DesignContractError(`${path}.assetId`, 'يجب أن يكون معرّف أصل داخلياً موجباً.');
    }

    return {
        assetId,
        version: integerValue(asset.version, `${path}.version`, 1, DESIGN_CONTRACT.maximumAssetVersion),
    };
}

function parseFrame(value: unknown, path: string): LayerFrame {
    const frame = record(value, path);
    const coordinateLimit = DESIGN_CONTRACT.maximumCanvasDimension;

    return {
        x: numberValue(frame.x, `${path}.x`, -coordinateLimit, coordinateLimit),
        y: numberValue(frame.y, `${path}.y`, -coordinateLimit, coordinateLimit),
        width: numberValue(frame.width, `${path}.width`, 1, coordinateLimit),
        height: numberValue(frame.height, `${path}.height`, 1, coordinateLimit),
        scaleX: numberValue(frame.scaleX, `${path}.scaleX`, 0.05, 20),
        scaleY: numberValue(frame.scaleY, `${path}.scaleY`, 0.05, 20),
        rotation: numberValue(frame.rotation, `${path}.rotation`, -360, 360),
        opacity: numberValue(frame.opacity, `${path}.opacity`, 0, 1),
    };
}

function parseLayer(value: unknown, index: number): Layer {
    const path = `canvas.layers.${index}`;
    const layer = record(value, path);
    const id = stringValue(layer.id, `${path}.id`, 64);
    if (!IDENTIFIER_PATTERN.test(id)) throw new DesignContractError(`${path}.id`, 'معرّف الطبقة غير صالح.');

    const common = {
        id,
        frame: parseFrame(layer.frame, `${path}.frame`),
        locked: booleanValue(layer.locked, `${path}.locked`),
        visible: booleanValue(layer.visible, `${path}.visible`),
    };
    const type = literal(layer.type, ['text', 'image', 'shape'] as const, `${path}.type`);

    if (type === 'text') {
        const fontWeight = integerValue(layer.fontWeight, `${path}.fontWeight`, 100, 900);
        if (fontWeight % 100 !== 0) {
            throw new DesignContractError(`${path}.fontWeight`, 'يجب أن يتدرج وزن الخط بمقدار 100.');
        }

        return {
            ...common,
            type,
            content: stringValue(layer.content, `${path}.content`, DESIGN_CONTRACT.maximumTextLength),
            language: literal<Language>(layer.language, ['ar', 'en', 'mixed'], `${path}.language`),
            direction: literal<TextDirection>(layer.direction, ['rtl', 'ltr', 'auto'], `${path}.direction`),
            alignment: literal<TextAlignment>(layer.alignment, ['left', 'center', 'right'], `${path}.alignment`),
            font: parseAssetReference(layer.font, `${path}.font`),
            fontSize: numberValue(layer.fontSize, `${path}.fontSize`, 8, 512),
            fontWeight,
            fill: parseColorValue(layer.fill, `${path}.fill`),
        };
    }

    if (type === 'image') {
        return {
            ...common,
            type,
            asset: parseAssetReference(layer.asset, `${path}.asset`),
            fit: literal<ImageFit>(layer.fit, ['contain', 'cover'], `${path}.fit`),
        };
    }

    return {
        ...common,
        type,
        shapeKind: literal<ShapeKind>(layer.shapeKind, ['rectangle', 'ellipse', 'line'], `${path}.shapeKind`),
        fill: layer.fill === null ? null : parseColorValue(layer.fill, `${path}.fill`),
        stroke: layer.stroke === null ? null : parseColorValue(layer.stroke, `${path}.stroke`),
        strokeWidth: numberValue(layer.strokeWidth, `${path}.strokeWidth`, 0, 64),
    };
}

function parseCanvas(value: unknown): CanvasData {
    const canvas = record(value, 'canvas');
    if (!Array.isArray(canvas.layers) || canvas.layers.length > DESIGN_CONTRACT.maximumLayers) {
        throw new DesignContractError('canvas.layers', `يجب ألا يتجاوز عدد الطبقات ${DESIGN_CONTRACT.maximumLayers}.`);
    }

    const layers = canvas.layers.map(parseLayer);
    if (new Set(layers.map((layer) => layer.id)).size !== layers.length) {
        throw new DesignContractError('canvas.layers', 'معرّفات الطبقات يجب أن تكون فريدة.');
    }

    return {
        schemaVersion: literal(canvas.schemaVersion, [1] as const, 'canvas.schemaVersion'),
        width: literal(canvas.width, [DESIGN_CONTRACT.canvasWidth] as const, 'canvas.width'),
        height: literal(canvas.height, [DESIGN_CONTRACT.canvasHeight] as const, 'canvas.height'),
        background: parseColorValue(canvas.background, 'canvas.background'),
        layers,
    };
}

function parsePalette(value: unknown): Palette {
    const palette = record(value, 'palette');
    const values = record(palette.values, 'palette.values');
    const parsedValues = {} as Record<PaletteRole, HexColor>;
    for (const role of PALETTE_ROLES) parsedValues[role] = parseHexColor(values[role], `palette.values.${role}`);

    return {
        schemaVersion: literal(palette.schemaVersion, [1] as const, 'palette.schemaVersion'),
        values: parsedValues,
    };
}

function parseEnvelopeAppearance(value: unknown, path: string): EnvelopeAppearance {
    const appearance = record(value, path);
    return {
        style: literal(appearance.style, ['classic', 'luxury', 'minimal', 'rounded', 'gatefold'] as const, `${path}.style`),
        sealStyle: literal(appearance.sealStyle, ['wax', 'medallion', 'methaq'] as const, `${path}.sealStyle`),
        sealX: integerValue(appearance.sealX, `${path}.sealX`, 20, 80),
        sealY: integerValue(appearance.sealY, `${path}.sealY`, 25, 75),
        sealSize: integerValue(appearance.sealSize, `${path}.sealSize`, 14, 26),
    };
}

function parseEnvelope(value: unknown, path: string): EnvelopeConfig {
    const envelope = record(value, path);
    const presetId = literal<EnvelopePresetId>(envelope.presetId, ['classic-fold'], `${path}.presetId`);
    const capability = ENVELOPE_REGISTRY[presetId];

    return {
        ...(envelope.appearance === undefined ? {} : { appearance: parseEnvelopeAppearance(envelope.appearance, `${path}.appearance`) }),
        presetId,
        presetVersion: literal(envelope.presetVersion, [capability.version] as const, `${path}.presetVersion`),
        paperColor: parseColorValue(envelope.paperColor, `${path}.paperColor`),
        liningColor: parseColorValue(envelope.liningColor, `${path}.liningColor`),
        sealColor: parseColorValue(envelope.sealColor, `${path}.sealColor`),
        monogram: stringValue(envelope.monogram, `${path}.monogram`, capability.maximumMonogramLength),
    };
}

function parseOpening(value: unknown): OpeningConfig {
    const opening = record(value, 'scene.opening');
    const type = literal(opening.type, ['direct', 'fade', 'envelope'] as const, 'scene.opening.type');
    if (type === 'direct') {
        return { type, durationMs: literal(opening.durationMs, [0] as const, 'scene.opening.durationMs') };
    }

    const durationMs = integerValue(opening.durationMs, 'scene.opening.durationMs', 100, 5000);
    if (type === 'fade') return { type, durationMs };

    return { type, durationMs, envelope: parseEnvelope(opening.envelope, 'scene.opening.envelope') };
}

function parseEffect(value: unknown, index: number): EffectConfig {
    const path = `scene.effects.${index}`;
    const effect = record(value, path);
    const effectId = literal<EffectId>(effect.effectId, ['sparkle', 'smoke'], `${path}.effectId`);
    const capability = EFFECT_REGISTRY[effectId];

    return {
        effectId,
        effectVersion: literal(effect.effectVersion, [capability.version] as const, `${path}.effectVersion`),
        enabled: booleanValue(effect.enabled, `${path}.enabled`),
        color: parseColorValue(effect.color, `${path}.color`),
        intensity: numberValue(effect.intensity, `${path}.intensity`, ...capability.intensity),
        speed: numberValue(effect.speed, `${path}.speed`, ...capability.speed),
    };
}

function parseAudio(value: unknown): AudioConfig {
    const audio = record(value, 'scene.audio');
    const enabled = booleanValue(audio.enabled, 'scene.audio.enabled');
    const asset = audio.asset === null ? null : parseAssetReference(audio.asset, 'scene.audio.asset');
    if (enabled && asset === null) throw new DesignContractError('scene.audio.asset', 'الصوت المفعّل يحتاج أصلاً صوتياً.');

    return {
        enabled,
        asset,
        volume: numberValue(audio.volume, 'scene.audio.volume', 0, 1),
    };
}

function parseCover(value: unknown): NonNullable<SceneConfig['cover']> {
    const cover = record(value, 'scene.cover');
    const text = (key: string, limit: number): string => {
        const content = stringValue(cover[key], `scene.cover.${key}`, limit);
        if (/<[^>]*>/.test(content)) throw new DesignContractError(`scene.cover.${key}`, 'النص فقط مسموح.');
        return content;
    };
    return {
        heading: text('heading', 120), names: text('names', 160), dateLabel: text('dateLabel', 80), message: text('message', 500),
        language: literal(cover.language, ['ar', 'en', 'mixed'] as const, 'scene.cover.language'),
        decoration: literal(cover.decoration, ['none', 'floral', 'halo'] as const, 'scene.cover.decoration'),
        animateText: booleanValue(cover.animateText, 'scene.cover.animateText'),
    };
}

function parseBackdrop(value: unknown): NonNullable<SceneConfig['backdrop']> {
    const backdrop = record(value, 'scene.backdrop');
    return { preset: literal(backdrop.preset, ['inherit', 'burgundy-nebula', 'blush-cloud', 'midnight-gold', 'emerald-silk'] as const, 'scene.backdrop.preset') };
}

function parseScene(value: unknown): SceneConfig {
    const scene = record(value, 'scene');
    if (!Array.isArray(scene.effects) || scene.effects.length > DESIGN_CONTRACT.maximumEffects) {
        throw new DesignContractError('scene.effects', `يجب ألا يتجاوز عدد المؤثرات ${DESIGN_CONTRACT.maximumEffects}.`);
    }

    const effects = scene.effects.map(parseEffect);
    if (new Set(effects.map((effect) => effect.effectId)).size !== effects.length) {
        throw new DesignContractError('scene.effects', 'لا يمكن تكرار المؤثر نفسه.');
    }

    return {
        ...(scene.cover === undefined ? {} : { cover: parseCover(scene.cover) }),
        ...(scene.backdrop === undefined ? {} : { backdrop: parseBackdrop(scene.backdrop) }),
        sceneSchemaVersion: literal(scene.sceneSchemaVersion, [1] as const, 'scene.sceneSchemaVersion'),
        opening: parseOpening(scene.opening),
        effects,
        audio: parseAudio(scene.audio),
        motionPolicy: literal<MotionPolicy>(scene.motionPolicy, ['system', 'reduced', 'off'], 'scene.motionPolicy'),
    };
}

function assertMaximumDepth(value: unknown): void {
    const visited = new WeakSet<object>();
    function visit(node: unknown, depth: number): void {
        if (depth > DESIGN_CONTRACT.maximumDepth) {
            throw new DesignContractError('document', `عمق البيانات يتجاوز ${DESIGN_CONTRACT.maximumDepth}.`);
        }
        if (typeof node !== 'object' || node === null) return;
        if (visited.has(node)) throw new DesignContractError('document', 'البيانات الدائرية غير مدعومة.');
        visited.add(node);
        const children: unknown[] = Array.isArray(node) ? node : Object.values(node);
        children.forEach((child) => visit(child, depth + 1));
        visited.delete(node);
    }
    visit(value, 1);
}

export function parseDesignDocument(value: unknown): DesignDocument {
    assertMaximumDepth(value);
    const document = record(value, 'document');

    return {
        schemaVersion: literal(document.schemaVersion, [DESIGN_CONTRACT.schemaVersion] as const, 'schemaVersion'),
        canvas: parseCanvas(document.canvas),
        palette: parsePalette(document.palette),
        scene: parseScene(document.scene),
    };
}

export function parseDesignDocumentJson(serialized: string): DesignDocument {
    if (new TextEncoder().encode(serialized).byteLength > DESIGN_CONTRACT.maximumJsonBytes) {
        throw new DesignContractError('document', `حجم البيانات يتجاوز ${DESIGN_CONTRACT.maximumJsonBytes} بايت.`);
    }

    let decoded: unknown;
    try {
        decoded = JSON.parse(serialized) as unknown;
    } catch {
        throw new DesignContractError('document', 'JSON غير صالح.');
    }

    return parseDesignDocument(decoded);
}

export function serializeDesignDocument(document: DesignDocument): string {
    const serialized = JSON.stringify(parseDesignDocument(document));
    if (new TextEncoder().encode(serialized).byteLength > DESIGN_CONTRACT.maximumJsonBytes) {
        throw new DesignContractError('document', `حجم البيانات يتجاوز ${DESIGN_CONTRACT.maximumJsonBytes} بايت.`);
    }

    return serialized;
}

export function parseSaveDesignPayload(value: unknown): SaveDesignPayload {
    const payload = record(value, 'payload');

    return {
        document: parseDesignDocument(payload.document),
        expectedRevision: integerValue(payload.expectedRevision, 'expectedRevision', 1, 4_294_967_295),
    };
}

export function parseDesignSnapshot(value: unknown): DesignSnapshot {
    const snapshot = record(value, 'snapshot');

    return {
        document: parseDesignDocument(snapshot.document),
        revision: integerValue(snapshot.revision, 'revision', 1, 4_294_967_295),
    };
}
