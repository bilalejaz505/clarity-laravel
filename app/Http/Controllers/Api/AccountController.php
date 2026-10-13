<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AppSettingsService;
use App\Services\EntitlementService;
use App\Services\UsageMeter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AccountController extends Controller
{
    public function show(
        Request $request,
        EntitlementService $access,
        UsageMeter $usage,
    ): Response {
        $u = $request->user();
        $e = $access->entitlement($u, $access->local($request));
        $c = DB::table('usage_counters')
            ->where('user_id', $u->id)
            ->whereIn('bucket_key', $usage->counterKeys($u, $e['bucket']))
            ->first();

        return response()->json([
            'member' => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ],
            'admin' => $access->admin($u),
            'local' => $access->local($request),
            'entitlement' => $e,
            'usage' => [
                'messages' => $c->messages ?? 0,
                'requests' => $c->requests ?? 0,
            ],
            'trialAvailable' => $access->settings->get()['trialEnabled'] && ! $u->trial_start,
            'subscriptions' => DB::table('subscriptions')
                ->where('user_id', $u->id)
                ->orderByDesc('updated_at')
                ->get(),
        ]);
    }

    public function trial(
        Request $request,
        EntitlementService $access,
        AppSettingsService $settings,
    ): Response {
        $s = $settings->get();
        abort_unless(
            $request->user()->status === 'active' &&
                ($request->user()->hasVerifiedEmail() ||
                    $access->local($request)) &&
                $s['trialEnabled'] &&
                ! $s['maintenance'],
            403,
            'Trial access is unavailable.',
        );
        $changed = DB::table('users')
            ->where('id', $request->user()->id)
            ->whereNull('trial_start')
            ->whereNull('trial_end')
            ->update([
                'trial_start' => now(),
                'trial_end' => now()->addDays($s['trialDays']),
            ]);
        abort_unless($changed, 409, 'This account has already used its trial.');
        $settings->audit(
            $request->user()->id,
            'trial.started',
            'One-time trial activated',
        );

        return response()->json(['ok' => true]);
    }

    public function config(
        Request $request,
        EntitlementService $access,
        AppSettingsService $settings,
    ): Response {
        $s = $settings->get();
        $u = $request->user();
        $e = $u ? $access->entitlement($u, $access->local($request)) : null;

        return response()->json([
            'ai' => $s['aiEnabled'] &&
                (bool) ($settings->secret('openai') ?:
                    config('clarity.openai_key')) &&
                (bool) ($s['aiModel'] ?: config('clarity.openai_model')),
            'cloud' => (bool) $u,
            'model' => $s['aiModel'] ?: config('clarity.openai_model'),
            'admin' => $u ? $access->admin($u) : false,
            'products' => $e['products'] ?? [],
            'local' => $access->local($request),
            'notice' => $s['notice'],
            'maxUploadMB' => $s['maxUploadMB'],
            'maxImportMessages' => $s['maxImportMessages'],
        ]);
    }
}
