# Clarity

Conversation insights and freelance agreement tracking, built with Laravel 13, MySQL and Blade. Source on this computer: `E:\clarity-laravel`.

No Node/npm, Next.js, React, Livewire, Inertia or Supabase is required. Browser scripts and styles are served directly from `public`. PHP libraries live in Composer's standard `vendor` directory. This lockfile needs PHP 8.4.

## Start the existing local installation

Start MySQL in Laragon, then:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Open http://127.0.0.1:8000. Register and log in normally. Grant your account administration using `php artisan clarity:admin your-email@example.com`, then manage settings and provider credentials at `/admin`.

## Guides

- [Localhost setup](LOCALHOST_SETUP.md): existing/fresh setup, authentication, email and troubleshooting.
- [cPanel deployment](CPANEL_DEPLOYMENT.md): PHP/MySQL requirements, secure public root, releases and backups.
- [Billing architecture](BILLING_ARCHITECTURE.md): PayPal activation, entitlements, quotas and credential storage.
- [Migration inventory](FEATURE_PARITY.md): preserved features, source structure, data migration and verification limits.

## Development

Run `php artisan test` for isolated backend tests and `php vendor/bin/pint --dirty --format agent` for PHP formatting. Optional parser/document checks: `node --test tests/frontend/*.test.mjs`. Those optional tests do not add a Node runtime or package dependency to the application.

Each table has its own migration. Keep input validation in Form Requests, provider/business behavior in services, persistence in Eloquent models or transactional Laravel queries, and page markup in Blade. Preserve the existing APP_KEY and MySQL records when updating an installed instance. Never use `migrate:fresh` as a deployment command.

OpenAI and PayPal connections need your credentials; live provider transactions and cPanel deployment remain unverified until those are configured. The previous hosted preview is separate from this PHP application.
