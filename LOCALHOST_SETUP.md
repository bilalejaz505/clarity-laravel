# Run Clarity on localhost

Source: `E:\clarity-laravel`. The old Node application in `E:\conversation-suite` is a reference copy; run the Laravel folder.

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

Edit `.env` with `APP_URL=http://127.0.0.1:8000`, `APP_ENV=local`, your MySQL connection details, and `LOCALHOST_FREE_PLAN=true`. Then:

```powershell
php artisan migrate --seed
php artisan clarity:admin your-email@example.com
php artisan serve --host=127.0.0.1 --port=8000
```

Seeding creates application defaults, never a shared login. `composer dev` also starts the server. There is no frontend build step.

## Email, AI, and PayPal

Local `MAIL_MAILER=log` writes verification/reset emails to `storage/logs/laravel.log`; it does not deliver email. Configure your hosting SMTP to deliver them. Console-created admins are verified. The authenticated localhost plan works only when the application environment is `local` and the request host is exact loopback.

In `/admin`, save the OpenAI key and model, then test the connection. Keys are encrypted in MySQL and never returned to the browser. The app's local observations and statistics work without a key; AI requests require one.

PayPal settings remain inactive until you supply a Business account's sandbox or live credentials, webhook ID, and matching monthly plan IDs. Use sandbox first. See `BILLING_ARCHITECTURE.md`.

## Verification and troubleshooting

```powershell
php artisan test
php artisan migrate:status
php artisan optimize:clear
```

Tests use an isolated in-memory SQLite database by default, not your MySQL application records. Optional browser-domain regression tests use `node --test tests/frontend/*.test.mjs`; Node is only needed for that optional developer check, never to run the app.

If port 8000 is occupied, stop the other server or choose another port and update `APP_URL`, then clear cached configuration. A SQL connection error means MySQL is stopped or `.env` credentials/database are wrong. A 419 response means the session/CSRF token expired; reload the form. Keep `storage` and `bootstrap/cache` writable.
