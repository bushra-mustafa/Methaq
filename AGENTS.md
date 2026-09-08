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

## Delivery

Follow the execution checklist and update completed boxes only with implementation and verification evidence. Do not introduce deferred features into the first release. Do not assume unresolved commercial settings. Routine authorized implementation does not require repeated confirmation.

Reference projects outside Methaq are read-only design references; do not alter them or copy their personal invitation details as production defaults. Check asset licensing before reuse. Keep credentials and generated dependencies out of version control.

Payment integration is the final implementation phase (14), followed by final verification/release (15). Use isolated test fixtures beforehand, never a production payment bypass. Fixed customer/admin roles and guest access follow document 11. Admin is not a blanket owner-policy override. Include limited administration and audit_logs (13 business tables total).
