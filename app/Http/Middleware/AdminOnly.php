<?php

namespace App\Http\Middleware;

use App\Services\EntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user() &&
                app(EntitlementService::class)->admin($request->user()),
            403,
            'Administrator access required.',
        );

        return $next($request);
    }
}
