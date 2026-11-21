# Fabric.js v6 in Methaq

Installed: `fabric@6.9.1` (`node_modules/fabric/package.json`); `package.json` declares `"fabric": "^6.0.0"`. The typings in `node_modules/fabric/dist/fabric.d.ts` and `node_modules/fabric/dist/src/` are the authority. Most Fabric examples online, and most model memory, are v5; check every snippet against this page.

Paths: `Editor/…` = `resources/js/Domains/Editor/…`.

## Imports

| v5 or wrong | v6, as used here | Example |
|---|---|---|
| `import { fabric } from 'fabric'`, `new fabric.Canvas(…)` | `import { Canvas } from 'fabric'` | `Editor/Hooks/useCanvas.ts` |
| `fabric.Image`, `import { Image } from 'fabric'` (deprecated alias) | `FabricImage` | `Editor/Services/renderCardMaterial.ts` |
| `fabric.Text`, `import { Text } from 'fabric'` (deprecated alias) | `FabricText` | `Editor/Hooks/usePrototypeCanvas.ts` |
| `fabric.Object` | `import type { FabricObject } from 'fabric'` | `Editor/Services/capturePresentationCard.ts` |
| `fabric.util.groupSVGElements` | `import { util } from 'fabric'` then `util.groupSVGElements(…)` | `Editor/Hooks/usePrototypeCanvas.ts` |

`tsconfig.json` sets `verbatimModuleSyntax`, so anything used only as a type must be imported with `import type`.

## Callbacks became Promises

| v5 | v6 | Example |
|---|---|---|
| `fabric.Image.fromURL(url, callback, options)` | `await FabricImage.fromURL(url, { crossOrigin: 'anonymous', signal }, imageOptions?)` | `renderDocument` in `Editor/Hooks/useCanvas.ts` |
| `fabric.loadSVGFromString(svg, callback)` | `const { objects, options } = await loadSVGFromString(svg)`; `objects` may contain `null`, so filter it | `createArtwork` in `Editor/Hooks/usePrototypeCanvas.ts` |
| `object.clone(callback)` | `await object.clone()` | not used yet |
| `canvas.loadFromJSON(json, callback)` | `await canvas.loadFromJSON(json)` | not used: event designs are `DesignDocument`, not Fabric JSON |
| `canvas.dispose()` (synchronous) | returns `Promise<boolean>`; in cleanup write `void canvas.dispose().catch(() => undefined)` | `Editor/Hooks/useCanvas.ts`, `Editor/Proposals/Ivory/Hooks/useIvoryCanvas.ts` |
| `canvas.lowerCanvasEl.toBlob(callback)` | `await canvas.toBlob({ format: 'png', multiplier: 1, enableRetinaScaling: false })`, which resolves to `Blob \| null` | not used yet; gives a file without a data URL |

Every loader takes `signal` from an `AbortController` owned by the effect. A cancelled load rejects. After each `await`, check `signal.aborted` (or a `cancelled` flag) and dispose objects or textures you created but will not add; `useCanvas.ts` disposes the unused material texture this way.

## Collections, stacking and geometry

| v5 | v6 |
|---|---|
| `canvas.moveTo(object, index)`, `object.moveTo(index)` | `canvas.moveObjectTo(object, index)` |
| `canvas.insertAt(object, index)` | `canvas.insertAt(index, ...objects)` (index first) |
| `canvas.bringToFront(object)`, `object.bringToFront()` | `canvas.bringObjectToFront(object)` |
| `canvas.sendToBack(object)`, `object.sendToBack()` | `canvas.sendObjectToBack(object)` |
| `bringForward`, `sendBackwards` | `canvas.bringObjectForward(object)`, `canvas.sendObjectBackwards(object)` |
| `object.getBoundingRect(absolute, calculate)` | `object.getBoundingRect()`, no arguments, aligned to the canvas axes |
| `canvas.getPointer(event)` | `canvas.getScenePoint(event)` or `canvas.getViewportPoint(event)`; `getPointer` is deprecated |
| `fabric.Object.prototype.foo = …` | `FabricObject.ownDefaults` (or pass options per object, as this repo does) |

In the event editor, z-order is the `canvas.layers` array. Reorder the document (`moveLayer` in `Editor/Services/editorDocument.ts`) and let `useCanvas` apply it with `moveObjectTo`. `usePrototypeCanvas.ts` uses `insertAt(0, decoration)` to keep its artwork behind the text.

## Canvas options used here

`new Canvas(element, { width, height, enableRetinaScaling: false, preserveObjectStacking: true, selection: false })` in `useCanvas.ts`:

- `width`/`height` are the document size (1080×1920). On-screen size changes only with `setDimensions({ width, height }, { cssOnly: true })` followed by `calcOffset()`.
- `enableRetinaScaling: false` keeps one canvas pixel per document pixel, so `toDataURL({ multiplier: 1 })` is exactly 1080×1920.
- `preserveObjectStacking: true` stops a selected object from jumping to the top.
- `selection: false` turns off drag-to-select: the event editor edits one layer at a time. `useIvoryCanvas.ts` selects several objects programmatically with `new ActiveSelection(members, { canvas })`.

## Events used here

| Event | Payload | Used in |
|---|---|---|
| `selection:created`, `selection:updated` | `{ selected: FabricObject[] }` | `useCanvas.ts`, `useIvoryCanvas.ts` |
| `selection:cleared` | none needed | `useCanvas.ts` |
| `object:moving` | `{ target }`, used for snapping guides | `useCanvas.ts` |
| `object:modified` | `{ target }`, written back to the document | `useCanvas.ts`, `useIvoryCanvas.ts` |
| `before:transform` | `{ transform }`, with `transform.target` | `useIvoryCanvas.ts` |
| `mouse:up` | clears the guides | `useCanvas.ts` |
| `editing:exited` on a `Textbox` | none needed; text is written back | `useCanvas.ts` |
| `modified` on an object | none needed | `usePrototypeCanvas.ts` |

Handlers are attached once at mount and read fresh state from refs. Ignore them while `synchronizing.current` is true.

## Rendering and export

- After `object.set({ … })` changes position or size, call `object.setCoords()` so hit-testing matches, then `canvas.requestRenderAll()` (batched); avoid calling `renderAll()` in loops.
- Editor objects use `objectCaching: false` so text edits and palette changes repaint at once. `exportPreview` in `usePrototypeCanvas.ts` shows how to switch caching off for a high-resolution export and restore it with `dirty: true`.
- Background: assign `canvas.backgroundColor` (hex) and `canvas.backgroundImage` (a `FabricImage` texture from `renderCardMaterial.ts`); dispose the previous background image first.
- `toDataURL` options (`TDataUrlOptions`): `multiplier` (required), `format`, `quality`, `enableRetinaScaling`, `left`, `top`, `width`, `height` and `filter(object)`. `capturePresentationCard.ts` uses `filter` and a crop to export text and decoration groups for the animated card, clears the background while exporting and restores it in `finally`. Copy that pattern for any partial export.
- Text boxes hide the top and bottom middle handles with `setControlsVisibility({ mt: false, mb: false })`; corner and side handles go through `normalizeTextScale` (`Editor/Services/textScaling.ts`).

## Typing

- `toObject()`, `toJSON()` and `SVGParsingOutput.options` are typed `any` by Fabric. Keep those values inside the Fabric call and never pass them into application state; application state is parsed from `unknown` (`Editor/Services/DesignDocumentSerializer.ts`).
- Narrow with `instanceof Textbox`, `instanceof FabricImage` or `instanceof Group` instead of casting.
- `useCanvas.ts` keeps its own `Map<FabricObject, string>` from object to layer id instead of adding properties to Fabric objects; do the same.
