# Importing an approved design

Only after the user has explicitly approved this design for the library (quote their words in your report). Import follows the convention every library template uses (see commit `270e295`, «qiran-sand»): a `TemplatePreset` case, a `TemplateDesignFactory` method, `TemplateLibrarySeeder` entries, private SVG originals, public PNG rasters and a watermarked preview with WebP and AVIF twins. `S=.ai/skills/methaq-card-designer/scripts`, run from the project root.

## 1. Check, then copy the files

```bash
node $S/import.mjs <draft>            # dry run: refuses a failed or stale build or a taken slug, writes <draft>/import/SNIPPETS.md
node $S/import.mjs <draft> --write    # copies the files and makes the .webp and .avif twins (cwebp, avifenc)
```

Files land in `storage/app/private/editor/assets/v1/<folder>/<slug>-<key>.svg`, `public/editor/previews/assets/v1/<folder>/<slug>-<key>.png` and `public/editor/previews/templates/<slug>.png` (+ `.webp`, `.avif`). It never overwrites a file unless you pass `--force`, which is only for re-importing the same draft.

## 2. Insert the generated PHP

Paste each block from `<draft>/import/SNIPPETS.md` with your edit tool, exactly as generated:

1. `app/Domains/Editor/Enums/TemplatePreset.php`: the new case, last.
2. `app/Domains/Editor/Services/TemplateDesignFactory.php`: the match arm, last in `make()`; the method after the last template method, just before `/** @param list<LayerData> $layers */ private function document(`; and any `use` lines listed under 3b.
3. `database/seeders/TemplateLibrarySeeder.php`: the asset lines at the end of the visual assets in `assetDefinitions()` (before the `noir-paper-frame` entry, which stays last); the template array last in `templateDefinitions()`.

Don't touch other templates, their assets or the collections. Then format:

```bash
vendor/bin/pint --dirty --format agent
```

## 3. Prove it is the same design

```bash
php $S/verify-import.php <draft> .
```

It builds the preset through the real factory with stand-in assets carrying `design.json`'s placeholder ids and compares the result field by field with `design.json`, then checks the seeder entries, the copied files (byte for byte), the SVG validator and the preview twins. It must print `OK`. On `FAIL`, fix the PHP you pasted, not the design; if the design itself changed, rebuild, run `import.mjs --write --force` and paste the regenerated method.

## 4. Tests on the isolated MySQL test database

The template tests are skipped under the default SQLite config, which proves nothing. Run them on `methaq_test` (other connection settings come from `.env`; `.env.testing.example` shows them; the tests refuse a database without the `_test` suffix):

```bash
DB_CONNECTION=mysql DB_DATABASE=methaq_test php artisan test --compact tests/Feature/TemplateLibraryTest.php tests/Feature/EventEditorTest.php tests/Feature/EventManagementTest.php tests/Feature/TemplateStudioTest.php
```

A new template changes only count assertions, and you update exactly those:

| Test | Assertion | Change |
|---|---|---|
| `TemplateLibraryTest` | `assertDatabaseCount('templates', …)` | +1 |
| `TemplateLibraryTest` | `assertDatabaseCount('template_assets', …)` | + number of new assets |
| `TemplateLibraryTest` | `->has('templates', …)` (public library, unpriced-hidden test) | +1 each |
| `EventEditorTest` | `->has('assets', …)` (two tests) | + number of new assets each |
| `EventManagementTest` | `->has('templates', …)` | +1 |

Any other failure is a real problem: stop, investigate and report it; never weaken an assertion to pass.

## 5. Make it visible locally

```bash
php artisan db:seed --class=TemplateLibrarySeeder
```

Only on the local development database, never with `--force` and never against production (the deploy does not seed either; that is the owner's server step). The seeder is idempotent and keeps any template an administrator has redesigned in the studio. Find the id for the studio link:

```bash
php artisan tinker --execute 'echo App\Domains\Editor\Models\Template::query()->where("slug", "<slug>")->value("id");'
```

## 6. Report

Tell the user, briefly: what was added (files), `verify-import.php` OK, test results, and that the template starts **unpriced, so it is hidden** from the home page, `/templates` and event creation until an administrator sets its price in Filament (`/admin/templates`). It can be refined in the studio at `/app/studio/templates/<id>` (active administrator with confirmed two-factor authentication). Add «imported on <date>» to the draft's `brief.md`. Commit or push only if the user asks; a pushed commit still needs the deploy approval.

## Changing an imported template later

Edit the draft in the workspace, rebuild, show the user, then `import.mjs --write --force`, replace the factory method with the regenerated one, and run steps 3–5 again. A template already redesigned in the studio (`design_revision > 1`) keeps its studio design when seeded, and a preview uploaded in the studio is never replaced; say so if that applies.
