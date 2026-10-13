# Migration and feature inventory

Clarity is now a Laravel 13 / MySQL / Blade application. Source is `E:\laragon\www\clarity-laravel`. The prior Next/Vinext/Cloudflare source is retained separately in `E:\conversation-suite`; archived draft build files are outside the Laravel project in `E:\clarity-migration-tools`.

## Preserved features

| Area | Implemented behavior |
| --- | --- |
| Public site | Landing page, pricing, sample-only demo, email/password login, registration, reset and verification |
| Chat imports | WhatsApp TXT including multiline messages, pasted text, CSV, first worksheet XLSX, Telegram/Instagram/generic JSON, screenshot OCR with review |
| Import review | Sender aliases and renaming, approximate contact redaction, date order, preview and deduplication |
| Chat analysis | Local observations, evidence views, participation/words/activity/reply-gap statistics, comparison, search/filter/explorer |
| AI | Server-side OpenAI Responses, structured reports, evidence-ID validation, chunked analysis, retrieved-excerpt Q&A, reply coaching |
| Freelance | Candidate contract review and history, scope/change items, task statuses/dates, ICS export, invoice records with currency-separated totals and CSV |
| Document studio | Editable SOW, proposal, contract summary, change request and follow-up drafts; TXT and browser print/PDF |
| Storage | Memory by default, optional account-scoped device storage, version-one JSON backup/restore, owner-only MySQL snapshots |
| Administration | Separate administrator authentication and dashboard, users/status, settings revision conflict checks, encrypted OpenAI/PayPal/SMTP secrets, automatic SMTP selection, provider tests, packages/prices/product access, zero-price activation, quotas/trials, site identity, maintenance mode, customer notices, and actor/area-aware audit records |
| Billing | Paid/trial/free-package entitlements, checkout locks, immutable plan snapshot, verified provider reconciliation/webhook deduplication, cancellation/refund rules |

These are the existing app's features, not a claim that every possible competitor feature exists. Document drafts are not electronic signatures. Invoice records do not collect your clients' payments. OCR must be reviewed; interpretations of intentions or personality remain uncertain and evidence-based, not facts about a person.

## Standard Laravel structure

- `app/Http/Controllers/Auth`, `Admin`, and `Api`: request orchestration.
- `app/Http/Requests`: authentication, admin, AI, billing and snapshot validation.
- `app/Models`: Eloquent persisted records and casts, including encrypted provider secrets.
- `app/Services`: settings, entitlement, metering, OpenAI analysis and PayPal integration.
- `database/migrations`: **18 migration files, one table per file**.
- `database/factories` and `seeders`: fixtures and repeatable defaults without a shared default login.
- `resources/views`: formatted Blade templates.
- `public/js` and `public/css`: editable plain browser modules and styles; no compilation step.
- `public/vendor`: prebuilt free XLSX/OCR dependencies, SweetAlert2, Bootstrap Icons, fonts, and licenses.
- `tests/Feature`: backend behavior; `tests/frontend`: optional native Node regression checks for parsing/documents.

There is no `package.json`, `node_modules`, Vite, React, Next, Livewire, Inertia, or Supabase dependency in this application. Composer's `vendor` directory is the standard PHP dependency directory.

## Existing local data

The previous local platform database was imported read-only into MySQL: settings, 13 legacy user records, and 3 audit entries; no saved provider secrets or subscriptions existed in that source. Legacy accounts had no reusable Laravel passwords, so they need a password reset and email verification. The console admin command can grant the owner's chosen account access. Existing browser backups keep their version-one format; data stored under another website origin must be exported/restored manually.

A private SQL backup was saved before restructuring in `E:\db-backups`. The local migration history was aligned with the separate table migrations without resetting tables, and all table row counts were verified unchanged. Do not ship that private backup with the public application.

## Verification and remaining activation

Backend tests cover authentication, password resets, member/admin route isolation, role-specific logout, maintenance mode, encrypted OpenAI/PayPal/SMTP settings, revision checks, trials/expiry, zero-price package activation without PayPal, product access, snapshots, failed AI credits/evidence validation, usage reservations and verified PayPal lifecycle events. They run on SQLite and a separate MySQL test database. Parser/document regression tests cover WhatsApp dates, CSV escaping, JSON imports, deduplication, bounded reply gaps, conservative contract detection, formula-safe CSV and document/calendar exports.

Local browser checks verify normal login, import review, MySQL snapshot save, and the preserved interfaces. Real OpenAI requests, PayPal transactions and cPanel SMTP/deployment still require your credentials and hosting, and are not claimed as live-tested.

## Owner-requested local reset — 13 October 2026

The current source is in `E:\laragon\www\clarity-laravel`. Earlier imported accounts are historical: the current local database has one administrator plus chat-only, contracts-only, and complete members with independent starter workspaces. A private SQL backup precedes the reset. The member entitlements are explicit local-only `TEST_PAID` records with matching dummy billing events and are deliberately distinct from verified PayPal subscriptions.

Administration has a dedicated `/admin/login` page and dashboard, with role/status/verification checks before session establishment; administrators cannot enter member dashboards and members cannot enter administration. Logout returns each role to its own login page. Database-controlled maintenance mode blocks the public site and member area while keeping administration available for recovery. The public footer links to the administrator login. The updated brand uses a blue/violet logo, cool neutral surfaces, a responsive product preview landing page, and a shared modern design system in `public/css/modern.css`.

## Numeric database identifiers — 12 October 2026

At the owner's request, the original 18 migrations were revised and the local database was rebuilt and reseeded. No additional conversion migrations are required. Internal application records use auto-incrementing unsigned BIGINT IDs; the current local user IDs are 1–4. Relationship columns use matching integer types and ownership foreign keys.

PayPal subscription/event identifiers remain in separate provider fields. Usage hashes are `bucket_key`, settings use `key`, provider secrets use `provider`, and legacy import fingerprints are separate from numeric row IDs. Workspace/message/term IDs inside JSON backups and framework session tokens remain opaque strings; they are not application database primary keys.

This was an explicitly authorized local reset, preceded by a private SQL backup. `migrate:fresh` destroys data and remains inappropriate for routine deployment. Reusing an older populated UUID-schema database requires restoration or an explicit migration plan; running `migrate` against its already-completed original migrations does not change it. Existing sessions were invalidated; log in again with the same passwords.

## Notifications

SweetAlert2 11.26.25 is served locally from public/vendor with its license. Public, member and admin pages share styled toast notifications and confirmation dialogs. Network-backed forms submit asynchronously. Server validation errors render as plain text beneath their matching fields, while non-field failures use the shared toast. Native browser validation bubbles are disabled in favor of the same accessible inline treatment on local-only workspace forms. Errors remain until dismissed; routine success messages close automatically. The contracts workspace is available at /contracts; /agreements remains compatible with existing links and older document backups.

Bootstrap Icons 1.13.2 is also self-hosted with its font files and license. Public, member, workspace, and administration controls use meaningful icons with decorative icons hidden from assistive technology and accessible names retained on icon-only buttons.

Laravel form requests provide server-side validation and authorization. Eloquent and the query builder bind values rather than concatenating user input into SQL. Blade output is escaped by default, dynamic browser output is encoded or inserted as text, and feedback messages never accept HTML. Web responses add a nonce-based Content Security Policy, clickjacking, MIME-sniffing, referrer, permissions, opener, and HTTPS transport protections. State-changing requests retain Laravel CSRF and session protections; PayPal webhooks are separately verified by the provider integration.

Encrypted OpenAI, PayPal, and SMTP credentials appear in administration as masked password fields. The plaintext is never returned by the API or placed in HTML. Keeping the mask preserves the saved value, entering text replaces it, and clearing the field then saving removes it. All non-secret settings are populated with their current database values.
