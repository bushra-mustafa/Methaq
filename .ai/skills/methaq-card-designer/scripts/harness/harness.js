/*
 * Render harness: draws a Methaq design document with the same Fabric calls the editor makes
 * (resources/js/Domains/Editor/Hooks/useCanvas.ts) so previews match what the product shows.
 * Runs inside headless Chrome; driven by tools/build.mjs through the DevTools protocol.
 */
(() => {
    'use strict';
    const { StaticCanvas, Textbox, FabricImage, Rect, Ellipse, Line } = fabric;

    const TEXT_WRAP_OPTIONS = { splitByGrapheme: false };
    const textLineHeight = (slug) => (slug === 'font-amiri-quran' ? 1.7 : 1.25);
    const WATERMARK_TEXT = 'ميثاق · METHAQ';

    const resolveColor = (color, palette) => (color.source === 'palette' ? palette.values[color.role] : color.value);

    function imagePlacement(frame, width, height, fit) {
        const targetWidth = frame.width * frame.scaleX;
        const targetHeight = frame.height * frame.scaleY;
        const widthRatio = targetWidth / Math.max(1, width);
        const heightRatio = targetHeight / Math.max(1, height);
        const scale = fit === 'cover' ? Math.max(widthRatio, heightRatio) : Math.min(widthRatio, heightRatio);
        return {
            left: frame.x + (targetWidth - width * scale) / 2,
            top: frame.y + (targetHeight - height * scale) / 2,
            scaleX: scale,
            scaleY: scale,
        };
    }

    function hasFontFace(family) {
        for (const face of document.fonts) {
            if (face.family.replace(/^["']|["']$/g, '') === family) return true;
        }
        return false;
    }

    async function loadFonts(fonts) {
        await Promise.all(fonts.filter(Boolean).map(async (font) => {
            if (hasFontFace(font.family)) return;
            const face = new FontFace(font.family, `url("${font.url}")`, font.variable ? { weight: '100 900' } : {});
            document.fonts.add(await face.load());
        }));
    }

    function loadImage(url) {
        return new Promise((resolve, reject) => {
            const image = new Image();
            image.onload = () => resolve(image);
            image.onerror = () => reject(new Error(`image failed to load: ${url.slice(0, 120)}`));
            image.src = url;
        });
    }

    /** Draws an SVG at its declared size onto a transparent PNG: the editor shows this raster, not the SVG. */
    async function rasterizeSvg({ svg, width, height }) {
        const image = await loadImage(`data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`);
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        canvas.getContext('2d').drawImage(image, 0, 0, width, height);
        return canvas.toDataURL('image/png');
    }

    function commonProperties(layer) {
        return {
            left: layer.frame.x,
            top: layer.frame.y,
            width: layer.frame.width,
            height: layer.frame.height,
            scaleX: layer.frame.scaleX,
            scaleY: layer.frame.scaleY,
            angle: layer.frame.rotation,
            opacity: layer.frame.opacity,
            visible: layer.visible,
            selectable: false,
            evented: false,
            objectCaching: false,
        };
    }

    const channel = (value) => {
        const v = value / 255;
        return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4;
    };
    const luminance = (r, g, b) => 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);
    const hexToRgb = (hex) => [parseInt(hex.slice(1, 3), 16), parseInt(hex.slice(3, 5), 16), parseInt(hex.slice(5, 7), 16)];

    /** Per glyph pixel contrast between the text colour and whatever is painted beneath it. */
    function analyseText(canvas, entries, backgroundOf) {
        const context = canvas.getContext();
        const { width, height } = canvas;
        const everything = canvas.getObjects();
        const savedVisibility = everything.map((object) => object.visible);
        const savedBackground = canvas.backgroundColor;
        const savedImage = canvas.backgroundImage;

        // Everything except the text, as the card looks without its words.
        entries.forEach(({ object }) => { object.visible = false; });
        canvas.renderAll();
        const below = context.getImageData(0, 0, width, height).data;

        const results = new Map();
        for (const entry of entries) {
            const box = entry.object.getBoundingRect();
            const pad = 10;
            const x0 = Math.max(0, Math.floor(box.left) - pad);
            const y0 = Math.max(0, Math.floor(box.top) - pad);
            const x1 = Math.min(width, Math.ceil(box.left + box.width) + pad);
            const y1 = Math.min(height, Math.ceil(box.top + box.height) + pad);
            const regionWidth = x1 - x0;
            const regionHeight = y1 - y0;
            if (regionWidth <= 0 || regionHeight <= 0) {
                results.set(entry.id, { contrastP10: null, contrastMedian: null, glyphPixels: 0, ink: null });
                continue;
            }
            everything.forEach((object) => { object.visible = false; });
            entry.object.visible = savedVisibility[everything.indexOf(entry.object)];
            canvas.backgroundColor = '';
            canvas.backgroundImage = undefined;
            canvas.renderAll();
            const alpha = context.getImageData(x0, y0, regionWidth, regionHeight).data;
            const [tr, tg, tb] = hexToRgb(entry.fill);
            const opacity = entry.opacity;
            const histogram = new Uint32Array(220);
            let samples = 0;
            let minX = width; let minY = height; let maxX = -1; let maxY = -1;
            for (let ry = 0; ry < regionHeight; ry += 1) {
                for (let rx = 0; rx < regionWidth; rx += 1) {
                    const a = alpha[(ry * regionWidth + rx) * 4 + 3];
                    const x = x0 + rx;
                    const y = y0 + ry;
                    if (a > 40) {
                        if (x < minX) minX = x;
                        if (x > maxX) maxX = x;
                        if (y < minY) minY = y;
                        if (y > maxY) maxY = y;
                    }
                    if (a < 170) continue;
                    const offset = (y * width + x) * 4;
                    const br = below[offset]; const bg = below[offset + 1]; const bb = below[offset + 2];
                    const er = tr * opacity + br * (1 - opacity);
                    const eg = tg * opacity + bg * (1 - opacity);
                    const eb = tb * opacity + bb * (1 - opacity);
                    const l1 = luminance(er, eg, eb);
                    const l2 = luminance(br, bg, bb);
                    const ratio = (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
                    histogram[Math.min(219, Math.round(ratio * 10))] += 1;
                    samples += 1;
                }
            }
            const quantile = (q) => {
                if (samples === 0) return null;
                let seen = 0;
                for (let i = 0; i < histogram.length; i += 1) {
                    seen += histogram[i];
                    if (seen >= samples * q) return i / 10;
                }
                return 22;
            };
            results.set(entry.id, {
                contrastP10: quantile(0.1),
                contrastMedian: quantile(0.5),
                glyphPixels: samples,
                ink: maxX < 0 ? null : { x: minX, y: minY, w: maxX - minX + 1, h: maxY - minY + 1 },
            });
        }

        everything.forEach((object, index) => { object.visible = savedVisibility[index]; });
        canvas.backgroundColor = savedBackground;
        canvas.backgroundImage = savedImage;
        canvas.renderAll();
        return results;
    }

    function overlapArea(a, b) {
        const w = Math.min(a.x + a.w, b.x + b.w) - Math.max(a.x, b.x);
        const h = Math.min(a.y + a.h, b.y + b.h) - Math.max(a.y, b.y);
        return w > 0 && h > 0 ? { w, h } : null;
    }

    function watermarkTiles(width, height, stepX, stepY) {
        const span = Math.ceil(Math.hypot(width, height));
        const tiles = [];
        for (let row = 0, y = -span; y <= span; row += 1, y += stepY) {
            for (let x = -span - (row % 2 === 0 ? 0 : stepX / 2); x <= span; x += stepX) tiles.push({ x, y });
        }
        return tiles;
    }

    /** Same constants as resources/js/Domains/Editor/Services/previewWatermark.ts, 'subtle' strength. */
    async function watermarked(image, targetWidth) {
        const canvas = document.createElement('canvas');
        canvas.width = image.naturalWidth;
        canvas.height = image.naturalHeight;
        const context = canvas.getContext('2d');
        const unit = canvas.width / 1080;
        const look = { font: 46, stepX: 700, stepY: 406, alpha: 0.2 };
        const fontSize = Math.round(look.font * unit);
        await document.fonts.load(`700 ${fontSize}px "Reem Kufi"`).catch(() => []);
        context.drawImage(image, 0, 0);
        context.font = `700 ${fontSize}px "Reem Kufi", "Noto Sans Arabic", sans-serif`;
        context.textBaseline = 'middle';
        context.fillStyle = `rgba(110, 110, 110, ${look.alpha})`;
        context.shadowColor = 'rgba(255, 255, 255, 0.35)';
        context.shadowBlur = 2 * unit;
        context.translate(canvas.width / 2, canvas.height / 2);
        context.rotate((-28 * Math.PI) / 180);
        for (const tile of watermarkTiles(canvas.width, canvas.height, Math.round(look.stepX * unit), Math.round(look.stepY * unit))) {
            context.fillText(WATERMARK_TEXT, tile.x, tile.y);
        }
        const small = document.createElement('canvas');
        small.width = targetWidth;
        small.height = Math.round((canvas.height * targetWidth) / canvas.width);
        const smallContext = small.getContext('2d');
        smallContext.imageSmoothingQuality = 'high';
        smallContext.drawImage(canvas, 0, 0, small.width, small.height);
        return small.toDataURL('image/png');
    }

    function scaled(image, targetWidth, mime, quality) {
        const small = document.createElement('canvas');
        small.width = targetWidth;
        small.height = Math.round((image.naturalHeight * targetWidth) / image.naturalWidth);
        const context = small.getContext('2d');
        context.imageSmoothingQuality = 'high';
        context.drawImage(image, 0, 0, small.width, small.height);
        return small.toDataURL(mime, quality);
    }

    /**
     * payload: { design, fonts: {assetId: {family, slug, url, variable}}, assets: {assetId: {previewUrl}},
     *            materialUrl: string|null, extraFonts: [{family,url,variable}], outputs: boolean }
     */
    async function renderDocument(payload) {
        const { design, fonts, assets } = payload;
        const palette = design.palette;
        const layers = design.canvas.layers;
        const used = new Map();
        for (const layer of layers) if (layer.type === 'text') used.set(layer.font.assetId, fonts[layer.font.assetId]);
        await loadFonts([...used.values(), ...(payload.extraFonts ?? [])]);

        const element = document.createElement('canvas');
        const canvas = new StaticCanvas(element, {
            width: design.canvas.width,
            height: design.canvas.height,
            enableRetinaScaling: false,
            renderOnAddRemove: false,
        });
        canvas.backgroundColor = resolveColor(design.canvas.background, palette);
        const material = design.canvas.material;
        if (material && material.preset !== 'plain' && material.intensity > 0 && payload.materialUrl) {
            const texture = await FabricImage.fromURL(payload.materialUrl);
            texture.set({
                left: 0,
                top: 0,
                scaleX: design.canvas.width / texture.width,
                scaleY: design.canvas.height / texture.height,
                opacity: material.intensity,
                selectable: false,
                evented: false,
                objectCaching: false,
            });
            canvas.backgroundImage = texture;
        }

        const failedAssets = [];
        const textEntries = [];
        for (const layer of layers) {
            let object;
            if (layer.type === 'text') {
                const font = fonts[layer.font.assetId];
                const direction = layer.direction === 'auto'
                    ? (/\p{Script=Arabic}|\p{Script=Hebrew}/u.test(layer.content.match(/\p{Letter}/u)?.[0] ?? '') ? 'rtl' : 'ltr')
                    : layer.direction;
                object = new Textbox(layer.content, {
                    ...commonProperties(layer),
                    fontFamily: font.family,
                    fontSize: layer.fontSize,
                    fontWeight: layer.fontWeight,
                    textAlign: layer.alignment,
                    direction,
                    fill: resolveColor(layer.fill, palette),
                    ...TEXT_WRAP_OPTIONS,
                    lineHeight: textLineHeight(font.slug),
                    editable: false,
                });
                textEntries.push({ id: layer.id, object, layer, fill: resolveColor(layer.fill, palette), opacity: layer.frame.opacity });
            } else if (layer.type === 'image') {
                const info = assets[layer.asset.assetId];
                try {
                    if (!info) throw new Error('missing asset');
                    object = await FabricImage.fromURL(info.previewUrl, { crossOrigin: 'anonymous' });
                    object.set({
                        ...commonProperties(layer),
                        width: object.width,
                        height: object.height,
                        ...imagePlacement(layer.frame, object.width, object.height, layer.fit),
                    });
                } catch {
                    failedAssets.push(layer.id);
                    object = new Rect({ ...commonProperties(layer), fill: '#f0e8dc', stroke: '#9c8450', strokeDashArray: [18, 12], strokeWidth: 4 });
                }
            } else if (layer.shapeKind === 'ellipse') {
                object = new Ellipse({ ...commonProperties(layer), rx: layer.frame.width / 2, ry: layer.frame.height / 2, fill: layer.fill ? resolveColor(layer.fill, palette) : null, stroke: layer.stroke ? resolveColor(layer.stroke, palette) : null, strokeWidth: layer.strokeWidth });
            } else if (layer.shapeKind === 'line') {
                object = new Line([0, layer.frame.height / 2, layer.frame.width, layer.frame.height / 2], { ...commonProperties(layer), fill: layer.fill ? resolveColor(layer.fill, palette) : null, stroke: layer.stroke ? resolveColor(layer.stroke, palette) : null, strokeWidth: layer.strokeWidth });
            } else {
                object = new Rect({ ...commonProperties(layer), fill: layer.fill ? resolveColor(layer.fill, palette) : null, stroke: layer.stroke ? resolveColor(layer.stroke, palette) : null, strokeWidth: layer.strokeWidth });
            }
            canvas.add(object);
        }
        canvas.renderAll();

        const png = canvas.toDataURL({ format: 'png', multiplier: 1, enableRetinaScaling: false });

        const analysis = analyseText(canvas, textEntries);
        const textDiagnostics = textEntries.map(({ id, object, layer }) => {
            const rect = object.getBoundingRect();
            const lines = object._textLines ?? [];
            let maxLineWidth = 0;
            for (let i = 0; i < lines.length; i += 1) maxLineWidth = Math.max(maxLineWidth, object.getLineWidth(i));
            const measured = analysis.get(id);
            return {
                id,
                fontSize: layer.fontSize,
                declared: { x: layer.frame.x, y: layer.frame.y, w: layer.frame.width, h: layer.frame.height },
                box: { x: Math.round(rect.left), y: Math.round(rect.top), w: Math.round(rect.width), h: Math.round(rect.height) },
                lines: lines.length,
                maxLineWidth: Math.round(maxLineWidth),
                wordOverflow: maxLineWidth > object.width * object.scaleX + 2,
                heightExceedsDeclared: object.height * object.scaleY > layer.frame.height * layer.frame.scaleY + 8,
                ...measured,
            };
        });

        const overlaps = [];
        for (let i = 0; i < textDiagnostics.length; i += 1) {
            for (let j = i + 1; j < textDiagnostics.length; j += 1) {
                const a = textDiagnostics[i].ink; const b = textDiagnostics[j].ink;
                if (!a || !b) continue;
                const hit = overlapArea(a, b);
                if (hit && hit.w > 6 && hit.h > 6) overlaps.push({ a: textDiagnostics[i].id, b: textDiagnostics[j].id, w: hit.w, h: hit.h });
            }
        }

        const result = { png, diagnostics: { failedAssets, text: textDiagnostics, overlaps } };
        if (payload.outputs !== false) {
            const image = await loadImage(png);
            result.card = scaled(image, 540, 'image/jpeg', 0.9);
            result.thumb = await watermarked(image, 540);
        }
        return result;
    }

    window.Methaq = { renderDocument, rasterizeSvg, ready: true };
})();
