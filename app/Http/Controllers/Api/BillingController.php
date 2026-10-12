<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\SubscriptionActionRequest;
use App\Services\AppSettingsService;
use App\Services\EntitlementService;
use App\Services\PayPalService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class BillingController extends Controller
{
    public function action(SubscriptionActionRequest $request, EntitlementService $access, PayPalService $paypal, AppSettingsService $settings): Response
    {
        $u = $request->user();
        $s = $settings->get();
        abort_unless($u->status === 'active' && ($u->hasVerifiedEmail() || $access->local($request)) && ! $s['maintenance'], 403, 'Billing is unavailable for this account.');
        if ($request->action === 'checkout') {
            abort_if($access->local($request), 422, 'Localhost uses the free development plan. Test paid checkout on HTTPS staging.');
            $plan = collect($s['plans'])->first(fn ($p) => $p['key'] === $request->planKey && $p['enabled']);
            abort_unless($plan && ! empty($plan['paypalPlanId']), 422, 'This plan is not connected to PayPal yet.');
            $token = (string) Str::uuid();
            DB::transaction(function () use ($u, $token) {
                DB::table('users')->where('id', $u->id)->lockForUpdate()->first();
                $lock = DB::table('checkout_locks')->where('user_id', $u->id)->first();
                abort_if($lock && now()->lt($lock->expires_at), 409, 'Another checkout request is in progress.');
                abort_if(DB::table('subscriptions')->where('user_id', $u->id)->whereIn('status', ['ACTIVE', 'APPROVAL_PENDING', 'APPROVED', 'SUSPENDED'])->exists(), 409, 'You already have a subscription in progress. Manage it first.');
                DB::table('checkout_locks')->updateOrInsert(['user_id' => $u->id], ['token' => $token, 'expires_at' => now()->addMinutes(2)]);
            }, 3);
            try {
                $paypal->validatePlan($plan);
                $origin = rtrim(config('app.url'), '/');
                $remote = $paypal->request('/v1/billing/subscriptions', 'POST', ['plan_id' => $plan['paypalPlanId'], 'custom_id' => $u->id, 'application_context' => ['brand_name' => $s['appName'], 'user_action' => 'SUBSCRIBE_NOW', 'return_url' => $origin.'/account?checkout=returned', 'cancel_url' => $origin.'/pricing?checkout=cancelled']], $token);
                $approval = collect($remote['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;
                abort_unless(is_string($approval) && preg_match('~^https://(www\.)?(sandbox\.)?paypal\.com/~', $approval), 502, 'PayPal returned an invalid approval URL.');
                DB::table('subscriptions')->insert(['id' => $remote['id'], 'user_id' => $u->id, 'plan_key' => $plan['key'], 'status' => $remote['status'], 'plan_snapshot' => json_encode($plan), 'created_at' => now(), 'updated_at' => now()]);
                $settings->audit($u->id, 'subscription.created', $remote['id'].': '.$plan['key']);

                return response()->json(['approvalUrl' => $approval]);
            } finally {
                DB::table('checkout_locks')->where('user_id', $u->id)->where('token', $token)->delete();
            }
        }

        $sub = DB::table('subscriptions')->where('id', $request->subscriptionId)->where('user_id', $u->id)->first();
        abort_unless($sub, 404, 'Subscription not found.');
        if ($request->action === 'cancel') {
            $paypal->request('/v1/billing/subscriptions/'.rawurlencode($sub->id).'/cancel', 'POST', ['reason' => 'Customer requested cancellation in Clarity.']);
        }

        return response()->json($paypal->reconcile($sub->id));
    }

    public function webhook(Request $request, AppSettingsService $settings, PayPalService $paypal): Response
    {
        abort_if(strlen($request->getContent()) > 1000000, 413);
        $s = $settings->get();
        abort_unless($s['paypalWebhookId'], 503, 'Webhook is not configured.');
        $headers = ['transmission_id' => 'paypal-transmission-id', 'transmission_time' => 'paypal-transmission-time', 'cert_url' => 'paypal-cert-url', 'auth_algo' => 'paypal-auth-algo', 'transmission_sig' => 'paypal-transmission-sig'];
        $body = [];
        foreach ($headers as $key => $header) {
            abort_unless($request->header($header), 400, 'Missing PayPal signature.');
            $body[$key] = $request->header($header);
        }
        $event = $request->json()->all();
        abort_unless(is_string($event['id'] ?? null) && strlen($event['id']) <= 100 && is_string($event['event_type'] ?? null), 400);
        $body['webhook_id'] = $s['paypalWebhookId'];
        $body['webhook_event'] = $event;
        abort_unless(($paypal->request('/v1/notifications/verify-webhook-signature', 'POST', $body)['verification_status'] ?? '') === 'SUCCESS', 401, 'Invalid signature.');
        $id = $event['resource']['billing_agreement_id'] ?? $event['resource']['supplementary_data']['related_ids']['subscription_id'] ?? (str_starts_with($event['event_type'], 'BILLING.SUBSCRIPTION.') ? ($event['resource']['id'] ?? null) : null);
        if (DB::table('billing_events')->where('id', $event['id'])->value('status') === 'completed') {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }
        DB::table('billing_events')->insertOrIgnore(['id' => $event['id'], 'type' => $event['event_type'], 'subscription_id' => $id, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        try {
            if ($id && DB::table('subscriptions')->where('id', $id)->exists()) {
                $paypal->reconcile($id);
                if (in_array($event['event_type'], ['PAYMENT.SALE.REFUNDED', 'PAYMENT.SALE.REVERSED'])) {
                    DB::table('subscriptions')->where('id', $id)->update(['status' => 'REVIEW_REQUIRED', 'paid_until' => null, 'refund_cutoff' => isset($event['create_time']) ? CarbonImmutable::parse($event['create_time']) : now(), 'updated_at' => now()]);
                }
            }
            DB::table('billing_events')->where('id', $event['id'])->update(['status' => 'completed', 'updated_at' => now()]);
            $settings->audit('paypal', 'webhook.verified', $event['event_type'].': '.($id ?: 'unmapped'));

            return response()->json(['ok' => true]);
        } catch (\Throwable) {
            abort(503, 'Webhook processing failed; retry required.');
        }
    }
}
