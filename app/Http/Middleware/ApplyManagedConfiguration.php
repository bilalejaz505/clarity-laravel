<?php

namespace App\Http\Middleware;

use App\Services\AppSettingsService;
use App\Services\MailSettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ApplyManagedConfiguration
{
    public function __construct(
        private AppSettingsService $settings,
        private MailSettingsService $mail,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (Schema::hasTable('app_settings')) {
            $config = $this->settings->get();
            config()->set('app.name', $config['appName']);
            $this->mail->apply($config);
        }

        return $next($request);
    }
}
