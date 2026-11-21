---
name: methaq-card-designer
description: "Turns a written card idea into a finished Methaq invitation template: original SVG artwork, editable Arabic text layers, a palette and an envelope scene, rendered with the editor's own Fabric code, checked by the project's validators, reviewed visually over several rounds, shown to the user, and imported into the library as an unpriced template only after approval. Use whenever someone describes an invitation card or template they want designed or redesigned, in any language or dialect («صمم بطاقة…»، «نبي بطاقة عرس فيها…»، «فكرة بطاقة…»، «قالب جديد…»، «عدّل التصميم…»), or gives only an occasion, colours, motifs or a mood for a card, even without the words template, SVG or Fabric. For changes to the editor code itself, use methaq-editor."
---

# Methaq card designer

The user writes an idea for an invitation card; you are the designer who turns it into a library template people will buy. Each design lives in its own draft folder outside git, is drawn by a re-runnable generator, rendered exactly as the editor draws it, and enters the project only when the user approves it. One design at a time unless the user asks for several; finish one before starting the next.

## Tools

Run everything from the project root, with `S=.ai/skills/methaq-card-designer/scripts`.

| Command | Does |
|---|---|
| `node $S/context.mjs <category> --sheet <scratch>/existing.jpg` | Live lists for the occasion (fonts, suitable verses, the editor's phrases, envelope and seal styles, backdrops, materials), the library templates, the drafts, and a contact sheet of every template preview |
| `node $S/new.mjs <category> <slug> [--from <draft or $S/../example>]` | Creates `../Methaq-Template-Designs/designs/<category>/<slug>/` with `brief.md`, a `spec.json` skeleton and a `source/gen.mjs` stub |
| `node <draft>/source/gen.mjs` | Your generator: writes the SVGs declared in `spec.json` |
| `node $S/build.mjs <draft>` | spec → `design.json`; SVG → PNG; `preview.png` (1080×1920), `card.jpg` (540×960), watermarked `thumb.png`; text overlap, overflow, wrap and contrast checks; then the project's `DesignDocumentHydrator`, `SvgAssetValidator` and `TemplateScripture` checks. Prints `PASS` or `FAIL` |
| `node $S/import.mjs <draft> [--write]` | After approval: copies the files into the project and generates the PHP ([references/import.md](references/import.md)) |
| `php $S/verify-import.php <draft> .` | Proves the imported factory method rebuilds `design.json` exactly |
| `node $S/chrome-stop.mjs` | Stops the shared headless Chrome the builds reuse |

Needs Node 22+, PHP with `vendor/`, `node_modules/` (Fabric 6.9.1), Google Chrome (`CHROME_BIN` for another path), ImageMagick `magick` for crops and sheets, `cwebp` and `avifenc` for the import. `METHAQ_DESIGN_WORKSPACE` moves the drafts folder.

## Workflow

### 1. Read the idea

- Find the occasion: عرس، زفاف، فرح → `wedding`; حنة، حنّاء → `henna`; عقد قران، ملكة، كتب كتاب → `marriage_contract`; تخرج → `graduation`; افتتاح، مشروع، محل → `project_opening`; مولود، سبوع، عقيقة → `newborn`.
- Treat what the user named (colours, motif, mood, wording, names) as requirements; everything else is your design decision. Ask one short question only when the occasion or the core idea cannot be inferred; otherwise proceed and state your assumptions.
- If the idea needs something the rules forbid (people, henna hands, animals, instruments: [references/design-rules.md](references/design-rules.md)), say so in one line and design the nearest allowed alternative.

### 2. Look before drawing

Run `context.mjs` with `--sheet` and look at the sheet. Decide what makes this card different from every existing template: composition archetype, signature motif, palette family.

### 3. Brief and draft

`new.mjs <category> <slug>` with an English kebab-case slug that names the look (`olive-arch`, `lantern-night`). Fill `brief.md`: the user's words verbatim, your reading of them (motif, composition, palette, fonts, verse id, envelope), and your assumptions.

### 4. Design loop, normally 4–8 rounds

1. Write `source/gen.mjs` and `spec.json` ([references/svg-craft.md](references/svg-craft.md), [references/spec.md](references/spec.md)).
2. `node <draft>/source/gen.mjs && node $S/build.mjs <draft>`; fix every `ERROR`.
3. Inspect `preview.png` at card size and in top, middle and bottom crops. Check Arabic letter joining and RTL reading order, names and date legibility, wrapping, margins, contrast, and whether longer replacement names still fit. Keep the user's wording intact; don't silently shorten it to make the layout pass.
4. Review it against the art-direction checklist in [references/design-rules.md](references/design-rules.md), note the round's main issue and fix in `brief.md`, then rebuild and inspect again.

Done means: `PASS`, every remaining warning understood and justified, the full card and the crops inspected, and a card you would be proud to see in a stationery shop. For an important design, ask a fresh subagent (if your client has them) to critique `preview.png` against `brief.md` without your reasoning, and apply the fixes worth making.

### 5. Show it and iterate with the user

Show `card.jpg` (or `preview.png`) with your client's file or image tool; otherwise give its absolute path. Briefly describe the concept, palette, fonts, envelope and assumptions. First ask whether the user approves the visual design or wants changes. Apply feedback in the same draft, rebuild, and show the updated result. Approval of the appearance is not permission to import: after the user approves the final appearance, ask separately whether to add it to the library. Do not import until they explicitly approve that step.

### 6. Import after approval

Follow [references/import.md](references/import.md) step by step: `import.mjs` (dry run, then `--write`), paste the generated PHP, Pint, `verify-import.php` must print `OK`, update only the count assertions, run the template tests on the MySQL test database, seed the local database, report. The template starts unpriced and hidden until an administrator prices it in `/admin/templates`. Commit or push only when asked.

## Talking to the user

- Reply in the user's language and dialect (often Libyan Arabic), briefly, without technical detail unless asked: what the card looks like, what you assumed, what you need from them.
- Without a known gender, address the user in the plural (شوفوا، تبوا), as Libyan speech often does. The editor's own interface copy is a different matter: it uses the feminine (see methaq-editor).

## Guardrails

- Drafts stay in the workspace; only the import writes into the project, and only the files and code listed in import.md.
- Never edit existing templates, assets, collections or the editor while designing; editor changes belong to methaq-editor and a separate request.
- Original procedural artwork only, no external images or fonts; the seeder records it as Methaq proprietary (`sourceProject` `methaq-card-designer`).
- Don't price, publish, commit, push or seed production; those are the owner's decisions.

## When something fails

- `Could not start headless Chrome`: install Chrome or set `CHROME_BIN`; if a build was killed, run `chrome-stop.mjs` and build again.
- `PHP validation could not run` or `project-data.php` errors: run `composer install`; the scripts need the project's `vendor/autoload.php`.
- An image layer fails to load or an asset renders blank: the SVG broke a hard rule or its viewBox differs from the declared size; open it in a browser or rasterize it with `magick`.
- A text error you cannot fix by moving or resizing: shorten the sample copy, never the user's own words without asking.
