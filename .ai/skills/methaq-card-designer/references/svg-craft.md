# SVG artwork: hard rules and craft

The art is drawn by a Node generator (`<draft>/source/gen.mjs`) with `scripts/svgkit.mjs`, so every SVG can be regenerated after each critique. `example/source/gen.mjs` is a short working generator; read `svgkit.mjs` itself for exact signatures.

## Hard rules (SvgAssetValidator rejects anything else, or the result breaks in the product)

- Elements: `svg g defs title desc path rect circle ellipse line polyline polygon linearGradient radialGradient stop clipPath mask`.
- Never: `style` (attribute or element), `filter` (no blur, drop shadow or feTurbulence), `pattern`, `use`, `symbol`, `image`, `marker`, event attributes, external `href`s. `href`/`xlink:href` only as `#local`, `url()` only as `url(#id)`.
- No `text` or `tspan`: the validator lets them through, but fonts never load inside an SVG image. Any lettering in art would have to be paths, and usually shouldn't exist at all.
- Root: `<svg xmlns="http://www.w3.org/2000/svg" width="W" height="H" viewBox="0 0 W H">`, with W and H equal to the spec's declared size. `K.svg()` writes exactly this.
- At most 1 MB per file; aim for under 150 KB. Repeat through `<g transform>` and round coordinates (`K.n`) instead of emitting thousands of paths.
- Transparent background unless the art is meant to be an opaque background layer. A frame normally leaves the centre free for text.
- Any colours may be baked in. Gradients are welcome: Chrome rasterizes the SVG to the PNG the editor draws, so `objectBoundingBox` units and opacity stops render as you see them in the preview.

## svgkit

```js
import * as K from '<relative path from new.mjs>/svgkit.mjs';
K.writeSvg(file, K.svg({ width, height, defs: [...], body: [...] }));
```

- Elements: `K.el(tag, attrs, ...children)`, `K.g`, `K.path(d, attrs)`, `K.rect`, `K.circle`, `K.ellipse`, `K.line`, `K.polygon(points)`, `K.polyline`, `K.clipPath(id, ...)`, `K.mask(id, ...)`. CamelCase attributes become SVG names (`strokeWidth` → `stroke-width`, `fillOpacity`, `stopColor`, …); `style` and `on*` throw.
- Transforms: `K.translate(x, y)`, `K.rotate(deg, cx, cy)`, `K.scale(x, y)`.
- Gradients: `K.linearGradient(id, [[offset, '#hex', opacity?], …], { x1, y1, x2, y2, units })`, `K.radialGradient(id, stops, { cx, cy, r, fx, fy })`, `K.metal(id, 'gold' | 'roseGold' | 'silver' | 'bronze' | 'champagne' | 'copper' | 'brass', { angle })`.
- Geometry: `K.polar`, `K.polygonPoints`, `K.starPoints`, `K.smoothPath(points, { closed, tension })`, `K.sampleSmooth`, `K.arcPath`, `K.pointsToPath`, `K.archPath(x, y, w, h, 'round' | 'pointed' | 'ogee' | 'horseshoe' | 'trefoil')`, `K.scallopLine`.
- Repetition and symmetry: `K.around(count, cx, cy, make)`, `K.mirrorX(svg, axisX)`, `K.mirrorY`, `K.quad(svg, cx, cy)`, `K.tile(svg, columns, rows, dx, dy)`, `K.rng(seed)`, `K.scatter(count, box, make)`.
- Motifs: `K.leaf`, `K.petal`, `K.flower`, `K.vine(points, options)`, `K.khatam` (eight-point star), `K.crescent`, `K.sparkle`, `K.heart`, `K.balloon`, `K.cloud`.

## Making it look rich with only these elements

- **Metal**: `K.metal` as stroke or fill. Pair a 3–4px main stroke with a 1–1.5px hairline for the classic double line.
- **Depth without filters**: a darker copy 2–3px down-right at 0.25 opacity under the shape (shadow), a lighter copy up-left at 0.5 (highlight), or a radial gradient fading through `stop-opacity` for glow, vignette or inner light.
- **Soft washes** (watercolour): several large ellipses or blobs with radial gradients fading to opacity 0, layered in different hues.
- **Botanicals**: `K.vine` (stem with alternating leaves along a smooth curve), `K.flower`, `K.leaf`, `K.petal`; mirror with `K.mirrorX` or `K.quad`; vary size and angle with `K.rng(seed)`; baby's-breath as many 3–7px circles on tiny stems with `K.scatter`.
- **Geometry**: `K.khatam`, `K.starPoints`, `K.polygonPoints`, `K.around`, `K.tile` for star tessellations, mashrabiya lattices, zellige, arches, muqarnas rows and scallops.
- **Frames**: asymmetry reads modern, strict symmetry formal. Corner flourishes 140–260px, side borders 12–40px, always inset 40px or more. Give the paper an inner highlight line.
- **Restraint**: one signature motif at high quality; breathing room; alignment on the centre axis (x = 540).

## Looking at your own work

- `magick <draft>/preview.png -resize 50% <scratch>/half.png` for the whole card at fewer tokens.
- `magick <draft>/preview.png -crop 1080x700+0+0 +repage <scratch>/top.png` (and the middle and bottom bands) for details: corners, ornaments next to text, line ends, thin strokes.
- `magick <draft>/assets/frame.png -background '#777' -flatten <scratch>/frame-on-grey.png` shows the transparent areas of an asset.
