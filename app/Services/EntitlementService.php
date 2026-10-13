<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntitlementService
{
    public function __construct(public AppSettingsService $settings) {}

    public function local(Request $request): bool
    {
        return app()->environment('local') &&
            in_array(
                strtolower($request->getHost()),
                ['localhost', '127.0.0.1', '[::1]', '::1'],
                true,
            );
    }

    public function admin(User $u): bool
    {
        $u->refresh();

        return $u->status === 'active' &&
            $u->hasVerifiedEmail() &&
            ($u->is_admin ||
                in_array(
                    strtolower($u->email),
                    array_map('strtolower', config('clarity.admin_emails')),
                    true,
                ));
    }

    public function entitlement(User $u, bool $local): array
    {
        $u->refresh();
        $s = $this->settings->get();
        $none = [
            'allowed' => false,
            'reason' => 'Choose a paid plan or start your one-time trial.',
            'plan' => 'none',
            'products' => [],
            'messages' => 0,
            'requests' => 0,
            'bucket' => 'none',
            'expires' => null,
        ];
        if ($u->status !== 'active') {
            return array_replace($none, [
                'reason' => 'This account is suspended. Contact the administrator.',
            ]);
        }

        if (! $u->hasVerifiedEmail() && ! $local) {
            return array_replace($none, [
                'reason' => 'Verify your email to access your workspace.',
            ]);
        }

        if ($this->admin($u)) {
            return [
                'allowed' => true,
                'reason' => 'Administrator inspection access',
                'plan' => 'admin',
                'products' => ['chat', 'freelance'],
                'messages' => 1000000000,
                'requests' => 1000000000,
                'bucket' => 'admin',
                'expires' => null,
            ];
        }

        if ($s['maintenance']) {
            return array_replace($none, [
                'reason' => 'The apps are temporarily under maintenance.',
            ]);
        }

        $paidStatuses = ['ACTIVE', 'CANCELLED'];
        if (app()->environment('local')) {
            $paidStatuses[] = 'TEST_PAID';
        }

        $subs = DB::table('subscriptions')
            ->where('user_id', $u->id)
            ->where(function ($query) use ($paidStatuses): void {
                $query
                    ->where('status', 'FREE_ACTIVE')
                    ->orWhere(function ($query) use ($paidStatuses): void {
                        $query
                            ->whereIn('status', $paidStatuses)
                            ->where('paid_until', '>', now());
                    });
            })
            ->get();
        $plans = [];
        foreach ($subs as $sub) {
            $plan = $sub->plan_snapshot
                ? json_decode($sub->plan_snapshot, true)
                : collect($s['plans'])->firstWhere('key', $sub->plan_key);
            if ($plan) {
                $plans[] = [$sub, $plan];
            }
        }

        if ($plans) {
            $products = [];
            $messages = 0;
            $requests = 0;
            $buckets = [];
            $expires = [];
            $names = [];
            foreach ($plans as [$sub, $p]) {
                $products = array_merge($products, $p['products']);
                $messages += $p['messages'];
                $requests += $p['requests'];
                $names[] = $p['name'];
                $buckets[] =
                    $sub->paypal_id.
                    ':'.
                    ($sub->period_start ?: $sub->paid_until);
                if ($sub->paid_until) {
                    $expires[] = CarbonImmutable::parse(
                        $sub->paid_until,
                    )->toISOString();
                }
            }
            sort($buckets);
            sort($expires);

            $statuses = collect($plans)->pluck('0.status');
            $reason = match (true) {
                $statuses->contains('TEST_PAID') => 'Test payment access',
                $statuses->every(fn (string $status): bool => $status === 'FREE_ACTIVE') => 'Free package subscription',
                default => 'Verified paid subscription',
            };

            return [
                'allowed' => true,
                'reason' => $reason,
                'plan' => implode(' + ', $names),
                'products' => array_values(array_unique($products)),
                'messages' => $messages,
                'requests' => $requests,
                'bucket' => implode('|', $buckets),
                'expires' => $expires[0] ?? null,
            ];
        }

        if ($u->trial_start && $u->trial_end && $u->trial_end->isFuture()) {
            return [
                'allowed' => true,
                'reason' => 'Time-limited trial',
                'plan' => 'Trial',
                'products' => ['chat', 'freelance'],
                'messages' => $s['trialMessages'],
                'requests' => $s['trialRequests'],
                'bucket' => 'trial:'.$u->trial_start->toISOString(),
                'expires' => $u->trial_end->toISOString(),
            ];
        }

        return array_replace($none, [
            'reason' => $u->trial_end
                ? 'Your trial has ended. Choose a paid plan to continue.'
                : $none['reason'],
        ]);
    }

    public function requireProduct(Request $request, string $product): array
    {
        $u = $request->user();
        abort_unless($u, 401);
        $e = $this->entitlement($u, $this->local($request));
        abort_unless(
            $e['allowed'] && in_array($product, $e['products']),
            403,
            $e['reason'],
        );

        return $e;
    }

    public function dashboardPath(User $user, bool $local): string
    {
        $entitlement = $this->entitlement($user, $local);

        if (! $user->hasVerifiedEmail() && ! $local) {
            return '/email/verify';
        }

        if (! $entitlement['allowed']) {
            return '/account';
        }

        return in_array('chat', $entitlement['products'], true)
            ? '/chats'
            : '/contracts';
    }
}
