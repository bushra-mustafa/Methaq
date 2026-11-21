# spec.json reference

`build.mjs` compiles `spec.json` into `design.json`: the exact document `DesignDocumentHydrator` accepts, plus import metadata. Lists such as envelope styles come live from the project; `context.mjs` prints them. `example/spec.json` is a complete working spec.

```jsonc
{
  "slug": "olive-arch",                      // kebab-case, 4–40 chars, starts with a letter; not a TemplatePreset or another draft
  "name": "قوس الزيتون",                      // Arabic display name, 2–3 words
  "category": "wedding",                     // wedding | henna | marriage_contract | graduation | project_opening | newborn
  "concept": "…",                            // 1–3 sentences: the idea, the signature motif, the composition (20+ chars)
  "tags": ["olive", "arch"],
  "palette": { "background": "#…", "surface": "#…", "primaryText": "#…", "secondaryText": "#…", "accent": "#…", "effect": "#…" },
  "canvas": { "background": "background", "material": { "preset": "linen", "intensity": 0.4 } },   // both optional
  "assets": {                                // key → SVG; the library slug becomes "<slug>-<key>"
    "frame":   { "name": "إطار …", "type": "frame",      "file": "assets/frame.svg",   "width": 1080, "height": 1920 },
    "divider": { "name": "فاصل …", "type": "decoration", "file": "assets/divider.svg", "width": 720,  "height": 120 }
  },
  "layers": [
    { "id": "frame", "kind": "image", "asset": "frame", "x": 0, "y": 0, "w": 1080, "h": 1920 },
    { "id": "names", "kind": "text", "font": "font-aref-ruqaa", "text": "…", "x": 110, "y": 430, "w": 860, "h": 230,
      "size": 128, "weight": 400, "fill": "primaryText", "align": "center", "lang": "ar", "dir": "rtl", "expectLines": 1 },
    { "id": "verse", "kind": "verse", "verse": "verse-30-21-p", "x": 190, "y": 900, "w": 700, "h": 230, "size": 38, "fill": "secondaryText" },
    { "id": "panel", "kind": "shape", "shape": "rectangle", "x": 90, "y": 600, "w": 900, "h": 700, "fill": "surface", "stroke": null, "strokeWidth": 0 }
  ],
  "scene": {
    "opening": "envelope",                   // envelope | fade | direct
    "durationMs": 1700,
    "envelope": { "paper": "#d8c9a8", "lining": "surface", "seal": "accent", "monogram": "ي م",
                  "style": "luxury", "sealStyle": "wax-flower", "sealX": 50, "sealY": 52, "sealSize": 20 },
    "effects": { "sparkle": { "enabled": true, "color": "effect", "intensity": 0.5, "speed": 0.9 }, "smoke": { "enabled": false } },
    "audio": null,                           // or { "track": "audio-henna-celebration", "volume": 0.7 } for henna
    "backdrop": null,                        // or a preset from context.mjs
    "cover": null                            // or { "heading", "names", "dateLabel", "message", "language": "ar", "decoration": "none|floral|halo", "animateText": false }
  },
  "notes": "anything the importer should know"
}
```

## Fields

**Assets**: `type` is `frame | background | decoration | icon`. Use one full-canvas frame and 0–3 extras. The key (kebab-case, up to 25 characters) also picks the library folder at import, and the seeder derives placement metadata from that folder: keys containing `divider`, `corner`, `border`, or `medallion`/`wreath`/`emblem`/`seal` go to the matching ornament folder; icons go to `icons/<slug>/`; frames go to `frames/<occasion>/`. Name keys for what they are.

**Every layer**: `id` (letters, digits, `_`, `-`), `x`, `y`, `w`, `h`; optional `scaleX`, `scaleY` (1), `rotation` (0), `opacity` (1), `visible` (true), `locked`.

**image**: `asset` (a key from `assets`), `fit` `contain` (default) or `cover`. Locked by default for frames and backgrounds.

**text**: `font` (library slug), `text` (1–2000 chars, no markup; `\n` for a line break), `size`, `weight` (400; steps of 100), `fill` (role or `#rrggbb`, default `primaryText`), `align` (`center`), `lang` (`ar`, or `en` for Latin-only fonts, or `mixed`), `dir` (`rtl`, `ltr` for English, or `auto`), `expectLines`.

**verse**: `verse` (an id from `context.mjs`), `size`, `fill`; the font defaults to `font-amiri-quran`. The compiler copies `TemplateScripture::content(id)` verbatim, source line included.

**shape**: `shape` `rectangle | ellipse | line`, `fill` and `stroke` as a role, hex or null, `strokeWidth` 0–64. Locked by default.

**Scene**: `envelope.sealX` 20–80, `sealY` 25–75, `sealSize` 14–26 (percent of the envelope). Effects default to sparkle on (intensity 0.52, speed 0.9) and smoke off (0.28, 0.65).

Defaults the importer turns into the factory's short helpers (centred palette-coloured text, `contain` images, locked palette rectangles, the plain envelope scene) keep the PHP short; anything else is still imported exactly, as explicit DTOs.
