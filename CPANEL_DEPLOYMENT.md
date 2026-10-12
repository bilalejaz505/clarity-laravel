# Deploy Clarity to cPanel

Updated for the Laravel/MySQL/Blade source in `E:\laragon\www\clarity-laravel`. The previous `chatgpt.site` deployment still runs the old application; this PHP version needs your cPanel hosting. No cPanel server has been deployed or verified yet.

## Hosting requirements

Use PHP **8.4** for this Composer lockfile, MySQL 8, Apache rewriting, HTTPS, and writable `storage` and `bootstrap/cache`. Enable curl, mbstring, openssl, pdo_mysql, tokenizer, XML, fileinfo, session, and zip. Outbound HTTPS must reach OpenAI and PayPal. SMTP can use your existing hosting mail service. OCR runs in the browser and downloads language/worker resources; it requires the browser to reach those resources.

## First deployment

1. Back up your current website and database. In cPanel's MySQL Database Wizard create a dedicated database and user with privileges on that database. cPanel prefixes their names; use the full names in `.env`.
2. Upload the Laravel application to a private directory such as `/home/CPANEL_USER/clarity`. Exclude `.env`, `.git`, local databases, SQL backups, logs, cache files, and test credentials. There are no Node dependencies or frontend build commands.
3. Set the domain/subdomain document root to `/home/CPANEL_USER/clarity/public`. Never expose the project root. If the host fixes `public_html` as document root, ask it to point that directory to Laravel's `public` directory. If that is impossible, keep the application outside `public_html`, copy only `public` contents into `public_html`, and update `index.php`'s vendor/bootstrap paths plus Laravel's public path accordingly; the preferred supported layout is a document root directly at `public`.
4. In cPanel Terminal, using the PHP 8.4 binary configured by the host:

   ```sh
   cd /home/CPANEL_USER/clarity
   composer install --no-dev --optimize-autoloader
   cp .env.example .env
   php artisan key:generate
   ```

   Generate the key only on the first new installation. When migrating existing encrypted settings, transfer the same `APP_KEY` securely alongside the database.
5. Configure `.env`:

   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.example
   LOCALHOST_FREE_PLAN=false
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=CPANEL_PREFIX_clarity
   DB_USERNAME=CPANEL_PREFIX_clarityuser
   DB_PASSWORD="YOUR_DATABASE_PASSWORD"
   SESSION_DRIVER=database
   SESSION_SECURE_COOKIE=true
   CACHE_STORE=database
   MAIL_MAILER=smtp
   MAIL_HOST=YOUR_SMTP_HOST
   MAIL_PORT=587
   MAIL_USERNAME=YOUR_SMTP_USERNAME
   MAIL_PASSWORD="YOUR_SMTP_PASSWORD"
   MAIL_FROM_ADDRESS=accounts@your-domain.example
   MAIL_FROM_NAME="Clarity"
   ```

   Configure SMTP encryption according to your host's Laravel mail settings. Keep `.env` private and out of archives shared publicly.
6. Finish setup:

   ```sh
   php artisan migrate --force --seed
   php artisan clarity:admin your-email@example.com
   php artisan optimize
   ```

   Use directory ownership/group permissions appropriate to your hosting account, commonly 755 directories and 644 files, with write access for the PHP user to `storage` and `bootstrap/cache`. Do not make the entire app world-writable.
7. Visit `/`, `/login`, `/register`, `/account`, and `/admin`. Verify email delivery, password reset, ordinary-user admin denial, private snapshot ownership, trial expiry, and HTTPS cookies. Configure provider settings in `/admin`. Test PayPal sandbox subscription creation, verified webhooks, cancellation and refund handling before enabling live checkout.

If Composer/SSH are unavailable, create a deployment copy locally using the same PHP version, run `composer install --no-dev --optimize-autoloader` there, and upload its `vendor` folder with the app. Do not remove developer dependencies from your working source just to prepare that copy. Use the hosting terminal or host support to run Artisan migrations; do not create a public migration/key-generation endpoint.

## Updates and rollback

Back up MySQL and `APP_KEY`/encrypted settings before each release. Upload the new source and static assets, preserve `.env` and `storage`, run Composer install, `php artisan migrate --force`, and `php artisan optimize`. `migrate:fresh`, `db:wipe`, and key regeneration are not deployment commands. For rollback, restore the matching code/database backup rather than blindly rolling back populated tables.

The current schema has one migration per table. Its original migrations now define numeric application IDs and matching ownership references. A fresh database runs these migrations normally. Do not manually alter migration history on another server.

## Operations

Monitor `storage/logs`, MySQL backups, SMTP delivery, and webhook failures. Keep secrets and backups outside the web root. The app currently sends auth mail synchronously and has no scheduled billing jobs: access is time-bound from verified payment data, refreshed by signed PayPal webhooks or account reconciliation. A queue worker is optional if asynchronous jobs are introduced later; document that change at the same time.

Maintain this guide whenever PHP requirements, schema, environment variables, paths, authentication, or billing behavior change.

Administrator sign-in is `/admin/login`; regular member sign-in is `/login`. Initial local passwords/accounts are development-only and are never created by the production default seeder. Use `clarity:admin` to create your own production administrator with a private password. Do not deploy local credential files or the local-reset SQL backup.

The current release's original migrations define numeric application record IDs and matching integer ownership references. This schema was installed through an explicitly requested fresh local reset. For a new cPanel database, run the original migrations normally. An older populated UUID-schema database cannot be upgraded by simply running these revised, already-recorded migrations; use a planned conversion or restore/recreate only with explicit authorization and a backup. Never run `migrate:fresh` as a routine production update.

Notification assets (SweetAlert2 and Clarity styles/scripts) are self-hosted under public/vendor, public/css and public/js. Upload them with the rest of public; no CDN or Node build is required.
