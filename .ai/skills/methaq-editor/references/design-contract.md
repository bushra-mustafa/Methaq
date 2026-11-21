# Design document contract, saving and publishing

Paths: `Editor/…` = `resources/js/Domains/Editor/…`, `Types/…` = `resources/js/Types/…`, `Pages/…` = `resources/js/Pages/…`. Other paths start at the repository root.

## Shape (schema version 1)

`Types/DesignDocument.ts` holds four keys: `schemaVersion`, `canvas`, `palette` and `scene`.

- `canvas` (`Types/CanvasData.ts`): `width: 1080`, `height: 1920`, `background: ColorValue`, optional `material` (`Types/CardMaterial.ts`) and `layers`, at most 200 with unique ids matching `^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$`.
- `Layer` (`Types/Layer.ts`) is `text`, `image` or `shape`. Each has a `frame` (`x`, `y`, `width`, `height`, `scaleX`, `scaleY`, `rotation`, `opacity`), `locked` and `visible`. Fonts, images and audio are `{ assetId, version }` references to library assets, never URLs or data URLs.
- `palette` (`Types/Palette.ts`): one `#rrggbb` value per role in `PALETTE_ROLES`. A `ColorValue` is `{ source: 'palette', role }` or `{ source: 'literal', value }`; use palette roles where a palette change should recolour the element.
- `scene` (`Types/SceneConfig.ts`): `opening` (`direct`, `fade`, or `envelope` with an `EnvelopeConfig`), `effects` (at most 2, no repeats, ranges from `EFFECT_REGISTRY`), `audio`, `motionPolicy`, and optional `cover` and `backdrop`.

## Where each rule lives

| Rule | TypeScript | PHP |
|---|---|---|
| Sizes and limits (canvas, layers, JSON bytes, depth, text length, effects, asset version) | `DESIGN_CONTRACT`, `EFFECT_REGISTRY`, `ENVELOPE_REGISTRY` in `Editor/Services/designContract.ts` | `app/Domains/Editor/Contracts/DesignSchema.php` |
| Field validation | `parseDesignDocument` in `Editor/Services/DesignDocumentSerializer.ts` | `app/Domains/Editor/Services/DesignDocumentHydrator.php` and the value objects in `app/Domains/Editor/DTOs/` |
| Envelope and seal styles | `ENVELOPE_STYLES`, `SEAL_STYLES` in `Types/SceneConfig.ts` | `STYLES`, `SEAL_STYLES` in `app/Domains/Editor/DTOs/EnvelopeAppearanceData.php` |
| Save request | `SaveDesignPayload` in `Types/DesignDocument.ts`, sent by `Editor/Services/designSaveClient.ts` | `app/Http/Requests/Editor/SaveDesignRequest.php` |
| Asset references | `{ assetId, version }` in `Types/Layer.ts` | `app/Domains/Editor/Services/DesignAssetReferenceValidator.php`; the JSON columns it scans are listed in `app/Domains/Editor/Services/AssetUsage.php` |

The browser parses everything it receives (`parseDesignDocument`, `parseDesignSnapshot`) and the server hydrates everything it stores, so a field one side doesn't know is either rejected or lost.

## Adding a field or an option

1. Add the type under `resources/js/Types/` and its default where documents are created or migrated in the browser, such as `createEnvelope` in `Editor/Services/scenePresentation.ts` or `DEFAULT_CARD_MATERIAL` in `Editor/Services/cardMaterials.ts`.
2. Parse it in `DesignDocumentSerializer.ts`. Optional data follows the `cover`, `backdrop` and `material` pattern (`...(value === undefined ? {} : { key: parse(value) })`), so designs saved before the change still open.
3. Mirror it in `DesignDocumentHydrator.php` and the DTO, and make the DTO's `toArray()` return the same keys: the save response comes back through `parseDesignSnapshot`.
4. Render it. A card property goes through `Editor/Services/FabricAdapter.ts` and `Editor/Hooks/useCanvas.ts` (object options and `contentSignature`). A scene property goes through `Editor/Components/InvitationPlayer.tsx` and its editing panel under `Editor/Components/` (for example `ScenePanel.tsx`).
5. If a paid entitlement controls it, remove it from the published scene in `app/Domains/Editor/Services/PublishedFeatureSanitizer.php`, and surface the lock in the editor through `app/Domains/Events/Actions/GetEventLockedFeaturesAction.php`. The draft keeps the owner's choice.
6. If it references assets, make sure `DesignAssetReferenceValidator::idsFromJson` finds them.
7. Test both sides: `tests/Frontend/design-contract.test.ts` (and `editor-document.test.ts` for edit helpers) and `tests/Feature/SaveEventDesignTest.php`. If seeded templates need it, update `app/Domains/Editor/Services/TemplateDesignFactory.php`.

## Saving

1. Pages receive `document: unknown` and `revision`, and parse the document inside a `try` whose `catch` renders the `editor-contract-error` screen (`Pages/App/Editor.tsx`, `Pages/App/Presentation.tsx`). Do the same in any new page that receives a design.
2. `useDesignHistory` (`Editor/Hooks/useDesignHistory.ts`) validates every commit. `useAutosave` (`Editor/Hooks/useAutosave.ts`) compares serialized documents, waits 900 ms, sends one request at a time and queues the next, and saves again when the browser comes back online.
3. `saveDesign` PATCHes JSON with the `X-XSRF-TOKEN` header. On success it returns `{ document, revision }`; on 409 it throws `DesignSaveConflictError` with the server's snapshot; other failures become `DesignSaveError` with a short Arabic message.
4. `app/Http/Controllers/App/SaveEventDesignController.php` → `app/Domains/Editor/Actions/SaveDesignAction.php`: authorize `update`, lock `event_designs`, compare `revision`, validate assets, write `design_json`, `palette_json`, `scene_json` and `schema_version` and increment `revision`, all in one transaction. The studio's `app/Domains/Editor/Actions/SaveTemplateDesignAction.php` does the same on `templates` with `design_revision`, re-syncs the template's assets and writes an audit log entry.

## Publishing and preparing

- Publish from the site: `resources/js/Domains/Events/Components/PublishPanel.tsx` posts `{ render: File, expectedRevision }` to `/app/events/{id}/publish`. `app/Domains/Events/Actions/PublishEventAction.php` re-checks owner, draft state, approved payment, event date, revision and the PNG (`app/Domains/Editor/Services/InvitationPng.php`: a real 1080×1920 PNG, re-encoded without metadata). It stores the image on the `public` disk under `renders/invitations/{event}/`, writes `published_revision`, `published_scene_json` (sanitized), `published_palette_json`, `final_render_path` and `rendered_at`, sets the status and `expires_at` (`app/Domains/Events/Services/InvitationExpiry.php`), and records an audit entry and a WhatsApp notice. The draft columns are not modified.
- Prepare for WhatsApp: `Pages/App/Events/Payment.tsx` posts the PNG with `expectedRevision` to `/app/events/{id}/prepare`. `app/Domains/Events/Actions/PrepareInvitationAction.php` stores it privately on the `local` disk with `prepared_revision`. A later «نشر» from WhatsApp publishes those bytes only while `prepared_revision` still equals `revision`; after any new save the owner prepares again, without paying again.
- Both paths require the PNG from `capturePreview()` of the current, saved revision. Disable the action while the autosave is pending and send the revision `useAutosave` reports.

## Template studio

- `app/Http/Controllers/Admin/ShowTemplateStudioController.php` renders `Pages/App/Editor.tsx` with `mode: 'template'`; the `design` policy requires an active administrator with confirmed two-factor authentication.
- Saving changes only the starting point of new events. Events that already exist keep their own copy, as the studio notice in `Editor.tsx` tells the administrator.
- The preview image is the canvas capture, watermarked with `watermarkPreview(url, 540, 'subtle')` and uploaded to `/app/studio/templates/{id}/thumbnail`. `app/Domains/Editor/Actions/UpdateTemplateThumbnailAction.php` accepts only a 540×960 PNG.
- Library templates carry a Quran verse from `app/Domains/Editor/Services/TemplateScripture.php`; the editor's suggestions in `Editor/Services/textSuggestions.ts` mirror those texts, so change both together.

## Who reads which columns

| Reader | Data |
|---|---|
| Owner editor and presentation step (`app/Http/Controllers/App/EventEditorController.php`, `app/Http/Controllers/App/EventPresentationController.php`) | Draft document and `revision` |
| Template studio | Template defaults and `design_revision` |
| Admin draft review (`Pages/App/DraftReview.tsx`) | Draft, read-only, audited |
| Admin event studio (`/app/studio/events/{id}`, `Pages/App/Editor.tsx` and `Pages/App/Presentation.tsx` with `mode: 'event-studio'`) | Draft and `revision` (shared with the owner), audited; «تحديث الدعوة المنشورة» rewrites the published columns through `app/Domains/Events/Services/PublishedSnapshotWriter.php` |
| Guest page (`app/Domains/Events/Actions/GetPublicInvitationAction.php` → `Pages/Guest/Show.tsx`) | `final_render_path`, `published_scene_json`, `published_palette_json` only |
