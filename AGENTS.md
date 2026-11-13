# Methaq project instructions

## Approved baseline

The user approved the Methaq 2.0 plan on 2026-09-09. Read these project documents before implementation:

1. requirements/09-methaq-master-plan-v2.md — authoritative product and architecture plan.
2. requirements/10-execution-checklist.md — dependencies, implementation steps, acceptance criteria and progress.
3. requirements/11-users-and-permissions.md — access matrix and latest payment ordering.
4. requirements/08-decisions.md — approvals and unresolved decisions.

Documents 01–07 contain baseline detail; the approved 2.0 plan takes precedence where they conflict. New explicit user instructions take precedence over these files. Do not silently change scope; record requested changes in the decision log.

## Stack and boundaries

Use Laravel 12, PHP 8.3+, React 19, strict TypeScript, Inertia.js v2, Fabric.js v6 and MySQL. Use Modular DDD with Events, Editor, Payments, RSVP and Users. Keep business logic in Actions, Services and DTOs, not controllers or React presentation components. Use Form Requests and policies. PHP files use strict_types; TypeScript must not use any.

Fabric edits the static card. React implements the configurable invitation scene: envelope, effects, audio and guest experience. Templates are editable starting points with shared assets. Store scene, palette and canvas coherently under one revision. Public views use published snapshots, never the owner's current draft.

Server-side payment verification controls publishing and access to high resolution assets. Enforce expiry on every guest/content/RSVP request as well as the daily command. Watermarks deter copying but do not guarantee prevention.

## Current implementation snapshot (verified 2026-11-10)

The codebase has `app/Domains/{Events,Editor,Payments,RSVP,Users}` and matching frontend domain boundaries under `resources/js/Domains`. Their contents and completion status differ; a domain directory does not mean every planned feature in that domain is implemented. Backend HTTP adapters are grouped under `app/Http/Controllers/{Web,App,Admin,Guest}`; Inertia pages are under `resources/js/Pages`, and shared TypeScript contracts are under `resources/js/Types`.

The implemented route groups are composed in `routes/web.php`, which loads `routes/public.php`, `routes/app.php`, and `routes/guest.php`. `public.php` owns `Home` (`/`), `Templates` (`/templates`), `About` (`/about`), `Contact` (`/contact`), and `Privacy` (`/privacy`); `/app` routes are guarded by `auth` and `active`;  invitation subdomain routes guarded by `ResolvePublishedEvent`; and `/dev/design-lab`, restricted to `local` and `testing` by `LocalDevelopmentOnly`. The Filament 5 admin panel (`App\Providers\Filament\AdminPanelProvider`) is mounted at `/admin`; it reuses the Fortify login and admits users only through `User::canAccessPanel()` (active administrator with confirmed two-factor authentication). The older React/Inertia admin (`/app/admin`) was deleted on 2026-11-13; Filament is the only administration surface (templates, receipts, events, users, audit log, finance dashboard). The invitation middleware returns 404 for unknown, unpaid or suspended invitations and a 410 `Guest/Expired` page (no event data) once `expires_at` has passed, for both the guest page and RSVP. `expires_at` comes from `InvitationExpiry`: midnight after the event day plus `methaq.invitation_grace_days` (default 2) in `methaq.invitation_timezone` (Africa/Tripoli); the daily `methaq:expire-invitations` command only syncs the status. Expiry never changes the event design or its template. Fortify provides the configured authentication routes and Inertia views.

## Public website UI

The public-page source lives in `resources/js/Pages/Web`. `resources/js/Layouts/PublicLayout.tsx` is the RTL public shell with the accessible skip link, `Navbar`, main-content landmark, and `Footer`; it is used by `Web/Home`, `Web/About`, `Web/Contact`, and `Web/Templates`. `Navbar` adds responsive, account-aware navigation with the active-route state; `Footer` provides the shared links, brand message, and current-year copyright.

Tailwind CSS v4 is enabled through `@tailwindcss/vite`; `app.css` imports the base identity in `brand.css` and the public-only Editorial Serenity theme in `public-theme.css`. `brand.css` supplies the local Noto Arabic/Manrope identity fonts and legacy brand variables. The public theme supplies `methaq-*` tokens with the Stitch `home.html` palette (primary `#203322`, bronze `#7a613b` with light variants, surface `#f6fbf2` and its container layers, ink, muted, border); the logo file, envelope, dashboard and editor keep the `brand.css` colours; local Manrope and Amiri font faces; editorial display and body stacks; radii, soft shadow, RTL base styles, and keyboard focus treatment. This first public-UI phase establishes only the shared shell and theme; it does not redesign the editor, dashboard, or guest invitation. The Home page was recomposed on 2026-11-13 (hero, templates on a marble stage drawn in SVG, a single-thread steps section, the envelope band, a ruled feature list, a framed closing panel) using theme tokens for the type scale (`text-methaq-display`, `-h2`, `-h3`, `-lead`) and section rhythm (`py-methaq-section`), the SVG ornaments in `Components/Public/Home/Ornament.tsx`, and `PaperSurface`; the envelope showcase and `envelopeThemes.ts` were not touched. On 2026-11-13 the rest was redesigned in the same language: the sign-in pages (`Components/Auth`: `AuthLayout` form plus the silk-and-envelope atelier, `AuthField`/`PasswordField`/`AuthSubmit`, `useAuthForm`), `PageIntro` for About/Contact/Privacy/Templates, the signed-in shell (`account.css`, `auth.css` form kit, brand tokens remapped from the legacy greens), the guest page surroundings and the expired page, and the Filament panel (brand colours, logo, local fonts, `PlatformStats` widget, `public/css/admin-*.css`). The envelope, editor logic and the legacy `/app/admin` markup were not changed apart from colours and sizes.

The visual reference for public pages is `requirements/design-reference/editorial-serenity/` (Stitch export: `DESIGN.md` plus home, about, templates and contact mockups). Read its `README.md` first: it is a look-and-feel reference, not code to copy, and it lists unresolved palette/font conflicts and mockup copy that exceeds first-release scope.

The editor currently includes event design and presentation pages, document validation/serialization, revision-aware save handling, Fabric integration, and scene configuration. The envelope and seal exist once, as `InvitationEnvelope` and `InvitationSeal` (styles in `resources/css/envelope.css`, fixed Methaq logo in the seal), reused by the editor, the guest player, scene themes and the home-page showcase; motion is CSS only, without GSAP. Scene backdrops render a living ambience layer (`InvitationAmbience`); the `henna-noir` backdrop switches `InvitationPlayer` to `NoirInvitationScene` (animated smoke photograph, tilted envelope, paper reveal) and is used by the editable `henna-noir` library template. Keep the existing `resources/js/Domains/Editor/Services` layout and its files in place; do not move or rename them as part of unrelated work. The first release pays by manual bank transfer, end to end. `/app/events/{event}/payment` shows the price (template `price_minor`, LYD) and the receiving account from `methaq.payment.bank_transfer` (env only); `CreateManualPaymentAction` stores the receipt on the private `local` disk and creates a pending `manual_transfer` order with an amount and entitlement snapshot (one open order per event). Administrators decide in Filament (`/admin/orders`, `OrderResource`): `ApprovePaymentAction` marks the order completed and the event paid (it never publishes) and `RejectPaymentAction` stores a reason the owner sees; both lock event then order, authorize through `OrderPolicy` and write `audit_logs`. Receipts reach administrators only through `/admin/receipts/{order}` (panel auth, policy re-checked per request), never through a public or signed URL. The owner publishes from the presentation page: the browser uploads the rendered card PNG and `PublishEventAction` re-checks owner, draft state, approved payment, event date and saved revision, accepts only a real 1080x1920 PNG (re-encoded), stores it on the `public` disk (`final_render_path = storage/renders/invitations/...`; deploys link `public/storage` into shared storage) and writes the published snapshot with the features the event may not use removed (`PublishedFeatureSanitizer`: locked music and animated backdrop; the draft is untouched). Features come from the completed order's `entitlements` (`EventEntitlements`; template locks as fallback); a locked RSVP hides the guest form and `StoreRsvpRequest` refuses responses. The ivory prototype («حكاية بالعاجي») is now also a library template (`ivory-tale`, preset `IvoryTale`) whose arch, ornament, swans, chandelier and clock icons, envelope and heart-frame shapes are library assets and the collection `ivory-tale-set`; the standalone `/app/proposals/ivory-wedding` draft page still exists separately. `HdDownload` is only a marker today. There is no checkout or webhook route, and online gateway integration remains deferred to phase 14.

This snapshot records verified implementation, not a change to the approved product plan. Update it when implementation status or routes change; do not mark checklist items complete without implementation and verification evidence.

## Optional future refactoring notes

These notes are non-binding proposals, not current architecture requirements. If the editor service area grows, a separate review may consider grouping HTTP transport or Fabric rendering adapters into submodules while preserving the `Editor` boundary and public contracts. The current `Editor/Services` structure remains unchanged unless a later task explicitly authorizes and scopes a refactor.

## Delivery

Follow the execution checklist and update completed boxes only with implementation and verification evidence. Do not introduce deferred features into the first release. Do not assume unresolved commercial settings. Routine authorized implementation does not require repeated confirmation.

Reference projects outside Methaq are read-only design references; do not alter them or copy their personal invitation details as production defaults. Check asset licensing before reuse. Keep credentials and generated dependencies out of version control.

Payment integration is the final implementation phase (14), followed by final verification/release (15). Use isolated test fixtures beforehand, never a production payment bypass. Fixed customer/admin roles and guest access follow document 11. Admin is not a blanket owner-policy override. Include limited administration and audit_logs (13 business tables total).

## Payment webhook invariants

When payment integration begins, webhook controllers must remain thin: receive the provider request, invoke a dedicated Action, and return only the required acknowledgment. The Action is responsible for verifying the provider signature, validating the provider event identity, and enforcing idempotency before any state change. `Order` and `PaymentWebhook` records may be created or updated only through Actions; controllers, jobs, and gateways must not write them directly. A repeated webhook must return safely without duplicating payment, publishing, or rendering work.

## Asset access policy

`public/` contains only browser-deliverable assets: editor and template previews, approved public fonts and audio, trusted client-rendered material textures, and published renders that are explicitly safe to expose. Source SVG files, original high-resolution artwork, owner drafts, render snapshots, unapproved assets, and any asset whose entitlement depends on payment, ownership, suspension, or expiry belong under `storage/app/private/`. Private assets are served only through an authorized application response or a short-lived signed storage URL after the relevant policy and entitlement checks; never through the public storage symlink or a stored permanent public URL. Database records store paths, not public URLs, and DTOs expose only the delivery URL appropriate to the current viewer.

## AI Token Efficiency

- Focus strictly on the requested task.
- Do not re-audit the entire project.
- Read only relevant files and dependencies.
- Use Laravel Boost only when needed.
- Avoid unnecessary architecture discussions.
- Preserve existing conventions.
- Do not perform unrelated refactoring.
- Run targeted tests first.
- Keep final reports concise.
- Report only changes, test results, and blockers.
- # Do not repeat information already documented.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:

- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
    - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/Pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v2

- Use all Inertia features from v1 and v2. Check the documentation before making changes to ensure the correct approach.
- New features: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- This project uses the streamlined Laravel 11+ structure: register middleware, exceptions, and routing in `bootstrap/app.php` and service providers in `bootstrap/providers.php`. There is no `app/Http/Kernel.php` or `app/Console/Kernel.php`, and commands in `app/Console/Commands/` auto-register.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.

- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

</laravel-boost-guidelines>
