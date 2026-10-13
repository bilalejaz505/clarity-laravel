<?php

namespace App\Http\Middleware;

use App\Services\AppSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplicationMaintenance
{
    public function __construct(private AppSettingsService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $config = $this->settings->get();
        if (! $config['maintenance']) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(
                ['message' => $config['maintenanceMessage']],
                503,
            );
        }

        return response()->view('maintenance', ['settings' => $config], 503);
    }
}
