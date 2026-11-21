---
name: methaq-editor
description: "Methaq's invitation editor and how to use Fabric.js v6 (6.9.1) in it. Use this for any work on the card editor or what it feeds: Fabric canvas code (useCanvas, FabricAdapter, Textbox, FabricImage, toDataURL), layers, Arabic text and fonts on the card, card materials, undo and autosave, the DesignDocument contract (canvas, palette, scene) with its revisions and 409 conflicts, the presentation step (envelope, seal, effects, audio, InvitationPlayer), the template studio, card PNG capture for publishing or WhatsApp preparation, watermarked previews, and the guest invitation page. Use it even when the request only says editor, canvas, card, بطاقة, envelope, ظرف, seal or template design and never names Fabric."
---

# Methaq invitation editor

Fabric draws and edits the **static card**. React renders the **scene** around it (envelope, seal, effects, audio, cover, backdrop) for owner previews and for guests. Both read one `DesignDocument`, which is saved whole under one revision; guests only ever see the published snapshot.

Paths: `Editor/…` = `resources/js/Domains/Editor/…`, `Types/…` = `resources/js/Types/…`, `Pages/…` = `resources/js/Pages/…`. Other paths start at the repository root.

## Map

| Concern | Files |
|---|---|
| Editor page, owner and template studio | `Pages/App/Editor.tsx`, served by `app/Http/Controllers/App/EventEditorController.php` and, with `mode: 'template'`, `app/Http/Controllers/Admin/ShowTemplateStudioController.php` |
| Envelope and opening step | `Pages/App/Presentation.tsx`, `Editor/Components/ScenePanel.tsx` |
| Fabric bridge | `Editor/Hooks/useCanvas.ts`, `Editor/Services/FabricAdapter.ts` |
| Contract | `Types/DesignDocument.ts`, `Editor/Services/DesignDocumentSerializer.ts`, `Editor/Services/designContract.ts` |
| Edits, undo, autosave | `Editor/Services/editorDocument.ts`, `Editor/Hooks/useDesignHistory.ts`, `Editor/Hooks/useAutosave.ts`, `Editor/Services/designSaveClient.ts` |
| Card image | `Editor/Hooks/usePresentationCard.ts`, `Editor/Services/capturePresentationCard.ts`, `Editor/Services/pngFileFromDataUrl.ts`, `Editor/Services/previewWatermark.ts` |
| Scene rendering | `Editor/Components/InvitationPlayer.tsx`, `Editor/Components/InvitationEnvelope.tsx`, `Editor/Components/InvitationSeal.tsx`, `resources/css/envelope.css` |
| Server | `app/Domains/Editor/Actions/SaveDesignAction.php`, `app/Domains/Editor/Actions/SaveTemplateDesignAction.php`, `app/Domains/Events/Actions/PublishEventAction.php`, `app/Domains/Events/Actions/PrepareInvitationAction.php` |
| Guest page | `app/Domains/Events/Actions/GetPublicInvitationAction.php` → `Pages/Guest/Show.tsx` |

## 1. The document is the state; Fabric is a view

- Build Fabric objects from layers (`toFabricObject` in `Editor/Services/FabricAdapter.ts`, constructors in `renderDocument` in `Editor/Hooks/useCanvas.ts`) and turn gestures back into `Layer` values (`layerFromObject` → `onLayerChange`). The server validates and stores only `DesignDocument`, so anything that lives only on a Fabric object is lost at the next rebuild and never saved.
- Never persist Fabric output: no `canvas.toJSON()`, `toObject()`, `loadFromJSON()` or custom object properties as storage. The event editor uses none of them.
- Change the document only through `history.commit(update)`, written as pure functions like the helpers in `Editor/Services/editorDocument.ts`. In the editor that is `Editor/Hooks/useDesignHistory.ts`, which re-validates with `parseDesignDocument` and refuses an invalid edit, keeping the last valid document. `Presentation.tsx` uses plain `useHistory`, which validates nothing, while `useAutosave` serializes during render, so an invalid scene there throws: build scene values with the helpers in `Editor/Services/scenePresentation.ts` and keep numbers inside the parser's ranges.
- While `useCanvas` writes document → canvas it sets `synchronizing.current = true`, so Fabric's selection and modify events don't echo back. Keep that guard around any new programmatic change.
- Geometry-only changes update objects in place; content, palette or material changes rebuild the canvas. `contentSignature` and `paletteSignature` in `useCanvas.ts` decide which, so a new property that changes how a layer looks must be added there or the canvas won't redraw it.
- Layer order is the `canvas.layers` array. Reorder the document (`moveLayer` in `editorDocument.ts`) and let `useCanvas` call `moveObjectTo`; don't restack Fabric objects directly.

## 2. Fabric v6 as installed

- `fabric@6.9.1` (`package.json` declares `^6.0.0`). Use named ESM imports, `import { Canvas, Textbox, FabricImage } from 'fabric'`, and `import type { FabricObject } from 'fabric'` for types (`tsconfig.json` sets `verbatimModuleSyntax`). No `fabric.*` namespace and no deprecated `Image` or `Text` aliases.
- Layer types map to these classes: `text` → `Textbox`, `image` → `FabricImage` (a dashed `Rect` placeholder if the asset fails to load), `shape` → `Rect`, `Ellipse` or `Line`. The material texture is the canvas `backgroundImage` (`Editor/Services/renderCardMaterial.ts`), not a layer.
- Loading is Promise-based: `await FabricImage.fromURL(url, { crossOrigin: 'anonymous', signal })`, `await loadSVGFromString(svg)`, and `canvas.dispose()` returns a Promise. Pass the effect's `AbortSignal`, check `signal.aborted` after every `await`, and dispose what you created if the effect was cancelled (`renderDocument` in `useCanvas.ts`).
- v5 names are the most common mistake. Read `references/fabric-v6.md` before writing Fabric code.

## 3. Canvas lifecycle in React

- Mount once: create the `<canvas>` element yourself inside the host `div` and call `new Canvas(element, …)`. On cleanup disconnect observers, clear the maps, call `void instance.dispose().catch(() => undefined)` and `container.replaceChildren()` (`useCanvas.ts`). React must not own the element Fabric wraps, and `StrictMode` in `resources/js/app.tsx` mounts effects twice in development.
- Keep the backstore at document size (1080×1920) with `enableRetinaScaling: false`, and fit the screen with `setDimensions(size, { cssOnly: true })` plus `calcOffset()` from a `ResizeObserver`. The export size then never depends on the device.
- Handlers registered at mount read current props from refs (`latestDesign`, `latestChange`, `latestSelect`), not from the closure.

## 4. Text, Arabic first

- Load fonts before constructing a `Textbox`, because Fabric measures text when it is created. Follow `loadEditorFonts` and `hasFontFace` in `useCanvas.ts`: `document.fonts.check` is true for undeclared families, and variable fonts need `weight: '100 900'`.
- Spread `TEXT_WRAP_OPTIONS` and use `textLineHeight(slug)` from `Editor/Services/textWrapping.ts`. Wrapping by grapheme cuts Arabic words, and the Quran font needs taller lines.
- When a user scales a textbox, fold the scale into `fontSize` and `width` and reset scale to 1 with `normalizeTextScale` (`Editor/Services/textScaling.ts`), so the panel shows real sizes and text is never stretched.
- `direction: 'auto'` is resolved from the script of the first letter in `FabricAdapter.ts`; reuse that instead of guessing.

## 5. Save the whole document under one revision

- `useAutosave` (`Editor/Hooks/useAutosave.ts`) waits 900 ms, then PATCHes `{ document, expectedRevision }` through `saveDesign()` (`Editor/Services/designSaveClient.ts`) to `/app/events/{id}/design`, or `/app/studio/templates/{id}/design` in the studio. `SaveDesignAction.php` and `SaveTemplateDesignAction.php` lock the row, compare the revision, validate asset references, then write canvas, palette, scene and schema version with `revision + 1` in one transaction (`toStorageColumns` in `app/Domains/Editor/DTOs/DesignDocumentData.php`).
- `Editor.tsx` (card, palette, material, audio) and `Presentation.tsx` (envelope, effects, cover, backdrop, audio) both send the full document to the same endpoint and share that revision. Don't add an endpoint, column or request that saves part of the design; new design data goes inside `DesignDocument`.
- A stale revision gets a 409 with the server snapshot (`DesignRevisionConflict`, rendered in `bootstrap/app.php`). `useAutosave` then stops, and the user chooses `keepLocalVersion` or `takeServerVersion` (followed by `history.reset`). Never retry over a conflict automatically or drop the server version silently.
- Block leaving and publishing until saving is done: `autosave.hasUnsavedChanges` and `status === 'saved'` feed `BackLink`'s `canLeave` and the `saved` prop of `resources/js/Domains/Events/Components/PublishPanel.tsx`.

## 6. Contract changes happen on both sides

- The shape and limits exist in TypeScript (`Types/*.ts`, `Editor/Services/designContract.ts`, `Editor/Services/DesignDocumentSerializer.ts`) and in PHP (`app/Domains/Editor/Contracts/DesignSchema.php`, `app/Domains/Editor/Services/DesignDocumentHydrator.php`, `app/Domains/Editor/DTOs/`). Change both in the same task. Otherwise saves fail with 422, or stored designs stop opening and `Editor.tsx` shows its `editor-contract-error` fallback.
- Make new fields optional the way `cover`, `backdrop` and `material` are, so documents saved before the change still parse.
- The full checklist is in `references/design-contract.md`.

## 7. Card image: a clean 1080×1920 PNG from a ready canvas

- `capturePreview()` in `useCanvas.ts` calls `toDataURL({ format: 'png', multiplier: 1, enableRetinaScaling: false })`. With the backstore at document size this is exactly 1080×1920, the only size `app/Domains/Editor/Services/InvitationPng.php` accepts for publishing and WhatsApp preparation. Don't change multiplier, retina scaling or backstore size on this path.
- Capture only when the canvas is `ready`, has no `error` and is not synchronizing. Pages outside the editor render a hidden card with `usePresentationCard` into a `.presentation-render-source` host (`aria-hidden`, `inert`), as `Presentation.tsx`, `Pages/App/Events/Payment.tsx` and `Pages/App/DraftReview.tsx` do.
- Turn a data URL into a file with `pngFileFromDataUrl`, as `PublishPanel.tsx`, `Pages/App/Events/Payment.tsx` and `Editor/Services/templateThumbnailClient.ts` do, and send publish and prepare images with `expectedRevision`. Never `fetch(dataUrl)`: `connect-src 'self'` in `app/Http/Middleware/SecurityHeaders.php` blocks it everywhere except `local`, so it works on a developer machine and fails in production.
- Downloads and library thumbnails are watermarked with `watermarkPreview` (`Editor/Services/previewWatermark.ts`): the default strong mark for downloads, `(url, 540, 'subtle')` for studio thumbnails. The publish and prepare PNG is the clean card, so keep the watermark off it.

## 8. Guests see only the published snapshot

- `PublishEventAction.php` passes the scene through `app/Domains/Editor/Services/PublishedFeatureSanitizer.php` (locked music, animated backdrop and effects are removed) and stores it with the palette, revision and PNG path as `published_scene_json`, `published_palette_json`, `published_revision` and `final_render_path`. The draft is not touched.
- `GetPublicInvitationAction.php` gives `Pages/Guest/Show.tsx` only those fields, and the page shows the PNG inside `InvitationPlayer`. Never read the draft columns (`design_json`, `scene_json`, `palette_json`) for a guest page, share image or notification, and never mount `useCanvas` on a public page.
- The server enforces feature locks at publish. The editor only explains them (`musicLocked` → `Editor/Components/EditorAudioPanel.tsx`); don't strip features from the draft in the browser.

## 9. Scene, envelope and motion: React and CSS

- Fabric draws only the card. Envelope, seal, effects, audio, cover and backdrop are `scene` data rendered by `InvitationPlayer.tsx`, or by `Editor/Components/NoirInvitationScene.tsx` for the `henna-noir` backdrop.
- Render the envelope and seal only through `InvitationEnvelope` (with `EnvelopeLetter`) and `InvitationSeal`, and drive them with `state` (`closed` → `open` → `presenting`). Don't build a second envelope, draw one in Fabric, or change the Methaq logo inside the seal.
- Motion is CSS: transitions and keyframes keyed off `data-state` and `data-phase` in `resources/css/envelope.css` and `resources/css/invitation.css`, each with a `prefers-reduced-motion` override. `Editor/Hooks/useInvitationOpening.ts` times the phases and honours `scene.motionPolicy`. Add no GSAP or other animation library. The only JavaScript frame loops are the ambience canvases (`Editor/Services/invitationAmbience.ts`, which drives `Editor/Services/smokeRenderer.ts`) and the design lab's `Editor/Components/ReferenceAtmosphere.tsx`; they stop for reduced motion, hidden tabs and paused motion, and any new loop must do the same.
- A new envelope or seal style goes into `ENVELOPE_STYLES` or `SEAL_STYLES` (`Types/SceneConfig.ts`) and the matching constant in `app/Domains/Editor/DTOs/EnvelopeAppearanceData.php`, with `.is-<style>` rules in `envelope.css` and any artwork in `public/brand/envelopes/`. No test compares the two lists: `tests/Frontend/design-contract.test.ts` and `tests/Unit/CardMaterialTest.php` each check only their own side.

## 10. Code conventions here

- Strict TypeScript without `any` (`tsconfig.json`: `strict`, `noUncheckedIndexedAccess`). Fabric returns `any` from `toObject()`, `toJSON()` and SVG `options`; keep that inside the Fabric call. Narrow objects with `instanceof Textbox` or `instanceof FabricImage`, as `layerFromObject` does, and catch errors as `unknown`.
- Leave the files in `Editor/Services` where they are and add new files next to them; moving or renaming them is out of scope unless a task says so (`AGENTS.md`).
- Services imported by `tests/Frontend/*.test.ts` run under `node --experimental-strip-types`: no DOM access or `fabric` value import at module level, and explicit `.ts` extensions on value imports (see `DesignDocumentSerializer.ts`). That is why `FabricAdapter.ts` is a pure mapper with type-only imports.
- Interface copy is Arabic and RTL and addresses the user in the feminine second person (`حاولي مجدداً`), like the existing messages.

## Other Fabric canvases, outside the event contract

`Editor/Hooks/usePrototypeCanvas.ts` powers `/dev/design-lab` (local and testing only, saved to `localStorage`). `Editor/Proposals/Ivory/` is the standalone `/app/proposals/ivory-wedding` draft with its own `DesignProposal` type. Don't connect them to saving or publishing, and don't copy their export settings (`multiplier: 2`, 4K) into the publish path.

## Verify

```bash
npm run typecheck
npm run test:frontend
php artisan test --compact tests/Feature/SaveEventDesignTest.php
```

Add `tests/Feature/PublishEventTest.php`, `tests/Feature/TemplateStudioTest.php`, `tests/Feature/EventEditorTest.php` or `tests/Feature/WhatsAppPublishingTest.php` when their flow changes, and run `vendor/bin/pint --dirty --format agent` after PHP edits. No unit test loads Fabric, so check canvas behaviour in the browser as well.

## References

- `references/fabric-v6.md`: v5 → v6 renames, Promise APIs, events and export options as used in this repository.
- `references/design-contract.md`: the document shape, where each rule lives in TypeScript and PHP, the checklist for a new field, and the save, publish and WhatsApp-prepare flows.
