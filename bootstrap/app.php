<?php

use App\Http\Middleware\ActiveAccount;
use App\Http\Middleware\AdminOnly;
use App\Http\Middleware\PrivateResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['admin' => AdminOnly::class, 'active' => ActiveAccount::class, 'private' => PrivateResponse::class]);
        $middleware->preventRequestForgery(except: ['api/paypal/webhook']);
        $middleware->trimStrings(except: [fn (Request $r) => $r->is('api/*')]);
        $middleware->convertEmptyStringsToNull(except: [fn (Request $r) => $r->is('api/*')]);
        $middleware->trustHosts(at: fn () => ['^'.preg_quote(parse_url(config('app.url'), PHP_URL_HOST), '/').'$'], subdomains: false);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (RuntimeException $e, Request $request) {
            if ($request->is('api/billing', 'api/connection') && get_class($e) === RuntimeException::class) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
