# Accounts, subscriptions, and provider configuration

Public landing, pricing, and sample demo are accessible to guests. Personal workspaces require email/password sessions. Hosted product access requires a verified account with an active trial or paid entitlement. Exact localhost in `APP_ENV=local` can use the authenticated free development plan. Administrators have verified role-based access; account headers from the old platform are not trusted.

## PayPal flow

The administrator supplies sandbox/live client ID, encrypted client secret, webhook ID, and monthly PayPal plan IDs. Configure matching prices/currency and enabled products in the admin plans. Checkout validates the provider's plan against the app's monthly plan, with no unexpected setup fee or provider trial. The app issues an approval URL only for the owning signed-in account, protects concurrent checkouts with a per-user database lock, and stores an immutable local plan snapshot.

The browser return is not payment proof. Server reconciliation verifies the subscription ID, provider custom ID/owner, and plan. Paid access is bounded by verified settled-payment information and the next billing time. Cancellation retains only the already verified paid period. Refund events apply a hold, including protection against a later activation event restoring access incorrectly.

PayPal webhooks are the sole CSRF-exempt route. The server verifies their transmission signature with PayPal before accepting them, deduplicates event IDs, and records processing. Other JSON writes use Laravel session authentication and CSRF protection. Set the webhook URL to `https://YOUR_DOMAIN/api/paypal/webhook` and subscribe to the relevant subscription lifecycle and payment/refund events used in `BillingController`.

Quotas reserve message units and AI requests atomically in MySQL before provider calls. Completion commits the reservation; failure credits it once. Trial activation is a one-time account operation. Paid limits come from the subscription's stored plan snapshot, so editing a plan does not silently rewrite an existing customer's purchase.

## Code map

- `app/Http/Controllers/Api/BillingController.php`: authenticated checkout, cancellation, refresh, and verified webhook orchestration.
- `app/Services/PayPalService.php`: provider OAuth, plan validation, reconciliation, and payment-period verification.
- `app/Services/EntitlementService.php`: product access, trial and localhost rules.
- `app/Services/UsageMeter.php`: quota reservation and failure credits.
- `app/Services/AppSettingsService.php`: defaults, settings revision, encrypted provider secrets, and audit entries.
- `app/Http/Requests`: input validation; `app/Models`: persisted records; `database/migrations`: one table per file.

OpenAI keys and PayPal secrets are encrypted using Laravel `APP_KEY`. Keep that key stable and back it up securely. Public config exposes connection status, never plaintext secrets.

## Activation status

Local authentication, settings, snapshots, entitlements, metering, and provider lifecycle logic are implemented. Automated tests use simulated OpenAI/PayPal responses. Real provider requests and payment settlement have not been tested because merchant credentials and an OpenAI key have not been supplied. cPanel/SMTP deployment is also pending hosting configuration. PayPal processing fees depend on the merchant account; this app requires no Supabase subscription.
