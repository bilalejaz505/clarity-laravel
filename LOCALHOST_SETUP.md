# Run Clarity on localhost

Source: `E:\laragon\www\clarity-laravel`. The old Node application in `E:\conversation-suite` is a reference copy; run the Laravel folder.

This application uses Laravel 13, MySQL, Blade, plain browser JavaScript, and static CSS. Node, npm, Supabase, Livewire, and Inertia are not required. Composer manages PHP dependencies. The installed lockfile requires PHP 8.4.

## Existing installation on this computer

1. Start MySQL in Laragon. PHP and Composer are already available.
2. Open PowerShell:

   ```powershell
   php artisan serve --host=127.0.0.1 --port=8000
   ```

3. Visit `http://127.0.0.1:8000`. Use the public demo or register with an email and password. Account login uses Laravel sessions and hashed passwords.
4. To grant yourself administrator access, run in a second terminal:

   ```powershell
   php artisan clarity:admin your-email@example.com
   ```

   For a new account the command prompts for a hidden password. For an existing account it preserves the password and grants verified admin access. Log in normally, then open `/admin`.

The existing `.env` already targets the local MySQL database `clarity_laravel`. Do not run `migrate:fresh` against it: that would erase stored data. Do not regenerate its `APP_KEY`: saved provider credentials depend on that key.

## Fresh installation

Install PHP 8.4 with curl, mbstring, openssl, pdo_mysql, tokenizer, XML, fileinfo, session, and zip; Composer; and MySQL 8. Create an empty UTF-8 database. Then:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Edit `.env` with `APP_URL=http://127.0.0.1:8000`, `APP_ENV=local`, and your MySQL connection details. Then:

```powershell
php artisan migrate --seed
php artisan clarity:admin your-email@example.com
php artisan serve --host=127.0.0.1 --port=8000
```

Seeding creates application defaults, never a shared login. `composer dev` also starts the server. There is no frontend build step.

## Email, AI, and PayPal

Local `MAIL_MAILER=log` writes verification/reset emails to `storage/logs/laravel.log`; it does not deliver email. Configure your hosting SMTP to deliver them. Console-created admins are verified. Localhost no longer grants a special entitlement; access follows the same trial and package-subscription rules used by the deployed application.

In `/admin`, save the OpenAI key and model, then test the connection. Keys are encrypted in MySQL and never returned to the browser. The app's local observations and statistics work without a key; AI requests require one.

PayPal settings remain inactive until you supply a Business account's sandbox or live credentials, webhook ID, and matching monthly plan IDs. Use sandbox first. Packages priced at zero activate without PayPal. See `BILLING_ARCHITECTURE.md`.

## Verification and troubleshooting

```powershell
php artisan test
php artisan migrate:status
php artisan optimize:clear
```

Tests use an isolated in-memory SQLite database by default, not your MySQL application records. Optional browser-domain regression tests use `node --test tests/frontend/*.test.mjs`; Node is only needed for that optional developer check, never to run the app.

If port 8000 is occupied, stop the other server or choose another port and update `APP_URL`, then clear cached configuration. A SQL connection error means MySQL is stopped or `.env` credentials/database are wrong. A 419 response means the session/CSRF token expired; reload the form. Keep `storage` and `bootstrap/cache` writable.

## Current local accounts and administrator sign-in

The initialized local MySQL database contains four active, verified accounts: one administrator, one Chat Insights member, one Contracts member, and one Complete member. Their current generated passwords and exact access are saved in the private `Clarity-Local-Accounts-Current.txt` deliverable; credentials are never committed to source control. Administrator login is `/admin/login`; all member accounts use `/login`. The admin form refuses regular, suspended, or unverified accounts.

Three configured plans are stored in the application settings: Chat Plus (USD 9/month), Freelance Pro (USD 15/month), and Clarity Complete (USD 19/month). The administrator can change package details, availability, quotas, product access, and price. Setting the price to zero enables immediate free subscription without PayPal; positive prices require merchant credentials and matching provider plan IDs. The three initialized member accounts use clearly labeled `TEST_PAID` subscriptions and matching dummy completed billing events for pre-launch package-boundary testing. These are local fixtures, not PayPal transactions.

All four accounts have independent starter workspaces with a personal conversation, a Northstar Studio freelance project, reviewed terms, two milestones and two draft invoices. These are editable examples, clearly marked as starter/sample conversations. Saved server snapshots load automatically into the owning account's workspace; optional saved device data takes precedence. Changes remain in memory until you save them explicitly.

The old 13 imported migration accounts and associated stale records were removed after a private SQL backup. Migrations and encrypted provider-secret storage were preserved. Automated test fixtures remain isolated from this application database.

If you explicitly need to initialize the local database again, back it up first and run `php artisan clarity:reset-local-data --reset`. It creates new random passwords in `storage/app/private/local-access.txt`; choose a new `--credentials=ABSOLUTE_PRIVATE_PATH` if that file already exists. The command clears local application records, resets their numeric sequences, and intentionally refuses non-local environments. Do not run it to deploy or update a live site.

The current local accounts use integer IDs 1–4. Sessions were cleared during initialization, so sign in again with the current credential file.
