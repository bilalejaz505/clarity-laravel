<?php

namespace App\Providers;

use App\Services\AppSettingsService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        URL::forceRootUrl(config('app.url'));
        View::composer('layouts.public', function ($view) {
            $view->with('settings', app(AppSettingsService::class)->get());
        });
    }
}
