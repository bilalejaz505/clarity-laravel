<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\AppSettingsService;
use App\Services\EntitlementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PageController extends Controller
{
    public function __construct(
        public AppSettingsService $settings,
        public EntitlementService $access,
    ) {}

    public function landing(Request $request): View|Response
    {
        return view('landing', [
            'settings' => $this->settings->get(),
            'local' => $this->access->local($request),
        ]);
    }

    public function pricing(Request $request): View|Response
    {
        return view('pricing', [
            'settings' => $this->settings->get(),
            'local' => $this->access->local($request),
        ]);
    }

    public function demo(Request $request): View|Response
    {
        abort_unless($this->settings->get()['demoEnabled'], 404);

        return view('workspace', [
            'product' => $request->query('product') === 'freelance'
                    ? 'freelance'
                    : 'chat',
            'demo' => true,
        ]);
    }

    public function workspace(Request $request): View|Response
    {
        $product = $request->is('contracts', 'agreements')
            ? 'freelance'
            : 'chat';
        $e = $this->access->entitlement(
            $request->user(),
            $this->access->local($request),
        );
        if (! $e['allowed']) {
            return response()->view('gate', ['reason' => $e['reason']], 403);
        }
        if (! in_array($product, $e['products'])) {
            $productName = $product === 'chat' ? 'Chat Insights' : 'Contracts';

            return response()->view(
                'gate',
                [
                    'reason' => "Your {$e['plan']} package does not include $productName.",
                ],
                403,
            );
        }

        return view('workspace', [
            'product' => $product,
            'demo' => false,
            'initialStore' => Workspace::where(
                'user_id',
                $request->user()->id,
            )->first()?->data,
        ]);
    }

    public function account(): View|Response
    {
        return view('account');
    }

    public function admin(): View|Response
    {
        return view('admin');
    }
}
