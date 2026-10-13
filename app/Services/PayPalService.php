<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPalService
{
    public function __construct(public AppSettingsService $settings) {}

    public function request(
        string $path,
        string $method = 'GET',
        ?array $body = null,
        ?string $id = null,
    ): array {
        $s = $this->settings->get();
        $secret =
            $this->settings->secret('paypal') ?:
            config('clarity.paypal_secret');
        if (! $s['paypalClientId'] || ! $secret) {
            throw new RuntimeException(
                'PayPal is not connected. Configure merchant credentials in admin.',
            );
        }
        $origin =
            $s['paypalEnvironment'] === 'live'
                ? 'https://api-m.paypal.com'
                : 'https://api-m.sandbox.paypal.com';
        $token = Http::withBasicAuth($s['paypalClientId'], $secret)
            ->asForm()
            ->timeout(20)
            ->post($origin.'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);
        if (! $token->successful() || ! $token->json('access_token')) {
            throw new RuntimeException(
                'PayPal merchant authentication failed.',
            );
        }
        $client = Http::withToken($token->json('access_token'))
            ->acceptJson()
            ->timeout(25);
        if ($id) {
            $client = $client->withHeaders(['PayPal-Request-Id' => $id]);
        }
        $response = $client->send(
            $method,
            $origin.$path,
            $body === null ? [] : ['json' => $body],
        );
        if (! $response->successful()) {
            throw new RuntimeException(
                'PayPal request failed ('.$response->status().').',
            );
        }

        return $response->status() === 204 ? [] : $response->json() ?? [];
    }

    public function validatePlan(array $plan): void
    {
        $p = $this->request(
            '/v1/billing/plans/'.rawurlencode($plan['paypalPlanId']),
        );
        $cycles = $p['billing_cycles'] ?? [];
        $regular = collect($cycles)->firstWhere('tenure_type', 'REGULAR');
        if (
            ($p['status'] ?? '') !== 'ACTIVE' ||
            collect($cycles)->contains('tenure_type', 'TRIAL') ||
            (float) ($p['payment_preferences']['setup_fee']['value'] ?? 0) >
                0 ||
            ! $regular ||
            ($regular['frequency']['interval_unit'] ?? '') !== 'MONTH' ||
            ($regular['frequency']['interval_count'] ?? 0) !== 1 ||
            ($regular['pricing_scheme']['fixed_price']['currency_code'] ??
                '') !==
                $plan['currency'] ||
            (float) ($regular['pricing_scheme']['fixed_price']['value'] ??
                0) !== (float) $plan['price']
        ) {
            throw new RuntimeException(
                'PayPal plan does not match the configured monthly price/currency.',
            );
        }
    }

    public function paidWindow(array $remote, array $plan): array
    {
        $last = $remote['billing_info']['last_payment'] ?? [];
        $next = $remote['billing_info']['next_billing_time'] ?? null;
        if (
            ($remote['status'] ?? '') !== 'ACTIVE' ||
            empty($last['time']) ||
            ! $next ||
            ($last['amount']['currency_code'] ?? '') !== $plan['currency'] ||
            (float) ($last['amount']['value'] ?? 0) < $plan['price']
        ) {
            return [null, null];
        }
        try {
            $start = CarbonImmutable::parse($last['time']);
            $until = CarbonImmutable::parse($next)->min(
                $start->addMonthNoOverflow(),
            );

            return $start->isPast() && $until->isFuture()
                ? [$start, $until]
                : [null, null];
        } catch (\Throwable) {
            return [null, null];
        }
    }

    public function reconcile(int $id): array
    {
        $record = DB::table('subscriptions')->where('id', $id)->first();
        if (! $record) {
            throw new RuntimeException('Subscription not found.');
        }
        $remote = $this->request(
            '/v1/billing/subscriptions/'.rawurlencode($record->paypal_id),
        );
        $plan = $record->plan_snapshot
            ? json_decode($record->plan_snapshot, true)
            : collect($this->settings->get()['plans'])->firstWhere(
                'key',
                $record->plan_key,
            );
        if (
            ! $plan ||
            ($remote['plan_id'] ?? '') !== $plan['paypalPlanId'] ||
            (string) ($remote['custom_id'] ?? '') !==
                (string) ($record->provider_custom_id ?: $record->user_id)
        ) {
            throw new RuntimeException(
                'Subscription ownership or plan validation failed.',
            );
        }

        return DB::transaction(function () use ($id, $remote, $plan) {
            $record = DB::table('subscriptions')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();
            [$start, $until] = $this->paidWindow($remote, $plan);
            $held =
                $record->status === 'REVIEW_REQUIRED' &&
                (! $start ||
                    $start->lessThanOrEqualTo(
                        CarbonImmutable::parse(
                            $record->refund_cutoff ?: $record->updated_at,
                        ),
                    ));
            $status = $held ? 'REVIEW_REQUIRED' : $remote['status'];
            $until = $held
                ? null
                : ($until ?:
                ($status === 'CANCELLED'
                    ? $record->paid_until
                    : null));
            DB::table('subscriptions')
                ->where('id', $id)
                ->update([
                    'status' => $status,
                    'period_start' => $start ?: $record->period_start,
                    'paid_until' => $until,
                    'provider_updated_at' => isset($remote['update_time'])
                        ? CarbonImmutable::parse($remote['update_time'])
                        : null,
                    'updated_at' => now(),
                ]);
            $this->settings->audit(
                $record->user_id,
                'subscription.reconciled',
                "$id: $status",
            );

            return ['status' => $status, 'paidUntil' => $until];
        }, 3);
    }
}
