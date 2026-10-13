<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminActionRequest;
use App\Models\User;
use App\Services\AppSettingsService;
use App\Services\EntitlementService;
use App\Services\MailSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    public function index(
        Request $request,
        AppSettingsService $settings,
    ): Response {
        $search = mb_substr((string) $request->query('search', ''), 0, 100);

        return response()->json([
            'config' => $settings->get(),
            'revision' => $settings->revision(),
            'users' => User::query()
                ->where(
                    fn ($q) => $q
                        ->where('email', 'like', "%$search%")
                        ->orWhere('name', 'like', "%$search%"),
                )
                ->orderByDesc('last_seen')
                ->limit(200)
                ->get([
                    'id',
                    'email',
                    'name',
                    'status',
                    'is_admin',
                    'last_seen',
                    'created_at',
                ]),
            'subscriptions' => DB::table('subscriptions')
                ->orderByDesc('updated_at')
                ->limit(200)
                ->get(),
            'usage' => DB::table('usage_reservations')
                ->orderByDesc('created_at')
                ->limit(200)
                ->get(),
            'audit' => DB::table('audit_logs')
                ->orderByDesc('created_at')
                ->limit(100)
                ->get(),
            'secrets' => DB::table('app_secrets')->get([
                'id',
                'provider',
                'updated_at',
            ]),
            'totals' => [
                'users' => User::count(),
                'subscriptions' => DB::table('subscriptions')->count(),
                'requests' => DB::table('usage_reservations')->count(),
                'messages' => DB::table('usage_reservations')
                    ->where('status', 'completed')
                    ->sum('messages'),
            ],
            'server' => [
                'encryption' => (bool) config('app.key'),
                'openai' => (bool) $settings->secret('openai'),
                'database' => config('database.default'),
                'auth' => 'Laravel sessions',
            ],
        ]);
    }

    public function save(
        AdminActionRequest $request,
        AppSettingsService $settings,
        EntitlementService $access,
    ): Response {
        if ($request->action === 'user-status') {
            $user = User::findOrFail($request->userId);
            abort_if(
                $user->id === $request->user()->id || $user->is_admin,
                422,
                'Super-admin accounts cannot be suspended here.',
            );
            $user->forceFill(['status' => $request->status])->save();
            $settings->audit(
                $request->user()->id,
                'user.status',
                "$user->id: $request->status",
            );

            return response()->json(['ok' => true]);
        }

        if ($request->action === 'delete-secret') {
            DB::table('app_secrets')
                ->where('provider', $request->provider)
                ->delete();
            $settings->audit(
                $request->user()->id,
                'secret.removed',
                $request->provider,
            );

            return response()->json(['ok' => true]);
        }

        $data = $request->validated();
        $config = $data['config'];
        DB::transaction(function () use ($request, $settings, $config) {
            DB::table('app_settings')->insertOrIgnore([
                'key' => 'application',
                'data' => json_encode($settings->defaults()),
                'revision' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $row = DB::table('app_settings')
                ->where('key', 'application')
                ->lockForUpdate()
                ->first();
            abort_unless(
                $row->revision === (int) $request->revision,
                409,
                'Settings changed in another session. Reload before saving.',
            );
            DB::table('app_settings')
                ->where('key', 'application')
                ->update([
                    'data' => json_encode($config, JSON_THROW_ON_ERROR),
                    'revision' => $row->revision + 1,
                    'updated_at' => now(),
                ]);
            foreach (
                [
                    'openaiKey' => 'openai',
                    'paypalSecret' => 'paypal',
                    'smtpPassword' => 'smtp',
                ] as $field => $id
            ) {
                if ($request->filled($field)) {
                    $settings->saveSecret($id, $request->input($field));
                }
            }
            $settings->audit(
                $request->user()->id,
                'settings.updated',
                'Application settings revision '.($row->revision + 1),
            );
        }, 3);

        return response()->json(['ok' => true]);
    }

    public function connection(
        Request $request,
        AppSettingsService $settings,
        MailSettingsService $mail,
    ): Response {
        if ($request->input('provider') === 'smtp') {
            $config = $settings->get();
            abort_unless(
                $config['smtpEnabled'] &&
                    $config['smtpHost'] &&
                    $config['smtpFromAddress'],
                422,
                'Save and enable the SMTP settings first.',
            );
            $mail->apply($config);
            Mail::raw(
                'Your Clarity SMTP settings are working.',
                fn ($message) => $message
                    ->to($request->user()->email)
                    ->subject('Clarity SMTP test'),
            );

            return response()->json(['ok' => true]);
        }

        $key = $settings->secret('openai');
        $model = $settings->get()['aiModel'];
        abort_unless(
            $key && $model,
            422,
            'Save an OpenAI key and model first.',
        );
        $response = Http::withToken($key)
            ->timeout(20)
            ->get('https://api.openai.com/v1/models/'.rawurlencode($model));
        abort_unless(
            $response->successful(),
            502,
            'The saved key/model could not be verified.',
        );

        return response()->json(['ok' => true, 'model' => $model]);
    }
}
