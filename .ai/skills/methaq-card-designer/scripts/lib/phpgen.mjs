// Turns a compiled design.json into the PHP the library needs: a TemplatePreset case, a TemplateDesignFactory method
// and TemplateLibrarySeeder definitions. Layers that fit the factory's helpers (text/image/shape, envelopeScene,
// fadeScene, document) use them like the existing templates; anything else is written as explicit DTOs.
// verify-import.php then proves that the factory builds exactly this design.json.

const ROLE = { background: 'Background', surface: 'Surface', primaryText: 'PrimaryText', secondaryText: 'SecondaryText', accent: 'Accent', effect: 'Effect' };
const LANGUAGE = { ar: 'Arabic', en: 'English', mixed: 'Mixed' };
const DIRECTION = { rtl: 'RightToLeft', ltr: 'LeftToRight', auto: 'Automatic' };
const ALIGNMENT = { left: 'Left', center: 'Center', right: 'Right' };
const SHAPE = { rectangle: 'Rectangle', ellipse: 'Ellipse', line: 'Line' };
const FIT = { contain: 'Contain', cover: 'Cover' };
const OPENING = { envelope: 'Envelope', fade: 'Fade', direct: 'Direct' };
const EFFECT = { sparkle: 'Sparkle', smoke: 'Smoke' };
const MOTION = { system: 'System', reduced: 'Reduced', off: 'Off' };
const ASSET_TYPE = { frame: 'Frame', background: 'Background', decoration: 'Decoration', icon: 'Icon' };

/** Short class name → fully qualified name, for the `use` lines the factory may be missing. */
const CLASSES = {
    AudioData: 'App\\Domains\\Editor\\DTOs\\AudioData',
    CanvasData: 'App\\Domains\\Editor\\DTOs\\CanvasData',
    CardMaterialData: 'App\\Domains\\Editor\\DTOs\\CardMaterialData',
    CardMaterialPreset: 'App\\Domains\\Editor\\Enums\\CardMaterialPreset',
    ColorValueData: 'App\\Domains\\Editor\\DTOs\\ColorValueData',
    DesignDocumentData: 'App\\Domains\\Editor\\DTOs\\DesignDocumentData',
    DesignSchema: 'App\\Domains\\Editor\\Contracts\\DesignSchema',
    EffectData: 'App\\Domains\\Editor\\DTOs\\EffectData',
    EffectId: 'App\\Domains\\Editor\\Enums\\EffectId',
    EnvelopeAppearanceData: 'App\\Domains\\Editor\\DTOs\\EnvelopeAppearanceData',
    EnvelopeData: 'App\\Domains\\Editor\\DTOs\\EnvelopeData',
    EnvelopePresetId: 'App\\Domains\\Editor\\Enums\\EnvelopePresetId',
    ImageFit: 'App\\Domains\\Editor\\Enums\\ImageFit',
    ImageLayerData: 'App\\Domains\\Editor\\DTOs\\ImageLayerData',
    InvitationCoverData: 'App\\Domains\\Editor\\DTOs\\InvitationCoverData',
    Language: 'App\\Domains\\Editor\\Enums\\Language',
    LayerData: 'App\\Domains\\Editor\\DTOs\\LayerData',
    LayerFrameData: 'App\\Domains\\Editor\\DTOs\\LayerFrameData',
    LayerType: 'App\\Domains\\Editor\\Enums\\LayerType',
    MotionPolicy: 'App\\Domains\\Editor\\Enums\\MotionPolicy',
    OpeningData: 'App\\Domains\\Editor\\DTOs\\OpeningData',
    OpeningType: 'App\\Domains\\Editor\\Enums\\OpeningType',
    PaletteData: 'App\\Domains\\Editor\\DTOs\\PaletteData',
    PaletteRole: 'App\\Domains\\Editor\\Enums\\PaletteRole',
    SceneBackdropData: 'App\\Domains\\Editor\\DTOs\\SceneBackdropData',
    SceneConfigData: 'App\\Domains\\Editor\\DTOs\\SceneConfigData',
    ShapeKind: 'App\\Domains\\Editor\\Enums\\ShapeKind',
    ShapeLayerData: 'App\\Domains\\Editor\\DTOs\\ShapeLayerData',
    TextAlignment: 'App\\Domains\\Editor\\Enums\\TextAlignment',
    TextDirection: 'App\\Domains\\Editor\\Enums\\TextDirection',
    TextLayerData: 'App\\Domains\\Editor\\DTOs\\TextLayerData',
};

export const pascal = (slug) => slug.split(/[-_]/).filter(Boolean).map((part) => part[0].toUpperCase() + part.slice(1)).join('');
export const camel = (slug) => {
    const name = pascal(slug);
    return name[0].toLowerCase() + name.slice(1);
};

/** A PHP string literal: single quotes unless the text has line breaks, as in TemplateDesignFactory. */
export function phpString(value) {
    const text = String(value);
    if (!/[\n\r\t]/.test(text)) return `'${text.replace(/\\/g, '\\\\').replace(/'/g, "\\'")}'`;
    return `"${text.replace(/\\/g, '\\\\').replace(/"/g, '\\"').replace(/\$/g, '\\$').replace(/\n/g, '\\n').replace(/\r/g, '\\r').replace(/\t/g, '\\t')}"`;
}

const number = (value) => String(value);
const bool = (value) => (value ? 'true' : 'false');
const isRole = (color, role) => color?.source === 'palette' && color.role === role;
const plainFrame = (frame) => frame.scaleX === 1 && frame.scaleY === 1 && frame.rotation === 0 && frame.opacity === 1;

/**
 * @param {object} design  compiled design.json
 * @param {object} data    projectData()
 * @param {object} folders asset key → folder below editor/assets/v1 (from import.mjs)
 */
export function generatePhp(design, data, folders) {
    const used = new Set(['DesignDocumentData', 'PaletteData']);
    const use = (...names) => {
        names.forEach((name) => used.add(name));
    };
    const color = (value) => {
        use('ColorValueData');
        if (value.source === 'palette') {
            use('PaletteRole');
            return `ColorValueData::palette(PaletteRole::${ROLE[value.role]})`;
        }
        return `ColorValueData::literal(${phpString(value.value)})`;
    };
    const frameData = (frame) => {
        use('LayerFrameData');
        return `new LayerFrameData(${[frame.x, frame.y, frame.width, frame.height, frame.scaleX, frame.scaleY, frame.rotation, frame.opacity].map(number).join(', ')})`;
    };

    const assetSlugById = new Map(design.assets.map((asset) => [asset.placeholderId, asset.slug]));
    const fontSlugById = new Map(Object.entries(data.fonts).map(([slug, font]) => [font.placeholderId, slug]));
    const audioSlugById = new Map(Object.entries(data.templateAudio).map(([slug, track]) => [track.placeholderId, slug]));
    const verseByContent = new Map(Object.entries(data.verses).map(([id, verse]) => [verse.content, id]));

    const layers = design.document.canvas.layers.map((layer) => {
        if (layer.type === 'text') {
            use('Language', 'TextDirection');
            const font = phpString(fontSlugById.get(layer.font.assetId));
            const verse = verseByContent.get(layer.content);
            const content = verse ? `TemplateScripture::content(${phpString(verse)})` : phpString(layer.content);
            if (layer.alignment === 'center' && layer.fill.source === 'palette' && plainFrame(layer.frame) && !layer.locked && layer.visible) {
                use('PaletteRole');
                const { x, y, width, height } = layer.frame;
                return `$this->text(${phpString(layer.id)}, $assets, ${font}, ${content}, Language::${LANGUAGE[layer.language]}, TextDirection::${DIRECTION[layer.direction]}, ${[x, y, width, height, layer.fontSize, layer.fontWeight].map(number).join(', ')}, PaletteRole::${ROLE[layer.fill.role]})`;
            }
            use('LayerData', 'LayerType', 'TextLayerData', 'TextAlignment');
            return `new LayerData(${phpString(layer.id)}, LayerType::Text, ${frameData(layer.frame)}, ${bool(layer.locked)}, ${bool(layer.visible)}, new TextLayerData(${content}, Language::${LANGUAGE[layer.language]}, TextDirection::${DIRECTION[layer.direction]}, TextAlignment::${ALIGNMENT[layer.alignment]}, $this->reference($assets, ${font}), ${number(layer.fontSize)}, ${number(layer.fontWeight)}, ${color(layer.fill)}))`;
        }
        if (layer.type === 'image') {
            const slug = phpString(assetSlugById.get(layer.asset.assetId));
            if (layer.fit === 'contain' && plainFrame(layer.frame) && layer.visible) {
                const { x, y, width, height } = layer.frame;
                return `$this->image(${phpString(layer.id)}, $assets, ${slug}, ${[x, y, width, height].map(number).join(', ')}, ${bool(layer.locked)})`;
            }
            use('LayerData', 'LayerType', 'ImageLayerData', 'ImageFit');
            return `new LayerData(${phpString(layer.id)}, LayerType::Image, ${frameData(layer.frame)}, ${bool(layer.locked)}, ${bool(layer.visible)}, new ImageLayerData($this->reference($assets, ${slug}), ImageFit::${FIT[layer.fit]}))`;
        }
        if (layer.shapeKind === 'rectangle' && layer.fill?.source === 'palette' && layer.stroke === null && layer.strokeWidth === 0 && layer.locked && layer.visible && plainFrame(layer.frame)) {
            use('PaletteRole');
            const { x, y, width, height } = layer.frame;
            return `$this->shape(${phpString(layer.id)}, ${[x, y, width, height].map(number).join(', ')}, PaletteRole::${ROLE[layer.fill.role]})`;
        }
        use('LayerData', 'LayerType', 'ShapeLayerData', 'ShapeKind');
        return `new LayerData(${phpString(layer.id)}, LayerType::Shape, ${frameData(layer.frame)}, ${bool(layer.locked)}, ${bool(layer.visible)}, new ShapeLayerData(ShapeKind::${SHAPE[layer.shapeKind]}, ${layer.fill ? color(layer.fill) : 'null'}, ${layer.stroke ? color(layer.stroke) : 'null'}, ${number(layer.strokeWidth)}))`;
    });

    const values = design.document.palette.values;
    const palette = `new PaletteData(1, ${['background', 'surface', 'primaryText', 'secondaryText', 'accent', 'effect'].map((role) => phpString(values[role])).join(', ')})`;

    const scene = design.document.scene;
    const { opening, effects, audio } = scene;
    const envelope = opening.envelope;
    const plainExtras = !scene.cover && !scene.backdrop && scene.motionPolicy === 'system' && !audio.enabled && audio.asset === null && audio.volume === 0.6;
    const effectIs = (effect, id, intensity, speed) => effect?.effectId === id && effect.effectVersion === 1 && isRole(effect.color, 'effect') && effect.intensity === intensity && effect.speed === speed;
    let sceneCode;
    if (plainExtras && opening.type === 'envelope' && opening.durationMs === 1700 && envelope.presetId === 'classic-fold' && envelope.presetVersion === 1
        && envelope.paperColor.source === 'literal' && isRole(envelope.liningColor, 'surface') && isRole(envelope.sealColor, 'accent') && !envelope.appearance
        && effects.length === 2 && effectIs(effects[0], 'sparkle', 0.52, 0.9) && effectIs(effects[1], 'smoke', 0.28, 0.65)) {
        sceneCode = `$this->envelopeScene(${phpString(envelope.paperColor.value)}, ${phpString(envelope.monogram)}, ${bool(effects[0].enabled)}, ${bool(effects[1].enabled)})`;
    } else if (plainExtras && opening.type === 'fade' && opening.durationMs === 900 && effects.length === 0) {
        sceneCode = '$this->fadeScene()';
    } else {
        use('SceneConfigData', 'OpeningData', 'OpeningType', 'AudioData', 'MotionPolicy');
        let openingCode = `new OpeningData(OpeningType::${OPENING[opening.type]}, ${number(opening.durationMs)})`;
        if (envelope) {
            use('EnvelopeData', 'EnvelopePresetId');
            let appearance = '';
            if (envelope.appearance) {
                use('EnvelopeAppearanceData');
                const a = envelope.appearance;
                appearance = `, new EnvelopeAppearanceData(${phpString(a.style)}, ${phpString(a.sealStyle)}, ${a.sealX}, ${a.sealY}, ${a.sealSize})`;
            }
            openingCode = `new OpeningData(\n                OpeningType::Envelope,\n                ${number(opening.durationMs)},\n                new EnvelopeData(EnvelopePresetId::ClassicFold, 1, ${color(envelope.paperColor)}, ${color(envelope.liningColor)}, ${color(envelope.sealColor)}, ${phpString(envelope.monogram)}${appearance}),\n            )`;
        }
        const effectCode = effects.map((effect) => {
            use('EffectData', 'EffectId');
            return `                new EffectData(EffectId::${EFFECT[effect.effectId]}, 1, ${bool(effect.enabled)}, ${color(effect.color)}, ${number(effect.intensity)}, ${number(effect.speed)}),`;
        });
        const audioSlug = audio.asset ? audioSlugById.get(audio.asset.assetId) : null;
        const audioCode = audioSlug
            ? `new AudioData(${bool(audio.enabled)}, $this->reference($assets, ${phpString(audioSlug)}), ${number(audio.volume)})`
            : `new AudioData(${bool(audio.enabled)}, null, ${number(audio.volume)})`;
        const tail = [];
        if (scene.cover || scene.backdrop) {
            if (scene.cover) {
                use('InvitationCoverData');
                const c = scene.cover;
                tail.push(`new InvitationCoverData(${[c.heading, c.names, c.dateLabel, c.message, c.language, c.decoration].map(phpString).join(', ')}, ${bool(c.animateText)})`);
            } else {
                tail.push('null');
            }
            if (scene.backdrop) {
                use('SceneBackdropData');
                tail.push(`new SceneBackdropData(${phpString(scene.backdrop.preset)})`);
            }
        }
        sceneCode = [
            'new SceneConfigData(',
            '            1,',
            `            ${openingCode},`,
            effectCode.length ? `            [\n${effectCode.join('\n')}\n            ],` : '            [],',
            `            ${audioCode},`,
            `            MotionPolicy::${MOTION[scene.motionPolicy]},`,
            ...tail.map((code) => `            ${code},`),
            '        )',
        ].join('\n');
    }

    const canvas = design.document.canvas;
    let documentCode;
    if (isRole(canvas.background, 'background') && !canvas.material) {
        documentCode = [
            '        return $this->document(',
            '            $layers,',
            `            ${palette},`,
            `            ${sceneCode.replace(/\n/g, '\n    ')},`,
            '        );',
        ].join('\n');
    } else {
        use('DesignSchema', 'CanvasData');
        let material = '';
        if (canvas.material) {
            use('CardMaterialData', 'CardMaterialPreset');
            material = `, new CardMaterialData(CardMaterialPreset::${pascal(canvas.material.preset)}, 1, ${number(canvas.material.intensity)})`;
        }
        documentCode = [
            '        return new DesignDocumentData(',
            '            DesignSchema::VERSION,',
            `            new CanvasData(1, 1080, 1920, ${color(canvas.background)}, $layers${material}),`,
            `            ${palette},`,
            `            ${sceneCode.replace(/\n/g, '\n    ')},`,
            '        );',
        ].join('\n');
    }

    const casePascal = pascal(design.slug);
    const method = camel(design.slug);
    const factoryMethod = [
        `    /** «${design.name}». @param array<string, TemplateAsset> $assets */`,
        `    private function ${method}(array $assets): DesignDocumentData`,
        '    {',
        '        $layers = [',
        ...layers.map((line) => `            ${line},`),
        '        ];',
        '',
        documentCode,
        '    }',
    ].join('\n');

    const seederAssets = design.assets.map((asset) => {
        const folder = folders[asset.key];
        return `            $this->visualAsset(${phpString(asset.slug)}, ${phpString(asset.name)}, AssetType::${ASSET_TYPE[asset.type]}, ${phpString(`${folder}/${asset.slug}.svg`)}, ${phpString(`assets/v1/${folder}/${asset.slug}.png`)}, ${asset.width}, ${asset.height}, $visualCapabilities, $visualLicense, 'methaq-card-designer'),`;
    });
    const templateAssets = [...design.assets.map((asset) => asset.slug), ...design.fonts];
    const seederTemplate = [
        '            [',
        `                'slug' => TemplatePreset::${casePascal}->value,`,
        `                'name' => ${phpString(design.name)},`,
        `                'category' => EventCategory::${pascal(design.category)},`,
        `                'thumbnail_path' => 'editor/previews/templates/${design.slug}.png',`,
        `                'preset' => TemplatePreset::${casePascal},`,
        `                'assets' => [${templateAssets.map(phpString).join(', ')}],`,
        '            ],',
    ].join('\n');

    return {
        presetCase: `    case ${casePascal} = ${phpString(design.slug)};`,
        matchArm: `            TemplatePreset::${casePascal} => $this->${method}($assets),`,
        factoryMethod,
        seederAssets: seederAssets.join('\n'),
        seederTemplate,
        classesUsed: [...used].sort().map((name) => CLASSES[name]).filter(Boolean),
    };
}
