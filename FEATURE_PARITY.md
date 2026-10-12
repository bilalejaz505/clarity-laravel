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
| Freelance | Candidate agreement review and history, scope/change items, task statuses/dates, ICS export, invoice records with currency-separated totals and CSV |
| Document studio | Editable SOW, proposal, agreement summary, change request and follow-up drafts; TXT and browser print/PDF |
| Storage | Memory by default, optional account-scoped device storage, version-one JSON backup/restore, owner-only MySQL snapshots |
| Administration | Users/status, settings revision conflict checks, encrypted provider secrets, OpenAI setup/test, PayPal plans/settings, quotas/trials, application settings, audit records |
| Billing | Paid/trial entitlements, authenticated localhost-only free plan, checkout locks, immutable plan snapshot, verified provider reconciliation/webhook deduplication, cancellation/refund rules |

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
- `public/vendor`: prebuilt free XLSX/OCR browser dependencies and licenses.
- `tests/Feature`: backend behavior; `tests/frontend`: optional native Node regression checks for parsing/documents.

There is no `package.json`, `node_modules`, Vite, React, Next, Livewire, Inertia, or Supabase dependency in this application. Composer's `vendor` directory is the standard PHP dependency directory.

## Existing local data

The previous local platform database was imported read-only into MySQL: settings, 13 legacy user records, and 3 audit entries; no saved provider secrets or subscriptions existed in that source. Legacy accounts had no reusable Laravel passwords, so they need a password reset and email verification. The console admin command can grant the owner's chosen account access. Existing browser backups keep their version-one format; data stored under another website origin must be exported/restored manually.

A private SQL backup was saved before restructuring in `E:\db-backups`. The local migration history was aligned with the separate table migrations without resetting tables, and all table row counts were verified unchanged. Do not ship that private backup with the public application.

## Verification and remaining activation

Backend tests cover authentication, password resets, admin isolation, encrypted settings/revision checks, trials/expiry, product access, snapshots, failed AI credits/evidence validation, usage reservations and verified PayPal lifecycle events. They run on SQLite and a separate MySQL test database. Parser/document regression tests cover WhatsApp dates, CSV escaping, JSON imports, deduplication, bounded reply gaps, conservative agreement detection, formula-safe CSV and document/calendar exports.

Local browser checks verify normal login, import review, MySQL snapshot save, and the preserved interfaces. Real OpenAI requests, PayPal transactions and cPanel SMTP/deployment still require your credentials and hosting, and are not claimed as live-tested.

## Owner-requested local reset — 12 October 2026

The current source was moved to `E:\laragon\www\clarity-laravel`. The earlier imported accounts described above are historical: they have now been replaced by exactly one administrator and one member, three enabled plans, and independent coherent starter workspaces. A private SQL backup precedes this reset. No test fixtures, fake settled payments, or fake provider subscriptions remain in the application database.

Administration has a dedicated `/admin/login` page, with role/status/verification checks before session establishment; guests requesting `/admin` are redirected there. The public footer links to that page. The updated brand uses a blue/violet logo, cool neutral surfaces, a responsive product preview landing page, and a shared modern design system in `public/css/modern.css`.
